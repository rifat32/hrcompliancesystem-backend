<?php

namespace App\Http\Controllers;

use App\Exports\LeavesExport;
use App\Http\Components\AttendanceComponent;
use App\Http\Components\AuthorizationComponent;
use App\Http\Components\DepartmentComponent;

use App\Http\Components\LeaveComponent;
use App\Http\Requests\LeaveApproveRequest;
use App\Http\Requests\LeaveArrearApproveRequest;
use App\Http\Requests\LeaveBypassRequest;

use App\Http\Requests\LeaveRecordArrearApproveRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\ManipulateLeaveRequest;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\PayrunUtil;

use App\Models\Attendance;

use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveHistory;
use App\Models\LeaveRecord;
use App\Models\LeaveRecordArrear;
use App\Models\Payroll;

use App\Models\PayrollLeaveRecord;

use App\Models\SettingLeave;
use App\Models\User;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Validation\Rule;
use App\Rules\ValidateSettingLeaveType;
use App\Rules\ValidateUser;

class LeaveController extends Controller
{
    use ErrorUtil, BusinessUtil, PayrunUtil, BasicNotificationUtil, BasicUtil, ModuleUtil;

    protected $authorizationComponent;
    protected $leaveComponent;
    protected $departmentComponent;

    protected $attendanceComponent;


    public function __construct(AuthorizationComponent $authorizationComponent, LeaveComponent $leaveComponent, DepartmentComponent $departmentComponent, AttendanceComponent $attendanceComponent)
    {
        $this->authorizationComponent = $authorizationComponent;
        $this->leaveComponent = $leaveComponent;
        $this->departmentComponent = $departmentComponent;
        $this->attendanceComponent = $attendanceComponent;
    }

/**
 *
 * @OA\Get(
 *      path="/v1.0/manipulate-leave",
 *      operationId="manipulateLeaveHours",
 *      tags={"leaves"},
 *      security={
 *          {"bearerAuth": {}}
 *      },
 *      summary="Manipulate leave hours",
 *      description="This method is to manipulate leave via GET (for testing or special case use)",
 *
 *      @OA\Parameter(name="leave_duration", in="query", required=true, @OA\Schema(type="string"), example="single_day"),
 *      @OA\Parameter(name="day_type", in="query", @OA\Schema(type="string"), example="first_half"),
 *      @OA\Parameter(name="leave_type_id", in="query", required=true, @OA\Schema(type="integer"), example=2),
 *      @OA\Parameter(name="user_id", in="query", required=true, @OA\Schema(type="integer"), example=2),
 *      @OA\Parameter(name="date", in="query", @OA\Schema(type="string", format="date"), example="2023-11-03"),
 *      @OA\Parameter(name="note", in="query", @OA\Schema(type="string"), example="dfzg drfg"),
 *      @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date"), example="2023-11-22"),
 *      @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date"), example="2023-11-08"),
 *      @OA\Parameter(name="start_time", in="query", @OA\Schema(type="string", format="date-time"), example="18:00:00"),
 *      @OA\Parameter(name="end_time", in="query", @OA\Schema(type="string", format="date-time"), example="18:00:00"),
 *      @OA\Parameter(name="hourly_rate", in="query", @OA\Schema(type="number"), example="5"),
 *      @OA\Parameter(name="attachments", in="query", @OA\Schema(type="string"), example="/abcd.jpg,/efgh.jpg"),
 *
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Unprocessable Content",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=400,
 *          description="Bad Request",
 *          @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Not Found",
 *          @OA\JsonContent(),
 *      )
 * )
 */

