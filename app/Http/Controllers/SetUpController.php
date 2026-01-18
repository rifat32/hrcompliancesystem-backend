<?php

namespace App\Http\Controllers;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\SetupUtil;

use App\Models\ActivityLog;
use App\Models\Bank;

use App\Models\Designation;
use App\Models\EmploymentStatus;

use App\Models\JobPlatform;
use App\Models\JobType;
use App\Models\RecruitmentProcess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

use App\Models\ServicePlan;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\SettingPaymentDate;
use App\Models\SettingPayrun;
use App\Models\SocialSite;
use App\Models\SystemSetting;
use App\Models\TaskCategory;
use App\Models\TerminationReason;
use App\Models\TerminationType;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Exception;
use Stripe\WebhookEndpoint;
use Stripe\Stripe;


class SetUpController extends Controller
{
    use ErrorUtil, SetupUtil;





    public function testActivity($id,Request $request) {

        $activity_log = ActivityLog::where("id",$request->id)

        ->first();
        return view("test-activity",compact("activity_log"));
    }

    public function testApi($id,Request $request) {

        $activity_log = ActivityLog::where("id",$request->id)

        ->first();
        return view("test-api",compact("activity_log"));
    }



   public function getActivityLogs(Request $request) {
    $activity_logs = ActivityLog::
        when(!empty($request->status_code), function ($query) use ($request) {
            $query->where('status_code', $request->status_code);
        })
        ->when(!empty($request->user_id), function ($query) use ($request) {
            $query->where('user_id', $request->user_id);
        })
        ->when(!empty($request->business_id), function ($query) use ($request) {
            $query->whereExists(function ($subQuery) use ($request) {
                $subQuery->select(DB::raw(1))
                    ->from(DB::connection('mysql')->getDatabaseName() . '.users')
                    ->whereColumn('activity_logs.user_id', 'users.id')
                    ->where('users.business_id', $request->business_id);
            });
        })
        ->when(!empty($request->api_url), function ($query) use ($request) {
            $query->where('api_url', $request->api_url);
        })
        ->when(!empty($request->ip_address), function ($query) use ($request) {
            $query->where('ip_address', $request->ip_address);
        })
        ->when(!empty($request->request_method), function ($query) use ($request) {
            $query->where('request_method', $request->request_method);
        })
        ->when($request->filled('is_error'), function ($query) use ($request) {
            $query->where('is_error', $request->boolean('is_error') ? 1 : 0);
        })
        ->when(!empty($request->date), function ($query) use ($request) {
            $query->whereDate('created_at', $request->date);
        })
        ->when(!empty($request->id), function ($query) use ($request) {
            $query->where('id', $request->id);
        })
        ->orderbyDesc('id')
        ->paginate(20);

    return view('user-activity-log', compact('activity_logs'));
}

    public function migrate(Request $request) {

        Artisan::call('check:migrate');
        return "migrated";
            }

    public function cronJob(Request $request) {

        Artisan::call('notify:late-employees');
        return "cron job run";
        }

  public function artisanCommand(Request $request,$command) {

        Artisan::call($command);
        return "comman has run";
    }


    public function swaggerRefresh(Request $request) {

        Artisan::call('optimize:clear');
Artisan::call('l5-swagger:generate');
return "swagger generated";
    }

