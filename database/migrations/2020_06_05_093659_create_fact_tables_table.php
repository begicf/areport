<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fact_table', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fact_header_id')->unsigned();
            $table->string('cr_code');
            $table->string('string_value');
            $table->string('cr_sheet_code',10);
            $table->string('metric')->nullable();
            $table->text('xbrl_context_key');
            $table->json('xbrl_context_key_raw');
            $table->integer('user_id');
            $table->unique(['fact_header_id', 'cr_code','cr_sheet_code']);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fact_header');
        Schema::dropIfExists('fact_table');
    }
}
