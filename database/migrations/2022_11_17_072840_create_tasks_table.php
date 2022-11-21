<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('repeating')->default("off");
            $table->string('monday')->default("off");
            $table->string('tuesday')->default("off");
            $table->string('wednesday')->default("off");
            $table->string('thursday')->default("off");
            $table->string('friday')->default("off");
            $table->string('saturday')->default("off");
            $table->string('sunday')->default("off");
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
        Schema::dropIfExists('tasks');
    }
};
