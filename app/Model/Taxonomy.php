<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    protected $fillable = ['name','original_name','path','folder'];
    protected $guarded = [];

    /** Extension packages added to this taxonomy (a national regulator's, …). */
    public function packages()
    {
        return $this->hasMany(TaxonomyPackage::class)->orderBy('created_at');
    }
}
