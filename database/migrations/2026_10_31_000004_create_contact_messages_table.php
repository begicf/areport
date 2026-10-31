<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Messages sent to the author from the public contact form (plugin requests etc.). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('organisation', 190)->nullable();
            $table->string('topic', 40);
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->boolean('mailed')->default(false);
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
