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
            $table->string('repeating')->nullable()->default('off');
            $table->string('monday')->nullable()->default('off');
            $table->string('tuesday')->nullable()->default('off');
            $table->string('wednesday')->nullable()->default('off');
            $table->string('thursday')->nullable()->default('off');
            $table->string('friday')->nullable()->default('off');
            $table->string('saturday')->nullable()->default('off');
            $table->string('sunday')->nullable()->default('off');
            $table->string('time_due')->nullable()->default('off');
            $table->string('date_due')->nullable()->default('off');
            $table->string('completed')->nullable()->default('off');
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
