<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentSetupStepToBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->enum('current_setup_step', ['pending_setup','employee_setup', 'recruitment_setup', 'business_flow_setup', 'general_setup'])
                  ->default('general_setup')
                  ->after('enable_auto_business_setup'); // Change 'existing_column' as needed
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('current_setup_step');
        });
    }

}
