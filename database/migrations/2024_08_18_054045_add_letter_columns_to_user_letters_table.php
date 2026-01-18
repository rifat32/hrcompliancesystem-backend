<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLetterColumnsToUserLettersTable extends Migration
{
   /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_letters', function (Blueprint $table) {
            $table->boolean('letter_view_required')->default(false);
            $table->boolean('letter_viewed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_letters', function (Blueprint $table) {
            $table->dropColumn('letter_view_required');
            $table->dropColumn('letter_viewed');
        });
    }
}
