<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::create('fact_header', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('taxonomy_id')->unsigned();
            $table->timestamp('period');
            $table->string('table_path');
            $table->string('module_path');
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
        Schema::dropIfExists('fact_table');
        Schema::dropIfExists('fact_header');
    }
}
