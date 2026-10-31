<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filing settings an EBA submission needs per instance: consolidation scope (the
 * .CON / .IND suffix of the entity identifier), NCA country (package name), base
 * currency, the unit amounts are entered in, decimals, and explicit filing indicators.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->string('consolidation', 3)->default('CON');
            $table->string('country', 2)->nullable();
            $table->string('base_currency', 3)->default('EUR');
            $table->unsignedInteger('monetary_scale')->default(1000);
            $table->smallInteger('decimals_monetary')->default(-3);
            $table->smallInteger('decimals_percentage')->default(4);
            $table->smallInteger('decimals_integer')->default(0);
            $table->smallInteger('decimals_decimal')->default(2);
            $table->json('filing_indicators')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fact_module', function (Blueprint $table) {
            $table->dropColumn([
                'consolidation', 'country', 'base_currency', 'monetary_scale', 'decimals_monetary',
                'decimals_percentage', 'decimals_integer', 'decimals_decimal', 'filing_indicators',
            ]);
        });
    }
};
