<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Extension packages (e.g. a national regulator's) added to an uploaded taxonomy. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomy_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $table->string('identifier')->nullable();
            $table->string('name')->nullable();
            $table->string('version', 64)->nullable();
            $table->string('publisher')->nullable();
            $table->json('entry_points')->nullable();
            $table->unsignedInteger('files')->default(0);
            $table->string('original_name')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomy_packages');
    }
};
