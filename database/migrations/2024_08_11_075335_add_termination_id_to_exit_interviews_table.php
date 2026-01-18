<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTerminationIdToExitInterviewsTable extends Migration
{
  /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exit_interviews', function (Blueprint $table) {
            $table->foreignId('termination_id')
                  ->constrained('terminations')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exit_interviews', function (Blueprint $table) {
            $table->dropForeign(['termination_id']);
            $table->dropColumn('termination_id');
        });
    }
}
