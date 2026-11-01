<?php

namespace App\Support;

use App\Model\FactModule;

/**
 * Extension points for plugins that change who sees an instance and what may be done
 * with it (e.g. teams and a sign-off workflow). A plugin registers callbacks from its
 * service provider; without any, the application behaves as a single-user tool:
 *
 *  - visibleUsers(fn (int $userId): int[])    — extra users whose instances (and
 *    entities) the signed-in user sees, e.g. the other members of their team;
 *  - lock(fn (FactModule $m, string $action): ?string) — a reason why the action
 *    ('edit' the data or settings, 'export' a filing package) is not allowed now;
 *  - badge(fn (FactModule $m): ?array{key: string, label: string, tone?: string}) — a
 *    status shown under the instance's own status on Home (tone: info|ok|warn|danger);
 *  - view(slot, view) — a view rendered in a slot of a page ('form.bar' above the
 *    reporting form, with $instance; 'nav.account' in the account menu).
 */
class InstanceHooks
{
    private array $visibleUsers = [];

    private array $locks = [];

    private array $badges = [];

    private array $views = [];

    private static function hooks(): self
    {
        return app(self::class);
    }

    public static function visibleUsers(callable $resolver): void
    {
        self::hooks()->visibleUsers[] = $resolver;
    }

    public static function lock(callable $check): void
    {
        self::hooks()->locks[] = $check;
    }

    public static function badge(callable $badge): void
    {
        self::hooks()->badges[] = $badge;
    }

    public static function view(string $slot, string $view): void
    {
        self::hooks()->views[$slot][] = $view;
    }

    /** @return int[] users whose records $userId sees besides their own */
    public static function visibleUserIds(int $userId): array
    {
        $ids = [];
        foreach (self::hooks()->visibleUsers as $resolver) {
            array_push($ids, ...array_map('intval', (array) $resolver($userId)));
        }

        return $ids;
    }

    /** Why $action ('edit' | 'export') is not allowed on the instance now, or null. */
    public static function lockReason(?FactModule $instance, string $action): ?string
    {
        if (! $instance) {
            return null;
        }
        foreach (self::hooks()->locks as $check) {
            if ($reason = $check($instance, $action)) {
                return $reason;
            }
        }

        return null;
    }

    /** @return array<int, array{key: string, label: string, tone?: string}> */
    public static function badges(FactModule $instance): array
    {
        return array_values(array_filter(array_map(fn ($badge) => $badge($instance), self::hooks()->badges)));
    }

    /** @return string[] views registered for the slot */
    public static function views(string $slot): array
    {
        return self::hooks()->views[$slot] ?? [];
    }
}