     public function manipulateLeaveHours(Request $request)
     {

         try {
             $is_client=false;
             if(empty(auth()->user())) {
                 $is_client=true;
                 $this->logInClient();
             }

             if($is_client) {
                  if(auth()->user()->id != request()->user_id) {
                     throw new Exception("You are unauthenticated!",403);
                  }
             }



             $all_manager_department_ids = $this->get_all_departments_of_manager();
             $validator = Validator::make($request->all(), [
                 'user_id' => [
                     'required',
                     'numeric',

                     new ValidateUser($all_manager_department_ids,true),
                 ],
                 'start_date' => 'nullable|required|date',
                 'end_date' => 'nullable|required|date|after_or_equal:start_date',
             ], [
                 'leave_duration.required' => 'The leave duration field is required.',
                 'leave_duration.in' => 'Invalid value for leave duration. Valid values are: single_day, multiple_day, hours.',
                 'day_type.in' => 'Invalid value for day type. Valid values are: first_half, last_half.'
             ]);

             if ($validator->fails()) {
                 return response()->json([
                     'errors' => $validator->errors()
                 ], 422); // same as Laravel's FormRequest response
             }

             $request_data = $validator->validated(); // 💡 Equivalent of $request->validated()

             $request_data["leave_duration"] = "multiple_day";
             $request_data["day_type"] = "";


             $user_id = intval($request_data["user_id"]);

             $request_user_id = auth()->user()->id;


             if ((!auth()->user()->hasPermissionTo('leave_create') && ($request_user_id !== $user_id))) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }



             $processed_leave_data = $this->leaveComponent->processLeaveRequest($request_data);


             $response_data["total_leave_hours"] = array_sum(array_map(function ($item) {
                return (float) $item['leave_hours'];
            }, $processed_leave_data["leave_record_data_list"]));


            $response_data["total_leave_days"] = count($processed_leave_data["leave_record_data_list"]);




             return response($response_data, 200);

         } catch (Exception $e) {

             return $this->sendError($e);
         }
         finally {

             if($is_client) {
                 Auth::logout();
            }
             // Step 6: Log out the user

         }
     }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/leaves",
     *      operationId="createLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store leave",
     *      description="This method is to store leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *   @OA\Property(property="leave_duration", type="string", format="string", example="single_day"),
     *   @OA\Property(property="day_type", type="string", format="string", example="first_half"),
     *   @OA\Property(property="leave_type_id", type="integer", format="int", example=2),
     *   @OA\Property(property="user_id", type="integer", format="int", example=2),
     *   @OA\Property(property="date", type="string", format="date", example="2023-11-03"),
     *   @OA\Property(property="note", type="string", format="string", example="dfzg drfg"),
     *   @OA\Property(property="start_date", type="string", format="date", example="2023-11-22"),
     *   @OA\Property(property="end_date", type="string", format="date", example="2023-11-08"),
     *   @OA\Property(property="start_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="end_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="hourly_rate", type="number", format="number", example="5"),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
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

    public function createLeave(Request $request)
    {

        DB::beginTransaction();
        try {

            $is_client=false;

            if(empty(auth()->user())) {
                $is_client=true;
                $this->logInClient();
            }
            if($is_client) {
                 if(auth()->user()->id != request()->user_id) {
                    throw new Exception("You are unauthenticated!",403);
                 }
            }



            $all_manager_department_ids = $this->get_all_departments_of_manager(); // make sure this method is accessible in the controller

            $validator = Validator::make($request->all(), [
                'leave_duration' => ['required', Rule::in(['single_day', 'multiple_day', 'hours'])],
                'day_type' => ['nullable', Rule::in(['first_half', 'last_half'])],

                'leave_type_id' => [
                    'required',
                    'numeric',
                    new ValidateSettingLeaveType($request->user_id, null),
                ],

                'user_id' => [
                    'required',
                    'numeric',
                    new ValidateUser($all_manager_department_ids,true),
                ],

                'date' => [
                    'nullable',
                    Rule::requiredIf(function () use ($request) {
                        return in_array($request->leave_duration, ['single_day', 'hours']);
                    }),
                    'date',
                ],

                'note' => ['required', 'string'],

                'start_date' => [
                    'nullable',
                    Rule::requiredIf(function () use ($request) {
                        return $request->leave_duration === 'multiple_day';
                    }),
                    'date',
                ],
                'end_date' => [
                    'nullable',
                    Rule::requiredIf(function () use ($request) {
                        return $request->leave_duration === 'multiple_day';
                    }),
                    'date',
                    'after_or_equal:start_date',
                ],

                'start_time' => [
                    'nullable',
                    Rule::requiredIf(function () use ($request) {
                        return $request->leave_duration === 'hours';
                    }),
                    'date_format:H:i:s',
                ],
                'end_time' => [
                    'nullable',
                    Rule::requiredIf(function () use ($request) {
                        return $request->leave_duration === 'hours';
                    }),
                    'date_format:H:i:s',
                    'after_or_equal:start_time',
                ],

                'attachments' => ['present', 'array'],
                'attachments.*' => ['string'],

                'hourly_rate' => ['required', 'numeric'],
                'work_shift_history_detail_id' => ['nullable', 'string'],
                'year' => ['required', 'integer'],
            ], [
                'leave_duration.required' => 'The leave duration field is required.',
                'leave_duration.in' => 'Invalid value for leave duration. Valid values are: single_day, multiple_day, hours.',
                'day_type.in' => 'Invalid value for day type. Valid values are: first_half, last_half.',
                // Add more custom messages if needed
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $request_data = $validator->validated();


            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }


            $user_id = intval($request_data["user_id"]);

            $request_user_id = auth()->user()->id;


            if ((!auth()->user()->hasPermissionTo('leave_create') && ($request_user_id !== $user_id))) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"], "", "leave_attachments");

            $this->makeFilePermanent($request_data["attachments"], "");



            $processed_leave_data = $this->leaveComponent->processLeaveRequest($request_data);

            $leave =  Leave::create($processed_leave_data["leave_data"]);


            $leaveRecordsCollection = collect($processed_leave_data["leave_record_data_list"]);
            // Define chunk size (e.g., 100 records per chunk)
            $chunkSize = 1000;
            // Chunk the collection and insert each chunk
            $leaveRecordsCollection->chunk($chunkSize)->each(function ($chunk) use ($leave) {
                $leave->records()->createMany($chunk->toArray());
            });



            $leave_records = $leave->records()->get();

            $this->leaveComponent->createLeaveAvailability($leave, $leave_records, $request_data["year"]);





            // $leaveObserver = new LeaveObserver();
            // $leaveObserver->create($leave);

            $leave_history_data = $leave->toArray();
            $leave_history_data['leave_id'] = $leave->id;
            $leave_history_data['actor_id'] = auth()->user()->id;
            $leave_history_data['action'] = "create";
            $leave_history_data['is_approved'] = NULL;
            $leave_history_data['leave_created_at'] = $leave->created_at;
            $leave_history_data['leave_updated_at'] = $leave->updated_at;
            $leave_history = LeaveHistory::create($leave_history_data);



            // Chunk the collection and insert each chunk
            $leaveRecordsCollection->chunk($chunkSize)->each(function ($chunk) use ($leave_history) {
                $leave_history->records()->createMany($chunk->toArray());
            });



            $this->send_notification($leave, $leave->employee, "Leave Request Taken", "create", "leave");


            $leave_records = $leave->records;
            foreach ($leave_records as $leave_record) {
                $this->adjust_payroll_on_leave_update($leave_record, 0);
            }



            // $this->moveUploadedFiles($request_data["attachments"],"leave_request_docs");




            DB::commit();

            return response($leave, 200);
        } catch (Exception $e) {
            DB::rollBack();



            try {
                $this->moveUploadedFilesBack($request_data["attachments"], "", "leave_attachments");
            } catch (Exception $innerException) {
            }


            return $this->sendError($e);
        }
        finally {

            if($is_client) {
                Auth::logout();
           }
            // Step 6: Log out the user

        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/leaves/approve",
     *      operationId="approveLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve leave ",
     *      description="This method is to approve leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="leave_id", type="number", format="number", example="Updated Christmas"),
     *   @OA\Property(property="is_approved", type="boolean", format="boolean", example="1")


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

    public function approveLeave(LeaveApproveRequest $request)
    {

        DB::beginTransaction();
        try {



            if (!$request->user()->hasPermissionTo('leave_approve')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $request_data = $request->validated();


            $leave = Leave::where([
                "id" => $request_data["leave_id"]
            ])
                ->where('leaves.business_id', '=', auth()->user()->business_id)
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->whereHas("employee", function ($query) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })

                ->first();

            $this->restrict_leaves_modification_for_payroll($leave->records);

            if (empty($leave)) {
                throw new Exception("No leave request found", 400);
            }


            $previous_leave_status = $leave->status;


            $this->touchUserUpdatedAt([$leave->user_id]);


            $request_data["created_by"] = $request->user()->id;

            $leave_approval =  LeaveApproval::where([
                'leave_id' => $request_data["leave_id"],
                "created_by" => auth()->user()->id
            ])
                ->first();

            if (!empty($leave_approval)) {
                $leave_approval->is_approved = $request_data["is_approved"];
                $leave_approval->save();
            } else {
                $leave_approval =  LeaveApproval::create($request_data);
            }





            if (!$leave_approval) {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }



            $this->leaveComponent->processLeaveApproval($leave, $request_data["is_approved"]);

            $leave = $leave->refresh();
            $leave_status = $leave->status;


            if ($previous_leave_status != $leave_status) {
                if ($leave_status == "rejected") {
                    $this->leaveComponent->deleteLeaveAvailability($leave, $leave->total_leave_hours);
                } else if ($previous_leave_status == "rejected") {
                    $this->leaveComponent->addLeaveAvailability($leave, $leave->total_leave_hours);
                }
            }

                $leave_records =   $leave->records;
                $leave_record_ids = $leave_records->pluck("id")->toArray();
                $attendances = Attendance::whereIn("leave_record_id", $leave_record_ids)
                ->where("consider_overtime",1)
                    ->get();
                $this->attendanceComponent->updateAttendanceOverTime($attendances);








            $leave_history_data = $leave->toArray();
            $leave_history_data['leave_id'] = $leave->id;
            $leave_history_data['actor_id'] = auth()->user()->id;

            $leave_history_data['action'] = $request_data["is_approved"] ? "approve" : "reject";

            $leave_history_data['is_approved'] =  $request_data['is_approved'];
            $leave_history_data['leave_created_at'] = $leave->created_at;
            $leave_history_data['leave_updated_at'] = $leave->updated_at;


            $leave_history = LeaveHistory::create($leave_history_data);


            $chunkSize = 1000;

            // Fetch the records with the necessary fields
            $leave_records = $leave->records()->get(['date', 'start_time', 'end_time', 'capacity_hours', 'leave_hours']);

            // Chunk the records and insert each chunk
            $leave_records->chunk($chunkSize, function ($chunk) use ($leave_history) {

                // Convert the chunk to an array
                $data = $chunk->toArray();
                // Bulk insert the chunk data
                $leave_history->records()->createMany($data);
            });





            if ($request_data["is_approved"]) {
                $this->send_notification($leave, $leave->employee, "Leave Request Approved", "approve", "leave");
            } else {
                $this->send_notification($leave, $leave->employee, "Leave Request Rejected", "reject", "leave");
            }

            $leave = $leave->refresh();
            $leave_records = $leave->records;
            foreach ($leave_records as $leave_record) {
                $this->adjust_payroll_on_leave_update($leave_record, $request_data["add_in_next_payroll"]);
            }

            if (!empty($request_data["add_in_next_payroll"]) && ($leave->status ==
            "approved")) {
            LeaveRecordArrear::whereHas("leave_record", function ($query) use ($leave) {
                $query
                    ->whereIn("leave_records.id", $leave->records()->pluck("leave_records.id"));
            })
                ->update(["status" => "approved"]);
           }

            DB::commit();
            return response($leave_approval, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/leaves/bypass",
     *      operationId="bypassLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve leave ",
     *      description="This method is to approve leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="leave_id", type="number", format="number", example="Updated Christmas"),
     *    @OA\Property(property="add_in_next_payroll", type="number", format="number", example="1")
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

    public function bypassLeave(LeaveBypassRequest $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('leave_approve')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();
            $request_data["created_by"] = $request->user()->id;

            $setting_leave = SettingLeave::where([
                "business_id" => auth()->user()->business_id,
                "is_default" => 0
            ])->first();

            if (!$setting_leave->allow_bypass) {

                return response([
                    "message" => "bypass not allowed"
                ], 400);
            }

            $leave = Leave::where([
                "id" => $request_data["leave_id"],
                "business_id" => auth()->user()->business_id
            ])
                ->first();

            if (!$leave) {

                return response([
                    "message" => "no leave found"
                ], 400);
            }

            $previous_leave_status = $leave->status;

            $this->restrict_leaves_modification_for_payroll($leave->records);;

            $this->touchUserUpdatedAt([$leave->user_id]);

            $leave->status = "approved";
            $leave->save();


            $leave = $leave->refresh();
            $leave_status = $leave->status;

            if ($previous_leave_status != $leave_status) {
                if ($leave_status == "rejected") {
                    $this->leaveComponent->deleteLeaveAvailability($leave, $leave->total_leave_hours);
                } else if ($previous_leave_status == "rejected") {
                    $this->leaveComponent->addLeaveAvailability($leave, $leave->total_leave_hours);
                }
            }

            $leave_records =   $leave->records;
            $leave_record_ids = $leave_records->pluck("id")->toArray();
            $attendances = Attendance::whereIn("leave_record_id", $leave_record_ids)
            ->where("consider_overtime",1)
                ->get();
            $this->attendanceComponent->updateAttendanceOverTime($attendances);

            $leave_records = $leave->records;
            foreach ($leave_records as $leave_record) {
                $this->adjust_payroll_on_leave_update($leave_record, $request_data["add_in_next_payroll"]);
            }


            if (!empty($request_data["add_in_next_payroll"])) {
                LeaveRecordArrear::whereHas("leave_record", function ($query) use ($leave) {
                    $query
                        ->whereIn("leave_records.id", $leave->records()->pluck("leave_records.id"));
                })
                    ->update(["status" => "approved"]);
            }




            $leave_history_data = $leave->toArray();
            $leave_history_data['leave_id'] = $leave->id;
            $leave_history_data['actor_id'] = auth()->user()->id;
            $leave_history_data['action'] = "bypass";
            $leave_history_data['is_approved'] = NULL;
            $leave_history_data['leave_created_at'] = $leave->created_at;
            $leave_history_data['leave_updated_at'] = $leave->updated_at;


            $this->send_notification($leave, $leave->employee, "Leave Request Approved", "approve", "leave");



            DB::commit();
            return response($leave, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

 /**
     *
     * @OA\Put(
     *      path="/v1.0/leave/approve/arrears",
     *      operationId="approveLeaveArrear",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve attendances ",
     *      description="This method is to approve attendances",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="leave_record_id", type="number", format="number", example="1"),

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

     public function approveLeaveArrear(LeaveArrearApproveRequest $request)
     {

         DB::beginTransaction();
         try {



             // Check permission to approve attendance
             if (!$request->user()->hasPermissionTo("leave_approve")) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             // Extract data
             $request_data = $request->validated();

             $leave = Leave::where([
                "id" => $request_data["id"]
             ])
             ->first();

             if(empty($leave)){
                   throw new Exception("something went wrong!",400);
             }

            if($leave->status != "approved") {
                throw new Exception("Cannot approve leave arrear because the associated leave is not approved.",409);
            }

             foreach ($leave->records as $leave_record) {


                 $leave_record_arrear = LeaveRecordArrear::where([
                     "leave_record_id" => $leave_record->id
                 ])
                     ->first();

                 if ($leave_record_arrear) {
                     if ($leave_record_arrear->status == "pending_approval") {
                         $leave_record_arrear->status = "approved";
                         $leave_record_arrear->save();
                     }
                 }
             }


             DB::commit();
             return response($leave_record_arrear, 200);
         } catch (Exception $e) {
             DB::rollBack();
             return $this->sendError($e);
         }
     }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/leave-records/approve/arrears",
     *      operationId="approveLeaveRecordArrear",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to approve attendances ",
     *      description="This method is to approve attendances",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="leave_record_id", type="number", format="number", example="1"),

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

    public function approveLeaveRecordArrear(LeaveRecordArrearApproveRequest $request)
    {

        DB::beginTransaction();
        try {



            // Check permission to approve attendance
            if (!$request->user()->hasPermissionTo("leave_approve")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            // Extract data
            $request_data = $request->validated();


            foreach ($request_data["leave_record_ids"] as $leave_record_id) {

            $leave_record = LeaveRecord::where([
                "id" => $leave_record_id
             ])
             ->first();

             if($leave_record->leave->status != "approved") {
                throw new Exception("Cannot approve leave record arrear because the associated leave is not approved.",409);
            }



                $leave_record_arrear = LeaveRecordArrear::where([
                    "leave_record_id" => $leave_record_id
                ])
                    ->first();

                if ($leave_record_arrear) {
                    if ($leave_record_arrear->status == "pending_approval") {
                        $leave_record_arrear->status = "approved";
                        $leave_record_arrear->save();
                    }
                }
            }


            DB::commit();
            return response($leave_record_arrear, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/leaves",
     *      operationId="updateLeave",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update leave ",
     *      description="This method is to update leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *   @OA\Property(property="leave_duration", type="string", format="string", example="single_day"),
     *   @OA\Property(property="day_type", type="string", format="string", example="first_half"),
     *   @OA\Property(property="leave_type_id", type="integer", format="int", example=2),
     *   @OA\Property(property="user_id", type="integer", format="int", example=2),
     *   @OA\Property(property="date", type="string", format="date", example="2023-11-03"),
     *   @OA\Property(property="note", type="string", format="string", example="dfzg drfg"),
     *   @OA\Property(property="start_date", type="string", format="date", example="2023-11-22"),
     *   @OA\Property(property="end_date", type="string", format="date", example="2023-11-08"),
     *   @OA\Property(property="start_time", type="string", format="date-time", example="18:00:00"),
     *   @OA\Property(property="end_time", type="string", format="date-time", example="18:00:00"),
     *    *   @OA\Property(property="hourly_rate", type="number", format="number", example="5"),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})

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

    public function updateLeave(LeaveUpdateRequest $request)
    {

        DB::beginTransaction();
        try {



            if (!$request->user()->hasPermissionTo('leave_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $request_data = $request->validated();

            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }


            $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"], "", "leave_attachments");
            $this->makeFilePermanent($request_data["attachments"], "");

            $leaveRecords = LeaveRecord::where([
                "leave_id" => $request_data["id"]
            ])
                ->get();


            $previous_leave_record_ids = $leaveRecords->pluck("id")->toArray();


            $processed_leave_data = $this->leaveComponent->processLeaveRequest($request_data);

            $leave_query_params = [
                "id" => $request_data["id"],
                "business_id" => $business_id
            ];

            $leave = Leave::where($leave_query_params)->first();

            if (!$leave) {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }

            $this->leaveComponent->deleteLeaveAvailability($leave, $leave->total_leave_hours);

            $this->restrict_leaves_modification_for_payroll($leave->records);


            $leave->fill(
                collect($processed_leave_data["leave_data"])->only([
                    'leave_duration',
                    'day_type',
                    'leave_type_id',
                    'user_id',
                    'date',
                    'note',
                    'start_date',
                    'end_date',
                    'attachments',
                    'hourly_rate',
                    "work_shift_history_detail_id"
                ])->toArray()
            );

            if (!$leave->save()) {
                return response()->json([
                    "message" => "Failed to update leave."
                ], 500);
            }



            // Get the IDs of existing leave records
            $existingRecordIds = $leave->records()->pluck('id');
            $existingRecordDates = $leave->records()->pluck('date');

            $recordsToDelete = $existingRecordDates->filter(function ($existingDate) use ($processed_leave_data) {
                return !collect($processed_leave_data["leave_record_data_list"])->contains(function ($processedDate) use ($existingDate) {
                    return Carbon::parse($existingDate)->isSameDay(Carbon::parse($processedDate['date'])); // Parse inside the comparison
                });
            });




            $recordDataList = collect($processed_leave_data["leave_record_data_list"]);

            // Separate collections for updates and inserts
            $leaveRecordsToUpdate = collect();
            $leaveRecordsToInsert = collect();

            // Partition records into updates and inserts based on date comparison
            $recordDataList->each(function ($recordData) use ($leave, $existingRecordIds, &$leaveRecordsToUpdate, &$leaveRecordsToInsert) {
                $recordData["leave_id"] = $leave->id;

                // Compare the date in the record with the existing records' dates
                $existingRecord = $existingRecordIds->first(function ($existingRecordId) use ($recordData, $leave) {
                    return $leave->records()->where('id', $existingRecordId)
                        ->whereDate('date', Carbon::parse($recordData['date'])->toDateString())
                        ->exists();
                });

                // Check if the record exists based on ID or date match
                if ($existingRecord) {
                    // Add to update collection if found
                    $leaveRecordsToUpdate->push($recordData);
                } else {
                    // Add to insert collection if not found
                    $leaveRecordsToInsert->push($recordData);
                }
            });



            // Perform bulk updates using updateOrCreate
            $leaveRecordsToUpdate->each(function ($data) use ($leave) {
                LeaveRecord::whereDate("date", $data["date"])
                    ->where("leave_id", $leave->id)
                    ->update(collect($data)->only([
                        'date',
                        'start_time',
                        'end_time',
                        "capacity_hours",
                        "leave_hours",
                    ])->toArray());
            });




            // Perform bulk inserts using insert method for efficiency
            if ($leaveRecordsToInsert->isNotEmpty()) {
                $chunkSize = 1000;
                $leaveRecordsToInsert->chunk($chunkSize)->each(function ($chunk) use ($leave) {
                    $leave->records()->createMany($chunk->toArray());
                });
            }


            $leave->records()->whereIn('date', $recordsToDelete)->delete();


            // $payrolls = Payroll::whereHas("payroll_leave_records", function ($query) use ($recordsToDelete) {
            //     $query->whereIn("payroll_leave_records.leave_record_id", $recordsToDelete);
            // })->get();

            // PayrollLeaveRecord::whereIn("leave_record_id", $recordsToDelete)
            //     ->delete();



            $leave_records = $leave->records()->get();
            $total_recorded_hours = $leave_records->sum('leave_hours');
            $leave->total_leave_hours = $total_recorded_hours;
            $leave->save();
            $this->leaveComponent->addLeaveAvailability($leave, $total_recorded_hours);





            $leave_history_data = $leave->toArray();
            $leave_history_data['leave_id'] = $leave->id;
            $leave_history_data['actor_id'] = auth()->user()->id;
            $leave_history_data['action'] = "update";
            $leave_history_data['is_approved'] = NULL;
            $leave_history_data['leave_created_at'] = $leave->created_at;
            $leave_history_data['leave_updated_at'] = $leave->updated_at;
            $leave_history = LeaveHistory::create($leave_history_data);

            $leave_record_history = $leave->records->toArray();
            $leave_record_history["leave_id"] = $leave_history->id;


            // Perform bulk inserts using insert method for efficiency
            if ($recordDataList->isNotEmpty()) {
                $chunkSize = 1000;
                $recordDataList->chunk($chunkSize)->each(function ($chunk) use ($leave_history) {
                    $leave_history->records()->createMany($chunk->toArray());
                });
            }





            $previous_leave_record_ids = $leaveRecords->pluck("id")->toArray();


            $attendances = Attendance::whereIn("leave_record_id", $previous_leave_record_ids)
            ->where("consider_overtime",1)
                ->get();

            $this->attendanceComponent->updateAttendanceOverTime($attendances);

            $this->send_notification($leave, $leave->employee, "Leave Request Updated", "update", "leave");


            $leave_records = $leave->records;
            foreach ($leave_records as $leave_record) {
                $this->adjust_payroll_on_leave_update($leave_record, 0);
            }


            // $this->moveUploadedFiles($request_data["attachments"],"leave_request_docs");

            DB::commit();
            return response($leave, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/leaves",
     *      operationId="getLeaves",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
     *   *              @OA\Parameter(
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
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="leave_type_id",
     * in="query",
     * description="leave_type_id",
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
     * * @OA\Parameter(
     *     name="leave_date_time",
     *     in="query",
     *     description="Leave Date and Time",
     *     required=true,
     *     example="2024-02-14 08:00:00"
     * ),
     * @OA\Parameter(
     *     name="leave_type",
     *     in="query",
     *     description="Leave Type",
     *     required=true,
     *     example="Sick Leave"
     * ),
     * @OA\Parameter(
     *     name="leave_duration",
     *     in="query",
     *     description="Leave Duration",
     *     required=true,
     *     example="8"
     * ),
     *  * @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="status",
     *     required=true,
     *     example="status"
     * ),
     * @OA\Parameter(
     *     name="total_leave_hours",
     *     in="query",
     *     description="Total Leave Hours",
     *     required=true,
     *     example="8"
     * ),

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeaves(Request $request)
    {
        try {



            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('leave_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();

            $business_id =  auth()->user()->business_id;

            $leavesQuery = Leave::where(
                [
                    "leaves.business_id" => $business_id
                ]
            );


            $leavesQuery =   $this->leaveComponent->updateLeavesQuery($all_manager_department_ids, $leavesQuery);

            $leaves = $this->retrieveData($leavesQuery, "id", "leaves");



            foreach ($leaves as $leave) {
                $leave->total_leave_hours = $leave->records->sum(function ($record) {
                    return round($record->leave_hours, 2); // Converts seconds to hours and rounds to 2 decimals
                });

                foreach ($leave->records as $record) {

                    if (!empty($record->work_shift_history)) {

                        $record->work_shift_history->detail = collect($record->work_shift_history->details)
                            ->filter(function ($detail) use ($record) {
                                $day_number = Carbon::parse($record->date)->dayOfWeek;
                                return $day_number === $detail["day"];
                            })
                            ->values()  // Re-index the array
                            ->all();
                    }
                }
            }




            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {

                    if (empty($leaves->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.leaves', ["leaves" => $leaves]);
                    }
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new LeavesExport($leaves), ((!empty($request->file_name) ? $request->file_name : 'leave') . '.csv'));
                }
            } else {
                return response()->json($leaves, 200);
            }
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/leave-arrears",
     *      operationId="getLeaveArrears",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
     *   *              @OA\Parameter(
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
     *      *      * *  @OA\Parameter(
     * name="arrear_status",
     * in="query",
     * description="arrear_status",
     * required=true,
     * example="arrear_status"
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
     *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="leave_type_id",
     * in="query",
     * description="leave_type_id",
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
     * * @OA\Parameter(
     *     name="leave_date_time",
     *     in="query",
     *     description="Leave Date and Time",
     *     required=true,
     *     example="2024-02-14 08:00:00"
     * ),
     * @OA\Parameter(
     *     name="leave_type",
     *     in="query",
     *     description="Leave Type",
     *     required=true,
     *     example="Sick Leave"
     * ),
     * @OA\Parameter(
     *     name="leave_duration",
     *     in="query",
     *     description="Leave Duration",
     *     required=true,
     *     example="8"
     * ),
     *  * @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="status",
     *     required=true,
     *     example="status"
     * ),
     * @OA\Parameter(
     *     name="total_leave_hours",
     *     in="query",
     *     description="Total Leave Hours",
     *     required=true,
     *     example="8"
     * ),

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeaveArrears(Request $request)
    {
        try {


            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('leave_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();






            $leavesQuery =  LeaveRecord::with([
                "arrear",
                "leave.employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name',
                        'users.image'
                    );
                },
                "leave.employee.departments" => function ($query) {
                    // You can select specific fields from the departments table if needed
                    $query->select(
                        'departments.id',
                        'departments.name',
                        "departments.description"
                    );
                },
                "leave.leave_type" => function ($query) {
                    $query->select(
                        'setting_leave_types.id',
                        'setting_leave_types.name',
                        'setting_leave_types.type',
                        'setting_leave_types.amount',

                    );
                },

            ])
                ->when(
                    !empty($request->arrear_status),
                    function ($query) use ($request) {
                        $query->whereHas("arrear", function ($query) use ($request) {
                            $query
                                ->where(
                                    "leave_record_arrears.status",
                                    $request->arrear_status
                                );
                        });
                    },
                    function ($query) use ($request) {
                        $query->whereHas("arrear", function ($query) use ($request) {
                            $query
                                ->whereNotNull(
                                    "leave_record_arrears.status"
                                );
                        });
                    }
                )

                ->whereHas("leave", function($query) use($all_manager_department_ids) {
                       $query
                       ->where('leaves.status', "approved")
                       ->when(!empty(request()->user_id), function ($query) {
                        $query->where('leaves.user_id', request()->user_id);
                    })

                    ->when(
                        request()->boolean('show_all_data'),
                        function ($query) use ($all_manager_department_ids) {
                            $query->where(function ($query) use ($all_manager_department_ids) {

                                $query->where('leaves.user_id', auth()->user()->id)
                                    ->orWhere(function ($query) use ($all_manager_department_ids) {
                                        $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                                            $query->whereIn("departments.id", $all_manager_department_ids);
                                        })
                                            ->whereNotIn('leaves.user_id', [auth()->user()->id]);
                                    });
                            });
                        },
                        function ($query) use ($all_manager_department_ids) {
                            $query->when(
                                (request()->boolean('show_my_data')),
                                function ($query) {
                                    $query->where('leaves.user_id', auth()->user()->id);
                                },
                                function ($query) use ($all_manager_department_ids,) {

                                    $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                                        $query->whereIn("departments.id", $all_manager_department_ids);
                                    })
                                        ->whereNotIn('leaves.user_id', [auth()->user()->id]);;
                                }
                            );
                        }
                    )


                    ->when(!empty(request()->leave_type_id), function ($query) {
                        $query->where('leaves.leave_type_id', request()->leave_type_id);
                    })
                    ->when(!empty(request()->status), function ($query) {
                        $query->where('leaves.status', request()->status);
                    })
                    ->when((request()->filled("department_id")), function ($query) {
                        $query->whereHas("employee.departments", function ($query) {
                            $query->where("departments.id", request()->department_id);
                        });
                    });


                })
                ->when(!empty(request()->start_date), function ($query) {
                    $query->whereDate('leave_records.date', '>=', request()->start_date);
                })
                ->when(!empty(request()->end_date), function ($query) {
                    $query->whereDate('leave_records.date', '<=', request()->end_date);
                });



            $leave_records = $this->retrieveData($leavesQuery, "id", "leave_records");

            return response()->json($leave_records, 200);

        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v2.0/leaves",
     *      operationId="getLeavesV2",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
     *   *              @OA\Parameter(
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
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *  * @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="status",
     *     required=true,
     *     example="status"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeavesV2(Request $request)
    {
        try {


            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('leave_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();


            $leavesQuery =  Leave::with([
                "employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name',
                        'users.image'
                    );
                },
                "employee.departments" => function ($query) {
                    // You can select specific fields from the departments table if needed
                    $query->select(
                        'departments.id',
                        'departments.name',
                        "departments.description"
                    );
                },
                "leave_type" => function ($query) {
                    $query->select(
                        'setting_leave_types.id',
                        'setting_leave_types.name',
                        'setting_leave_types.type',
                        'setting_leave_types.amount',

                    );
                },

            ]);


            $leavesQuery =   $this->leaveComponent->updateLeavesQuery($all_manager_department_ids, $leavesQuery);

            $leaves = $this->retrieveData($leavesQuery, "id", "leaves");




            foreach ($leaves as $leave) {

                $leave_record_ids = $leave->records->pluck("id");

                $leave->is_attedance_exists = Attendance::whereIn("leave_record_id",$leave_record_ids)->exists();
            }


            // Get unique user IDs from leaves
            $uniqueUserIds = $leaves->pluck('user_id')->unique();
            $data["data"] = $leaves;
            $data["data_highlights"] = [];
            $data["data_highlights"]["employees_on_leave"] = $uniqueUserIds->count();

            $data["data_highlights"]["total_leave_hours"] = $leaves->reduce(function ($carry, $leave) {
                return $carry + $leave->records->sum(function ($record) {
                    if (
                        (request()->filled('start_date') && Carbon::parse($record->date)->lt(Carbon::parse(request('start_date')))) ||
                        (request()->filled('end_date') && Carbon::parse($record->date)->gt(Carbon::parse(request('end_date'))))
                    ) {
                        return 0;
                    }
                    return round($record->leave_hours, 2);
                });
            }, 0);

            $data["data_highlights"]["single_day_leaves"] = $leaves->filter(function ($leave) {
                return $leave->leave_duration == "single_day";
            })->pluck('user_id')->unique()->count();

            $data["data_highlights"]["multiple_day_leaves"] = $leaves->filter(function ($leave) {
                return $leave->leave_duration == "multiple_day";
            })->pluck('user_id')->unique()->count();


            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($leaves->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.leaves', ["leaves" => $leaves]);
                    }
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {
                    return Excel::download(new LeavesExport($leaves), ((!empty($request->file_name) ? $request->file_name : 'leave') . '.csv'));
                }
            } else {
                return response()->json($data, 200);
            }


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v3.0/leaves",
     *      operationId="getLeavesV3",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
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
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *  * @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="status",
     *     required=true,
     *     example="status"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeavesV3(Request $request)
    {
        try {


            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('leave_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;
            $employeesQuery = User::with(
                [
                    'leaves' => function ($query) use ($request) {
                        $query->when(!empty($request->start_date), function ($query) use ($request) {
                            return $query->where('start_date', '>=', ($request->start_date . ' 00:00:00'));
                        })
                            ->when(!empty($request->end_date), function ($query) use ($request) {
                                return $query->where('end_date', '<=', ($request->end_date . ' 23:59:59'));
                            });
                    },
                    'departments' => function ($query) use ($request) {
                        $query->select("departments.name");
                    },




                ]
            )

                ->when(!empty($all_manager_department_ids), function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    })
                        // ->whereNotIn('users.id', [auth()->user()->id])
                    ;
                }, function ($query) {
                    $query->where('users.id', auth()->user()->id);
                })

                ->whereHas("leaves", function ($q) use ($request) {
                    $q->whereNotNull("user_id")
                        ->when(!empty($request->user_id), function ($q) use ($request) {
                            $q->where('user_id', $request->user_id);
                        })
                        ->when(!empty($request->start_date), function ($q) use ($request) {
                            $q->where('start_date', '>=', $request->start_date . ' 00:00:00');
                        })
                        ->when(!empty($request->end_date), function ($q) use ($request) {
                            $q->where('end_date', '<=', ($request->end_date . ' 23:59:59'));
                        });
                })
                ->where(
                    [
                        "users.business_id" => $business_id
                    ]
                )
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('leaves.status', $request->status);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;

                    });
                })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("leaves", function ($q) use ($request) {
                        $q->where('user_id', $request->user_id);
                    });
                })
                ->select(
                    "users.id",
                    'users.title',
                    "users.first_Name",
                    "users.middle_Name",
                    "users.last_Name",
                    "users.image",
                );

                $employees =  $this->retrieveData($employeesQuery, "first_Name", "users");

            if ((!empty($request->start_date) && !empty($request->end_date))) {

                $startDate = Carbon::parse(($request->start_date . ' 00:00:00'));
                $endDate = Carbon::parse(($request->end_date . ' 23:59:59'));
                $dateArray = [];



                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    $dateArray[] = $date->format('Y-m-d');
                }

                // while ($startDate->lte($endDate)) {
                //     $dateArray[] = $startDate->toDateString();
                //     $startDate->addDay();
                // }




                $employees->each(function ($employee) use ($dateArray) {
                    // Get leaves for the current employee


                    $total_leave_hours = 0;

                    $employee->datewise_leave = collect($dateArray)->map(function ($date) use ($employee, &$total_leave_hours) {


                        $leave_record = LeaveRecord::whereHas(
                            "leave.employee",
                            function ($query) use ($employee, $date) {
                                $query->where([
                                    "users.id" => $employee->id,
                                    "leave_records.date" => $date
                                ]);
                            }
                        )
                            ->first();



                        if ($leave_record) {
                            $total_leave_hours += round($leave_record->leave_hours, 2);
                            return [
                                'date' => Carbon::parse($date)->format("Y-m-d"),
                                'is_on_leave' => $leave_record ? 1 : 0,
                                'leave_hours' => $leave_record->leave_hours,

                            ];
                        }
                        return null;
                    })->filter()->values();

                    $employee->total_leave_hours = $total_leave_hours;
                    $employee->unsetRelation('leaves');
                    return $employee;
                });
            }







            return response()->json($employees, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v4.0/leaves",
     *      operationId="getLeavesV4",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
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
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *  * @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="status",
     *     required=true,
     *     example="status"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeavesV4(Request $request)
    {
        try {


            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('leave_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $data = $this->leaveComponent->getLeaveV4Func();


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/leaves-get-dates-for-leave-year/{year}",
     *      operationId="getLeaveDatesForLeaveYear",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *
     *      @OA\Parameter(
     * name="year",
     * in="path",
     * description="year",
     * required=true,
     * example="2025"
     * ),
     *

     *      summary="This method is to get leaves  ",
     *      description="This method is to get leaves ",
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

    public function getLeaveDatesForLeaveYear($year, Request $request)
    {
        try {
            $is_client=false;

            if(empty(auth()->user())) {
                $is_client=true;
                $this->logInClient();
            }
            if($is_client) {
                 if(auth()->user()->id != request()->user_id) {
                    throw new Exception("You are unauthenticated!",403);
                 }
            }



            $responseData = $this->leaveComponent->calculateLeaveDates($year);


            return response()->json($responseData, 200);
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
     *      path="/v1.0/leaves/{id}",
     *      operationId="getLeaveById",
     *      tags={"leaves"},
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
     *      summary="This method is to get leave by id",
     *      description="This method is to get leave by id",
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


    public function getLeaveById($id, Request $request)
    {
        try {


            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('leave_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;
            $leave = Leave::withCount(['records as leave_records_with_attendance_count' => function ($query) {
                    $query->whereHas('attendance');
                }])
                ->with("employee_leave_allowance")
                ->where([
                    "id" => $id,
                    "business_id" => $business_id
                ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->first();

            if (!$leave) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            if ($leave->leave_duration == "hours") {

                foreach ($leave->records as $record) {
                    $workShiftHistory = $record->work_shift_history()->select(
                        'work_shift_histories.id',
                        'work_shift_histories.name',
                        'work_shift_histories.break_type',
                        'work_shift_histories.break_hours',
                        'work_shift_histories.type'
                    )->first();

                    if ($workShiftHistory) {
                        $workShiftHistory->detail = collect($workShiftHistory->details)
                            ->filter(function ($detail) use ($record) {
                                $day_number = Carbon::parse($record->date)->dayOfWeek;
                                return $day_number === $detail["day"];
                            })
                            ->values() // Re-index the array
                            ->all();
                        unset($workShiftHistory->details);

                        $leave->work_shift_history = $workShiftHistory;
                    }
                }
            }



            return response()->json($leave, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/leaves-get-current-hourly-rate",
     *      operationId="getLeaveCurrentHourlyRate",
     *      tags={"leaves"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *  *
     *    *      *    * *  @OA\Parameter(
     * name="date",
     * in="query",
     * description="date",
     * required=true,
     * example="date"
     * ),
     *
     *      summary="This method is to get leave current hourly rate",
     *      description="This method is to get leave current hourly rate",
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


    public function getLeaveCurrentHourlyRate(Request $request)
    {
        try {


            $user_id = intval($request->user_id);
            $request_user_id = auth()->user()->id;
            if (!$request->user()->hasPermissionTo('leave_create') && ($request_user_id !== $user_id)) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user =    $this->validateUserQuery($user_id, $all_manager_department_ids);


            $salary_info = $this->get_salary_info((!empty($user_id) ? $user_id : auth()->user()->id), (!empty($request->date) ? $request->date : today()));
            $salary_info["hourly_salary"] =  number_format($salary_info["hourly_salary"], 2);
            $salary_info["overtime_salary_per_hour"] = number_format($salary_info["overtime_salary_per_hour"], 2);
            $salary_info["holiday_considered_hours"] = number_format($salary_info["holiday_considered_hours"], 2);


            return response()->json($salary_info, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/leaves/{ids}",
     *      operationId="deleteLeavesByIds",
     *      tags={"leaves"},
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
     *      summary="This method is to delete leave by id",
     *      description="This method is to delete leave by id",
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

    public function deleteLeavesByIds(Request $request, $ids)
    {
        DB::beginTransaction();
        try {

            if (!$request->user()->hasPermissionTo('leave_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("leaves", function ($query) use ($idsArray) {
                $query->whereIn('leaves.id', $idsArray);
            })
                ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);




            $existingIds = Leave::where([
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->whereHas("employee", function ($query) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })

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

            $leaves =  Leave::whereIn("id", $existingIds)->get();

            foreach ($leaves as $leave) {


                $leave_records = $leave->records;
                $this->restrict_leaves_modification_for_payroll($leave_records);

                if ($leave->status != "rejected") {
                    $this->leaveComponent->deleteLeaveAvailability($leave, $leave->total_leave_hours);
                }
            }








            Leave::destroy($existingIds);


            foreach ($leaves as $leave) {

                $leave_record_ids = $leave_records->pluck("id")->toArray();
                $attendances = Attendance::whereIn("leave_record_id", $leave_record_ids)
                ->where("consider_overtime",1)
                    ->get();

                $this->attendanceComponent->updateAttendanceOverTime($attendances);
            }

            $this->send_notification($leaves, $leaves->first()->employee, "Leave Request Deleted", "delete", "leave");

            DB::commit();
            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }
}
