<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactTable extends Model
{
    protected $table = 'fact_table';
    protected $fillable = ['fact_header_id', 'cr_code', 'string_value', 'sheet_code', 'metric', 'xbrl_context_key', 'xbrl_context_key_raw', 'user_id'];


    public static function storeInstance($req){






    }
}