    public function configureStripe(Request $request)
    {

        $system_settings = SystemSetting::get();
        foreach ($system_settings as $system_setting) {
            $stripeValid = false;
            if (!empty($system_setting->STRIPE_SECRET) && !empty($system_setting->STRIPE_KEY)) {
                // Verify the Stripe credentials before updating
                try {
                    // Set Stripe client with the provided secret
                    $stripe = new \Stripe\StripeClient($system_setting->STRIPE_SECRET);

                    // Make a test API call to check balance instead of account details
                    $balance = $stripe->balance->retrieve();

                    // If the request is successful, mark the Stripe credentials as valid
                    $stripeValid = true;
                } catch (Exception $e) {
                    $stripeValid = false;
                }
            }

            if ($stripeValid) {

                Stripe::setApiKey($system_setting->STRIPE_SECRET);
                Stripe::setClientId($system_setting->STRIPE_KEY);

                // Define the required events
                $requiredEvents = [
                    'checkout.session.completed', // One-time payments
                    'invoice.payment_succeeded',  // Subscription payments
                ];
                // Retrieve all webhook endpoints from Stripe
                $webhookEndpoints = WebhookEndpoint::all();

                // Check if a webhook endpoint with the desired URL already exists
                $existingEndpoint = collect($webhookEndpoints->data)->first(function ($endpoint) {
                    return $endpoint->url === route('stripe.webhook'); // Replace with your actual endpoint URL
                });

                if ($existingEndpoint) {
                    // Check if all required events are already in enabled events
                    $currentEvents = $existingEndpoint->enabled_events ?? [];
                    $missingEvents = array_diff($requiredEvents, $currentEvents);

                    if (!empty($missingEvents)) {
                        // Add missing events to the existing endpoint
                        WebhookEndpoint::update(
                            $existingEndpoint->id,
                            [
                                'enabled_events' => array_unique(array_merge(
                                    $currentEvents,
                                    $missingEvents
                                )),
                            ]
                        );
                    }
                }
            }
        }

        return "ok";
    }
    public function setUp(Request $request)
    {

        // @@@@@@@@@@@@@@@@@@@
        // clear everything
        // @@@@@@@@@@@@@@@@@@@

        Artisan::call('migrate:fresh', [
            '--path' => 'database/activity_migrations',
            '--database' => 'logs'
        ]);

        Artisan::call('optimize:clear');
        Artisan::call('migrate:fresh');
        Artisan::call('migrate', ['--path' => 'vendor/laravel/passport/database/migrations']);
        Artisan::call('passport:install');
        Artisan::call('l5-swagger:generate');



        // ##########################################
        // user
        // #########################################
      $admin =  User::create([
        "title" => "Mr.",
        'first_Name' => "super",
        'last_Name'=> "admin",
        'phone'=> "01771034383",
        'address_line_1',
        'address_line_2',
        'country'=> "Bangladesh",
        'city'=> "Dhaka",
        'postcode'=> "1207",
        'email'=> "asjadtariq@gmail.com",
        'password'=>Hash::make("12345678@We"),
        "email_verified_at"=>now(),
        'is_active' => 1
        ]);
        $admin->email_verified_at = now();
        $admin->save();

        $this->setupRoles();

        $reseller =  User::create([
            "title" => "Mr.",
            'first_Name' => "Shahbaz",
            'last_Name'=> "Khan",
            'phone'=> "01771034383",
            'address_line_1',
            'address_line_2',
            'country'=> "Bangladesh",
            'city'=> "Dhaka",
            'postcode'=> "1207",
            'email'=> "shahbaz.scm@gmail.com",
            'password'=>Hash::make("12345678@We"),
            "email_verified_at"=>now(),
            'is_active' => 1
            ]);
            $reseller->email_verified_at = now();
            $reseller->save();

            $specialReseller =  User::create([
                "title" => "Mr.",
                'first_Name' => "Shahbaz",
                'last_Name'=> "Khan",
                'phone'=> "01771034383",
                'address_line_1',
                'address_line_2',
                'country'=> "Bangladesh",
                'city'=> "Dhaka",
                'postcode'=> "1207",
                'email'=> "kids20acc@gmail.com",
                'password' => Hash::make("12345678@We"),
                "email_verified_at"=>now(),
                'is_active' => 1
                ]);
                $specialReseller->email_verified_at = now();
                $specialReseller->save();

                $permissions = Permission::whereIn('name', ["handle_self_registered_businesses","system_setting_update"])->get();


                $specialReseller->givePermissionTo($permissions);





        $specialReseller->assignRole("reseller");
        $reseller->assignRole("reseller");
        $admin->assignRole("superadmin");



        $this->storeEmailTemplates();



        $this->setupAssetTypes();

       $this->setUpSocialMedia();

        $banks = [
            ['id' => 1, 'name' => 'Barclays'],
            ['id' => 2, 'name' => 'HSBC'],
            ['id' => 3, 'name' => 'Lloyds Banking Group'],
            ['id' => 4, 'name' => 'Nationwide'],
            ['id' => 5, 'name' => 'NatWest Group'],
            ['id' => 6, 'name' => 'Santander UK'],
            ['id' => 7, 'name' => 'Standard Chartered'],
        ];

        foreach ($banks as $data) {
            Bank::create([
                'name' => $data['name'],
                'is_active' => 1,
                'is_default' => 1,
                'business_id' => NULL,
                'created_by' => $admin->id
            ]);
        }











        $default_designations = [


            [
                'name' => "CEO",
                'description' => "Chief Executive Officer",
            ],
            [
                'name' => "HR Manager",
                'description' => "Human Resources Manager",
            ],
            [
                'name' => "Finance Manager",
                'description' => "Finance Manager",
            ],
            [
                'name' => "Sales Representative",
                'description' => "Sales Representative",
            ],
            [
                'name' => "IT Specialist",
                'description' => "Information Technology Specialist",
            ],
            [
                'name' => "Marketing Coordinator",
                'description' => "Marketing Coordinator",
            ],
            [
                'name' => "Customer Service Representative",
                'description' => "Customer Service Representative",
            ],
        ];

        // Iterate through the array and create records
        foreach ($default_designations as $data) {
            Designation::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }



        $default_termination_types = [
            [
                'name' => "Voluntary Resignation",
                'description' => "Employee voluntarily leaves the job.",
            ],
            [
                'name' => "Involuntary Termination",
                'description' => "Employee is terminated by the employer.",
            ],
            [
                'name' => "Retirement",
                'description' => "Employee retires from their position.",
            ],
            [
                'name' => "End of Contract",
                'description' => "Employee's contract comes to an end.",
            ],
            [
                'name' => "Layoff",
                'description' => "Employee is laid off due to company downsizing.",
            ],
            [
                'name' => "Other",
                'description' => "Other reasons for termination.",
            ],
        ];

        foreach ($default_termination_types as $data) {
            TerminationType::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'is_active' => 1,
                'is_default' => 1,
                'business_id' => NULL,
                'created_by' => $admin->id,
            ]);
        }



        $default_leave_reasons = [
            [
                'name' => "New Job Opportunity",
                'description' => "Left for a new job opportunity.",
            ],
            [
                'name' => "Career Change",
                'description' => "Left to pursue a different career.",
            ],
            [
                'name' => "Personal Reasons",
                'description' => "Left due to personal reasons.",
            ],
            [
                'name' => "Health Reasons",
                'description' => "Left due to health issues.",
            ],
            [
                'name' => "Relocation",
                'description' => "Left due to relocation.",
            ],
            [
                'name' => "Retirement",
                'description' => "Left due to retirement.",
            ],
            [
                'name' => "Dissatisfaction with Job",
                'description' => "Left due to dissatisfaction with the job.",
            ],
            [
                'name' => "Dissatisfaction with Management",
                'description' => "Left due to dissatisfaction with management.",
            ],
            [
                'name' => "Company Downsizing",
                'description' => "Left due to company downsizing.",
            ],
            [
                'name' => "Terminated for Cause",
                'description' => "Terminated for cause.",
            ],
            [
                'name' => "Other",
                'description' => "Other reasons for leaving.",
            ],
        ];

        foreach ($default_leave_reasons as $data) {
            TerminationReason::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'is_active' => 1,
                'is_default' => 1,
                'business_id' => NULL,
                'created_by' => $admin->id,
            ]);
        }



        $default_job_type = [
            [
                'name' => "Full Time Employee",
                'description' => "An employee who works a standard number of hours per week as defined by the organization."
            ],
            [
                'name' => "Part Time Employee",
                'description' => "An employee who works fewer hours than a full-time employee, often with a set schedule."
            ],
            [
                'name' => "Contractor",
                'description' => "An individual hired on a contract basis for a specific project or period, not considered a permanent employee."
            ],
            [
                'name' => "Temporary Employee",
                'description' => "An employee hired for a short-term period to cover a specific workload or project."
            ],
            [
                'name' => "Freelancer",
                'description' => "A self-employed individual who provides services to the organization on a project-by-project basis."
            ],
            [
                'name' => "Intern",
                'description' => "A student or recent graduate gaining practical experience in a specific field, often for a limited duration."
            ],
            [
                'name' => "Remote Worker",
                'description' => "An employee who works primarily from a location outside the office, such as from home or another remote location."
            ],
            // Add more job types as needed
        ];

        // Iterate through the array and create records
        foreach ($default_job_type as $data) {
            JobType::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }






       $this->storeWorkLocation();

        $default_job_platform = [
            [
                'name' => "LinkedIn",
                'description' => "A professional networking platform widely used for job postings, networking, and recruitment."
            ],
            [
                'name' => "Indeed",
                'description' => "A popular job search engine that aggregates job listings from various sources, including company websites and job boards."
            ],
            [
                'name' => "Monster",
                'description' => "An online job portal that connects employers with job seekers, offering a wide range of job postings."
            ],
            [
                'name' => "Reed",
                'description' => "One of the largest job sites in the UK, providing a platform for employers and job seekers across various industries."
            ],
            [
                'name' => "Glassdoor",
                'description' => "A platform that not only provides job listings but also offers company reviews, salary information, and interview insights."
            ],
            [
                'name' => "Totaljobs",
                'description' => "A UK-based job board that features a variety of job listings and career resources for both employers and job seekers."
            ],
            [
                'name' => "Jobsite",
                'description' => "An online recruitment platform that connects employers with job seekers, offering a range of job opportunities."
            ],
            [
                'name' => "CareerBuilder",
                'description' => "A global job board and recruitment platform that connects employers with qualified candidates."
            ],
            [
                'name' => "CWJobs",
                'description' => "Specialized in IT and tech jobs, CWJobs is a platform catering to employers and job seekers in the technology sector."
            ],
            // Add more job platforms as needed
        ];

        // Iterate through the array and create records
        foreach ($default_job_platform as $data) {
            JobPlatform::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }


        $default_task_categories = [
            [
                'name' => "To Do",
                'description' => "Tasks that are yet to be started."
            ],
            [
                'name' => "In Progress",
                'description' => "Tasks that are currently being worked on."
            ],
            [
                'name' => "Resolved",
                'description' => "Tasks that have been completed."
            ],
            [
                'name' => "Closed",
                'description' => "Tasks that have been completed and formally closed."
            ],
        ];


         // Iterate through the array and create records
         foreach ($default_task_categories as $index => $data) {
            TaskCategory::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "order_no" => $index,
                "created_by" => $admin->id
            ]);
        }




        $default_recruitment_process = [
            [
                'name' => "Job Requisition",
                'description' => "Identify the need for a new position or replacement and create a job requisition specifying the role's requirements."
            ],
            [
                'name' => "Job Posting",
                'description' => "Advertise the job opening through various channels, including the company's website, job boards, and social media."
            ],
            [
                'name' => "Application Screening",
                'description' => "Review resumes and applications to shortlist candidates who meet the basic qualifications for the position."
            ],
            [
                'name' => "Initial Interview",
                'description' => "Conduct a preliminary interview to assess candidates' skills, experience, and cultural fit with the organization."
            ],
            [
                'name' => "Skills Assessment",
                'description' => "Administer tests or exercises to evaluate candidates' technical or job-specific skills relevant to the role."
            ],
            [
                'name' => "Second Interview",
                'description' => "Invite shortlisted candidates for a more in-depth interview, often involving key team members or department heads."
            ],
            [
                'name' => "Reference Check",
                'description' => "Contact previous employers or references to verify the candidate's work history, performance, and reliability."
            ],
            [
                'name' => "Job Offer",
                'description' => "Extend a formal job offer to the selected candidate, including details about compensation, benefits, and start date."
            ],
            [
                'name' => "Negotiation",
                'description' => "Engage in negotiations with the candidate regarding salary, benefits, and other terms of employment."
            ],
            [
                'name' => "Onboarding",
                'description' => "Facilitate the onboarding process, including orientation, paperwork, and introductions to team members and company policies."
            ],
            [
                'name' => "Job Contract",
                'description' => "Ensure the job contract is provided and understood by the new employee."
            ],



        ];


        foreach ($default_recruitment_process as $data) {
            RecruitmentProcess::create([
                'name' => $data['name'],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }







        $default_employment_statuses = [
            [
                'name' => "Full-Time",
                'color' => "#22c55e",
                'description' => "Employee works the standard number of hours for a full-time position.",
            ],
            [
                'name' => "Part-Time",
                'color' => "#3b82f6",
                'description' => "Employee works fewer hours than a full-time position.",
            ],
            [
                'name' => "Contractor",
                'color' => "#f97316",
                'description' => "Employee is hired on a contractual basis for a specific project or duration.",
            ],
            [
                'name' => "Temporary",
                'color' => "#06b6d4",
                'description' => "Employee is hired for a temporary period, often to cover a specific absence or workload.",
            ],
            [
                'name' => "Intern",
                'color' => "#a855f7",
                'description' => "Employee is engaged in a temporary position for gaining practical work experience.",
            ],
        ];

        // Iterate through the array and create records
        foreach ($default_employment_statuses as $data) {
            EmploymentStatus::create([
                'name' => $data['name'],
                'color' => $data["color"],
                'description' => $data['description'],
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }


        $default_setting_leave_types = [
            [
                'name' => "Vacation Leave",
                'type' => "paid",
                'amount' => 80,
            ],
            [
                'name' => "Sick Leave",
                'type' => "paid",
                'amount' => 40,
            ],
            [
                'name' => "Personal Leave",
                'type' => "unpaid",
                'amount' => 30,
            ],
            [
                'name' => "Maternity Leave",
                'type' => "paid",
                'amount' => 120,
            ],
            [
                'name' => "Paternity Leave",
                'type' => "paid",
                'amount' => 80,
            ],
            [
                'name' => "Bereavement Leave",
                'type' => "paid",
                'amount' => 24,
            ],
        ];

        // Iterate through the array and create records
        foreach ($default_setting_leave_types as $data) {
            SettingLeaveType::create([
                'name' => $data['name'],
                'type' => $data["type"],
                'amount' => $data['amount'],

                'carry_over_limit' => 0,
                'leave_rollover_type' => "none",


                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => $admin->id
            ]);
        }


        SettingLeave::create([
            'start_month' => 1,
            'approval_level' => "multiple",
            'allow_bypass' => 1,
          "business_id" => NULL,
          "is_active" => 1,
          "is_default" => 1,
          "created_by" => $admin->id,
        ]);

        SettingAttendance::create([
            'punch_in_time_tolerance' => 0.25,
            'work_availability_definition' => 80,
            'punch_in_out_alert' => 0,
            'punch_in_out_interval' => 0.5,
            'alert_area' => (["web","system"]),
            'auto_approval' => false,
            "is_geolocation_enabled" => 0,

            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1,
            "created_by" => $admin->id,
        ]);


        SettingPayrun::create([
            'payrun_period' => "weekly",
            'consider_type' => "daily_log",
            'consider_overtime' => 1,
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1,
            "created_by" => $admin->id,
        ]);

        SettingPaymentDate::create([
            'payment_type' => 'weekly',
            'day_of_week' => 2,
            'day_of_month' => null,
            'custom_frequency_interval' => null,
            'custom_frequency_unit' => null,
            'is_active' => 1,
            'is_default' => 1,
            'business_id' => null,
            'created_by' => $admin->id,
            'role_specific_settings' => null,
        ]);


        $default_work_shift_data_1 = [
            'name' => 'Main Work Shift',
            'type' => 'regular',
            'description' => '',
            'is_personal' => false,
            'break_type' => 'unpaid',
            'break_hours' => 1,

            "is_active" => 1,
            "is_default"=> 1,
            'details' => [
                [
                    'day' => '0',
                    "shifts" => [
                        'start_at' => '',
                        'end_at' => '',
                    ],

                    'is_weekend' => 1,
                ],
                [
                    'day' => '1',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '2',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '3',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '4',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '5',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '6',
                    "shifts" => [
                        'start_at' => '',
                        'end_at' => '',
                    ],

                    'is_weekend' => 1,
                ],
            ],
        ];


        $default_work_shift_1 = WorkShift::create($default_work_shift_data_1);




        $default_work_shift_1->details()->createMany($default_work_shift_data_1['details']);

        $employee_work_shift_history_data = $default_work_shift_1->toArray();
        $employee_work_shift_history_data["work_shift_id"] = $default_work_shift_1->id;
        $employee_work_shift_history_data["from_date"] = now();
        $employee_work_shift_history_data["to_date"] = NULL;
         $employee_work_shift_history =  WorkShiftHistory::create($employee_work_shift_history_data);
         $employee_work_shift_history->details()->createMany($default_work_shift_data_1['details']);

        $default_work_shift_data_2 = [
            'name' => 'main work shift',
            'type' => 'regular',
            'description' => '',
            'is_personal' => false,
            'break_type' => 'unpaid',
            'break_hours' => 1,

            "is_active" => 1,
            "is_default"=> 1,
            'details' => [
                [
                    'day' => '0',
                    "shifts" => [
                        'start_at' => '',
                        'end_at' => '',
                    ],
                    'is_weekend' => 1,
                ],
                [
                    'day' => '1',
                    "shifts" => [
                        'start_at' => '',
                        'end_at' => '',
                    ],
                    'is_weekend' => 1,
                ],
                [
                    'day' => '2',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '3',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '4',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '5',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
                [
                    'day' => '6',
                    "shifts" => [
                        'start_at' => '10:00:00',
                        'end_at' => '18:00:00',
                    ],
                    'is_weekend' => 0,
                ],
            ],
        ];


        $this->setupServicePlan();


                // $default_work_shift_2 = WorkShift::create($default_work_shift_data_2);
                // $default_work_shift_2->details()->createMany($default_work_shift_data_2['details']);



        return "You are done with setup";
    }


    public function roleRefresh(Request $request)
    {



        $this->roleRefreshFunc();

        return "You are done with setup";
    }


    public function backup(Request $request) {

        foreach(DB::connection('backup_database')->table('users')->get() as $backup_data){

        $data_exists = DB::connection('mysql')->table('users')->where([
            "id" => $backup_data->id
           ])->first();
           if(!$data_exists) {
            DB::connection('mysql')->table('users')->insert(get_object_vars($backup_data));
           }
        }


        // foreach(DB::connection('backup_database')->table('automobile_categories')->get() as $backup_data){
        //     $data_exists = DB::connection('mysql')->table('automobile_categories')->where([
        //         "id" => $backup_data->id
        //        ])->first();
        //        if(!$data_exists) {
        //         DB::connection('mysql')->table('automobile_categories')->insert(get_object_vars($backup_data));
        //        }
        //     }

        //     foreach(DB::connection('backup_database')->table('automobile_makes')->get() as $backup_data){
        //         $data_exists = DB::connection('mysql')->table('automobile_makes')->where([
        //             "id" => $backup_data->id
        //            ])->first();
        //            if(!$data_exists) {
        //             DB::connection('mysql')->table('automobile_makes')->insert(get_object_vars($backup_data));
        //            }
        //         }

        //         foreach(DB::connection('backup_database')->table('automobile_models')->get() as $backup_data){
        //             $data_exists = DB::connection('mysql')->table('automobile_models')->where([
        //                 "id" => $backup_data->id
        //                ])->first();
        //                if(!$data_exists) {
        //                 DB::connection('mysql')->table('automobile_models')->insert(get_object_vars($backup_data));
        //                }
        //             }

        //             foreach(DB::connection('backup_database')->table('services')->get() as $backup_data){
        //                 $data_exists = DB::connection('mysql')->table('services')->where([
        //                     "id" => $backup_data->id
        //                    ])->first();
        //                    if(!$data_exists) {
        //                     DB::connection('mysql')->table('services')->insert(get_object_vars($backup_data));
        //                    }
        //                 }


        //                 foreach(DB::connection('backup_database')->table('sub_services')->get() as $backup_data){
        //                     $data_exists = DB::connection('mysql')->table('sub_services')->where([
        //                         "id" => $backup_data->id
        //                        ])->first();
        //                        if(!$data_exists) {
        //                         DB::connection('mysql')->table('sub_services')->insert(get_object_vars($backup_data));
        //                        }
        //                     }



                            foreach(DB::connection('backup_database')->table('businesses')->get() as $backup_data){
                                $data_exists = DB::connection('mysql')->table('businesses')->where([
                                    "id" => $backup_data->id
                                   ])->first();
                                   if(!$data_exists) {
                                    DB::connection('mysql')->table('businesses')->insert(get_object_vars($backup_data));
                                   }
                                }

                                foreach(DB::connection('backup_database')->table('business_automobile_makes')->get() as $backup_data){
                                    $data_exists = DB::connection('mysql')->table('business_automobile_makes')->where([
                                        "id" => $backup_data->id
                                       ])->first();
                                       if(!$data_exists) {
                                        DB::connection('mysql')->table('business_automobile_makes')->insert(get_object_vars($backup_data));
                                       }
                                    }

                                    foreach(DB::connection('backup_database')->table('business_automobile_models')->get() as $backup_data){
                                        $data_exists = DB::connection('mysql')->table('business_automobile_models')->where([
                                            "id" => $backup_data->id
                                           ])->first();
                                           if(!$data_exists) {
                                            DB::connection('mysql')->table('business_automobile_models')->insert(get_object_vars($backup_data));
                                           }
                                        }

                                        foreach(DB::connection('backup_database')->table('business_services')->get() as $backup_data){
                                            $data_exists = DB::connection('mysql')->table('business_services')->where([
                                                "id" => $backup_data->id
                                               ])->first();
                                               if(!$data_exists) {
                                                DB::connection('mysql')->table('business_services')->insert(get_object_vars($backup_data));
                                               }
                                            }

                                            foreach(DB::connection('backup_database')->table('business_sub_services')->get() as $backup_data){
                                                $data_exists = DB::connection('mysql')->table('business_sub_services')->where([
                                                    "id" => $backup_data->id
                                                   ])->first();
                                                   if(!$data_exists) {
                                                    DB::connection('mysql')->table('business_sub_services')->insert(get_object_vars($backup_data));
                                                   }
                                                }
                                                foreach(DB::connection('backup_database')->table('fuel_stations')->get() as $backup_data){
                                                    $data_exists = DB::connection('mysql')->table('fuel_stations')->where([
                                                        "id" => $backup_data->id
                                                       ])->first();
                                                       if(!$data_exists) {
                                                        DB::connection('mysql')->table('fuel_stations')->insert(get_object_vars($backup_data));
                                                       }
                                                    }

                                                return response()->json("done",200);
    }



}
