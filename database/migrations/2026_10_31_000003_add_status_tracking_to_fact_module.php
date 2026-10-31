<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Where an instance stands: when its data last changed, when it was last fully
 * validated (and the outcome), and when it was last exported (format, package).
 * The status shown on Home is derived from these three moments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->timestamp('data_changed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->json('validation_summary')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->string('export_format', 8)->nullable();
            $table->string('export_package')->nullable();
        });

        DB::table('fact_module')->update(['data_changed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->dropColumn(['data_changed_at', 'validated_at', 'validation_summary', 'exported_at', 'export_format', 'export_package']);
        });
    }
};
