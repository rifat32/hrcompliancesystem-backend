<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminations', function (Blueprint $table) {
            $table->id();


            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');


            $table->foreignId('termination_type_id')->constrained('termination_types')->onDelete('cascade');
            $table->foreignId('termination_reason_id')->constrained('termination_reasons')->onDelete('cascade');
            $table->date('date_of_termination');
            $table->date('joining_date');


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
        Schema::dropIfExists('terminations');
    }
}
