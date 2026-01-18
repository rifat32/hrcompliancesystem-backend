<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminationProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('termination_processes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('recruitment_process_id')->nullable();
            $table->foreign('recruitment_process_id')->references('id')->on('recruitment_processes')->onDelete('cascade');

            $table->unsignedBigInteger('termination_id')->nullable();
            $table->foreign('termination_id')->references('id')->on('terminations')->onDelete('cascade');

            $table->text("description")->nullable();
            $table->json('attachments')->nullable();


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
        Schema::dropIfExists('termination_processes');
    }
}
