<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserTimeZoneToUsersTable extends Migration
{
      public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_time_zone')->nullable()->after('email'); // adjust position as needed
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_time_zone');
        });
    }
}
