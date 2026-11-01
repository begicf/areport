<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reporting entities are part of the application (they used to come with the AReport Pro
 * plugin as dpm_entities): an existing dpm_entities table is renamed, keeping its rows
 * and the ids instances refer to; otherwise the table is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entities')) {
            return;
        }
        if (Schema::hasTable('dpm_entities')) {
            Schema::rename('dpm_entities', 'entities');

            return;
        }

        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('lei', 32)->index();
            $table->string('name');
            $table->string('country', 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
