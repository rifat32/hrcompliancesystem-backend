<?php

namespace App\Http\Controllers;

use App\Http\Components\AttendanceComponent;

use App\Http\Components\LeaveComponent;
use App\Http\Components\UserManagementComponent;
use App\Http\Components\WorkTimeManagementComponent;
use App\Http\Utils\BasicEmailUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\SetupUtil;
use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\AttendanceHistoryProject;
use App\Models\AttendanceHistoryRecord;
use App\Models\AttendanceProject;
use App\Models\AttendanceRecord;
use App\Models\Business;
use App\Models\BusinessPensionHistory;
use App\Models\Candidate;
use App\Models\CandidateRecruitmentProcess;
use App\Models\Department;
use App\Models\EmailTemplate;
use App\Models\EmployeeLeaveAllowance;
use App\Models\EmployeePensionHistory;
use App\Models\EmployeeRightToWorkHistory;
use App\Models\EmployeeVisaDetailHistory;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveRecord;
use App\Models\Module;
use App\Models\Payslip;
use App\Models\RecruitmentProcess;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use App\Models\SettingLeave;
use App\Models\SettingPayslip;
use App\Models\User;
use App\Models\UserAsset;
use App\Models\UserDocument;
use App\Models\UserEducationHistory;
use App\Models\UserRecruitmentProcess;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UpdateDatabaseController extends Controller
{
    use BasicEmailUtil, SetupUtil, BusinessUtil;

    protected $attendanceComponent;

    protected $leaveComponent;
    protected $userManagementComponent;
    protected $departmentComponent;
    protected $workTimeManagementComponent;
    public function __construct(AttendanceComponent $attendanceComponent, LeaveComponent $leaveComponent, UserManagementComponent $userManagementComponent, WorkTimeManagementComponent $workTimeManagementComponent)
    {
        $this->attendanceComponent = $attendanceComponent;
        $this->leaveComponent = $leaveComponent;
        $this->userManagementComponent = $userManagementComponent;
        $this->workTimeManagementComponent = $workTimeManagementComponent;
    }

    private function storeEmailTemplates()
    {

        // Prepare initial email templates
        $email_templates = collect([
            $this->prepareEmailTemplateData("business_welcome_mail", NULL),
            $this->prepareEmailTemplateData("email_verification_mail", NULL),
            $this->prepareEmailTemplateData("reset_password_mail", NULL),
            $this->prepareEmailTemplateData("send_password_mail", NULL),
            $this->prepareEmailTemplateData("job_application_received_mail", NULL),

        ]);

        // Fetch business IDs and prepare business-specific email templates
        $business_email_templates = Business::pluck("id")->flatMap(function ($business_id) {
            return [
                $this->prepareEmailTemplateData("reset_password_mail", $business_id),
                $this->prepareEmailTemplateData("send_password_mail", $business_id),
                $this->prepareEmailTemplateData("job_application_received_mail", $business_id),

            ];
        });

        // Combine the two collections
        $email_templates = $email_templates->merge($business_email_templates);


        // Insert all email templates at once
        EmailTemplate::upsert(
            $email_templates->toArray(),
            ['type', 'business_id'], // Columns that determine uniqueness
            [
                "name",
                // "type",
                "template",
                "is_active",
                "is_default",
                // "business_id",
                'wrapper_id',
                "template_variables"
            ] // Columns to update if a match is found
        );
    }

    public function updateModule()
    {
        $modules = config("setup-config.system_modules");
        foreach ($modules as $module) {
            $module_exists = Module::where([
                "name" => $module
            ])
                ->exists();

            if (!$module_exists) {
                Module::create([
                    "name" => $module,
                    "is_enabled" => 0,
                    'created_by' => 1,
                ]);
            }
        }
    }

    public function updateFields()
    {
        // Check and add the 'number_of_employees_allowed' column if it doesn't exist
        if (!Schema::hasColumn('businesses', 'number_of_employees_allowed')) {
            DB::statement("ALTER TABLE businesses ADD COLUMN number_of_employees_allowed INTEGER DEFAULT 0");
        }


        // Check if the foreign key exists before trying to drop it
        $letterTemplateForeignKeyExists = DB::select(DB::raw("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'letter_templates'
    AND CONSTRAINT_NAME = 'letter_templates_business_id_foreign'
"));

        if (!empty($letterTemplateForeignKeyExists)) {
            // Drop the existing foreign key constraint
            DB::statement("ALTER TABLE letter_templates DROP FOREIGN KEY letter_templates_business_id_foreign");
        }

        // Modify the column to be nullable
        DB::statement("
    ALTER TABLE letter_templates
    MODIFY COLUMN business_id BIGINT UNSIGNED NULL;
");

        // Re-add the foreign key constraint
        DB::statement("
    ALTER TABLE letter_templates
    ADD CONSTRAINT letter_templates_business_id_foreign
    FOREIGN KEY (business_id) REFERENCES businesses(id)
    ON DELETE CASCADE;
");

        // Check and add the 'in_geolocation' column if it doesn't exist
        if (!Schema::hasColumn('attendance_histories', 'in_geolocation')) {
            DB::statement("ALTER TABLE attendance_histories ADD COLUMN in_geolocation VARCHAR(255) NULL");
        }
        if (!Schema::hasColumn('users', 'stripe_id')) {
            DB::statement("ALTER TABLE users ADD COLUMN stripe_id VARCHAR(255) NULL");
        }


        // Check and add the 'out_geolocation' column if it doesn't exist
        if (!Schema::hasColumn('attendance_histories', 'out_geolocation')) {
            DB::statement("ALTER TABLE attendance_histories ADD COLUMN out_geolocation VARCHAR(255) NULL");
        }



        // Check if the 'feedback' column exists
        if (Schema::hasColumn('candidates', 'feedback')) {
            // Make the 'feedback' column nullable
            DB::statement('ALTER TABLE candidates MODIFY feedback VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('work_locations', 'address')) {
            // Make the 'feedback' column nullable
            DB::statement('ALTER TABLE work_locations MODIFY address VARCHAR(255) NULL');
        }


        // Make the 'feedback' column nullable
        DB::statement('ALTER TABLE asset_types MODIFY business_id BIGINT(20) UNSIGNED NULL');

        if (Schema::hasColumn('comments', 'description')) {
            DB::statement('ALTER TABLE comments MODIFY description LONGTEXT NULL');
        }


        $foreignKeys = [
            'disabled_setting_leave_types' => 'disabled_setting_leave_types_business_id_foreign',
            'disabled_task_categories' => 'disabled_task_categories_business_id_foreign',
            'disabled_letter_templates' => 'disabled_letter_templates_business_id_foreign',
            'disabled_asset_types' => 'disabled_asset_types_business_id_foreign',
            'disabled_designations' => 'disabled_designations_business_id_foreign',
            'disabled_employment_statuses' => 'disabled_employment_statuses_business_id_foreign',
            'disabled_job_platforms' => 'disabled_job_platforms_business_id_foreign',
            'disabled_job_types' => 'disabled_job_types_business_id_foreign',
            'disabled_work_locations' => 'disabled_work_locations_business_id_foreign',
            'disabled_recruitment_processes' => 'disabled_recruitment_processes_business_id_foreign',
            'disabled_banks' => 'disabled_banks_business_id_foreign',
            'disabled_termination_types' => 'disabled_termination_types_business_id_foreign',
            'disabled_termination_reasons' => 'disabled_termination_reasons_business_id_foreign',
        ];

        // Disable foreign key checks to avoid errors during deletion
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete invalid records from tables
        foreach ($foreignKeys as $table => $foreignKey) {
            // Delete records with invalid business_id
            DB::statement("
              DELETE FROM {$table}
              WHERE business_id IS NOT NULL
              AND business_id NOT IN (SELECT id FROM businesses);
          ");
        }

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Drop foreign key constraints if they exist
        foreach ($foreignKeys as $table => $foreignKey) {
            try {
                // Check if the foreign key exists before attempting to drop it
                $foreignKeyExists = DB::select(DB::raw("
                  SELECT CONSTRAINT_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_NAME = '{$table}'
                  AND CONSTRAINT_NAME = '{$foreignKey}'
              "));

                if (!empty($foreignKeyExists)) {
                    DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");
                } else {
                }
            } catch (Exception $e) {
                // Log the error or handle it as needed
                echo "Failed to drop foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
            }
        }

        // Re-add foreign key constraints
        foreach ($foreignKeys as $table => $foreignKey) {
            try {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$foreignKey} FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE");
            } catch (Exception $e) {
                // Log the error or handle it as needed
                echo "Failed to add foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
            }
        }


        if (Schema::hasColumn('notifications', 'entity_name')) {
            // Modify the column type to VARCHAR in MySQL
            DB::statement('ALTER TABLE notifications MODIFY entity_name VARCHAR(255) NULL');
        }
        DB::statement("
                ALTER Table tasks
                MODIFY COLUMN task_category_id BIGINT UNSIGNED NULL;
            ");
    }

    public function updateAndGetServicePlan()
    {

        $modules = Module::where('is_enabled', 1)->pluck('id');

        $service_plan = ServicePlan::first(); // Retrieve the first service plan

        if ($service_plan) {
            $service_plan->update([
                'name' => 'Standard Plan',
                'description' => '',
                'set_up_amount' => 100,
                'number_of_employees_allowed' => 100,
                'duration_months' => 1,
                'price' => 20,
                'business_tier_id' => 1,
                'created_by' => 1,
            ]);

            $service_plan_modules = $modules->map(function ($module_id) use ($service_plan) {
                return [
                    'is_enabled' => 1,
                    'service_plan_id' => $service_plan->id,
                    'module_id' => $module_id,
                    'created_by' => 1,
                ];
            })->toArray();

            ServicePlanModule::insert($service_plan_modules);
        } else {
            $this->setupServicePlan();
            $service_plan = ServicePlan::first(); // Retrieve the first service plan
            if (empty($service_plan)) {
                throw new Exception("service plan issues");
            }
        }

        return $service_plan;
    }

    public function updateDatabase()
    {
        try {
            $this->updateFields();
            EmailTemplate::where("type", "send_password_mail")->delete();

            $this->updateModule();
            $this->storeEmailTemplates();
            $this->setupAssetTypes();

            $businesses = Business::whereHas("owner")
                ->get(["id", "owner_id", "service_plan_id", "reseller_id", "created_by"]);

            $service_plan = $this->updateAndGetServicePlan();

            $this->defaultDataSetupForBusinessV2($businesses, $service_plan);

            return "ok";
        } catch (Exception $e) {
            return [
                "message" => $e->getMessage(),
                "line" => $e->getLine(),
            ];
        }
    }




    public function moveFilesToBusinessFolder(array $fileNames, $businessId, $fileKey = "")
    {
        // Define the base directory for files
        $baseDirectory = public_path();

        // Construct the new base directory path with the business ID
        $newBaseDirectory = public_path("{$businessId}");

        // Ensure the new base directory exists
        if (!File::exists($newBaseDirectory)) {
            File::makeDirectory($newBaseDirectory, 0755, true);
        }

        foreach ($fileNames as $fileName) {

            if (!empty($fileKey)) {
                $fileName = $fileName[$fileKey];
            }


            // Construct the old file path
            $oldFilePath = $baseDirectory . DIRECTORY_SEPARATOR . $fileName;

            // Check if the file exists at the old path
            if (File::exists($oldFilePath)) {
                // Construct the new file path
                $relativeFilePath = $fileName; // The relative path to the file within the base directory
                $newFilePath = $newBaseDirectory . DIRECTORY_SEPARATOR . $relativeFilePath;

                // Ensure the new directory exists
                $newDirectory = dirname($newFilePath);
                if (!File::exists($newDirectory)) {
                    File::makeDirectory($newDirectory, 0755, true);
                }

                // Move the file to the new location
                try {
                    File::move($oldFilePath, $newFilePath);
                    Log::info("File moved successfully from {$oldFilePath} to {$newFilePath}");
                } catch (Exception $e) {
                    // Log any exceptions that occur during the file move
                    Log::error("Failed to move file from {$oldFilePath} to {$newFilePath}: " . $e->getMessage());
                }
            } else {
                // Log an error if the file does not exist
                Log::error("File does not exist: {$oldFilePath}");
            }
        }
    }

    public function moveFilesAndUpdateDatabaseForBusiness($businessId)
    {
        // @@@missing pension
        $modelData = Business::where("id", $businessId)->get(["id", "logo", "image", "background_image"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->logo,
                $data->image,
                $data->background_image
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'logo' =>   !empty($data->logo) ? DIRECTORY_SEPARATOR . $businessId . $data->logo : "",
                'image' =>  !empty($data->image) ? DIRECTORY_SEPARATOR . $businessId . $data->image : "",
                'background_image' => !empty($data->background_image) ? DIRECTORY_SEPARATOR . $businessId . $data->background_image : "",
            ]);
        });
    }
    public function moveFilesAndUpdateDatabaseForBusinessPension($businessId)
    {
        $modelData = Business::where("business_id", $businessId)->get(["id", "pension_scheme_letters"]);

        $modelData->each(function ($data) use ($businessId) {
            // Convert pension_scheme_letters to an array if it's not already one
            $pensionSchemeLetters = is_array($data->pension_scheme_letters) ? $data->pension_scheme_letters : json_decode($data->pension_scheme_letters, true);

            if (is_array($pensionSchemeLetters)) {
                // Move files to the business folder
                // $this->moveFilesToBusinessFolder($pensionSchemeLetters, $businessId);

                // Update the paths in the database
                $updatedLetters = collect($pensionSchemeLetters)->map(function ($letter) use ($businessId) {
                    return  DIRECTORY_SEPARATOR . $businessId . $letter;
                })->toArray();

                $data->update([
                    'pension_scheme_letters' => json_encode($updatedLetters)
                ]);
            }
        });
    }
    public function moveFilesAndUpdateDatabaseForBusinessPensionHistory($businessId)
    {
        $modelData = BusinessPensionHistory::where("business_id", $businessId)->get(["id", "pension_scheme_letters"]);

        $modelData->each(function ($data) use ($businessId) {
            // Convert pension_scheme_letters to an array if it's not already one
            $pensionSchemeLetters = is_array($data->pension_scheme_letters) ? $data->pension_scheme_letters : json_decode($data->pension_scheme_letters, true);

            if (is_array($pensionSchemeLetters)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($pensionSchemeLetters, $businessId);

                // Update the paths in the database
                $updatedLetters = collect($pensionSchemeLetters)->map(function ($letter) use ($businessId) {
                    return  DIRECTORY_SEPARATOR . $businessId . $letter;
                })->toArray();

                $data->update([
                    'pension_scheme_letters' => json_encode($updatedLetters)
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForCandidateRecruitmentProcess($businessId)
    {
        $modelData = CandidateRecruitmentProcess::whereHas("candidate", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return DIRECTORY_SEPARATOR . $businessId . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }
    public function moveFilesAndUpdateDatabaseForCandidate($businessId)
    {
        $modelData = Candidate::where("business_id", $businessId)->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return DIRECTORY_SEPARATOR . $businessId . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForLeave($businessId)
    {
        $modelData = Leave::where("business_id", $businessId)->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return DIRECTORY_SEPARATOR . $businessId . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }
    public function moveFilesAndUpdateDatabaseForSettingPayslip($businessId)
    {
        $modelData = SettingPayslip::where("business_id", $businessId)->get(["id", "logo"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->logo
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'logo' => !empty($data->logo) ? DIRECTORY_SEPARATOR . $businessId . $data->logo : ""
            ]);
        });
    }

    public function moveFilesAndUpdateDatabaseForUserAsset($businessId)
    {
        $modelData = UserAsset::where("business_id", $businessId)->get(["id", "image"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->image
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'image' => !empty($data->image) ? DIRECTORY_SEPARATOR . $businessId . $data->image : ""
            ]);
        });
    }
    public function moveFilesAndUpdateDatabaseForUserDocument($businessId)
    {
        $modelData = UserDocument::whereHas('user', function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })

            ->get(["id", "file_name"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->file_name
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'file_name' => !empty($data->logo) ? DIRECTORY_SEPARATOR . $businessId . $data->file_name : ""
            ]);
        });
    }
    public function moveFilesAndUpdateDatabaseForUserEducationHistory($businessId)
    {
        $modelData = UserEducationHistory::whereHas('user', function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })
            ->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return DIRECTORY_SEPARATOR . $businessId . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForUserRecruitmentProcess($businessId)
    {
        $modelData = UserRecruitmentProcess::whereHas("user", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return DIRECTORY_SEPARATOR . $businessId . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }


    public function moveFilesAndUpdateDatabaseForEmployeeRightToWorkHistory($businessId)
    {
        $modelData = EmployeeRightToWorkHistory::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "right_to_work_docs"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $right_to_work_docs = $data->right_to_work_docs;

            if (is_array($right_to_work_docs)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($right_to_work_docs, $businessId, "file_name");

                // Update the paths in the database
                $updatedAttachments = collect($right_to_work_docs)->map(function ($attachment) use ($businessId) {
                    $attachment["file_name"] = DIRECTORY_SEPARATOR . $businessId . $attachment["file_name"];
                    return $attachment;
                })->toArray();


                $data->update([
                    'right_to_work_docs' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForEmployeeVisaDetailHistory($businessId)
    {
        $modelData = EmployeeVisaDetailHistory::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "visa_docs"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $visa_docs = $data->visa_docs;

            if (is_array($visa_docs)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($visa_docs, $businessId, "file_name");

                // Update the paths in the database
                $updatedAttachments = collect($visa_docs)->map(function ($attachment) use ($businessId) {
                    $attachment["file_name"] = DIRECTORY_SEPARATOR . $businessId . $attachment["file_name"];
                    return $attachment;
                })->toArray();

                $data->update([
                    'visa_docs' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForPayslip($businessId)
    {
        $modelData = Payslip::whereHas("user", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })
            ->get(["id", "payslip_file", "payment_record_file"]);


        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->payslip_file,
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $payment_record_file = $data->payment_record_file;

            if (is_array($payment_record_file)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($payment_record_file, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($payment_record_file)->map(function ($attachment) use ($businessId) {
                    return DIRECTORY_SEPARATOR . $businessId . $attachment;
                })->toArray();

                $data->update([
                    'payment_record_file' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }


            $data->update([
                'payslip_file' => !empty($data->payslip_file) ? DIRECTORY_SEPARATOR . $businessId . $data->payslip_file : "",
            ]);
        });
    }


    public function moveFilesAndUpdateDatabaseForEmployeePensionHistory($businessId)
    {
        $modelData = EmployeePensionHistory::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "pension_letters"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $pension_letters = $data->pension_letters;

            if (is_array($pension_letters)) {
                // Move files to the business folder

                $this->moveFilesToBusinessFolder($pension_letters, $businessId, "file_name");

                // Update the paths in the database
                $updatedAttachments = collect($pension_letters)->map(function ($attachment) use ($businessId) {
                    $attachment["file_name"] = DIRECTORY_SEPARATOR . $businessId . $attachment["file_name"];
                    return $attachment;
                })->toArray();


                $data->update([
                    'pension_letters' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }




    public function updateDatabaseFilesForBusiness()
    {
        DB::beginTransaction();
        try {
            $businesses = Business::get(["id", "logo", "image", "background_image"]);

            $businesses->each(function ($business) {
                echo "" . "<br/>";
                echo "" . "<br/>";

                echo "1" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForBusiness($business->id);
                echo "2" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForBusinessPensionHistory($business->id);
                $this->moveFilesAndUpdateDatabaseForBusinessPension($business->id);
                echo "3" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForCandidateRecruitmentProcess($business->id);
                echo "4" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForCandidate($business->id);
                echo "5" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForLeave($business->id);
                echo "6" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForSettingPayslip($business->id);
                echo "7" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForUserAsset($business->id);
                echo "8" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForUserDocument($business->id);
                echo "9" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForUserEducationHistory($business->id);
                echo "10" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForUserRecruitmentProcess($business->id);
                echo "11" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForEmployeeRightToWorkHistory($business->id);
                echo "12" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForEmployeeVisaDetailHistory($business->id);
                echo "13" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForPayslip($business->id);
                echo "14" . "<br/>";
                $this->moveFilesAndUpdateDatabaseForEmployeePensionHistory($business->id);
                echo "15" . "<br/>";
            });
            DB::commit();
            return "ok";
        } catch (Exception $e) {
            DB::rollBack();
            return [
                "message" => $e->getMessage(),
                "line" => $e->getLine(),
            ];
        }
    }



    public function dbOperation()
    {

        // Update attendances table
        DB::statement("ALTER TABLE `attendances` MODIFY `break_type` VARCHAR(255) NULL;");
        DB::statement("ALTER TABLE `attendances` MODIFY `work_shift_history_id` BIGINT UNSIGNED NULL;");
        DB::statement("ALTER TABLE `attendances` MODIFY `is_weekend` TINYINT(1) NULL;");

        // Update attendance_histories table
        DB::statement("ALTER TABLE `attendance_histories` MODIFY `break_type` VARCHAR(255) NULL;");
        DB::statement("ALTER TABLE `attendance_histories` MODIFY `work_shift_history_id` BIGINT UNSIGNED NULL;");
        DB::statement("ALTER TABLE `attendance_histories` MODIFY `is_weekend` TINYINT(1) NULL;");

        DB::statement("ALTER TABLE leave_records MODIFY start_time TIME NULL");
        DB::statement("ALTER TABLE leave_records MODIFY end_time TIME NULL");

        DB::statement("ALTER TABLE leave_record_histories MODIFY start_time TIME NULL");
        DB::statement("ALTER TABLE leave_record_histories MODIFY end_time TIME NULL");

        DB::table('businesses')
            ->whereRaw('currency IS NULL OR TRIM(currency) = "" OR currency = "£"')
            ->update(['currency' => 'GBP']);

        DB::statement('ALTER TABLE attendances CHANGE attendance_records attendance_records_test JSON');

        DB::statement('ALTER TABLE attendance_histories CHANGE attendance_records attendance_records_test JSON');


        return "ok";
    }
    public function dbOperationV2()
    {

        $work_shifts = WorkShift::get();
        foreach ($work_shifts as $work_shift) {
            $total_schedule_hours = 0;
            foreach ($work_shift->details as $detail) {
                $shifts = [];

                $shifts[] = [
                    "id" =>  Str::random(10),
                    "start_at" =>  $detail->start_at,
                    "end_at" =>  $detail->end_at
                ];
                $detail->shifts = $shifts;

                $start_time = Carbon::parse($detail['start_at']);
                $end_time = Carbon::parse($detail['end_at']);
                $total_duration = $end_time->diffInSeconds($start_time);

                $detail['schedule_hour'] = $total_duration / 3600;

                $total_schedule_hours += $detail['schedule_hour'];
                $detail->save();
            }
            $work_shift->total_schedule_hours = $total_schedule_hours;
            $work_shift->save();
        }

        $work_shift_histories = WorkShiftHistory::get();
        foreach ($work_shift_histories as $work_shift_history) {
            $total_schedule_hours = 0;
            foreach ($work_shift_history->details as $detail) {
                $shifts = [];

                $shifts[] = [
                    "id" =>  Str::random(10),
                    "start_at" =>  $detail->start_at,
                    "end_at" =>  $detail->end_at
                ];
                $detail->shifts = $shifts;

                $start_time = Carbon::parse($detail['start_at']);
                $end_time = Carbon::parse($detail['end_at']);
                $total_duration = $end_time->diffInSeconds($start_time);

                $detail['schedule_hour'] = $total_duration / 3600;

                $total_schedule_hours += $detail['schedule_hour'];
                $detail->save();
            }
            $work_shift_history->total_schedule_hours = $total_schedule_hours;
            $work_shift_history->save();
        }



        $attendances = Attendance::get();
        foreach ($attendances as $attendance) {

            $attendance_record = $attendance->attendance_records_test;
            if (!is_array($attendance_record)) {
                $attendance_record = json_decode($attendance_record, true);
            }


            foreach ($attendance_record as $record) {
                $attendance_record = AttendanceRecord::create([
                    'in_time' => $record["in_time"],
                    'out_time'  => $record["out_time"],
                    'in_latitude'  => $record["in_latitude"] ?? "",
                    'in_longitude'  => $record["in_longitude"] ?? "",
                    'out_latitude'  => $record["out_latitude"] ?? "",
                    'out_longitude'  => $record["out_longitude"] ?? "",
                    'in_ip_address'  => $record["in_ip_address"] ?? "",
                    'out_ip_address'  => $record["out_ip_address"] ?? "",
                    'clocked_in_by'  => $record["clocked_in_by"] ?? NULL,
                    'clocked_out_by'  => $record["clocked_out_by"] ?? NULL,

                    'time_zone'  => $record["time_zone"] ?? "",
                    'attendance_id' => $attendance->id,
                    'break_hours' => ($attendance->break_hours ? ($attendance->break_hours / count($attendance->attendance_records_test)) : 0),
                    'is_paid_break' => ($attendance->break_type == "paid" ? 1 : 0),
                    'note' => $attendance->note,
                    'work_location_id' => $attendance->work_location_id,
                ]);

                $attendance_projects = AttendanceProject::where([
                    "attendance_id" => $attendance->id
                ])
                    ->get();

                $project_ids =  $attendance_projects->pluck("project_id")->toArray();
                $attendance_record->projects()->sync($project_ids);
            }
        }

        $attendance_histories = AttendanceHistory::get();
        foreach ($attendance_histories as $attendance_history) {

            $attendance_history_record = $attendance_history->attendance_records_test;
            if (!is_array($attendance_record)) {
                $attendance_history_record = json_decode($attendance_history_record, true);
            }
            foreach ($attendance_history_record as $record) {
                $attendance_history_record = AttendanceHistoryRecord::create([
                    'in_time' => $record["in_time"],
                    'out_time'  => $record["out_time"],
                    'in_latitude'  => $record["in_latitude"] ?? "",
                    'in_longitude'  => $record["in_longitude"] ?? "",
                    'out_latitude'  => $record["out_latitude"] ?? "",
                    'out_longitude'  => $record["out_longitude"] ?? "",
                    'in_ip_address'  => $record["in_ip_address"] ?? "",
                    'out_ip_address'  => $record["out_ip_address"] ?? "",
                    'clocked_in_by'  => $record["clocked_in_by"] ?? NULL,
                    'clocked_out_by'  => $record["clocked_out_by"] ?? NULL,
                    'time_zone'  => $record["time_zone"] ?? "",

                    'attendance_id' => $attendance_history->id,
                    'break_hours' => ($attendance_history->break_hours ? ($attendance_history->break_hours / count($attendance->attendance_records_test)) : 0),
                    'is_paid_break' => ($attendance_history->break_type == "paid" ? 1 : 0),
                    'note' => $attendance_history->note,
                    'work_location_id' => $attendance_history->work_location_id,
                ]);

                $attendance_projects = AttendanceHistoryProject::where([
                    "attendance_id" => $attendance->id
                ])
                    ->get();

                $project_ids =  $attendance_projects->pluck("project_id")->toArray();
                $attendance_history_record->projects()->sync($project_ids);
            }
        }

        return "ok";
    }

    public function dbOperationV3()
    {

        // Alter columns in attendance_history_records table to DATETIME if needed
        DB::statement('ALTER TABLE attendance_history_records MODIFY in_time DATETIME NULL');
        DB::statement('ALTER TABLE attendance_history_records MODIFY out_time DATETIME NULL');

        // Alter columns in attendance_records table to DATETIME if needed
        DB::statement('ALTER TABLE attendance_records MODIFY in_time DATETIME NULL');
        DB::statement('ALTER TABLE attendance_records MODIFY out_time DATETIME NULL');

        // Process all Attendance records
        $attendances = Attendance::get();

        foreach ($attendances as $attendance) {

            // Get AttendanceRecord records for each attendance
            $attendanceRecords = AttendanceRecord::where('attendance_id', $attendance->id)->get();
            foreach ($attendanceRecords as $attendanceRecord) {

                // Combine the date (in_date) from attendance with the existing time (in_time)

                $attendanceRecord->in_time = Carbon::parse($attendance->in_date . ' ' . Carbon::parse($attendanceRecord->in_time)->toTimeString())->toDateTimeString();
                $attendanceRecord->out_time = Carbon::parse($attendance->in_date . ' ' . Carbon::parse($attendanceRecord->out_time)->toTimeString())->toDateTimeString();

                $attendanceRecord->save();
            }
        }

        // Process all AttendanceHistory records
        $attendancesHistory = AttendanceHistory::get();
        foreach ($attendancesHistory as $attendance) {

            // Get AttendanceHistoryRecord records for each attendance history
            $attendanceHistoryRecords = AttendanceHistoryRecord::where('attendance_id', $attendance->id)->get();
            foreach ($attendanceHistoryRecords as $attendanceHistoryRecord) {

                $attendanceHistoryRecord->in_time = Carbon::parse($attendance->in_date . ' ' . Carbon::parse($attendanceHistoryRecord->in_time)->toTimeString())->toDateTimeString();
                $attendanceHistoryRecord->out_time = Carbon::parse($attendance->in_date . ' ' . Carbon::parse($attendanceHistoryRecord->out_time)->toTimeString())->toDateTimeString();

                $attendanceHistoryRecord->save();
            }
        }

        return "ok";
    }


    public function dbOperationV4()
    {

        $leaves = Leave::get();

        foreach ($leaves as $leave) {
            $leave_records = $leave->records()->get();
            $total_recorded_hours = $leave_records->sum('leave_hours');
            $leave->total_leave_hours = $total_recorded_hours;
            $leave->save();
        }

        return "ok";
    }

    public function dbOperationV5()
    {

        $businesses = Business::get();
        foreach ($businesses as $business) {
            $minDate = LeaveRecord::whereHas("leave", function ($query) use ($business) {
                    $query->where("leaves.business_id", $business->id);
                })
                ->min('date');
            $maxDate = LeaveRecord::whereHas("leave", function ($query) use ($business) {
                    $query->where("leaves.business_id", $business->id);
                })
                ->max('date');

            // Extract the years from the dates
            $startYear = Carbon::parse($minDate)->year;
            $endYear = Carbon::parse($maxDate)->year;

            $setting_leave = SettingLeave::where('business_id', $business->id)
                ->where('is_default', 0)
                ->first();

            if (empty($setting_leave)) {
                $this->loadDefaultSettingLeave($business);
                $setting_leave = SettingLeave::where('business_id', $business->id)
                    ->where('is_default', 0)
                    ->first();
            }

            // Loop through the years between the start and end years
            for ($year = $startYear; $year <= $endYear; $year++) {
                // Get all leaves that have records within this year
                $leavesForYear = Leave::where("leaves.business_id", $business->id)
                    ->whereHas('records', function ($query) use ($year) {
                        $query->whereDate('date', '>=', Carbon::createFromDate($year)->startOfYear())
                            ->whereDate('date', '<', Carbon::createFromDate($year + 1)->startOfYear());
                    })->get();

                foreach ($leavesForYear as $leave) {

                    // Use the passed year to set the start month and first day of that month
                    $leave_start_date = Carbon::create($year, $setting_leave->start_month, 1);

                    // Calculate leave expiry date (1 year after the start of leave)
                    $leave_expiry_date = $leave_start_date->copy()->addYear()->subDay();

                    $hasMultipleYears = $leave->records()
                        ->whereDate('date', '>=', Carbon::createFromDate($year - 1)->startOfYear())
                        ->whereDate('date', '<', Carbon::createFromDate($year)->startOfYear())
                        ->exists();


                    if (!$hasMultipleYears) {
                        $leave_type = $leave->leave_type;

                        $leaveAllowance = EmployeeLeaveAllowance::where('user_id', $leave->user_id)
                            ->where('setting_leave_type_id', $leave_type->id)
                            ->whereDate('leave_start_date', $leave_start_date)
                            ->whereDate('leave_expiry_date', $leave_expiry_date)
                            ->first();

                        if (empty($leaveAllowance)) {
                            // Create a new EmployeeLeaveAllowance record if it doesn't exist
                            $leaveAllowance = EmployeeLeaveAllowance::create([
                                'user_id' => $leave->user_id,
                                'setting_leave_type_id' => $leave_type->id,
                                'total_leave_hours' => $leave_type->amount,
                                'used_leave_hours' => 0,
                                'carry_over_hours' => 0,
                                'leave_start_date' => $leave_start_date,
                                'leave_expiry_date' => $leave_expiry_date,
                            ]);
                        }
                        $leaveAllowance->used_leave_hours = $leaveAllowance->used_leave_hours + $leave->total_leave_hours;
                        $leaveAllowance->save();
                        $leave->employee_leave_allowance_id = $leaveAllowance->id;
                        $leave->save();
                    }
                }
            }
        }


        return "ok";
    }

    public function dbOperationV6()
    {

        $users = User::get();

        foreach ($users as $user) {

            if ($user->gender == "male" || $user->gender == "other") {
                $user->title = "Mr";
            } else if ($user->gender == "female") {
                $user->title = "Ms";
            }

            $user->save();
        }

        return "ok";
    }
    public function dbOperationV7()
    {

        $attendances = Attendance::where([
                "id" => 800
            ])
            ->get();

        foreach ($attendances as $attendance) {


            $work_shift_history = $attendance->work_shift_history;


            if (!empty($work_shift_history)) {
                $work_shift_details = collect($work_shift_history->details)
                    ->filter(function ($detail) use ($attendance) {
                        $day_number = Carbon::parse($attendance->in_date)->dayOfWeek;
                        return $day_number === $detail["day"];
                    })
                    ->first();

                $leave_record = $attendance->leave_record;
                $capacity_hours = $attendance->capacity_hours;
                $total_paid_hours = $attendance->total_paid_hours;

                $overtime_information = $this->attendanceComponent->calculate_overtime($work_shift_details->is_weekend, $capacity_hours, $total_paid_hours, $leave_record, $attendance->holiday_id ?? NULL, $attendance);

                $attendance->leave_hours = $overtime_information["leave_hours"];
            } else {
                $attendance->leave_hours = $attendance->overtime_hours;
                echo $attendance->id . ":";
            }
            $attendance->save();
        }

        return "ok";
    }

    public function editMigrationFunc($migrationName)
    {
        DB::table('migrations')
            ->where('batch', '>=', 84)
            ->increment('batch');

        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => 84,
        ]);
    }
    public function editMigration()
    {

        $this->editMigrationFunc("2025_01_14_131522_add_work_shift_history_detail_id_to_leaves_and_leave_histories_tables");

        return "ok";
    }


    public function dbOperationV8()
    {

        DB::statement('ALTER TABLE exit_interviews DROP COLUMN exit_interview_conducted');
        DB::statement('ALTER TABLE terminations DROP COLUMN continuation_of_benefits_offered');

        return "ok";
    }





    public function dbOperationV9()
    {

        $users = User::whereNotNull("business_id")->get();

        foreach ($users as $user) {
            $this->manipulateCurrentData("EmployeePassportDetailHistory", "passport_issue_date", "passport_expiry_date", $user->id);
            $this->manipulateCurrentData("EmployeePensionHistory", "pension_enrollment_issue_date", "pension_enrollment_issue_date", $user->id);
            $this->manipulateCurrentData("EmployeeRightToWorkHistory", "right_to_work_check_date", "right_to_work_expiry_date", $user->id);
            $this->manipulateCurrentData("EmployeeSponsorshipHistory", "date_assigned", "expiry_date", $user->id);
            $this->manipulateCurrentData("EmployeeVisaDetailHistory", "visa_issue_date", "visa_expiry_date", $user->id);
        }


        return "ok";
    }

    public function dbOperationV10()
    {

        if (Schema::hasTable('department_holidays')) {
            DB::statement('DROP TABLE department_holidays');
        }

        if (Schema::hasTable('user_holidays')) {
            DB::statement('DROP TABLE user_holidays');
        }

        if (Schema::hasColumn('candidate', 'interview_date')) {
            DB::statement('ALTER TABLE candidate DROP COLUMN interview_date');
        }
        if (Schema::hasTable('access_revocations')) {
            DB::statement('DROP TABLE access_revocations');
        }


        return "ok";
    }
}
