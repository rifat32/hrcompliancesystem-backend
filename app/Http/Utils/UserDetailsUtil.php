<?php

namespace App\Http\Utils;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Business;
use App\Models\EmployeeAddressHistory;

use App\Models\EmployeePassportDetailHistory;

use App\Models\EmployeePensionHistory;
use App\Models\EmployeeProjectHistory;

use App\Models\EmployeeRightToWorkHistory;

use App\Models\EmployeeSponsorshipHistory;
use App\Models\EmployeeUserWorkShiftHistory;
use App\Models\EmployeeVisaDetailHistory;

use App\Models\LeaveRecord;
use App\Models\Project;
use App\Models\SalaryHistory;
use App\Models\Termination;
use App\Models\User;
use App\Models\UserAsset;
use App\Models\UserAssetHistory;

use App\Models\UserRecruitmentProcess;
use App\Models\UserWorkShift;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

trait UserDetailsUtil
{
    use BasicUtil;

    public function manageTokens() {
        // Get the user
        $user = auth()->user();

        // Remove expired tokens for all users directly from the database (no need to loop through all users)
        DB::table('oauth_access_tokens')
            ->where('expires_at', '<', now())
            ->delete();


        // Regenerate the current token (delete the current token first)
        $current_token = $user->tokens->where('id', $user->token()->id)->first();
        if ($current_token && env("DEV_ENV") != "local") {

            $current_token->expires_at = now()->addMinute(); // Set expiration time to 1 minute from now
            $current_token->save(); // Save the updated expiration time
        }
        
        // Create a new current token (the one used in this request)
        $new_current_token = $user->createToken('authToken')->accessToken;



        // Add the newly created current token to the user's tokens collection
        $user->token = $new_current_token;

        return $user;
    }




    public function checkEmployeeCreationLimit($throwErr = false, $employeeInserting = 1)
    {
        $user = auth()->user();
        // Early return if user or business is missing
        if (empty($user) || empty($user->business_id)) {
            return true;
        }

        // Eager load service plan for efficiency
        $business = Business::with("service_plan")->where([
            "id" => $user->business_id
        ])
            ->first();

        $service_plan = $business->service_plan;

        // Early return if business or service plan is missing
        if (empty($service_plan)) {
            return true;
        }

        $total_employees = User::where(["business_id" => $business->id])
        ->where("users.is_active", 1)
        ->whereNotIn("users.id",[auth()->user()->business->owner_id])
        ->whereDoesntHave("lastTermination", function ($query) {
            $query->where('terminations.date_of_termination', "<", today())
                ->whereRaw('terminations.date_of_termination > users.joining_date');
        })
        ->count();

        if (($total_employees + $employeeInserting) <= $service_plan->number_of_employees_allowed) {
            return true;
        }

        if ($throwErr) {
            throw new Exception("You have reached your employees limit including in the current package " . $total_employees . " please contact customer services to upgrade", 409);
        }


        return false;
    }


    public function store_work_shift_history($work_shift_id, $user)
    {

            $work_shift =  WorkShift::where([
                "id" => $work_shift_id,
            ])
                ->first();
            if (!$work_shift) {
                throw new Exception("Work shift validation failed");
            }
            if (!$work_shift->is_active) {

                throw new Exception(("Please activate the work shift named '" . $work_shift->name . "'"), 400);

                // return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
            }
            $work_shift->users()->attach($user->id);



            $work_shift_history_data = $work_shift->toArray();
            $work_shift_history_data["work_shift_id"] = $work_shift_history_data["id"];
            // $employee_work_shift_history_data["from_date"] = $request_data["start_date"]?$request_data["start_date"]:now();
            $work_shift_history_data["from_date"] = auth()->user()->business->start_date;
            $work_shift_history_data["to_date"] = NULL;
            $work_shift_history_data["user_id"] =  $user->id;




            $work_shift_history =  WorkShiftHistory::create($work_shift_history_data);

            foreach ($work_shift->details as $details) {
                $details_data = $details->toArray();
                $details_data["work_shift_id"] = $work_shift_history_data["work_shift_id"];
                $work_shift_history->details()->create($details_data);
            }

    }


