<?php

namespace App\Http\Controllers;

use App\Exports\WorkShiftsExport;
use App\Http\Components\AttendanceComponent;
use App\Http\Components\DepartmentComponent;

use App\Http\Components\LeaveComponent;
use App\Http\Components\WorkShiftHistoryComponent;
use App\Http\Components\WorkTimeManagementComponent;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\WorkShiftCreateRequest;
use App\Http\Requests\WorkShiftUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\Attendance;
use App\Models\BusinessTime;
use App\Models\Department;
use App\Models\EmployeeUserWorkShiftHistory;
use App\Models\Leave;
use App\Models\LeaveRecord;
use App\Models\Notification;
use App\Models\PayrollAttendance;
use App\Models\PayrollLeaveRecord;
use App\Models\WorkShiftHistory;
use App\Models\User;

use App\Models\WorkShift;
use App\Models\WorkShiftDetailHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class WorkShiftController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, ModuleUtil;


    protected $workShiftHistoryComponent;
    protected $attendanceComponent;
    protected $leaveComponent;

    protected $departmentComponent;
    protected $workTimeManagementComponent;


    public function __construct(WorkShiftHistoryComponent $workShiftHistoryComponent, AttendanceComponent $attendanceComponent, LeaveComponent $leaveComponent, DepartmentComponent $departmentComponent, WorkTimeManagementComponent $workTimeManagementComponent)
    {
        $this->workShiftHistoryComponent = $workShiftHistoryComponent;
        $this->attendanceComponent = $attendanceComponent;
        $this->leaveComponent = $leaveComponent;

        $this->departmentComponent = $departmentComponent;
        $this->workTimeManagementComponent = $workTimeManagementComponent;
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/work-shifts",
     *      operationId="createWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store work shift",
     *      description="This method is to store work shift",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *  *     @OA\Property(property="description", type="string", format="string", example="description"),
     *      *  *     @OA\Property(property="is_personal", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="break_type", type="string", format="string", example="paid"),
     *  *     @OA\Property(property="break_hours", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),
     *  *     @OA\Property(property="work_locations", type="string",  format="array", example={1,2,3}),

     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "is_weekend": 1,
     *             "shifts": {
     *             {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "1",
     *             "is_weekend": 0,
     *             "shifts": {
     *                 {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "2",
     *             "is_weekend": 0,
     *             "shifts": {
     *             {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "3",
     *             "is_weekend": 0,
     *             "shifts": {
     *                    {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "4",
     *             "is_weekend": 0,
     *             "shifts": {
     *               {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "5",
     *             "is_weekend": 0,
     *             "shifts": {
     *            {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "6",

     *             "is_weekend": 1,
     *             "shifts": {
     *                 {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         }
     *     }),
     *
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

    public function createWorkShift(WorkShiftCreateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('work_shift_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                if (empty($request_data['departments'])) {
                    $request_data['departments'] = Department::where(
                        [
                            "business_id" => auth()->user()->business_id,
                            "manager_id" => auth()->user()->id
                        ]

                    )
                        ->pluck("id");
                }


                if ($request_data["type"] == "flexible") {
                    $this->isModuleEnabled("flexible_shifts");
                }

                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_default"] = false;

                $request_data = $this->workShiftHistoryComponent->adjustScheduleTimesAndHours($request_data);


                $work_shift =  WorkShift::create($request_data);

                $work_shift->departments()->sync($request_data['departments']);

                $work_shift->work_locations()->sync($request_data["work_locations"]);
                // $work_shift->users()->sync($request_data['users'], []);


                $work_shift->details()->createMany($request_data['details']);






                DB::commit();
                return response($work_shift, 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/work-shifts",
     *      operationId="updateWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update work shift ",
     *      description="This method is to update work_shift",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *     @OA\Property(property="description", type="string", format="string", example="description"),
     *    *      *  *     @OA\Property(property="is_personal", type="boolean", format="boolean", example="0"),
     *   *     @OA\Property(property="break_type", type="string", format="string", example="paid"),
     *  *     @OA\Property(property="break_hours", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3,4}),
     *      *  *     @OA\Property(property="work_locations", type="string",  format="array", example={1,2,3}),

     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "is_weekend": 1,
     *             "shifts": {
     *         {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "1",
     *             "is_weekend": 0,
     *             "shifts": {
     *           {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "2",
     *             "is_weekend": 0,
     *             "shifts": {
     *            {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "3",
     *             "is_weekend": 0,
     *             "shifts": {
     *            {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "4",
     *             "is_weekend": 0,
     *             "shifts": {
     *               {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "5",
     *             "is_weekend": 0,
     *             "shifts": {
     *                 {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         },
     *         {
     *             "day": "6",
     *             "is_weekend": 1,
     *             "shifts": {
     *                 {"start_at": "", "end_at": "","work_location_id":""}
     *             }
     *         }
     *     }),

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

    public function updateWorkShift(WorkShiftUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {


                if (!$request->user()->hasPermissionTo('work_shift_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $request_data["business_id"] = auth()->user()->business_id;

                $request_data = $this->workShiftHistoryComponent->adjustScheduleTimesAndHours($request_data);

                if (empty($request_data['departments'])) {
                    $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];
                }



                if ($request_data["type"] == "flexible") {
                    $this->isModuleEnabled("flexible_shifts");
                }

                $work_shift_query_params = [
                    "id" => $request_data["id"],
                ];

                $work_shift_prev = WorkShift::where($work_shift_query_params)->first();

                $work_shift  =  tap(WorkShift::where($work_shift_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'type',
                        "description",

                        'is_personal',
                        'break_type',
                        'break_hours',
                        'total_schedule_hours'

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$work_shift) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                $work_shift->departments()->sync($request_data['departments']);
                $work_shift->work_locations()->sync($request_data['work_locations']);
                $work_shift_prev_details  =  $work_shift_prev->details;

                $work_shift->details()->delete();
                $work_shift->details()->createMany($request_data['details']);


                $fields_changed = 0; // Initialize to false

                // Step 2: Use it in your main logic
                if (empty($work_shift_prev) || empty($work_shift) || empty($work_shift_prev_details) || empty($work_shift->details)) {
                    $fields_changed = 1; // Initialize to false
                }

                // Check main fields
                $main_fields_to_check = ['type', 'is_personal', 'break_type', 'break_hours', "total_schedule_hours"];
                if ($this->fieldsHaveChanged($main_fields_to_check, $work_shift_prev, $work_shift)) {
                    $fields_changed = 1;
                }



                // Check nested details if no main changes
                if (!$fields_changed) {
                    $details_fields_to_check = ['work_shift_id', 'day', 'is_weekend', 'schedule_hour'];
                    foreach ($work_shift_prev_details as $key => $prev_detail) {
                        if (!isset($work_shift->details[$key])) {
                            $fields_changed = 1;
                            break;
                        }

                        if ($this->fieldsHaveChanged($details_fields_to_check, $prev_detail, $work_shift->details[$key])) {
                            $fields_changed = 1;
                            break;
                        }

                        // Check nested shifts
                        $shift_fields_to_check = ['start_at', 'end_at'];

                        if (!empty($prev_detail->shifts)) {
                            foreach ($prev_detail->shifts as $shift_key => $prev_shift) {
                                if (!isset($work_shift->details[$key]->shifts[$shift_key])) {
                                    $fields_changed = 1;
                                    break 2;
                                }

                                if ($this->fieldsHaveChanged($shift_fields_to_check, $prev_shift, $work_shift->details[$key]->shifts[$shift_key])) {
                                    $fields_changed = 1;
                                    break 2;
                                }
                            }
                        }
                    }
                }


                $work_shift_histories = WorkShiftHistory::where([
                    "work_shift_id" => $work_shift->id
                ])
                    ->get();



                if (
                    $fields_changed
                ) {

                    $work_shift_history_ids = $work_shift_histories->pluck("id")->toArray();

                    $attendance_payroll_exists = PayrollAttendance::whereHas("attendance", function ($query) use ($work_shift_history_ids) {
                        $query->whereIn("attendances.work_shift_history_id", $work_shift_history_ids);
                    })
                        ->exists();

                    if ($attendance_payroll_exists) {
                        throw new Exception("A payroll has already been created for this work shift. Updates are not allowed.", 409);
                    }


                    $leave_payroll_exists = PayrollLeaveRecord::whereHas("leave_record", function ($query) use ($work_shift_history_ids) {
                        $query->whereIn("leave_records.work_shift_history_id", $work_shift_history_ids);
                    })
                        ->exists();

                    if ($leave_payroll_exists) {
                        throw new Exception("A payroll has already been created for this work shift. Updates are not allowed.", 409);
                    }


                    foreach ($work_shift_histories as $work_shift_history) {
                        $work_shift_history_data = $work_shift->toArray();
                        $work_shift_history->fill($work_shift_history_data);
                        $work_shift_history->save();

                        $work_shift_history->details()->delete();
                        $work_shift_history->details()->createMany($work_shift->details->toArray());
                        $employee_id = $work_shift_history->user_id;
                        if(!empty($employee_id)) {
                            $notification_description = "Your work schedule has been updated. Please review the new schedule.";
                            $notification_link = "http://example.com/workshifts/1"; // Example link
                            Notification::create([
                                "entity_id" => $work_shift_history->id,
                                "entity_ids" => [$work_shift_history->id],
                                "entity_name" => "work_shift",
                                'notification_title' => "Schedule Updated",
                                'notification_description' => $notification_description,
                                'notification_link' => $notification_link,
                                "sender_id" => auth()->user()->id,
                                "receiver_id" => $employee_id,
                                "business_id" => auth()->user()->business_id,
                                "is_system_generated" => 1,
                                "status" => "unread",
                            ]);
                        }



                    }





                    $leaveRecords = LeaveRecord::whereHas("leave", function ($query) {
                        $query->whereIn("leaves.leave_duration", ["multiple_day", "single_day"]);
                    })
                        ->whereIn("leave_records.work_shift_history_id", $work_shift_history_ids)
                        ->get();


                    foreach ($leaveRecords as $leaveRecord) {

                        $work_shift_details = collect($leaveRecord->work_shift_history->details)
                            ->filter(function ($detail) use ($leaveRecord) {
                                $day_number = Carbon::parse($leaveRecord->date)->dayOfWeek;
                                return $day_number === $detail["day"];
                            })
                            ->first();

                        $capacity_hours = (!empty($work_shift_details->is_weekend))
                            ? 0
                            : (
                                ($work_shift_history->break_type != "paid")
                                ? ($work_shift_details->schedule_hour - $work_shift_history->break_hours)
                                : $work_shift_details->schedule_hour
                            );

                        $leave_record_data["break_type"] = $work_shift_history->break_type ?? "";
                        $leave_record_data["capacity_hours"] = $capacity_hours;
                        $leave_record_data["leave_hours"] = $capacity_hours;


                        $leaveRecord->update($leave_record_data);
                    }

                    $leaves = Leave::whereIn("id",$leaveRecords->pluck("leave_id")->toArray())
                    ->get();

                    foreach($leaves as $leave) {
            $this->leaveComponent->deleteLeaveAvailability($leave, $leave->total_leave_hours);

            $leave_records = $leave->records()->get();
            $total_recorded_hours = $leave_records->sum('leave_hours');
            $leave->total_leave_hours = $total_recorded_hours;
            $leave->save();
            $this->leaveComponent->addLeaveAvailability($leave, $total_recorded_hours);

                    }


                    $attendances = Attendance::with("work_shift_history", "leave_record")
                        ->whereIn("attendances.work_shift_history_id", $work_shift_history_ids)
                        ->where("consider_overtime", 1)
                        ->get();

                    $this->attendanceComponent->updateAttendanceOverTime($attendances);







                }



                return response($work_shift, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }









    /**
     *
     * @OA\Put(
     *      path="/v1.0/work-shifts/toggle-active",
     *      operationId="toggleActiveWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle work shift activity",
     *      description="This method is to toggle work shift activity",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
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


    public function toggleActiveWorkShift(GetIdRequest $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shift = WorkShift::where([
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })


                ->first();
            if (!$work_shift) {

                return response()->json([
                    "message" => "no department found"
                ], 404);
            }




            $is_active = !$work_shift->is_active;



            $work_shift->update([
                'is_active' => $is_active
            ]);

            return response()->json(['message' => 'department status updated successfully'], 200);


            if (false) {
                if ($is_active) {

                    $last_inactive_date = WorkShiftHistory::where("work_shift_id", $work_shift->id)
                        ->orderByDesc("work_shift_histories.id")
                        ->first();


                    $employee_work_shift_history_data = $work_shift->toArray();

                    $employee_work_shift_history_data["is_active"] = $is_active;

                    $employee_work_shift_history_data["work_shift_id"] = $work_shift->id;
                    $employee_work_shift_history_data["from_date"] = $last_inactive_date->to_date;
                    $employee_work_shift_history_data["to_date"] = NULL;
                    $employee_work_shift_history =  WorkShiftHistory::create($employee_work_shift_history_data);
                    $employee_work_shift_history->details()->createMany($work_shift->details->toArray());
                    $user_ids = $work_shift->users()->pluck('users.id')->toArray();
                    $pivot_data = collect($user_ids)->mapWithKeys(function ($user_id) {
                        return [$user_id => ['from_date' => now(), 'to_date' => null]];
                    });
                    $employee_work_shift_history->users()->sync($pivot_data);

                    // $employee_work_shift_history_data = $work_shift->toArray();
                    // $employee_work_shift_history_data["work_shift_id"] = $work_shift->id;
                    // $employee_work_shift_history_data["from_date"] = now();
                    // $employee_work_shift_history_data["to_date"] = NULL;
                    // $employee_work_shift_history =  WorkShiftHistory::create($employee_work_shift_history_data);
                    // $employee_work_shift_history->details()->createMany($request_data['details']);
                    // //  $employee_work_shift_history->users()->sync($work_shift->users()->pluck("id"));

                    // $user_ids = $work_shift->users()->pluck('users.id')->toArray();

                    // // Define the additional pivot data for each user
                    // $pivot_data = collect($user_ids)->mapWithKeys(function ($user_id) {
                    // return [$user_id => ['from_date' => now(), 'to_date' => null]];
                    // });
                    // $employee_work_shift_history->users()->sync($pivot_data);
                } else {

                    WorkShiftHistory::where([
                        "to_date" => NULL
                    ])
                        ->where("work_shift_id", $work_shift->id)
                        // ->whereHas('users',function($query) use($work_shift)  {
                        //     $query->whereIn("users.id",$work_shift->users()->pluck("users.id"));
                        // })
                        ->update([
                            "to_date" => now()
                        ]);
                }
            }
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts",
     *      operationId="getWorkShifts",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),

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
     *
     *
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     * @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *    * @OA\Parameter(
     * name="type",
     * in="query",
     * description="type",
     * required=true,
     * example="type"
     * ),
     *
     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      * *  @OA\Parameter(
     * name="is_personal",
     * in="query",
     * description="is_personal",
     * required=true,
     * example="1"
     * ),
     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get work shifts  ",
     *      description="This method is to get work shifts ",
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

    public function getWorkShifts(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shifts_query = WorkShift::with("details", "departments", "users", "work_locations");

            $work_shiftsQuery = $this->workShiftHistoryComponent->updateWorkShiftsQuery($all_manager_department_ids, $work_shifts_query);

                 $work_shifts =  $this->retrieveData($work_shiftsQuery, "id", "work_shifts");


            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {

                    if (empty($work_shifts->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.work_shifts', ["work_shifts" => $work_shifts]);
                    }
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new WorkShiftsExport($work_shifts), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                }
            } else {
                return response()->json($work_shifts, 200);
            }


            return response()->json($work_shifts, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v2.0/work-shifts",
     *      operationId="getWorkShiftsV2",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),

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
     *
     *
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     * @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *    * @OA\Parameter(
     * name="type",
     * in="query",
     * description="type",
     * required=true,
     * example="type"
     * ),
     *
     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      * *  @OA\Parameter(
     * name="is_personal",
     * in="query",
     * description="is_personal",
     * required=true,
     * example="1"
     * ),
     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get work shifts  ",
     *      description="This method is to get work shifts ",
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

    public function getWorkShiftsV2(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shifts_query = WorkShift::with(

                [
                    "departments" => function ($query) {
                        $query->select(
                            'departments.id',
                            'departments.name',
                        );
                    },
                    "work_locations" => function ($query) {
                        $query->select(
                            'work_locations.id',
                            'work_locations.name',
                        );
                    },



                ]
            );

            $work_shiftsQuery = $this->workShiftHistoryComponent->updateWorkShiftsQuery($all_manager_department_ids, $work_shifts_query)

                ->select(
                    "work_shifts.id",
                    "work_shifts.name",
                    "work_shifts.type",
                    "work_shifts.break_type",
                    "work_shifts.business_id",
                    "work_shifts.description",
                    "work_shifts.is_active",
                    "work_shifts.is_business_default",
                    "work_shifts.is_default",
                    "work_shifts.is_personal",
                );

                 $work_shifts =  $this->retrieveData($work_shiftsQuery, "id", "work_shifts");


            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                // if (strtoupper($request->response_type) == 'PDF') {
                //     $pdf = PDF::loadView('pdf.work_shifts', ["work_shifts" => $work_shifts]);
                //     return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                // } elseif (strtoupper($request->response_type) === 'CSV') {

                //     return Excel::download(new WorkShiftsExport($work_shifts), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                // }
            } else {
                return response()->json($work_shifts, 200);
            }




            return response()->json($work_shifts, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v3.0/work-shifts",
     *      operationId="getWorkShiftsV3",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),

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
     *
     *
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     * @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *    * @OA\Parameter(
     * name="type",
     * in="query",
     * description="type",
     * required=true,
     * example="type"
     * ),
     *
     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      * *  @OA\Parameter(
     * name="is_personal",
     * in="query",
     * description="is_personal",
     * required=true,
     * example="1"
     * ),
     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get work shifts  ",
     *      description="This method is to get work shifts ",
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

    public function getWorkShiftsV3(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shifts_query = WorkShift::query();

            $work_shifts_query = $this->workShiftHistoryComponent->updateWorkShiftsQuery($all_manager_department_ids, $work_shifts_query)

                ->select([
                    'id',
                    'name',
                    "break_type",
                    "break_hours",
                    "total_schedule_hours",
                    'type',
                    "description",
                    "is_active",
                ])
                 ->selectSub(function ($query) {
        $query->from('work_shift_histories')
              ->selectRaw('COUNT(user_id)')
              ->whereColumn('work_shift_id', 'work_shifts.id');
    }, 'number_of_employee');

                  $work_shifts = $this->retrieveData($work_shifts_query, "id", "work_shifts");







            return response()->json($work_shifts, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts/{id}",
     *      operationId="getWorkShiftById",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get work shift by id",
     *      description="This method is to get work shift by id",
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


    public function getWorkShiftById($id, Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shift =  WorkShift::with("details", "departments", "users", "work_locations")
                ->where([
                    "id" => $id
                ])
                ->where(function ($query) use ($all_manager_department_ids) {
                    $query
                        ->where([
                            "work_shifts.business_id" => auth()->user()->business_id
                        ])
                        ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })
                        ->when(auth()->user()->hasRole("business_owner"), function($query) {
                            $query->orWhereDoesntHave("departments");
                        });

                })

                // ->orWhere(function ($query) {
                //     $query->where([
                //         "is_active" => 1,
                //         "business_id" => NULL,
                //         "is_default" => 1
                //     ]);
                // })
                ->first();
            if (!$work_shift) {

                return response()->json([
                    "message" => "no work shift found"
                ], 404);
            }


            return response()->json($work_shift, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts/get-by-user-id/{user_id}",
     *      operationId="getWorkShiftByUserId",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get work shift by user id",
     *      description="This method is to get work shift by user id",
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


    public function getWorkShiftByUserId($user_id, Request $request)
    {

        try {


            $user_id = intval($user_id);
            $request_user_id = auth()->user()->id;

            $hasPermission = auth()->user()->hasPermissionTo('work_shift_view');

            if ((!$hasPermission && ($request_user_id !== $user_id))) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $this->validateUserQuery($user_id, $all_manager_department_ids);




            $work_shift = $this->workShiftHistoryComponent->getWorkShiftByUserId($user_id);



            return response()->json($work_shift, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
  /**
     *
     * @OA\Get(
     *      path="/v2.0/work-shifts/get-by-user-id/{user_id}",
     *      operationId="getWorkShiftByUserIdV2",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *  *              @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *  example="6"
     *      ),
     *  *  *              @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get work shift by user id",
     *      description="This method is to get work shift by user id",
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


    public function getWorkShiftByUserIdV2($user_id, Request $request)
    {

        try {

            $is_client=false;

            if(empty(auth()->user())) {
                $is_client=true;
                $this->logInClient();
            }
            if($is_client) {
                 if(auth()->user()->id != $user_id) {
                    throw new Exception("You are unauthenticated!",403);
                 }
            }


            $user_id = intval($user_id);
            $request_user_id = auth()->user()->id;

            $hasPermission = auth()->user()->hasPermissionTo('work_shift_view');

            if ((!$hasPermission && ($request_user_id !== $user_id))) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $start_date = request()->input("start_date");
            $end_date = request()->input("end_date");



            if (empty($start_date) || empty($end_date)) {
                return response()->json([
                    "message" => "start date and end date is required"
                ], 400);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

           $user = $this->validateUserQuery($user_id, $all_manager_department_ids);





            $work_shift_histories =   $this->workTimeManagementComponent->get_work_shift_histories($start_date, $end_date, $user_id, false);

            $already_taken_attendance_dates = $this->attendanceComponent->get_already_taken_attendance_dates($user_id, $start_date, $end_date);


            $date_of_termination = $user->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($user->joining_date,$date_of_termination,$start_date,$end_date);
              $holiday_start_date = Carbon::parse($dates["start_date"]);
              $holiday_end_date = Carbon::parse($dates["end_date"]);

            $holiday_dates =  $this->workTimeManagementComponent->get_holiday_dates($holiday_start_date, $holiday_end_date,$user_id);

            $already_taken_leaves =  LeaveRecord::whereHas("leave", function ($query) use ($user_id) {
                $query->where("leaves.user_id", $user_id)
                    ->whereNotIn("leaves.status", ["rejected"]);
            })
                ->where('leave_records.date', '>=', $start_date)
                ->where('leave_records.date', '<=', Carbon::parse($end_date)->endOfDay())
                ->get();





            // Parse start and end dates
            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);

            $joining_date = Carbon::parse($user->joining_date);

            if ($joining_date->gt($end_date)) {
                return response()->json(
                    [
                        "message" => "Employee joining date is " . $joining_date->format('d/m/Y')
                    ],
                    409
                );
            }

            if ($joining_date->gt($start_date)) {
                $start_date = $joining_date;
            }


            // Create date range between start and end dates
            $date_range = $start_date->daysUntil($end_date);


            $dates = [];
            // Map date range to create attendance details
            // Iterate through the date range
            foreach ($date_range as $date) {
                // Convert Carbon date object to string for comparison
                $date = Carbon::parse($date);

                $business_time =   BusinessTime::where([
                    "business_id" => auth()->user()->business_id,
                    "day" => $date->dayOfWeek
                ])
                    ->first();

                // Initialize the date data array
                $date_data = [
                    "date" => $date,
                ];

                $dateToCheck = Carbon::parse($date_data["date"]);


                // Check if work shift histories are available
                if (!empty($work_shift_histories)) {
                    // Find the corresponding work shift history for the given date
                    $work_shift_history = $work_shift_histories->first(function ($history) use ($date, $end_date) {
                        $fromDate = Carbon::parse($history->from_date);
                        $toDate = $history->to_date ? Carbon::parse($history->to_date) : $end_date;

                        return $date->greaterThanOrEqualTo($fromDate)
                            && ($toDate === null || $date->lessThanOrEqualTo($toDate));
                    });


                    // If a work shift history is found, get its details
                    if (!empty($work_shift_history)) {

                        $work_shift_details = $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $date);

                        // Add work shift details to the date data array
                        $date_data["work_shift_details"]["is_weekend"] = $work_shift_details->is_weekend;

                        $date_data["work_shift_details"]["schedule_hour"] = $work_shift_details->schedule_hour;

                        $date_data["work_shift_details"]["shifts"] = $work_shift_details->shifts;

                        // $date_data["work_shift_details"]["start_at"] = $work_shift_details->start_at;
                        // $date_data["work_shift_details"]["end_at"] = $work_shift_details->end_at;


                        $date_data["work_shift_details"]["work_shift_id"] = $work_shift_details->work_shift_id;
                        $date_data["work_shift_details"]["day"] = $work_shift_details->day;
                        $date_data["work_shift_details"]["break_minutes"] = round($work_shift_history->break_hours * 60);
                        $date_data["work_shift_details"]["name"] = $work_shift_history->name;

                        $date_data["work_shift_details"]["type"] = $work_shift_history->type;
                        $date_data["work_shift_details"]["shifts"] = $work_shift_details->shifts;

                        // $work_shift_start_time = Carbon::parse($work_shift_details->start_at);
                        // $work_shift_end_time = Carbon::parse($work_shift_details->end_at);



                        if (empty($work_shift_history->is_weekend)) {
                            $available_times = [];

                            if (!empty($work_shift_details->shifts)) {
                                foreach ($work_shift_details->shifts as $shift) {
                                    $work_shift_start_time = Carbon::parse($shift['start_at']);
                                    $work_shift_end_time = Carbon::parse($shift['end_at']);

                                    $available_times[] = [
                                        "in_time" => $work_shift_start_time->format('H:i:s'),
                                        "out_time" => $work_shift_end_time->format('H:i:s'),

                                    ];
                                }
                                // Add the available times to the date data array
                                $date_data["work_shift_details"]["available_times"] = $available_times;
                            }
                        } else {

                            if (!empty($business_time->is_weekend)) {
                                $date_data["work_shift_details"]["available_times"] =     [
                                    [
                                        "in_time" => Carbon::parse("09:00")->format('H:i:s'),
                                        "out_time" => Carbon::parse("18:00")->format('H:i:s'),
                                    ]
                                ];
                                $date_data["work_shift_details"]["is_business_weekend"] = 1;
                            } else {
                                $date_data["work_shift_details"]["available_times"] =     [
                                    [
                                        "in_time" => Carbon::parse($business_time->start_at)->format('H:i:s'),
                                        "out_time" => Carbon::parse($business_time->end_at)->format('H:i:s'),

                                    ]

                                ];
                            }
                        }

                        $leave_record =  collect($already_taken_leaves)->first(function ($record) use ($dateToCheck) {
                            return Carbon::parse($record->date)->equalTo($dateToCheck);
                        });

                        if (!empty($leave_record)) {
                            $date_data["leave"] = $leave_record;
                            $date_data["is_on_leave"] = 1;
                            // Check leave type
                            if (!in_array($leave_record->leave->leave_duration, ['single_day', 'multiple_day'])) {
                                // Handle hourly leave (e.g., 10 AM to 1 PM)
                                // Remove leave hours from the work shift and add available times
                                $leave_start_time = Carbon::parse($leave_record->start_time);
                                $leave_end_time = Carbon::parse($leave_record->end_time);
                                $new_available_times = [];

                                // Before the leave
                                foreach ($available_times as $time) {
                                    $shift_start = Carbon::parse($time['in_time']);
                                    $shift_end = Carbon::parse($time['out_time']);

                                    if ($leave_start_time->gt($shift_start)) {
                                        $new_available_times[] = [
                                            "in_time" => $shift_start->format('H:i:s'),
                                            "out_time" => $leave_start_time->format('H:i:s'),
                                        ];
                                    }

                                    if ($leave_end_time->lt($shift_end)) {
                                        $new_available_times[] = [
                                            "in_time" => $leave_end_time->format('H:i:s'),
                                            "out_time" => $shift_end->format('H:i:s'),
                                        ];
                                    }
                                }

                                if (!empty($new_available_times)) {
                                    $date_data["work_shift_details"]["available_times"] = $new_available_times;
                                }
                            }
                        } else {
                            $date_data["leave"] = [];
                            $date_data["is_on_leave"] = 0;
                        }



                        if (!empty($work_shift_details->is_weekend)) {
                            $date_data["is_on_weekend"] = 1;
                        } else {
                            $date_data["is_on_weekend"] = 0;
                        }


                        $attendance = collect($already_taken_attendance_dates)->first(function ($date) use ($dateToCheck) {
                            return Carbon::parse($date)->equalTo($dateToCheck);
                        });
                        if (!empty($attendance)) {
                            $date_data["attendance_exists"] = 1;
                            $date_data["work_shift_details"]["available_times"] = [];
                        } else {
                            $date_data["attendance_exists"] = 0;
                        }

                        $holiday = collect($holiday_dates)->first(function ($date) use ($dateToCheck) {
                            return Carbon::parse($date)->equalTo($dateToCheck);
                        });

                        if (!empty($holiday)) {
                            $date_data["is_on_holiday"] = 1;
                        } else {
                            $date_data["is_on_holiday"] = 0;
                        }
                    }
                }


                // Check if there is more than one available time entry
                if (!empty($date_data["work_shift_details"]["available_times"])) {
                    if (count($date_data["work_shift_details"]["available_times"]) > 1) {
                        // If more than 1 entry, set break_hours to 0
                        foreach ($date_data["work_shift_details"]["available_times"] as &$available_time) {
                            $available_time['break_hours'] = 0;
                        }
                    } else {
                        // If only one entry, set break_hours based on the work shift
                        foreach ($date_data["work_shift_details"]["available_times"] as &$available_time) {
                            $available_time['break_hours'] = ($work_shift_history->break_hours);
                        }
                    }
                }




                // Add the date data to the dates array
                $dates[] = $date_data;
            }


            return response()->json($dates, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }  finally {

            if($is_client) {
                Auth::logout();
           }
            // Step 6: Log out the user

        }
    }

  /**
     *
     * @OA\Get(
     *      path="/v4.0/work-shifts/get-by-user-id/{user_id}",
     *      operationId="getWorkShiftByUserIdV4",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *  *              @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *  example="6"
     *      ),
     *  *  *              @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get work shift by user id",
     *      description="This method is to get work shift by user id",
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


    public function getWorkShiftByUserIdV4($user_id, Request $request)
    {

        try {



            $user_id = intval($user_id);
            $request_user_id = auth()->user()->id;

            $hasPermission = auth()->user()->hasPermissionTo('work_shift_view');

            if ((!$hasPermission && ($request_user_id !== $user_id))) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $start_date = request()->input("start_date");
            $end_date = request()->input("end_date");

            if (empty($start_date) || empty($end_date)) {
                return response()->json([
                    "message" => "start date and end date is required"
                ], 400);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();
           $user = $this->validateUserQuery($user_id, $all_manager_department_ids);

            $date_of_termination = $user->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($user->joining_date, $date_of_termination, $start_date, $end_date);
                $start_date = Carbon::parse($dates["start_date"]);
                $end_date = Carbon::parse($dates["end_date"]);

            $attendances = Attendance::where('is_present', 1)
                ->where('user_id', $user)
                ->whereBetween('in_date', [$start_date->startOfDay(), $end_date->endOfDay()])
                ->select('id', 'total_paid_hours', 'break_hours', 'paid_break_hours', 'unpaid_break_hours', 'in_date', "regular_work_hours")
                ->get();


            $schedule_information = $this->workTimeManagementComponent
                    ->getScheduleInformationData(
                        $user->id,
                        $user->joining_date,
                        $user->lastTermination->date_of_termination ?? null,
                        $start_date,
                        $end_date
                    );

            $total_capacity_hours = $schedule_information["total_capacity_hours"];
            $total_leave_hours = $schedule_information["total_leave_hours"];
            $total_paid_hours = $attendances->sum("total_paid_hours");
            $total_regular_work_hours = $attendances->sum("regular_work_hours");
            $total_break_hours = $attendances->sum("break_hours");
            $total_absent_hours =  ($total_capacity_hours - $total_regular_work_hours);


            $responseData =  [
                "total_capacity_hours" => number_format($total_capacity_hours, 2),
                "total_leave_hours" => number_format($total_leave_hours, 2),
                "total_paid_hours" => number_format($total_paid_hours, 2),
                "total_regular_work_hours" => number_format($total_regular_work_hours, 2),
                "total_break_hours" => -number_format($total_break_hours, 2),
                "total_absent_hours" => number_format($total_absent_hours, 2),
            ];

            return response()->json($responseData, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v3.0/work-shifts/get-by-user-id/{user_id}",
     *      operationId="getWorkShiftByUserIdV3",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *  *              @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *  example="6"
     *      ),
     *  *  *              @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get work shift by user id",
     *      description="This method is to get work shift by user id",
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


    public function getWorkShiftByUserIdV3($user_id, Request $request)
    {

        try {

            if (!request()->has("current_date")) {
                return response()->json([
                    "message" => "current_date field is missing"
                ], 400);
            }

            $current_date = Carbon::parse(request()->input("current_date"));
            // $current_date = today();

            $attendance = Attendance::with(
                [
                    "employee" => function ($query) {
                        $query->select(
                            'users.id',
                        );
                    },



                    "work_shift_history" => function ($query) {
                        $query->select(
                            'work_shift_histories.id',
                            'work_shift_histories.name',
                            'work_shift_histories.break_type',
                            'work_shift_histories.break_hours',
                            'work_shift_histories.type'
                        );
                    },
                    "attendance_records.projects",
                    "attendance_records.work_location",

                ]

            )
                ->where('attendances.user_id', auth()->user()->id)
                ->where('attendances.is_clocked_in', 1)
                ->where(function ($query) use ($current_date) {
                    $query->whereDate('attendances.in_date', $current_date)
                        ->orWhereDate('attendances.in_date', $current_date->copy()->subDay());
                })
                ->where('attendances.business_id', auth()->user()->business_id)
                ->orderBy("attendances.in_date", "DESC")
                ->first();

            if (empty($attendance)) {
                $attendance =  Attendance::with(
                    [
                        "employee" => function ($query) {
                            $query->select(
                                'users.id',
                            );
                        },

                        "work_shift_history" => function ($query) {
                            $query->select(
                                'work_shift_histories.id',
                                'work_shift_histories.name',
                                'work_shift_histories.break_type',
                                'work_shift_histories.break_hours',
                                'work_shift_histories.type'
                            );
                        },

                        "attendance_records.work_location",
                    ]
                )
                    ->where('attendances.user_id', auth()->user()->id)
                    ->whereDate('attendances.in_date', $current_date)
                    ->where('attendances.business_id', auth()->user()->business_id)
                    ->orderByDesc("attendances.id")
                    ->first();
            }

            if (empty($attendance)) {
                return response()->json(NULL, 200);
            }

    $attendance->is_overlapping_time = $this->attendanceComponent->validateAttendanceRecords($attendance->in_date, $attendance->attendance_records, false);





            $user_id = intval($user_id);
            $request_user_id = auth()->user()->id;

            $hasPermission = auth()->user()->hasPermissionTo('work_shift_view');

            if ((!$hasPermission && ($request_user_id !== $user_id))) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $start_date = $attendance->in_date;
            $end_date = $attendance->in_date;



          $all_manager_department_ids = $this->get_all_departments_of_manager();
           $user = $this->validateUserQuery($user_id, $all_manager_department_ids);

            $work_shift_histories =  WorkShiftHistory::with("details")
            ->where("id", $attendance->work_shift_history_id)
            ->orderByDesc("work_shift_histories.id")
            ->get();


            $already_taken_attendance_dates = $this->attendanceComponent->get_already_taken_attendance_dates($user_id, $start_date, $end_date);

            $date_of_termination = $user->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($user->joining_date,$date_of_termination,$start_date,$end_date);

            $holiday_start_date = Carbon::parse($dates["start_date"]);
            $holiday_end_date = Carbon::parse($dates["end_date"]);

            $holiday_dates =  $this->workTimeManagementComponent->get_holiday_dates($holiday_start_date, $holiday_end_date,$user_id);

            $already_taken_leaves =  LeaveRecord::whereHas("leave", function ($query) use ($user_id) {
                $query->where("leaves.user_id", $user_id)
                    ->whereNotIn("leaves.status", ["rejected"]);
            })
                ->where('leave_records.date', '>=', $start_date)
                ->where('leave_records.date', '<=', Carbon::parse($end_date)->endOfDay())
                ->get();




            // Parse start and end dates
            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);

            $joining_date = Carbon::parse($user->joining_date);

            if ($joining_date->gt($end_date)) {
                return response()->json(
                    [
                        "message" => "Employee joining date is " . $joining_date->format('d/m/Y')
                    ],
                    409
                );
            }

            if ($joining_date->gt($start_date)) {
                $start_date = $joining_date;
            }


            // Create date range between start and end dates
            $date_range = $start_date->daysUntil($end_date);


            $dates = [];
            // Map date range to create attendance details
            // Iterate through the date range
            foreach ($date_range as $date) {
                // Convert Carbon date object to string for comparison
                $date = Carbon::parse($date);

                $business_time =   BusinessTime::where([
                    "business_id" => auth()->user()->business_id,
                    "day" => $date->dayOfWeek
                ])
                    ->first();

                // Initialize the date data array
                $date_data = [
                    "date" => $date,
                ];

                $dateToCheck = Carbon::parse($date_data["date"]);


                // Check if work shift histories are available
                if (!empty($work_shift_histories)) {
                    // Find the corresponding work shift history for the given date
                    $work_shift_history = $work_shift_histories->first(function ($history) use ($date, $end_date) {
                        $fromDate = Carbon::parse($history->from_date);
                        $toDate = $history->to_date ? Carbon::parse($history->to_date) : $end_date;

                        return $date->greaterThanOrEqualTo($fromDate)
                            && ($toDate === null || $date->lessThanOrEqualTo($toDate));
                    });


                    // If a work shift history is found, get its details
                    if (!empty($work_shift_history)) {

                        $work_shift_details = $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $date);

                        // Add work shift details to the date data array
                        $date_data["work_shift_details"]["is_weekend"] = $work_shift_details->is_weekend;

                        $date_data["work_shift_details"]["schedule_hour"] = $work_shift_details->schedule_hour;

                        $date_data["work_shift_details"]["shifts"] = $work_shift_details->shifts;

                        // $date_data["work_shift_details"]["start_at"] = $work_shift_details->start_at;
                        // $date_data["work_shift_details"]["end_at"] = $work_shift_details->end_at;


                        $date_data["work_shift_details"]["work_shift_id"] = $work_shift_details->work_shift_id;
                        $date_data["work_shift_details"]["day"] = $work_shift_details->day;
                        $date_data["work_shift_details"]["break_minutes"] = round($work_shift_history->break_hours * 60);
                        $date_data["work_shift_details"]["name"] = $work_shift_history->name;

                        $date_data["work_shift_details"]["type"] = $work_shift_history->type;
                        $date_data["work_shift_details"]["shifts"] = $work_shift_details->shifts;

                        // $work_shift_start_time = Carbon::parse($work_shift_details->start_at);
                        // $work_shift_end_time = Carbon::parse($work_shift_details->end_at);



                        if (empty($work_shift_history->is_weekend)) {
                            $available_times = [];

                            if (!empty($work_shift_details->shifts)) {
                                foreach ($work_shift_details->shifts as $shift) {
                                    $work_shift_start_time = Carbon::parse($shift['start_at']);
                                    $work_shift_end_time = Carbon::parse($shift['end_at']);

                                    $available_times[] = [
                                        "in_time" => $work_shift_start_time->format('H:i:s'),
                                        "out_time" => $work_shift_end_time->format('H:i:s'),

                                    ];
                                }
                                // Add the available times to the date data array
                                $date_data["work_shift_details"]["available_times"] = $available_times;
                            }
                        } else {

                            if (!empty($business_time->is_weekend)) {
                                $date_data["work_shift_details"]["available_times"] =     [
                                    [
                                        "in_time" => Carbon::parse("09:00")->format('H:i:s'),
                                        "out_time" => Carbon::parse("18:00")->format('H:i:s'),
                                    ]
                                ];
                                $date_data["work_shift_details"]["is_business_weekend"] = 1;
                            } else {
                                $date_data["work_shift_details"]["available_times"] =     [
                                    [
                                        "in_time" => Carbon::parse($business_time->start_at)->format('H:i:s'),
                                        "out_time" => Carbon::parse($business_time->end_at)->format('H:i:s'),

                                    ]

                                ];
                            }
                        }

                        $leave_record =  collect($already_taken_leaves)->first(function ($record) use ($dateToCheck) {
                            return Carbon::parse($record->date)->equalTo($dateToCheck);
                        });

                        if (!empty($leave_record)) {
                            $date_data["leave"] = $leave_record;
                            $date_data["is_on_leave"] = 1;
                            // Check leave type
                            if (!in_array($leave_record->leave->leave_duration, ['single_day', 'multiple_day'])) {
                                // Handle hourly leave (e.g., 10 AM to 1 PM)
                                // Remove leave hours from the work shift and add available times
                                $leave_start_time = Carbon::parse($leave_record->start_time);
                                $leave_end_time = Carbon::parse($leave_record->end_time);
                                $new_available_times = [];

                                // Before the leave
                                foreach ($available_times as $time) {
                                    $shift_start = Carbon::parse($time['in_time']);
                                    $shift_end = Carbon::parse($time['out_time']);

                                    if ($leave_start_time->gt($shift_start)) {
                                        $new_available_times[] = [
                                            "in_time" => $shift_start->format('H:i:s'),
                                            "out_time" => $leave_start_time->format('H:i:s'),
                                        ];
                                    }

                                    if ($leave_end_time->lt($shift_end)) {
                                        $new_available_times[] = [
                                            "in_time" => $leave_end_time->format('H:i:s'),
                                            "out_time" => $shift_end->format('H:i:s'),
                                        ];
                                    }
                                }

                                if (!empty($new_available_times)) {
                                    $date_data["work_shift_details"]["available_times"] = $new_available_times;
                                }
                            }
                        } else {
                            $date_data["leave"] = [];
                            $date_data["is_on_leave"] = 0;
                        }



                        if (!empty($work_shift_details->is_weekend)) {
                            $date_data["is_on_weekend"] = 1;
                        } else {
                            $date_data["is_on_weekend"] = 0;
                        }


                        $attendance = collect($already_taken_attendance_dates)->first(function ($date) use ($dateToCheck) {
                            return Carbon::parse($date)->equalTo($dateToCheck);
                        });
                        if (!empty($attendance)) {
                            $date_data["attendance_exists"] = 1;
                            $date_data["work_shift_details"]["available_times"] = [];
                        } else {
                            $date_data["attendance_exists"] = 0;
                        }

                        $holiday = collect($holiday_dates)->first(function ($date) use ($dateToCheck) {
                            return Carbon::parse($date)->equalTo($dateToCheck);
                        });

                        if (!empty($holiday)) {
                            $date_data["is_on_holiday"] = 1;
                        } else {
                            $date_data["is_on_holiday"] = 0;
                        }
                    }
                }


                // Check if there is more than one available time entry
                if (!empty($date_data["work_shift_details"]["available_times"])) {
                    if (count($date_data["work_shift_details"]["available_times"]) > 1) {
                        // If more than 1 entry, set break_hours to 0
                        foreach ($date_data["work_shift_details"]["available_times"] as &$available_time) {
                            $available_time['break_hours'] = 0;
                        }
                    } else {
                        // If only one entry, set break_hours based on the work shift
                        foreach ($date_data["work_shift_details"]["available_times"] as &$available_time) {
                            $available_time['break_hours'] = ($work_shift_history->break_hours);
                        }
                    }
                }


                // Add the date data to the dates array
                $dates[] = $date_data;
            }


            return response()->json($dates, 200);

        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }






    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/work-shifts/{ids}",
     *      operationId="deleteWorkShiftsByIds",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="1,2,3"
     *      ),
     *      summary="This method is to delete work shift by id",
     *      description="This method is to delete work shift by id",
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

    public function deleteWorkShiftsByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('work_shift_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);

            $existingIds = WorkShift::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);


            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }


            $work_shift_history_ids =   WorkShiftHistory::where([
                "to_date" => NULL
            ])
                ->whereIn("work_shift_id", $existingIds)
                ->get()
                ->pluck("id");

            $attendance_ids =    Attendance::whereIn(
                "work_shift_history_id",
                $work_shift_history_ids
            )
                ->get()->pluck("id")->toArray();

            if (!empty($attendance_ids)) {

                return response()->json([
                   'message' => 'Attendance records exist for the selected work shift(s). Please remove associated attendance records before proceeding.',
                    // "affected_users" => $affected_users
                ], 404);
            }


            WorkShiftHistory::whereIn("work_shift_id", $existingIds)->delete();


            WorkShift::destroy($existingIds);

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
