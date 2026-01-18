<?php

namespace App\Http\Utils;

use App\Mail\BusinessWelcomeMail;

use App\Models\Business;
use App\Models\BusinessTime;
use App\Models\Department;

use App\Models\EmailTemplate;
use App\Models\LetterTemplate;
use App\Models\Project;
use App\Models\Role;
use App\Models\ServicePlan;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingPaymentDate;
use App\Models\SettingPayrun;
use App\Models\Task;
use App\Models\User;
use App\Models\UserLetter;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Models\WorkShiftDetail;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


trait BusinessUtil
{
    use BasicUtil, DiscountUtil, SetupUtil;
    // this function do all the task and returns transaction id or -1


    public function checkServicePlanAvailability($service_plan_id, $business)
    {


        $new_service_plan = ServicePlan::with("service_plan_modules.module")
            ->find($service_plan_id);

        if (!$new_service_plan) {
            throw new Exception('Service plan not found.', 404);
        }

        // Prepare a map of modules with their enabled status
        $module_status = [];
        foreach ($new_service_plan->service_plan_modules as $sp_module) {
            if ($sp_module->module && isset($sp_module->module->name)) {
                $module_status[$sp_module->module->name] = $sp_module->is_enabled;
            }
        }


        if (empty($business->number_of_employees_allowed)) {
            $number_of_employees_allowed = $new_service_plan->getRawOriginal('number_of_employees_allowed');

            $user_count = Business::withCount([
                'users' => function ($query) {
                    $query->where("users.is_active", 1)
                        ->whereColumn('users.id', '!=', 'businesses.owner_id')
                        ->whereDoesntHave("lastTermination", function ($query) {
                            $query->where('terminations.date_of_termination', "<", today())
                                ->whereRaw('terminations.date_of_termination > users.joining_date');
                        });
                }
            ])
                ->where('id', $business->id)
                ->having('users_count', '>', $number_of_employees_allowed)
                ->exists();

            if ($user_count) {
                throw new Exception("Business ID {$business->id} exceeds allowed number of employees.", 409);
            }
        }

        // Check for multiple departments
        if (isset($module_status['department']) && !$module_status['department']) {
            if (Department::where('business_id', $business->id)->count() > 1) {

                throw new Exception('Cannot change service plan. "Department" module must be enabled for businesses with multiple departments.', 409);
            }
        }

        // Check for multiple projects
        if (isset($module_status['project']) && !$module_status['project']) {
            if (Project::where('business_id', $business->id)->count() > 1) {

                throw new Exception('Cannot change service plan. "Project" module must be enabled for businesses with multiple projects.', 409);
            }
        }

        // Check for multiple letter_template
        if (isset($module_status['letter_template']) && !$module_status['letter_template']) {
            if (LetterTemplate::where('business_id', $business->id)->count()) {
                throw new Exception('Cannot change service plan. "letter template" module must be enabled for businesses with letter template.', 409);
            }
            if (UserLetter::where('business_id', $business->id)->count()) {
                throw new Exception('Cannot change service plan. "letter template" module must be enabled for businesses with employee letters.', 409);
            }
        }

        // Check for multiple flexible_shifts
        if (isset($module_status['flexible_shifts']) && !$module_status['flexible_shifts']) {
            if (WorkShift::where('business_id', $business->id)
                ->where("type", "flexible")
                ->count()
            ) {
                throw new Exception('Cannot change service plan. "flexible shifts" module must be enabled for businesses with flexible shifts.', 409);
            }
            if (WorkShiftHistory::where('business_id', $business->id)
                ->where("type", "flexible")
                ->count()
            ) {
                throw new Exception('Cannot change service plan. "flexible shifts" module must be enabled for businesses with flexible shifts.', 409);
            }
        }

        // Check for multiple task_management
        if (isset($module_status['task_management']) && !$module_status['task_management']) {
            if (Task::where('business_id', $business->id)
                ->count()
            ) {
                throw new Exception('Cannot change service plan. "task management" module must be enabled for businesses with tasks.', 409);
            }
        }
    }


