<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A filing deadline set on the instance (otherwise derived from the reference date). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->date('due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
