<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeneratingTypeToPayrunsTable extends Migration
{
    public function up()
    {
        Schema::table('payruns', function (Blueprint $table) {
            $table->string('generating_type')->nullable(); // Adjust the type as needed
        });
    }

    public function down()
    {
        Schema::table('payruns', function (Blueprint $table) {
            $table->dropColumn('generating_type');
        });
    }
}