    public function delete_old_histories()
    {
        $ten_years_ago = Carbon::now()->subYears(10);
        EmployeePensionHistory::where('pension_re_enrollment_due_date', '<=', $ten_years_ago)->delete();
        $ten_years_ago = Carbon::now()->subYears(10);
        EmployeeRightToWorkHistory::where('right_to_work_expiry_date', '<=', $ten_years_ago)->delete();
        $ten_years_ago = Carbon::now()->subYears(10);
        EmployeeVisaDetailHistory::where('visa_expiry_date', '<=', $ten_years_ago)->delete();
        $ten_years_ago = Carbon::now()->subYears(10);
        EmployeePassportDetailHistory::where('passport_expiry_date', '<=', $ten_years_ago)->delete();
        $ten_years_ago = Carbon::now()->subYears(10);
        EmployeeSponsorshipHistory::where('expiry_date', '<=', $ten_years_ago)->delete();


        $three_years_ago = Carbon::now()->subYears(3);
        EmployeeAddressHistory::where('to_date', '<=', $three_years_ago)->delete();
    }

    public function store_right_to_works($request_data, $user)
    {
        if (!empty($request_data["right_to_works"]) && $request_data["is_active_right_to_works"]) {

            $request_data["right_to_works"]["created_by"] = auth()->user()->id;

            $request_data["right_to_works"]["user_id"] = $user->id;
            $request_data["right_to_works"]["business_id"] = $user->business_id;

            $request_data["right_to_works"]["from_date"] = now();
            $request_data["right_to_works"]["is_current"] = 1;
            EmployeeRightToWorkHistory::create($request_data["right_to_works"]);
        }
    }


    public function store_visa_details($request_data, $user)
    {
        if (!empty($request_data["visa_details"]) && $request_data["is_active_visa_details"]) {


            $request_data["visa_details"]["created_by"] = auth()->user()->id;
            $request_data["visa_details"]["user_id"] = $user->id;
            $request_data["visa_details"]["business_id"] = $user->business_id;
            $request_data["visa_details"]["from_date"] = now();
            $request_data["visa_details"]["is_current"] = 1;
            $employee_visa_details_history  =  EmployeeVisaDetailHistory::create($request_data["visa_details"]);
        }
    }




    public function store_passport_details($request_data, $user)
    {
        if (!empty($request_data["passport_details"])) {
            $request_data["passport_details"]["created_by"] = auth()->user()->id;
            $request_data["passport_details"]["user_id"] = $user->id;
            $request_data["passport_details"]["business_id"] = $user->business_id;

            $request_data["passport_details"]["from_date"] = now();
            $request_data["passport_details"]["is_current"] = 1;
            $employee_passport_details_history  =  EmployeePassportDetailHistory::create($request_data["passport_details"]);
        }
    }


