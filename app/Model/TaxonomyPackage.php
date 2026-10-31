<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/** An extension package merged into an uploaded taxonomy (see App\Support\TaxonomyPackage). */
class TaxonomyPackage extends Model
{
    protected $guarded = [];

    protected $casts = ['entry_points' => 'array'];

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class);
    }
}
