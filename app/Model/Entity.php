<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * A reporting entity: its legal name, LEI and country. A user keeps several; one is
 * the default, and each instance names the entity it reports for (the LEI is part of
 * the entity identifier and of the filing package name).
 */
class Entity extends Model
{
    protected $table = 'entities';

    protected $fillable = ['user_id', 'lei', 'name', 'country', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    /**
     * Entities belong to the user who added them; a signed-in user sees the same owners'
     * entities as their instances (their own, the shared demo user's, their team's).
     */
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (Auth::check()) {
                $query->whereIn($query->getModel()->getTable().'.user_id', FactModule::visibleOwnerIds());
            }
        });

        static::creating(function (Entity $entity) {
            if (empty($entity->user_id) && Auth::check()) {
                $entity->user_id = Auth::id();
            }
        });
    }

    /** The default entity (or the first one, if none is flagged). */
    public static function current(): ?self
    {
        return static::query()->where('is_default', true)->first()
            ?? static::query()->orderBy('id')->first();
    }

    /** The visible entities, the default first. */
    public static function choices()
    {
        return static::query()->orderByDesc('is_default')->orderBy('name')->get();
    }

    /** Make this the only default (among the visible entities — the scope limits the reset). */
    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
