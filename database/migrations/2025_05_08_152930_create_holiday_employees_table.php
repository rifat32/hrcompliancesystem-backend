<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidayEmployeesTable extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained("holidays")->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_employees');
    }
}
