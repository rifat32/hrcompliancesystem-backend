<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidayDepartmentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained("holidays")->onDelete('cascade');
            $table->foreignId('department_id')->constrained("departments")->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_departments');
    }
}
