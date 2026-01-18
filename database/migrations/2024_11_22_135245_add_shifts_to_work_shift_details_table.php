<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShiftsToWorkShiftDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_shift_details', function (Blueprint $table) {
            // Add a JSON column to store shifts
            $table->json('shifts')->nullable()->after('work_shift_id'); // You can change 'details' to the appropriate column name if necessary
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_shift_details', function (Blueprint $table) {
            // Drop the shifts column when rolling back the migration
            $table->dropColumn('shifts');
        });
    }
}
