<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Model\FactAudit;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The same module reported for another period: copy its values into an instance
 * (identification tables, methodologies… that rarely change) and show them next
 * to the cells for a period-over-period check.
 */
class PriorPeriodController extends Controller
{
    /** Other instances of the module the user can see, latest period first. */
    public function instances(Request $request): JsonResponse
    {
        $target = FactModule::query()->findOrFail($request->get('instance_id'));
        $rows = FactModule::query()->where('module_path', $target->module_path)->where('id', '!=', $target->id)
            ->orderByDesc('period')->orderByDesc('id')->get(['id', 'module_name', 'period', 'consolidation'])
            ->map(fn ($m) => ['id' => $m->id, 'label' => $m->module_name.' · '.($m->period ? $m->period->format('d.m.Y') : '—').' · '.$m->consolidation,
                'earlier' => $m->period && $target->period && $m->period->lt($target->period)])->values();

        return response()->json(['instances' => $rows, 'suggested' => optional($rows->firstWhere('earlier', true))['id'] ?? null]);
    }

    /** Copy another instance's values into this one: empty cells only, or overwriting. */
    public function copy(Request $request): JsonResponse
    {
        $data = $request->validate(['instance_id' => ['required', 'integer'], 'source_id' => ['required', 'integer'], 'overwrite' => ['nullable', 'boolean']]);
        $target = FactModule::query()->findOrFail($data['instance_id']);
        $source = FactModule::query()->where('module_path', $target->module_path)->findOrFail($data['source_id']);
        abort_if($source->id === $target->id, 422, 'Choose another instance.');
        $overwrite = (bool) ($data['overwrite'] ?? false);
        $added = $changed = $kept = 0;

        DB::transaction(function () use ($source, $target, $overwrite, &$added, &$changed, &$kept) {
            $targetHeaders = $target->factHeader()->get()->keyBy(fn ($h) => self::path($h->table_path));
            foreach ($source->factHeader()->with('factTable')->get() as $sh) {
                $th = $targetHeaders->get(self::path($sh->table_path))
                    ?? FactHeader::query()->create(['module_id' => $target->id, 'table_path' => $sh->table_path, 'cr_sheet_code_last' => $sh->cr_sheet_code_last ?: '000']);
                $existing = FactTable::query()->where('fact_header_id', $th->id)->get()->keyBy(fn ($f) => $f->cr_sheet_code.'|'.$f->cr_code);
                foreach ($sh->factTable as $f) {
                    if ($f->string_value === null || $f->string_value === '') {
                        continue;
                    }
                    $have = $existing->get($f->cr_sheet_code.'|'.$f->cr_code);
                    if ($have && ($have->string_value !== null && $have->string_value !== '') && ! $overwrite) {
                        $kept++;
                        continue;
                    }
                    if ($have && (string) $have->string_value === (string) $f->string_value) {
                        continue;
                    }
                    $old = $have?->string_value;
                    if ($have) {
                        $have->update(['string_value' => $f->string_value, 'user_id' => Auth::id()]);
                        $changed++;
                    } else {
                        FactTable::query()->create([
                            'fact_header_id' => $th->id, 'cr_code' => $f->cr_code, 'cr_sheet_code' => $f->cr_sheet_code,
                            'string_value' => $f->string_value, 'metric' => $f->metric, 'xbrl_context_key' => $f->xbrl_context_key,
                            'xbrl_context_key_raw' => $f->xbrl_context_key_raw, 'user_id' => Auth::id(),
                        ]);
                        $added++;
                    }
                    FactAudit::query()->create([
                        'fact_module_id' => $target->id, 'fact_header_id' => $th->id, 'table_path' => $th->table_path,
                        'cr_sheet_code' => $f->cr_sheet_code, 'cr_code' => $f->cr_code,
                        'old_value' => $old === '' ? null : $old, 'new_value' => $f->string_value, 'user_id' => Auth::id(), 'created_at' => now(),
                    ]);
                }
            }
            if ($added || $changed) {
                $target->update(['data_changed_at' => now()]);
            }
        });

        return response()->json(['ok' => true, 'added' => $added, 'changed' => $changed, 'kept' => $kept]);
    }

    /** A table path without "dir/../" detours, so the same table always compares equal. */
    private static function path(?string $p): string
    {
        $p = ltrim(str_replace('\\', '/', (string) $p), '/');
        do {
            $p = preg_replace('#[^/]+/\.\./#', '', $p, 1, $n);
        } while ($n);

        return $p;
    }

    /** The other instance's values of one table and sheet: cell → value. */
    public function values(Request $request): JsonResponse
    {
        $target = FactModule::query()->findOrFail($request->get('instance_id'));
        $source = FactModule::query()->where('module_path', $target->module_path)->findOrFail($request->get('source_id'));
        $sheet = (string) ($request->get('sheet') ?: '000');
        $path = (string) $request->get('table_path');
        $want = self::path($path);
        $header = $source->factHeader()->get()->first(fn ($h) => $want !== '' && str_ends_with($want, self::path($h->table_path)));
        $values = $header
            ? FactTable::query()->where('fact_header_id', $header->id)->where('cr_sheet_code', $sheet)->pluck('string_value', 'cr_code')
            : collect();

        return response()->json(['source' => $source->module_name.' · '.($source->period ? $source->period->format('d.m.Y') : ''), 'values' => $values]);
    }
}
