<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Daily usage counts (App\Support\Stats): numbers only, nothing about who. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('metric', 40);
            $table->unsignedInteger('value')->default(0);
            $table->unique(['day', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stats');
    }
};
