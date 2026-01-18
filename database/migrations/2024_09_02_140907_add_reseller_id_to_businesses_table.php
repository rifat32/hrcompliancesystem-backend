<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResellerIdToBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_id')->nullable()->after('id'); // Add the reseller_id column
            $table->foreign('reseller_id')->references('id')->on('users')->onDelete('set null'); // Define foreign key constraint
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']); // Drop the foreign key
            $table->dropColumn('reseller_id'); // Drop the column
        });
    }
}
