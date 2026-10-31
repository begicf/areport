<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FactModule extends Model
{
    protected $table = 'fact_module';
    protected $fillable = ['user_id', 'taxonomy_id', 'entity_id', 'period', 'module_name', 'module_path', 'groups'];

    protected $casts = [
        'period' => 'date:d-m-Y',
    ];

    /**
     * An instance belongs to the user who created it: every query made for a signed-in
     * user only sees that user's instances, and new instances are stamped with the owner.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (Auth::check()) {
                $query->where($query->getModel()->getTable() . '.user_id', Auth::id());
            }
        });

        static::creating(function (FactModule $module) {
            if (empty($module->user_id) && Auth::check()) {
                $module->user_id = Auth::id();
            }
        });
    }

    /** The signed-in user's instance named by the request's instance_id, with its taxonomy. */
    public static function fromRequest(): ?self
    {
        $id = request('instance_id');

        return $id ? static::query()->with('taxonomy')->find($id) : null;
    }

    /** Reference date as Y-m-d (the period column holds a date). */
    public function periodYmd(): ?string
    {
        return $this->period ? $this->period->format('Y-m-d') : null;
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
