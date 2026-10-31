<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/** One cell change on an instance: who changed which cell from what to what, and when. */
class FactAudit extends Model
{
    protected $table = 'fact_audit';

    public const UPDATED_AT = null;

    protected $fillable = ['fact_module_id', 'fact_header_id', 'table_path', 'cr_sheet_code', 'cr_code', 'old_value', 'new_value', 'user_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
