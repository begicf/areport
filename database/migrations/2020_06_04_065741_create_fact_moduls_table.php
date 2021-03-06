<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactModulsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fact_module', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('taxonomy_id')->unsigned();
            $table->timestamp('period');
            $table->string('module_name');
            $table->string('module_path');
            $table->json('groups');
            $table->unique(['period', 'module_path']);
            $table->timestamps();
            $table->foreign('taxonomy_id')->references('id')->on('taxonomies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fact_module');
    }
}
