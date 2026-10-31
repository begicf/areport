<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Instances become owned records identified by their id:
 *  - fact_module.user_id — who the instance belongs to (existing rows go to the first user);
 *  - the (period, module_path) unique key is dropped, so the same module and period can be
 *    reported per user, per taxonomy and per entity (solo vs consolidated);
 *  - fact_audit — one row per cell change (old → new), the trail a reviewer asks for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        $firstUser = DB::table('users')->orderBy('id')->value('id');
        if ($firstUser) {
            DB::table('fact_module')->whereNull('user_id')->update(['user_id' => $firstUser]);
        }

        Schema::table('fact_module', function (Blueprint $table) {
            $table->dropUnique(['period', 'module_path']);
            $table->index(['user_id', 'taxonomy_id', 'period', 'module_path'], 'fact_module_owner_lookup_idx');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('fact_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fact_module_id');
            $table->unsignedBigInteger('fact_header_id')->nullable();
            $table->string('table_path');
            $table->string('cr_sheet_code', 64)->default('000');
            $table->string('cr_code', 64);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['fact_module_id', 'created_at']);
            $table->foreign('fact_module_id')->references('id')->on('fact_module')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_audit');

        Schema::table('fact_module', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex('fact_module_owner_lookup_idx');
            $table->dropColumn('user_id');
            $table->unique(['period', 'module_path']);
        });
    }
};