    public function store_sponsorship_details($request_data, $user)
    {
        if (!empty($request_data["sponsorship_details"])) {
            $request_data["sponsorship_details"]["created_by"] = auth()->user()->id;
            $request_data["sponsorship_details"]["user_id"] = $user->id;
            $request_data["sponsorship_details"]["business_id"] = $user->business_id;


            $request_data["sponsorship_details"]["from_date"] = now();
            $request_data["sponsorship_details"]["is_current"] = 1;
            $employee_sponsorship_history  =  EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);
        }
    }


    public function store_recruitment_processes($request_data, $user)
    {
        if (!empty($request_data["recruitment_processes"])) {
            foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {

                 $recruitment_processes =  $user->recruitment_processes()->create($recruitment_process_data);

                 foreach($recruitment_process_data["tasks"] as $task_data) {
                    $recruitment_processes->tasks()->create($task_data);
                 }


            }
        }
    }

    public function store_pension($user)
    {


        EmployeePensionHistory::create([
            'user_id' => $user->id,
            'pension_eligible' => false,
            'pension_enrollment_issue_date' => NULL,
            'pension_letters' => [],
            'pension_scheme_status' => NULL,
            'pension_scheme_opt_out_date' => NULL,
            'pension_re_enrollment_due_date' => NULL,
            "is_manual" => 0,
            "from_date" => now(),
            "to_date" => NULL,
            "business_id" => auth()->user()->business_id,
            'created_by' => auth()->user()->id

        ]);
    }

    public function store_project($user)
    {
        $project = Project::where([
            "business_id" => auth()->user()->business_id,
            "is_default" => 1
        ])
            ->first();
        if (!$project) {
            throw new Exception("no project defined for this business");
        }
        $employee_project_history_data = $project->toArray();
        $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
        $employee_project_history_data["user_id"] = $user->id;
        $employee_project_history_data["from_date"] = now();
        $employee_project_history_data["to_date"] = NULL;
        EmployeeProjectHistory::create($employee_project_history_data);
        $user->projects()->attach([$project->id]);
    }





    public function update_address_history($request_data, $user)
    {


        $address_history_data = [
            'user_id' => $user->id,
            'from_date' => now(),
            'created_by' => auth()->user()->id,
            'address_line_1' => $request_data["address_line_1"],
            'address_line_2' => $request_data["address_line_2"],
            'country' => $request_data["country"],
            'city' => $request_data["city"],
            'postcode' => $request_data["postcode"],
            'lat' => $request_data["lat"],
            'long' => $request_data["long"]
        ];

        $employee_address_history  =  EmployeeAddressHistory::where([
            "user_id" =>   $user->id,
            "to_date" => NULL
        ])
        ->orderByDesc("employee_address_histories.id")
            ->first();

        if ($employee_address_history) {

            $fields_to_check = [
                "address_line_1",
                "address_line_2",
                "country",
                "city",
                "postcode"

            ];
            $date_fields = [];


            $fields_changed = $this->fieldsHaveChanged($fields_to_check, $employee_address_history, $request_data, $date_fields);

            if (
                $fields_changed
            ) {
                $employee_address_history->to_date = now();
                $employee_address_history->save();
                EmployeeAddressHistory::create($address_history_data);
            }
        } else {
            EmployeeAddressHistory::create($address_history_data);
        }
    }









    public function update_recruitment_processes($request_data, $user)
    {
        if (!empty($request_data["recruitment_processes"])) {
            $user->recruitment_processes()->delete();
            foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {
                if (!empty($recruitment_process_data["description"])) {
                 $recruitment_processes =  $user->recruitment_processes()->create($recruitment_process_data);
                    foreach($recruitment_process_data["tasks"] as $task_data) {
                        $recruitment_processes->tasks()->create($task_data);
                     }


                }
            }
        }
    }





    public function update_recruitment_processes_v2($request_data, $user)
    {
        if (!empty($request_data["recruitment_processes"])) {

            foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {

                    $userRecruitmentProcess =   UserRecruitmentProcess::where([
                        "id" => $recruitment_process_data["id"],

                        "user_id"  => $user->id
                    ])
                        ->first();

                    $userRecruitmentProcess->fill($recruitment_process_data);

                    $userRecruitmentProcess->save();
                    $userRecruitmentProcess->tasks()->delete();

                    foreach($recruitment_process_data["tasks"] as $task_data) {
                        $userRecruitmentProcess->tasks()->create($task_data);
                     }

            }
        }
    }



    public function update_work_shift($request_data, $user)
    {
        if (!empty($request_data["work_shift_id"])) {
            $work_shift =  WorkShift::where([
                "id" => $request_data["work_shift_id"],
            ])
                ->where(function ($query) {
                    $query->where([
                        "business_id" => auth()->user()->business_id
                    ])
                        // ->orWhere(function ($query) {
                        //     $query->where([
                        //         "is_active" => 1,
                        //         "business_id" => NULL,
                        //         "is_default" => 1
                        //     ]);
                        // })
                    ;
                })
                ->orderByDesc("id")
                ->first();
            if (!$work_shift) {
                throw new Exception("no work shift found", 403);
            }

            if (!$work_shift->is_active) {
                throw new Exception("Please activate the work shift named '" . $work_shift->name . "'", 400);
            }


            $current_workshift = $user->work_shifts->last();

            $current_workshift_id = NULL;
            if ($current_workshift) {
                $current_workshift_id = $current_workshift->id;
            }

            if ($work_shift->id != $current_workshift_id) {
                UserWorkShift::where([
                    "user_id" => $user->id
                ])
                    ->delete();

                $work_shift->users()->attach($user->id);



                WorkShiftHistory::where([
                    "to_date" => NULL,
                    "user_id" => $user->id,
                    "work_shift_id" => $current_workshift_id
                ])

                    // ->where("work_shift_id",$current_workshift->id)
                    ->update([
                        "to_date" => now()
                    ]);

                $work_shift_history =  WorkShiftHistory::where([
                    "to_date" => NULL,
                    "work_shift_id" => $work_shift->id
                ])
                    ->first();



                $work_shift_history->users()->attach($user->id, ['from_date' => now(), 'to_date' => NULL]);
            }
        }
    }




    public function update_sponsorship($request_data, $user)
    {

        if (!empty($request_data["sponsorship_details"])) {

            $request_data["sponsorship_details"]["business_id"] = auth()->user()->business_id;
            $request_data["sponsorship_details"]["user_id"] = $user->id;
            $request_data["sponsorship_details"]["from_date"] = now();

            if(!empty($user->sponsorship_details)) {

            $user_sponsorship_history = $user->sponsorship_details;
            $user_sponsorship_history->fill($request_data["sponsorship_details"]);
            $user_sponsorship_history->save();

            } else {
           $request_data["sponsorship_details"]["created_by"] = auth()->user()->id;
             EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);
            }

            $this->manipulateCurrentData("EmployeeSponsorshipHistory","date_assigned","expiry_date",$user->id);

    }

    }


    public function update_passport_details($request_data, $user)
    {

        if (!empty($request_data["passport_details"])) {

                $request_data["passport_details"]["business_id"] = auth()->user()->business_id;
                $request_data["passport_details"]["user_id"] = $user->id;
                $request_data["passport_details"]["from_date"] = now();



                if(!empty($user->passport_details)) {

                $user_passport_history = $user->passport_details;
                // Fill the object with the new data
                $user_passport_history->fill($request_data["passport_details"]);
                $user_passport_history->save();

                } else {
                    $request_data["passport_details"]["created_by"] = auth()->user()->id;
                    $employee_passport_details_history  =  EmployeePassportDetailHistory::create($request_data["passport_details"]);
                }

              $this->manipulateCurrentData("EmployeePassportDetailHistory","passport_issue_date","passport_expiry_date",$user->id);

        }

    }




    public function update_visa_details($request_data, $user)
    {

        if(!empty($request_data["visa_details"]) && $request_data["is_active_visa_details"]) {

        $request_data["visa_details"]["business_id"] = auth()->user()->business_id;
        $request_data["visa_details"]["user_id"] = $user->id;
        $request_data["visa_details"]["from_date"] = now();

        if(!empty($user->visa_details)) {

        $user_visa_history = $user->visa_details;
        $user_visa_history->fill($request_data["visa_details"]);
        $user_visa_history->save();

        } else {
        $request_data["visa_details"]["created_by"] = auth()->user()->id;
           EmployeeVisaDetailHistory::create($request_data["visa_details"]);
        }

        $this->manipulateCurrentData("EmployeeVisaDetailHistory","visa_issue_date","visa_expiry_date",$user->id);

    }


    }

    public function update_right_to_works($request_data, $user)
    {

        if(!empty($request_data["right_to_works"]) && $request_data["is_active_right_to_works"]) {

        $request_data["right_to_works"]["business_id"] = auth()->user()->business_id;
        $request_data["right_to_works"]["user_id"] = $user->id;
        $request_data["right_to_works"]["from_date"] = now();

        if(!empty($user->right_to_works)) {

        $user_right_to_works = $user->right_to_works;
        $user_right_to_works->fill($request_data["right_to_works"]);
        $user_right_to_works->save();

        } else {
            $request_data["right_to_works"]["created_by"] = auth()->user()->id;
            EmployeeRightToWorkHistory::create($request_data["right_to_works"]);
        }

        $this->manipulateCurrentData("EmployeeRightToWorkHistory","right_to_work_check_date","right_to_work_expiry_date",$user->id);

    }

    }

    public function validateJoiningDate($joining_date, $user)
    {
$user_id = $user->id;
        $termination =  $user->lastTermination;;

        if (!empty($termination) && Carbon::parse($termination->date_of_termination)->gte(Carbon::parse($joining_date))) {
            throw new Exception("The employee has been terminated on " . Carbon::parse($termination->date_of_termination)->format('d/m/Y'), 401);
        }

        if (!empty($joining_date)) {
            $attendance_exists = Attendance::when(!empty($termination), function ($query) use ($termination) {
                    $query->where(
                        "in_date",
                        ">",
                        $termination->date_of_termination,
                    );
                })
                ->where(
                    "in_date",
                    "<",
                    $joining_date,
                )
                ->where([
                    "user_id" => $user_id
                ])->exists();

            if ($attendance_exists) {
                throw new Exception(("Attendance exists before " . Carbon::parse($joining_date)->format('d/m/Y')), 401);
            }


            $leave_exists = LeaveRecord::when(!empty($termination), function ($query) use ($termination) {
                    $query->where(
                        "date",
                        ">",
                        $termination->date_of_termination,
                    );
                })
                ->where(
                    "date",
                    "<",
                    $joining_date,
                )
                ->whereHas("leave", function ($query) use ($user_id) {
                    $query->where("leaves.user_id", $user_id);
                })
                ->exists();

            if ($leave_exists) {
                throw new Exception(($leave_exists . "Leave exists before " . $joining_date), 401);
            }

            $asset_assigned = UserAssetHistory::when(!empty($termination), function ($query) use ($termination) {
                    $query->where(
                        "from_date",
                        ">",
                        $termination->date_of_termination,
                    );
                })
                ->where(
                    "from_date",
                    "<",
                    $joining_date,
                )
                ->where([
                    "user_id" => $user_id
                ])->exists();

            if ($asset_assigned) {
                throw new Exception(("Asset assigned before " . $joining_date), 401);
            }
        }
    }
    public function validateJoiningDateForRejoin($joining_date, $user)
    {
        if (!empty($joining_date)) {
            $termination = $user->lastTermination;

            if (!empty($termination) && Carbon::parse($termination->date_of_termination)->gte(Carbon::parse($joining_date))) {

                throw new Exception("The employee has been terminated on " . Carbon::parse($termination->date_of_termination)->format('d/m/Y'), 401);

            }
        }
    }


    public function checkInformationsBasedOnExitDate($user_id, $date_of_termination)
    {

        $attendance_exists =  Attendance::where([
            "user_id" => $user_id
        ])
            ->where("in_date", ">", $date_of_termination)
            ->exists();


        if ($attendance_exists) {
            throw new Exception("Attendance exists after date " . Carbon::parse($date_of_termination)->format('d/m/Y'), 401);
        }

        LeaveRecord::whereHas("leave", function ($query) use ($user_id) {
            $query->where("leaves.user_id", $user_id);
        })
            ->where("leave_records.date", ">", $date_of_termination)
            ->delete();

    }



    public function getEmployeeFormData($businessOwnerToken)
    {
        $url = env('APP_URL') . '/api/v1.0/dropdown-options/employee-form';

        // Perform the API request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $businessOwnerToken,
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        } else {
            // Handle error response
            throw new \Exception('Failed to fetch employee form data');
        }
    }

    public function extractIdsFromJson(array $jsonData, string $nodeName)
    {
        $idList = [];

        if (isset($jsonData[$nodeName])) {
            foreach ($jsonData[$nodeName] as $item) {
                if (isset($item['id'])) {
                    $idList[] = $item['id'];
                }
            }
        }

        return $idList;
    }
}