    public function businessOwnerCheck($business_id, $strict = FALSE)
    {

        $business = Business::where('id', $business_id)
            ->when(
                $strict || !request()->user()->hasRole('superadmin'),
                function ($query) {
                    $query->where(function ($query) {
                        $query
                            // ->where('id', auth()->user()->business_id)
                            // ->orWhere('created_by', auth()->user()->id)
                            ->orWhere('owner_id', auth()->user()->id)
                            ->orWhere('reseller_id', auth()->user()->id)
                        ;
                    });
                },
            )
            ->first();


        if (empty($business)) {
            throw new Exception("you are not the owner of the business or the requested business does not exist.", 401);
        }
        return $business;
    }











    public function loadDefaultEmailTemplates($business_id)
    {

        // Fetch active, default email templates without a business_id
        $email_templates = EmailTemplate::where([
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => NULL
        ])->get();

        // Transform the collection to include only the necessary fields for insertion
        $transformed_templates = $email_templates->map(function ($template) use ($business_id) {
            return [
                "name" => $template->name,
                "type" => $template->type,
                "is_active" => 1,
                "wrapper_id" => 1,
                "is_default" => 0,
                "business_id" => $business_id,
                "template" => $template->template,
                "template_variables" =>  implode(',', $template->template_variables),
                "created_at" => now(),
                "updated_at" => now(),
            ];
        });

        // Insert the transformed templates
        EmailTemplate::insert($transformed_templates->toArray());
    }







    public function loadDefaultSettingLeave($business = NULL)
    {
        // load setting leave

        $default_setting_leave_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" =>  1,
        ];

        $defaultSettingLeaves = SettingLeave::where($default_setting_leave_query)->get();


        foreach ($defaultSettingLeaves as $defaultSettingLeave) {

            $insertableData = [
                'start_month' => $defaultSettingLeave->start_month,
                'approval_level' => $defaultSettingLeave->approval_level,
                'allow_bypass' => $defaultSettingLeave->allow_bypass,
                "created_by" => $business->created_by,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business->id,
            ];


            $setting_leave  = SettingLeave::create($insertableData);

            $business_owner_role_id = Role::where([
                "name" => ("business_owner#" . $business->id)
            ])
                ->pluck("id");

            $setting_leave->special_roles()->sync($business_owner_role_id);


            $default_paid_leave_employment_statuses = $defaultSettingLeave->paid_leave_employment_statuses()->pluck("employment_status_id");
            $setting_leave->paid_leave_employment_statuses()->sync($default_paid_leave_employment_statuses);

            $default_unpaid_leave_employment_statuses = $defaultSettingLeave->unpaid_leave_employment_statuses()->pluck("employment_status_id");
            $setting_leave->unpaid_leave_employment_statuses()->sync($default_unpaid_leave_employment_statuses);
        }

