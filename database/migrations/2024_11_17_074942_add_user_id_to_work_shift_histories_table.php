<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToWorkShiftHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_shift_histories', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable() // Allow NULL if needed
                ->constrained('users') // Reference the `users` table
                ->onDelete('cascade') // Delete work shifts if the user is deleted
                ->after('id'); // Place it after the `id` column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_shift_histories', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drop the foreign key
            $table->dropColumn('user_id');    // Remove the `user_id` column
        });
    }
}
