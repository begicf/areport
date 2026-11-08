<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * How much the installation is used, per day: counts only — no e-mails, IP addresses or
 * who did what. They stay in this installation's database and nothing is sent anywhere;
 * `php artisan areport:stats` shows them. Who was signed in today is remembered in the
 * cache until tomorrow, so each user counts once a day.
 */
class Stats
{
    public const METRICS = [
        'active_users' => 'Signed-in users',
        'registrations' => 'Registrations',
        'verified' => 'E-mails confirmed',
        'logins' => 'Sign-ins',
        'reports_created' => 'Reports created',
        'forms_opened' => 'Forms opened',
        'validations' => 'Validations',
        'exports' => 'Exports',
        'imports' => 'Imports',
        'architect' => 'DPM Architect opened',
    ];

    public static function hit(string $metric, int $n = 1): void
    {
        try {
            $day = now()->toDateString();
            $row = fn () => DB::table('daily_stats')->where('day', $day)->where('metric', $metric);
            if (! $row()->increment('value', $n)) {
                DB::table('daily_stats')->insertOrIgnore(['day' => $day, 'metric' => $metric, 'value' => 0]);
                $row()->increment('value', $n);
            }
        } catch (\Throwable) {
            // Counting never gets in the way of the work.
        }
    }

    /** Counts the signed-in user once a day. */
    public static function seen(Authenticatable $user): void
    {
        $day = now()->toDateString();
        if (Cache::add('stats:seen:'.$day.':'.$user->getAuthIdentifier(), 1, now()->addDays(2))) {
            self::hit('active_users');
        }
    }

    /** @return array<string, array<string, int>> day (newest first) => metric => count */
    public static function days(int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();
        $out = [];
        for ($d = now(); $d->toDateString() >= $from; $d = $d->copy()->subDay()) {
            $out[$d->toDateString()] = array_fill_keys(array_keys(self::METRICS), 0);
        }
        foreach (DB::table('daily_stats')->where('day', '>=', $from)->get() as $r) {
            $day = substr((string) $r->day, 0, 10);
            if (isset($out[$day][$r->metric])) {
                $out[$day][$r->metric] = (int) $r->value;
            }
        }

        return $out;
    }
}