        // end load setting leave
    }


    public function loadDefaultAttendanceSetting($business = NULL)
    {
        // load setting attendance

        $default_setting_attendance_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" =>  1,
        ];


        $defaultSettingAttendances = SettingAttendance::where($default_setting_attendance_query)->get();


        foreach ($defaultSettingAttendances as $defaultSettingAttendance) {

            $insertableData = [
                'punch_in_time_tolerance' => $defaultSettingAttendance->punch_in_time_tolerance,
                'work_availability_definition' => $defaultSettingAttendance->work_availability_definition,
                'punch_in_out_alert' => $defaultSettingAttendance->punch_in_out_alert,
                'punch_in_out_interval' => $defaultSettingAttendance->punch_in_out_interval,
                'alert_area' => $defaultSettingAttendance->alert_area,
                'auto_approval' => $defaultSettingAttendance->auto_approval,
                'is_geolocation_enabled' => $defaultSettingAttendance->is_geolocation_enabled,


                'service_name' => $defaultSettingAttendance->service_name,
                'api_key' => $defaultSettingAttendance->api_key,

                "created_by" => $business->created_by,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business->id,







            ];

            $setting_attendance  = SettingAttendance::create($insertableData);




            $business_owner_role_id = Role::where([
                "name" => ("business_owner#" . $business->id)
            ])
                ->pluck("id");
            $setting_attendance->special_roles()->sync($business_owner_role_id);
        }

        // end load setting attendance

    }
    public function loadDefaultPayrunSetting($business = NULL)
    {
        // load setting attendance

        $default_setting_payrun_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1,
        ];


        $defaultSettingPayruns = SettingPayrun::where($default_setting_payrun_query)->get();




        foreach ($defaultSettingPayruns as $defaultSettingPayrun) {
            $insertableData = [
                'payrun_period' => $defaultSettingPayrun->payrun_period,
                'consider_type' => $defaultSettingPayrun->consider_type,
                'consider_overtime' => $defaultSettingPayrun->consider_overtime,

                "created_by" => $business->created_by,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business->id,
            ];

            $setting_payrun  = SettingPayrun::create($insertableData);




            //   $business_owner_role_id = Role::where([
            //       "name" => ("business_owner#" . $business_id)
            //   ])
            //   ->pluck("id");
            //   $setting_attendance->special_roles()->sync($business_owner_role_id, []);
        }
    }

    public function loadDefaultPaymentDateSetting($business = null)
    {
        // Load default payment date settings

        $default_setting_payment_date_query = [
            'business_id' => null,
            'is_active' => 1,
            'is_default' =>  1,
        ];

        $defaultSettingPaymentDates = SettingPaymentDate::where($default_setting_payment_date_query)->get();



        foreach ($defaultSettingPaymentDates as $defaultSettingPaymentDate) {
            $insertableData = [
                'payment_type' => $defaultSettingPaymentDate->payment_type,
                'day_of_week' => $defaultSettingPaymentDate->day_of_week,
                'day_of_month' => $defaultSettingPaymentDate->day_of_month,
                'custom_frequency_interval' => $defaultSettingPaymentDate->custom_frequency_interval,
                'custom_frequency_unit' => $defaultSettingPaymentDate->custom_frequency_unit,
                'notification_delivery_status' => $defaultSettingPaymentDate->notification_delivery_status,
                'is_active' => 1,
                'is_default' => 0,
                'business_id' => $business->id,
                'created_by' => $business->created_by,
                'role_specific_settings' => $defaultSettingPaymentDate->role_specific_settings,
            ];

            $settingPaymentDate = SettingPaymentDate::create($insertableData);

            // Additional logic can be added here if needed
        }
    }




    // end load setting attendance

    public function storeDefaultsToBusiness($business)
    {
        // $business->service_plan_id;

        // all the data that must be setup by the system.
        $work_location =  WorkLocation::create([
            'name' => ($business->name . " " . "Office"),
            "is_active" => 1,
            "is_default" => 0,
            "business_id" => $business->id,
            "created_by" => $business->owner_id,
            'description' => "{$business->name} Office located at {$business->address_line_1}.",
            'address' => $business->address_line_1,
            'is_location_enabled' => 1,
            "is_geo_location_enabled" => 1,
            "is_ip_enabled" => 0,
            "max_radius" => "",
            "ip_address" => "",
            'latitude' => $business->lat,
            'longitude' => $business->long
        ]);

        $department =  Department::create([
            "name" => $business->name,
            "location" => $business->address_line_1,
            "is_active" => 1,
            "manager_id" => $business->owner_id,
            "business_id" => $business->id,
            "work_location_id" => $work_location->id,
            "created_by" => $business->owner_id
        ]);

        $default_work_shift_data = [
            'name' => 'Default work shift',
            'break_type' => 'unpaid',
            "break_hours" => 1,
            "total_schedule_hours" => 0,
            'type' => 'regular',
            'description' => '',
            'is_business_default' => 1,
            'is_personal' => 0,
            "is_default" => 1,
            "is_active" => 1,
            "business_id" => $business->id,
            "created_by" => $business->owner_id,

        ];

        $default_work_shift = WorkShift::create($default_work_shift_data);

        $total_schedule_hours = 0;
        foreach ($business->times as $business_time) {
            // $business_time =

            $start_time = $business_time->start_at ? Carbon::parse($business_time->start_at) : NULL;
            $end_time = $business_time->end_at ? Carbon::parse($business_time->end_at) : NULL;

            $schedule_hour = 0;
            if (!$business_time->is_weekend && $start_time && $end_time) {
                $schedule_hour = $end_time->diffInSeconds($start_time) / 3600;
            }

            $total_schedule_hours += $schedule_hour;

            WorkShiftDetail::create([
                "work_shift_id" => $default_work_shift->id,
                "shifts" => [[
                    "id" =>  Str::random(10),
                    "start_at" => $start_time ? $start_time->format('H:i:s') : null, // Handle null for start time
                    "end_at" => $end_time ? $end_time->format('H:i:s') : null,       // Handle null for end time
                ]],
                'day' => $business_time->day,
                // "start_at",
                // 'end_at',
                'is_weekend' => $business_time->is_weekend,
                "schedule_hour" => $schedule_hour

            ]);
        }
        $default_work_shift->total_schedule_hours = $total_schedule_hours;
        $default_work_shift->save();
        $default_work_shift->departments()->sync([$department->id]);



        $defaultRoles = Role::where([
            "business_id" => NULL,
            "is_default" => 1,
            "is_default_for_business" => 1,
            "guard_name" => "api",
        ])->get();
        foreach ($defaultRoles as $defaultRole) {
            $insertableData = [
                'name'  => ($defaultRole->name . "#" . $business->id),
                "is_default" => 1,
                "business_id" => $business->id,
                "is_default_for_business" => 0,
                "guard_name" => "api",
            ];
            $role  = Role::create($insertableData);
            $permissions = $defaultRole->permissions;
            foreach ($permissions as $permission) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }


        $this->loadDefaultEmailTemplates($business->id);

        $this->defaultDataSetupForBusiness([$business]);

        if (!empty($business->enable_auto_business_setup)) {

            $business->current_setup_step = "general_setup";

            Project::create([
                'name' => $business->name,
                'description',
                'start_date' => $business->start_date,
                'end_date' => NULL,
                'status' => "in_progress",
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => $business->id,
                "created_by" => $business->owner_id
            ]);

            $this->loadDefaultSettingLeave($business);

            $this->loadDefaultAttendanceSetting($business);

            $this->loadDefaultPayrunSetting($business);

            $this->loadDefaultPaymentDateSetting($business);
        } else {
            $business->current_setup_step = "pending_setup";
        }
        $business->save();
    }


    public function businessImageStore($business)
    {
        if (!empty($business["images"])) {
            $business["images"] = $this->storeUploadedFiles($business["images"], "", "business_images");
            $this->makeFilePermanent($business["images"], "");
        }
        if (!empty($business["image"])) {
            $business["image"] = $this->storeUploadedFiles([$business["image"]], "", "business_images")[0];
            $this->makeFilePermanent([$business["image"]], "");
        }
        if (!empty($business["logo"])) {
            $business["logo"] = $this->storeUploadedFiles([$business["logo"]], "", "business_images")[0];
            $this->makeFilePermanent([$business["logo"]], "");
        }
        if (!empty($business["background_image"])) {
            $business["background_image"] = $this->storeUploadedFiles([$business["background_image"]], "", "business_images")[0];
            $this->makeFilePermanent([$business["background_image"]], "");
        }
        return $business;
    }



    public function businessImageRollBack($request_data)
    {
        if (!empty($request_data["business"]["images"])) {
            try {

                $this->moveUploadedFilesBack($request_data["business"]["images"], "", "business_images");
            } catch (Exception $innerException) {
            }
        }

        if (!empty($request_data["business"]["image"])) {
            try {

                $this->moveUploadedFilesBack($request_data["business"]["image"], "", "business_images");
            } catch (Exception $innerException) {
            }
        }
        if (!empty($request_data["business"]["logo"])) {
            try {

                $this->moveUploadedFilesBack($request_data["business"]["logo"], "", "business_images");
            } catch (Exception $innerException) {
            }
        }

        if (!empty($request_data["business"]["background_image"])) {
            try {

                $this->moveUploadedFilesBack($request_data["business"]["background_image"], "", "business_images");
            } catch (Exception $innerException) {
            }
        }
    }




    public function createUserWithBusiness($request_data)
    {
        // user info starts ##############

        $password = $request_data['user']['password'];

        $request_data['user']['password'] = Hash::make($request_data['user']['password']);
        $request_data['user']['remember_token'] = Str::random(10);
        $request_data['user']['is_active'] = true;
        $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
        $request_data['user']['address_line_2'] = (!empty($request_data['business']['address_line_2']) ? $request_data['business']['address_line_2'] : "");
        $request_data['user']['country'] = $request_data['business']['country'];
        $request_data['user']['city'] = $request_data['business']['city'];
        $request_data['user']['postcode'] = $request_data['business']['postcode'];
        $request_data['user']['lat'] = $request_data['business']['lat'];
        $request_data['user']['long'] = $request_data['business']['long'];



        $user =  User::create($request_data['user']);


        if (!auth()->check()) {

            Auth::login($user);
        }

        $user->assignRole('business_owner');

        $created_by_user = auth()->user();

        if (empty($created_by_user)) {

            if (!empty($request_data['business']['reseller_id'])) {
                $created_by_user = $request_data['business']['reseller_id'];
            } else {

                $created_by_user = User::permission(['handle_self_registered_businesses'])->first();
            }

            $request_data["business"]["number_of_employees_allowed"] = 0;
        }

        if (empty($request_data['business']['reseller_id'])) {
            $request_data['business']['reseller_id'] = $created_by_user->id;
        }


        if (empty($request_data["business"]["number_of_employees_allowed"])) {
            $request_data["business"]["number_of_employees_allowed"] = 0;
        }



        // end user info ##############


        //  business info ##############

        $request_data['business']['status'] = "pending";
        $request_data['business']['owner_id'] = $user->id;
        $request_data['business']['created_by'] = $created_by_user->id;
        $request_data['business']['is_active'] = true;
        $request_data['business']["pension_scheme_letters"] = [];
        $request_data['business']['service_plan_discount_amount'] = $this->getDiscountAmount($request_data['business']);

        $business =  Business::create($request_data['business']);

        $user->email_verified_at = now();
        $user->business_id = $business->id;
        $token = Str::random(30);
        $user->resetPasswordToken = $token;
        $user->resetPasswordExpires = Carbon::now()->subDays(-1);
        $user->created_by = $created_by_user->id;
        $user->save();

        BusinessTime::where([
            "business_id" => $business->id
        ])
            ->delete();
        $timesArray = collect($request_data["times"])->unique("day");
        foreach ($timesArray as $business_time) {
            BusinessTime::create([
                "business_id" => $business->id,
                "day" => $business_time["day"],
                "start_at" => $business_time["start_at"],
                "end_at" => $business_time["end_at"],
                "is_weekend" => $business_time["is_weekend"],
            ]);
        }

        $this->storeDefaultsToBusiness($business);

        if (env("SEND_EMAIL") == true) {
            $this->checkEmailSender($user->id, 0);

            try {
                Mail::to($request_data['user']['email'])->send(new BusinessWelcomeMail($user, $password));
            } catch (\Exception $e) {
                // Optionally log the error message if needed
                Log::error("Failed to send email: " . $e->getMessage());
                // Continue processing without interrupting the flow
            }

            // @@@important email should go to the reseller of the business

        }

        $business->service_plan = $business->service_plan;

        $this->createTicketingSystemUser($user,$business);

        return [
            "user" => $user,
            "business" => $business
        ];
    }


}
