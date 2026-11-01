<?php

namespace App\Console\Commands;

use App\Http\Controllers\Table\TableController;
use App\Model\FactModule;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Run the full validation report for saved instances (as "Validate" on the home page
 * does) so their status — errors, warnings, ready — is current, e.g. for the shared
 * sample reports of a demo server.
 */
class ValidateInstances extends Command
{
    protected $signature = 'areport:validate-instances
        {--user= : Only this user\'s instances (id)}
        {--id=* : Only these instance ids}
        {--stale : Only instances not validated, or changed since}';

    protected $description = 'Validate saved instances and store their status';

    public function handle(): int
    {
        if (! class_exists(\Begicf\AReportDpm\Services\InstanceValidator::class)) {
            $this->error('The validation plugin is not installed.');

            return self::FAILURE;
        }
        if ($reason = \App\Support\License::refusal('validation')) {
            $this->error($reason);

            return self::FAILURE;
        }
        $query = FactModule::query()->withoutGlobalScopes()->orderBy('id');
        if ($this->option('user')) {
            $query->where('user_id', (int) $this->option('user'));
        }
        if ($ids = array_filter((array) $this->option('id'))) {
            $query->whereIn('id', $ids);
        }
        $failed = 0;
        foreach ($query->get() as $module) {
            if ($this->option('stale') && $module->validated_at && (! $module->data_changed_at || $module->data_changed_at <= $module->validated_at)) {
                continue;
            }
            Auth::loginUsingId($module->user_id);
            $request = Request::create('/table/validation-report', 'GET', ['id' => $module->id]);
            app()->instance('request', $request);
            $started = microtime(true);
            try {
                app(TableController::class)->validationReport($request);
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("#{$module->id} {$module->module_name}: ".$e->getMessage());
                continue;
            }
            $status = $module->fresh()->status();
            $this->line(sprintf('#%d %s — %s (%.1fs)', $module->id, $module->module_name, $status['label'], microtime(true) - $started));
        }
        Auth::logout();

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
