<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');

            // Attendance details
            $table->time('in_time');
            $table->time('out_time');

            $table->string('in_latitude')->nullable();
            $table->string('in_longitude')->nullable();
            $table->string('out_latitude')->nullable();
            $table->string('out_longitude')->nullable();
            $table->string('in_ip_address')->nullable();
            $table->string('out_ip_address')->nullable();

            // Break hours
            $table->decimal('break_hours', 10, 2)->default(0);  // The break hours for the record
            $table->boolean('is_paid_break')->default(false); // Whether the break is paid

            // Optional note for the record
            $table->string('note')->nullable();


            // Work location information
            $table->unsignedBigInteger('work_location_id');

            $table->timestamps();

            // Add a foreign key for work location
            $table->foreign('work_location_id')->references('id')->on('work_locations')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_records');
    }
}
