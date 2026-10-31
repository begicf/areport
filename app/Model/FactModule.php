<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FactModule extends Model
{
    protected $table = 'fact_module';
    protected $fillable = [
        'user_id', 'taxonomy_id', 'entity_id', 'period', 'module_name', 'module_path', 'groups',
        'consolidation', 'country', 'base_currency', 'monetary_scale', 'decimals_monetary',
        'decimals_percentage', 'decimals_integer', 'decimals_decimal', 'filing_indicators',
        'data_changed_at', 'validated_at', 'validation_summary', 'exported_at', 'export_format', 'export_package', 'due_date',
    ];

    protected $casts = [
        'period' => 'date:d-m-Y',
        'due_date' => 'date:Y-m-d',
        'filing_indicators' => 'array',
        'monetary_scale' => 'integer',
        'decimals_monetary' => 'integer',
        'decimals_percentage' => 'integer',
        'decimals_integer' => 'integer',
        'decimals_decimal' => 'integer',
        'data_changed_at' => 'datetime',
        'validated_at' => 'datetime',
        'validation_summary' => 'array',
        'exported_at' => 'datetime',
    ];

    /** Units an amount can be entered in on the form. */
    public const MONETARY_SCALES = [1 => 'units', 1000 => 'thousands', 1000000 => 'millions'];

    /**
     * An instance belongs to the user who created it: every query made for a signed-in
     * user only sees that user's instances (plus the shared demo user's, when
     * areport.demo.shared_user_id is set), and new instances are stamped with the owner.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (Auth::check()) {
                $query->whereIn($query->getModel()->getTable() . '.user_id', static::visibleOwnerIds());
            }
        });

        static::creating(function (FactModule $module) {
            if (empty($module->user_id) && Auth::check()) {
                $module->user_id = Auth::id();
            }
        });
    }

    /** Owners whose instances the signed-in user sees: themselves and the shared demo user. */
    public static function visibleOwnerIds(): array
    {
        return array_values(array_unique(array_filter([Auth::id(), config('areport.demo.shared_user_id')])));
    }

    /** The signed-in user's instance named by the request's instance_id, with its taxonomy. */
    public static function fromRequest(): ?self
    {
        $id = request('instance_id');

        return $id ? static::query()->with('taxonomy')->find($id) : null;
    }

    /**
     * Filing deadline: the one set on the instance, otherwise the EBA remittance date
     * for the reference date (ITS on supervisory reporting): quarter ends → 12 May,
     * 11 August, 11 November, 11 February; other month ends → the 15th day after.
     * A deadline on a weekend moves to the next Monday.
     */
    public function dueDate(): ?\Carbon\CarbonImmutable
    {
        if ($this->due_date) {
            return \Carbon\CarbonImmutable::parse($this->due_date->format('Y-m-d'));
        }
        if (! $this->period) {
            return null;
        }
        $ref = \Carbon\CarbonImmutable::parse($this->period->format('Y-m-d'));
        $quarterly = ['03-31' => [5, 12, 0], '06-30' => [8, 11, 0], '09-30' => [11, 11, 0], '12-31' => [2, 11, 1]];
        $q = $quarterly[$ref->format('m-d')] ?? null;
        $due = $q ? \Carbon\CarbonImmutable::create($ref->year + $q[2], $q[0], $q[1]) : $ref->addDays(15);
        while ($due->isWeekend()) {
            $due = $due->addDay();
        }

        return $due;
    }

    /** Reference date as Y-m-d (the period column holds a date). */
    public function periodYmd(): ?string
    {
        return $this->period ? $this->period->format('Y-m-d') : null;
    }

    /** The reporting entity (areport-pro), if one is set and the plugin is installed. */
    public function entityRecord()
    {
        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;

        return ($this->entity_id && class_exists($entityCls)) ? $entityCls::find($this->entity_id) : null;
    }

    /** NCA country for the package name: set on the instance, else the entity's country. */
    public function filingCountry(): ?string
    {
        $country = $this->country ?: ($this->entityRecord()->country ?? null);

        return $country ? strtoupper($country) : null;
    }

    /** Entity identifier as EBA reports it: rs:{LEI}.{CON|IND}. */
    public function entityIdentifier(?string $lei): string
    {
        return 'rs:' . strtoupper((string) $lei) . '.' . ($this->consolidation ?: 'CON');
    }

    /** "thousands EUR" — what the amounts on the form are. */
    public function amountUnitLabel(): string
    {
        return (self::MONETARY_SCALES[$this->monetary_scale ?? 1000] ?? 'thousands') . ' ' . ($this->base_currency ?: 'EUR');
    }

    /**
     * Where the instance stands, derived from when its data last changed, when it
     * was last fully validated and when it was last exported:
     * draft · errors · warnings · validated · exported.
     *
     * @return array{key: string, label: string, errors: int, warnings: int}
     */
    public function status(): array
    {
        $changed = $this->data_changed_at ?? $this->updated_at;
        $summary = (array) $this->validation_summary;
        $errors = (int) ($summary['failed_errors'] ?? 0);
        $warnings = (int) ($summary['failed_warnings'] ?? 0);
        $validatedCurrent = $this->validated_at && (! $changed || $this->validated_at->gte($changed));

        if ($this->exported_at && (! $changed || $this->exported_at->gte($changed))) {
            $key = 'exported';
        } elseif (! $validatedCurrent) {
            $key = 'draft';
        } elseif ($errors > 0) {
            $key = 'errors';
        } elseif ($warnings > 0) {
            $key = 'warnings';
        } else {
            $key = 'validated';
        }

        $labels = [
            'draft' => $this->validated_at ? 'Changed since validation' : 'Not validated',
            'errors' => $errors.' error'.($errors === 1 ? '' : 's'),
            'warnings' => 'Validated · '.$warnings.' warning'.($warnings === 1 ? '' : 's'),
            'validated' => 'Validated',
            'exported' => 'Exported'.($this->export_format ? ' ('.strtoupper($this->export_format).')' : ''),
        ];

        return ['key' => $key, 'label' => $labels[$key], 'errors' => $errors, 'warnings' => $warnings];
    }

    /** Record a moment without touching updated_at (which tracks edits). */
    public function stamp(array $attributes): void
    {
        $this->timestamps = false;
        $this->forceFill($attributes)->save();
        $this->timestamps = true;
    }

    /** Options for the xBRL-XML and xBRL-CSV writers. */
    public function exportOptions(): array
    {
        return [
            'baseCurrency' => $this->base_currency ?: 'EUR',
            'monetaryScale' => $this->monetary_scale ?: 1000,
            'decimalsMonetary' => $this->decimals_monetary ?? -3,
            'decimalsPercentage' => $this->decimals_percentage ?? 4,
            'decimalsInteger' => $this->decimals_integer ?? 0,
            'decimalsDecimal' => $this->decimals_decimal ?? 2,
            'country' => $this->filingCountry(),
            'filingIndicators' => array_map('boolval', (array) $this->filing_indicators),
        ];
    }

    public function factHeader()
    {
        return $this->hasMany('App\Model\FactHeader', 'module_id', 'id');
    }

    public function taxonomy()
    {

        return $this->belongsTo('App\Model\Taxonomy', 'taxonomy_id', 'id');
    }

    public function audits()
    {
        return $this->hasMany(FactAudit::class, 'fact_module_id', 'id');
    }

}
