<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAndAddColumnsToRecruitmentProcesses extends Migration
{



    public function up()
    {
        Schema::table('recruitment_processes', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('use_in_employee', 'use_in_recruitment');

            $table->boolean('use_in_termination')->default(false);
            $table->boolean('is_required')->default(false);

        });
    }

    public function down()
    {
        Schema::table('recruitment_processes', function (Blueprint $table) {
            // Rename the column back to original
            $table->renameColumn('use_in_recruitment', 'use_in_employee');



            // Drop the new columns
            $table->dropColumn(['use_in_termination','is_required']);

        });
    }



}
