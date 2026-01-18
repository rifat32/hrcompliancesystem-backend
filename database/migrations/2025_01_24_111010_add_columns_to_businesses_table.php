<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToBusinessesTable extends Migration
{
    public function up()
{
    Schema::table('businesses', function (Blueprint $table) {
        $table->boolean('delete_read_notifications_after_30_days')->default(false);
        $table->integer('business_start_day')->default(1);
    });
}

public function down()
{
    Schema::table('businesses', function (Blueprint $table) {
        $table->dropColumn(['delete_read_notifications_after_30_days', 'business_start_day']);
    });
}

}
