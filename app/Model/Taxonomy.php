<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    protected $fillable = ['name','original_name','path','folder'];
    protected $guarded = [];
}
