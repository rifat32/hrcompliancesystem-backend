<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessFlowSetupCreateRequest;
use App\Http\Requests\EmployeeSetupCreateRequest;
use App\Http\Requests\GeneralSetupCreateRequest;
use App\Http\Requests\RecruitmentProcessSetupCreateRequest;
use App\Models\AssetType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\JobPlatform;
use App\Models\JobType;
use App\Models\Project;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\SettingPaymentDate;
use App\Models\SettingPayrun;
use App\Models\TerminationReason;
use App\Models\TerminationType;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessInitializationController extends Controller
{

    /**
     *
     * @OA\Post(
     *      path="/v1.0/business-initializations/employee-setup",
     *      operationId="storeEmployeeSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store employee setup data",
     *      description="This method is to store employee setup data",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *              @OA\Property(property="designations", type="array", @OA\Items(
 *                  @OA\Property(property="name", type="string", example="Manager"),
 *                  @OA\Property(property="description", type="string", nullable=true, example="Manages the team")
 *              )),
 *
 *              @OA\Property(property="projects", type="array", @OA\Items(
 *                  @OA\Property(property="name", type="string", example="Project A"),
 *                  @OA\Property(property="description", type="string", nullable=true, example="A new project"),
 *                  @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
 *                  @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
 *                  @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed"}, example="in_progress")
 *              )),
 *
 *              @OA\Property(property="employment_statuses", type="array", @OA\Items(
 *                  @OA\Property(property="name", type="string", example="Full-time"),
 *                  @OA\Property(property="description", type="string", nullable=true, example="Permanent employee"),
 *                  @OA\Property(property="color", type="string", example="#ff0000")
 *              ))
     *
     *
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function storeEmployeeSetupData(EmployeeSetupCreateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {



                $request_data = $request->validated();
                $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];

                foreach ($request_data["designations"] as $designation_data) {
                    $designation_data["is_active"] = 1;
                    $designation_data["is_default"] = 0;
                    $designation_data["created_by"] = auth()->user()->id;
                    $designation_data["business_id"] = auth()->user()->business_id;
                    Designation::create($designation_data);
                }

                // foreach ($request_data["work_locations"] as $work_location) {
                //     $work_location["is_active"] = 1;
                //     $work_location["is_default"] = 0;
                //     $work_location["created_by"] = auth()->user()->id;
                //     $work_location["business_id"] = auth()->user()->business_id;
                //     WorkLocation::create($work_location);
                // }


                foreach ($request_data["projects"] as $project_data) {
                    $project_data["is_active"] = 1;
                    $project_data["is_default"] = 0;
                    $project_data["created_by"] = auth()->user()->id;
                    $project_data["business_id"] = auth()->user()->business_id;

                    $project =  Project::create($project_data);
                    $project->departments()->sync($request_data['departments']);
                }





                foreach ($request_data["employment_statuses"] as $employment_status_data) {
                    $employment_status_data["is_active"] = 1;
                    $employment_status_data["is_default"] = 0;
                    $employment_status_data["created_by"] = auth()->user()->id;
                    $employment_status_data["business_id"] = auth()->user()->business_id;
                    EmploymentStatus::create($employment_status_data);
                }




                return response()->json([
                    "message" => "Data Inserted Successfully!"
                ], 200);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-initializations/employee-setup",
     *      operationId="getEmployeeSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get designations  ",
     *      description="This method is to get designations ",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getEmployeeSetupData(Request $request)
    {
        try {



            $data["designations"] = Designation::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])->get();

            // $data["work_locations"] = WorkLocation::where([
            //         "is_active" => 1,
            //         "is_default" => 1,
            //         "business_id" => NULL,
            //         "parent_id" => NULL
            //     ])->get();

            $data["employment_statuses"] = EmploymentStatus::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])->get();


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/business-initializations/recruitment-process-setup",
     *      operationId="storeRecruitmentProcessSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store recruitment process setup data",
     *      description="This method is to store recruitment process setup data",
     *
   *      @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *              @OA\Property(property="job_platforms", type="array",
 *                  @OA\Items(
 *                      @OA\Property(property="name", type="string", example="LinkedIn"),
 *                      @OA\Property(property="description", type="string", nullable=true, example="Professional networking platform")
 *                  )
 *              ),
 *              @OA\Property(property="job_types", type="array",
 *                  @OA\Items(
 *                      @OA\Property(property="name", type="string", example="Full-time"),
 *                      @OA\Property(property="description", type="string", nullable=true, example="Standard 40-hour work week")
 *                  )
 *              ),
 *              @OA\Property(property="recruitment_processes", type="array",
 *                  @OA\Items(
 *                      @OA\Property(property="name", type="string", example="Initial Screening"),
 *                      @OA\Property(property="description", type="string", nullable=true, example="First stage interview"),
 *                      @OA\Property(property="use_in_recruitment", type="boolean", example=true),
 *                      @OA\Property(property="use_in_on_boarding", type="boolean", example=false),
 *                      @OA\Property(property="use_in_termination", type="boolean", example=false),
 *  *                      @OA\Property(property="is_required", type="boolean", example=false)
 *                  )
 *              )
 *         ),
 *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function storeRecruitmentProcessSetupData(RecruitmentProcessSetupCreateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {


                $request_data = $request->validated();
                $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];

                foreach ($request_data["job_platforms"] as $job_platform_data) {
                    $job_platform_data["is_active"] = 1;
                    $job_platform_data["is_default"] = 0;
                    $job_platform_data["created_by"] = auth()->user()->id;
                    $job_platform_data["business_id"] = auth()->user()->business_id;
                    JobPlatform::create($job_platform_data);
                }

                foreach ($request_data["job_types"] as $job_type_data) {
                    $job_type_data["is_active"] = 1;
                    $job_type_data["is_default"] = 0;
                    $job_type_data["created_by"] = auth()->user()->id;
                    $job_type_data["business_id"] = auth()->user()->business_id;
                    JobType::create($job_type_data);
                }


                foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {
                    $recruitment_process_data["is_active"] = 1;
                    $recruitment_process_data["is_default"] = 0;
                    $recruitment_process_data["created_by"] = auth()->user()->id;
                    $recruitment_process_data["business_id"] = auth()->user()->business_id;
                  $recruitment_process = RecruitmentProcess::create($recruitment_process_data);

                    $order_no_count = RecruitmentProcess::count();

                    $recruitment_process->employee_order_no = $order_no_count;
                    $recruitment_process->candidate_order_no = $order_no_count;
                    $recruitment_process->termination_order_no = $order_no_count;
                  $recruitment_process->save();
                }




                return response()->json([
                    "message" => "Data Inserted Successfully!"
                ], 200);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-initializations/recruitment-process-setup",
     *      operationId="getRecruitmentProcessSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get designations  ",
     *      description="This method is to get designations ",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getRecruitmentProcessSetupData(Request $request)
    {
        try {



            $data["job_platforms"] = JobPlatform::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])->get();

            $data["job_types"] = JobType::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])->get();

            $data["recruitment_processes"] = RecruitmentProcess::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])->get();



            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/business-initializations/business-flow-setup",
     *      operationId="storeBusinessFlowSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store recruitment process setup data",
     *      description="This method is to store recruitment process setup data",
     *
     *      @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="leave_types", type="array", @OA\Items(
 *                 @OA\Property(property="name", type="string", example="Annual Leave"),
 *                 @OA\Property(property="type", type="string", enum={"paid", "unpaid"}),
 *                 @OA\Property(property="amount", type="number", example=10),
 *                 @OA\Property(property="is_active", type="boolean", example=true),
 *                 @OA\Property(property="is_earning_enabled", type="boolean", example=false),
 *                 @OA\Property(property="carry_over_limit", type="integer", example=5),
 *                 @OA\Property(property="leave_rollover_type", type="string", enum={"none", "partial", "full"}),
 *                 @OA\Property(property="employment_statuses", type="array", @OA\Items(type="integer", example=1))
 *             )),
 *
 *             @OA\Property(property="leave_setting", type="object",
 *                 @OA\Property(property="start_month", type="integer", minimum=1, maximum=12, example=1),
 *                 @OA\Property(property="approval_level", type="string", enum={"single", "multiple"}),
 *                 @OA\Property(property="allow_bypass", type="boolean"),
 *                 @OA\Property(property="special_roles", type="array", @OA\Items(type="integer")),
 *                 @OA\Property(property="paid_leave_employment_statuses", type="array", @OA\Items(type="integer")),
 *                 @OA\Property(property="unpaid_leave_employment_statuses", type="array", @OA\Items(type="integer"))
 *             ),
 *
 *             @OA\Property(property="attendance_setting", type="object",
 *                 @OA\Property(property="single_day_work_shift", type="string", enum={"same_day", "split_time"}),
 *                 @OA\Property(property="multi_day_work_shift", type="string", enum={"same_day", "split_time"}),
 *                 @OA\Property(property="punch_in_time_tolerance", type="number", minimum=0),
 *                 @OA\Property(property="work_availability_definition", type="number", minimum=0),
 *                 @OA\Property(property="punch_in_out_alert", type="boolean"),
 *                 @OA\Property(property="punch_in_out_interval", type="number", minimum=0),
 *                 @OA\Property(property="alert_area", type="array", @OA\Items(type="string")),
 *                 @OA\Property(property="service_name", type="string"),
 *                 @OA\Property(property="api_key", type="string"),
 *                 @OA\Property(property="auto_approval", type="boolean"),
 *                 @OA\Property(property="is_geolocation_enabled", type="boolean"),
 *                 @OA\Property(property="special_roles", type="array", @OA\Items(type="integer"))
 *             ),
 *
 *             @OA\Property(property="payrun_setting", type="object",
 *                 @OA\Property(property="payrun_period", type="string", enum={"monthly", "weekly"}),
 *                 @OA\Property(property="consider_type", type="string", enum={"hour", "daily_log", "none"}),
 *                 @OA\Property(property="consider_overtime", type="boolean")
 *             ),
 *
 *             @OA\Property(property="payment_date_setting", type="object",
 *                 @OA\Property(property="payment_type", type="string", enum={"weekly", "monthly", "custom"}),
 *                 @OA\Property(property="custom_date", type="string", format="date", nullable=true),
 *                 @OA\Property(property="day_of_week", type="integer", minimum=0, maximum=6, nullable=true),
 *                 @OA\Property(property="day_of_month", type="integer", minimum=1, maximum=31, nullable=true),
 *                 @OA\Property(property="custom_frequency_interval", type="integer", minimum=1, nullable=true),
 *                 @OA\Property(property="custom_frequency_unit", type="string", enum={"days", "weeks", "months"}, nullable=true),
 *                 @OA\Property(property="role_specific_settings", type="array", @OA\Items(type="integer"))
 *             )
 *         )
 *     ),
     *
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function storeBusinessFlowSetupData(BusinessFlowSetupCreateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {



                $request_data = $request->validated();
                $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];

                foreach ($request_data["leave_types"] as $setting_leave_type_data) {
                    $setting_leave_type_data["is_active"] = 1;
                    $setting_leave_type_data["is_default"] = 0;
                    $setting_leave_type_data["created_by"] = auth()->user()->id;
                    $setting_leave_type_data["business_id"] = auth()->user()->business_id;
                    SettingLeaveType::create($setting_leave_type_data);
                }




                $leave_setting = $request_data["leave_setting"];
                $leave_setting["is_active"] = 1;
                $leave_setting["business_id"] = auth()->user()->business_id;
                $leave_setting["is_default"] = 0;

                $setting_leave =     SettingLeave::create($leave_setting);

                $setting_leave->special_roles()->sync($leave_setting['special_roles']);
                $setting_leave->paid_leave_employment_statuses()->sync($leave_setting['paid_leave_employment_statuses']);
                $setting_leave->unpaid_leave_employment_statuses()->sync($leave_setting['unpaid_leave_employment_statuses']);


                $attendance_setting = $request_data["attendance_setting"];
                $attendance_setting["is_active"] = 1;

                $attendance_setting["business_id"] = auth()->user()->business_id;
                $attendance_setting["is_default"] = 0;

                $setting_attendance =     SettingAttendance::create($attendance_setting);

                $setting_attendance->special_roles()->sync($attendance_setting['special_roles']);

                $permission = 'attendance_approve';

                foreach ($attendance_setting['special_roles'] as $special_role_id) {
                    $special_role = Role::where([
                        "id" => $special_role_id
                    ])->first();
                    if (!$special_role) {
                        throw new Exception("no special role found");
                    }

                    if (!$special_role->hasPermissionTo($permission)) {
                        $special_role->givePermissionTo($permission);
                    }
                }

                $payrun_setting = $request_data["payrun_setting"];
                $payrun_setting["is_active"] = 1;



                $payrun_setting["business_id"] = auth()->user()->business_id;
                $payrun_setting["is_default"] = 0;

                $setting_payrun =     SettingPayrun::create($payrun_setting);


                $payment_date_setting = $request_data["payment_date_setting"];

                $payment_date_setting["is_active"] = 1;


                $payment_date_setting["business_id"] = auth()->user()->business_id;
                $payment_date_setting["is_default"] = 0;



                $setting_payment_date =  SettingPaymentDate::create($payment_date_setting, $request_data);








                return response()->json([
                    "message" => "Data Inserted Successfully!"
                ], 200);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-initializations/business-flow-setup",
     *      operationId="getBusinessFlowSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get designations  ",
     *      description="This method is to get designations ",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessFlowSetupData(Request $request)
    {
        try {



            $data["leave_types"] = SettingLeaveType::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])
                ->get();

            $data["leave_setting"] = SettingLeave::where([
                "business_id" => NULL,
                "is_active" => 1,
                "is_default" =>  1,
            ])->first();

            $data["attendance_setting"] = SettingAttendance::where([
                "business_id" => NULL,
                "is_active" => 1,
                "is_default" =>  1,
            ])->get();

            $data["payrun_setting"] = SettingPayrun::where([
                "business_id" => NULL,
                "is_active" => 1,
                "is_default" => 1,
            ])->get();

            $data["payment_date_setting"] = SettingPaymentDate::where([
                'business_id' => null,
                'is_active' => 1,
                'is_default' =>  1,
            ])->get();


            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }





 /**
     *
     * @OA\Post(
     *      path="/v1.0/business-initializations/general-setup",
     *      operationId="storeGeneralSetupData",
     *      tags={"business_initialization"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store recruitment process setup data",
     *      description="This method is to store recruitment process setup data",
     *
   *  @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="asset_types", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="name", type="string", example="Asset Type Name"),
 *                     @OA\Property(property="description", type="string", nullable=true, example="Asset Type Description")
 *                 )
 *             ),
 *             @OA\Property(property="termination_reasons", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="name", type="string", example="Termination Reason Name"),
 *                     @OA\Property(property="description", type="string", nullable=true, example="Termination Reason Description")
 *                 )
 *             ),
 *             @OA\Property(property="termination_types", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="name", type="string", example="Termination Type Name"),
 *                     @OA\Property(property="description", type="string", nullable=true, example="Termination Type Description")
 *                 )
 *             )
 *         ),
 *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

     public function storeGeneralSetupData(GeneralSetupCreateRequest $request)
     {
         try {



             return DB::transaction(function () use ($request) {

                 $request_data = $request->validated();

                 $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];

                 foreach ($request_data["asset_types"] as $asset_type_data) {
                     $asset_type_data["is_active"] = 1;
                     $asset_type_data["is_default"] = 0;
                     $asset_type_data["created_by"] = auth()->user()->id;
                     $asset_type_data["business_id"] = auth()->user()->business_id;
                     AssetType::create($asset_type_data);
                 }

                 foreach ($request_data["termination_reasons"] as $termination_reason_data) {
                     $termination_reason_data["is_active"] = 1;
                     $termination_reason_data["is_default"] = 0;
                     $termination_reason_data["created_by"] = auth()->user()->id;
                     $termination_reason_data["business_id"] = auth()->user()->business_id;
                     TerminationReason::create($termination_reason_data);
                 }

                 foreach ($request_data["termination_types"] as $termination_type_data) {
                     $termination_type_data["is_active"] = 1;
                     $termination_type_data["is_default"] = 0;
                     $termination_type_data["created_by"] = auth()->user()->id;
                     $termination_type_data["business_id"] = auth()->user()->business_id;
                     TerminationType::create($termination_type_data);
                 }




                 return response()->json([
                     "message" => "Data Inserted Successfully!"
                 ], 200);
             });
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }



     /**
      *
      * @OA\Get(
      *      path="/v1.0/business-initializations/general-setup",
      *      operationId="getGeneralSetupData",
      *      tags={"business_initialization"},
      *       security={
      *           {"bearerAuth": {}}
      *       },

      *              @OA\Parameter(
      *         name="per_page",
      *         in="query",
      *         description="per_page",
      *         required=true,
      *  example="6"
      *      ),
      *      * *  @OA\Parameter(
      * name="is_active",
      * in="query",
      * description="is_active",
      * required=true,
      * example="1"
      * ),
      *      * *  @OA\Parameter(
      * name="start_date",
      * in="query",
      * description="start_date",
      * required=true,
      * example="2019-06-29"
      * ),
      * *  @OA\Parameter(
      * name="end_date",
      * in="query",
      * description="end_date",
      * required=true,
      * example="2019-06-29"
      * ),
      * *  @OA\Parameter(
      * name="search_key",
      * in="query",
      * description="search_key",
      * required=true,
      * example="search_key"
      * ),
      * *  @OA\Parameter(
      * name="order_by",
      * in="query",
      * description="order_by",
      * required=true,
      * example="ASC"
      * ),

      *      summary="This method is to get designations  ",
      *      description="This method is to get designations ",
      *

      *      @OA\Response(
      *          response=200,
      *          description="Successful operation",
      *       @OA\JsonContent(),
      *       ),
      *      @OA\Response(
      *          response=401,
      *          description="Unauthenticated",
      * @OA\JsonContent(),
      *      ),
      *        @OA\Response(
      *          response=422,
      *          description="Unprocesseble Content",
      *    @OA\JsonContent(),
      *      ),
      *      @OA\Response(
      *          response=403,
      *          description="Forbidden",
      *   @OA\JsonContent()
      * ),
      *  * @OA\Response(
      *      response=400,
      *      description="Bad Request",
      *   *@OA\JsonContent()
      *   ),
      * @OA\Response(
      *      response=404,
      *      description="not found",
      *   *@OA\JsonContent()
      *   )
      *      )
      *     )
      */

     public function getGeneralSetupData(Request $request)
     {
         try {



             $data["asset_types"] = AssetType::where([
                     "is_active" => 1,
                     "is_default" => 1,
                     "business_id" => NULL,
                     "parent_id" => NULL
                 ])->get();

             $data["termination_reasons"] = TerminationReason::where([
                     "is_active" => 1,
                     "is_default" => 1,
                     "business_id" => NULL,
                     "parent_id" => NULL
                 ])
                 ->get();


                $data["termination_types"] = TerminationType::where([
                    "is_active" => 1,
                    "is_default" => 1,
                    "business_id" => NULL,
                    "parent_id" => NULL
                ])
                ->get();




             return response()->json($data, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }










}
