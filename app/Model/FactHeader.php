<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactHeader extends Model
{
    protected $table = 'fact_header';
    protected $fillable = ['taxonomy_id', 'table_path', 'module_path', 'period'];
}
