<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactModule extends Model
{
    protected $table = 'fact_module';
    protected $fillable = ['taxonomy_id', 'period', 'module_name', 'module_path', 'groups'];

    protected $casts = [
        'period' => 'date:d-m-Y',
    ];

    public function factHeader()
    {
        return $this->hasMany('App\Model\FactHeader', 'module_id', 'id');
    }

    public function taxonomy()
    {

        return $this->belongsTo('App\Model\Taxonomy', 'taxonomy_id', 'id');
    }

}
