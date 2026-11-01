<?php

namespace App\Console\Commands;

use App\Support\Stats;
use Illuminate\Console\Command;

class ShowStats extends Command
{
    protected $signature = 'areport:stats {--days=30 : How many days back}';

    protected $description = 'Show how much this installation is used, per day (counts only)';

    public function handle(): int
    {
        $days = Stats::days(max(1, (int) $this->option('days')));
        $short = ['active_users' => 'Users', 'registrations' => 'Reg.', 'verified' => 'Conf.', 'logins' => 'Sign-ins', 'reports_created' => 'Reports',
            'forms_opened' => 'Forms', 'validations' => 'Valid.', 'exports' => 'Exports', 'imports' => 'Imports', 'architect' => 'Architect'];
        $rows = array_map(fn ($day, $counts) => array_merge([$day], array_values($counts)), array_keys($days), $days);
        $totals = array_merge(['Total'], array_map(fn ($m) => array_sum(array_column($days, $m)), array_keys(Stats::METRICS)));
        $this->table(array_merge(['Day'], array_values($short)), array_merge($rows, [$totals]));

        return self::SUCCESS;
    }
}
