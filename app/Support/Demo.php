<?php

namespace App\Support;

use App\Model\FactModule;
use Illuminate\Support\Facades\Auth;

/**
 * Public demo server (areport.demo.shared_user_id set): one user's reports are shared
 * with every signed-in user. With areport.demo.read_only (default), visitors look and
 * try without changing what everybody else sees: the workspace (taxonomies, settings,
 * the DPM Architect model) and the shared reports stay as they are, while their own
 * reports are fully theirs. The shared user and the listed admins can change anything.
 */
class Demo
{
    public static function sharedUserId(): ?int
    {
        return config('areport.demo.shared_user_id');
    }

    /** Read-only rules apply to the signed-in user. */
    public static function restricted(): bool
    {
        if (! self::sharedUserId() || ! config('areport.demo.read_only', true) || ! Auth::check()) {
            return false;
        }
        if ((int) Auth::id() === self::sharedUserId()) {
            return false;
        }

        return ! in_array(strtolower((string) Auth::user()->email), config('areport.demo.admins', []), true);
    }

    /** A shared demo report the signed-in user may look at but not change. */
    public static function readOnlyInstance(?FactModule $instance): bool
    {
        return $instance && self::restricted() && (int) $instance->user_id === self::sharedUserId();
    }
}
