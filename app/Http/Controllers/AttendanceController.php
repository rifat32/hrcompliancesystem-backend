<?php

namespace App\Http\Controllers;

use App\Exports\AttendancesExport;

use App\Http\Components\AttendanceComponent;

use App\Http\Components\LeaveComponent;
use App\Http\Components\UserManagementComponent;
use App\Http\Components\WorkTimeManagementComponent;
use App\Http\Requests\AttendanceApproveRequest;
use App\Http\Requests\AttendanceArrearApproveRequest;
use App\Http\Requests\AttendanceBypassMultipleCreateRequest;

use App\Http\Requests\AttendanceMultipleCreateRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Http\Requests\ManagerAttendanceCheckOutCreateRequest;
use App\Http\Requests\SelfAttendanceCheckInCreateRequest;
use App\Http\Requests\SelfAttendanceCheckOutCreateRequest;
use App\Http\Requests\SelfAttendanceCheckOutRequestCreateRequest;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\PayrunUtil;

use App\Models\Attendance;
use App\Models\AttendanceArrear;

use App\Models\AttendanceRecord;
use App\Models\CheckoutRequest;
use App\Models\LeaveRecord;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\PayrollAttendance;

use App\Models\User;
use App\Models\UserProject;
use App\Models\WorkLocation;
use App\Models\WorkShiftHistory;
use App\Observers\AttendanceObserver;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Exception;
use Faker\Calculator\Ean;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    use ErrorUtil, BusinessUtil, PayrunUtil, BasicNotificationUtil, BasicUtil, ModuleUtil;


    protected $attendanceComponent;

    protected $leaveComponent;
    protected $userManagementComponent;
    protected $departmentComponent;
    protected $workTimeManagementComponent;
        protected $firebase;
    public function __construct(AttendanceComponent $attendanceComponent, LeaveComponent $leaveComponent, UserManagementComponent $userManagementComponent, WorkTimeManagementComponent $workTimeManagementComponent, FirebaseService $firebase)
    {
        $this->attendanceComponent = $attendanceComponent;

        $this->leaveComponent = $leaveComponent;
        $this->userManagementComponent = $userManagementComponent;
        $this->workTimeManagementComponent = $workTimeManagementComponent;
             $this->firebase = $firebase;
    }


   /**
     *
     * @OA\PUT(
     *      path="/v1.0/attendances/self/check-out",
     *      operationId="createSelfAttendanceCheckOut",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This endpoint allows an employee to record their check-out time and attendance data after a self-clock-in. It updates the attendance record with the check-out details, including the break hours, work location, and project assignments. The request must include the attendance record and relevant information like out time, location, and whether a break was taken.",
     *      description="This endpoint allows an employee to record their check-out time and attendance data after a self-clock-in. It updates the attendance record with the check-out details, including the break hours, work location, and project assignments. The request must include the attendance record and relevant information like out time, location, and whether a break was taken.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *  *     @OA\Property(property="id", type="number",  format="number", example="1"),
     *     @OA\Property(property="note", type="string",  format="string", example="r"),
     *   *    *     @OA\Property(property="out_geolocation", type="string",  format="string", example="r"),
     *

     *
     * *     @OA\Property(property="attendance_records", type="string", format="array", example={
     * {
     * "in_time":"00:44:00",
     * "out_time":"00:45:00"
     * },
     * * {
     * "in_time":"00:48:00",
     *  "out_time":"00:50:00"
     * }
     *
     * }),
     *
     *
     *  * *     @OA\Property(property="consider_overtime", type="boolean", format="boolean", example="1"),

     * *     @OA\Property(property="does_break_taken", type="boolean", format="boolean", example="1"),
     * * *     @OA\Property(property="break_hours", type="boolean", format="boolean", example="1"),

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

     public function createSelfAttendanceCheckOut(SelfAttendanceCheckOutCreateRequest $request)
     {
         DB::beginTransaction();
         try {

              ;
             $this->isModuleEnabled("employee_login");

             $request_data = $request->validated();
             $request_data["break_hours"] = 0;
             $request_data["does_break_taken"] = 0;
             $request_data["consider_overtime"] = 1;


             // Ensure the authenticated user exists and has a business_id
             $user = auth()->user();
             if (!$user || !$user->business_id) {
                 // Handle the error as needed, e.g., throw an exception or return an error response
                 throw new Exception("User or business ID not found.");
             }
             $this->touchUserUpdatedAt([$user->id]);
             $setting_attendance = $this->attendanceComponent->get_attendance_setting();
             // Create the query parameters
             $attendance_query_params = [
                 "id" => $request_data["id"],
                 "business_id" => $user->business_id,
             ];


             // Find the attendance record
             $attendance = Attendance::where($attendance_query_params)->first();

             if (!$attendance) {
                 // Handle the case where the attendance record is not found, e.g., throw an exception or return an error response
                 throw new Exception("Attendance record not found.");
             }

             if (!count($request_data["attendance_records"])) {
                 throw new Exception("Attendance records is empty", 401);
             }

            $in_time = $request_data["attendance_records"][0]["in_time"];
            $out_time = $request_data["attendance_records"][0]["out_time"];
            // Check if this is the last record in the array
                         if ($attendance->is_clocked_in) {
                             if (empty($out_time) || $in_time == $out_time) {
                             throw new Exception(("You can not clock in twice".$out_time) ,409);
                             }

                             $attendance->is_clocked_in=0;

                         } else {
                            if (!empty($out_time) && $in_time != $out_time) {
                             throw new Exception("You can not clock out twice",409);
                             }
                              $attendance->is_clocked_in = 1;
                         }



            // Convert the attendance record to an array
             $attendance_data = $attendance->toArray();

         $previous_attendance_records = $attendance->attendance_records;
          foreach ($previous_attendance_records as $previous_attendance_record) {
                 $previous_attendance_record->project_ids = $previous_attendance_record->projects->pluck("id")->toArray();
             }
         $previous_attendance_records = $previous_attendance_records->toArray();






             $request_data_update = array_replace($attendance_data, $request_data);
             $request_data_update["attendance_records"] = $request_data['attendance_records'];
             $request_data_update["is_self_clocked_in"] = 1;



             $request_data_update["attendance_records"] = collect($request_data_update["attendance_records"])
                 ->map(function ($item) use ($previous_attendance_records) {

                     if (empty($item["out_latitude"])) {
                         $item["out_latitude"] = "";
                     }

                     if (empty($item["out_longitude"])) {
                         $item["out_longitude"] = "";
                     }

                     if (empty($item["out_time"])) {
                         $item["out_time"] = $item["in_time"];
                     }

                     $item["break_hours"] = 0;
                     $item["is_paid_break"] = 0;

                    if ($item["in_time"] == $item["out_time"]) {
                        $item["clocked_in_by"] = auth()->user()->id;
                          $item["in_ip_address"] = request()->ip();

                     } else {
                         $previous_attendance_record = end($previous_attendance_records);
                         $item["in_latitude"] = $previous_attendance_record["in_latitude"];
                         $item["in_longitude"] = $previous_attendance_record["in_longitude"];
                         $item["in_time"] = $previous_attendance_record["in_time"];
                         $item["in_ip_address"] = $previous_attendance_record["in_ip_address"];
                         $item["clocked_in_by"] = $previous_attendance_record["clocked_in_by"]??NULL;
                         $item["clocked_out_by"] = auth()->user()->id;
                         $item["out_ip_address"] = request()->ip();
                     }


                     if (!empty($item["out_latitude"]) && !empty($item["out_longitude"])) {
                         $this->attendanceComponent->validateWorkLocation($item["work_location_id"], $item["out_latitude"], $item["out_longitude"]);
                     }

                     return $item;
                 })
                 ->toArray();


             if ($request_data_update["attendance_records"][0]["out_time"] == $request_data_update["attendance_records"][0]["in_time"]) {
                 // Merge existing attendance records with the updated ones
                 $request_data_update["attendance_records"] = array_merge(
                     $previous_attendance_records,
                     $request_data_update["attendance_records"]
                 );

             } else {
                 $previous_attendance_records[count($previous_attendance_records) - 1] =  $request_data_update["attendance_records"][0];
                 // Set the modified attendance records back into the update data
                 $request_data_update["attendance_records"] = $previous_attendance_records;
             }

             $request_data_update["is_present"] =  $this->attendanceComponent->calculate_total_present_hours($request_data_update["attendance_records"]) > 0;
             // Retrieve attendance setting
             $termination = $user->lastTermination;
             // Process attendance data for update
             $attendance_data = $this->attendanceComponent->process_attendance_data($request_data_update, $setting_attendance, $user, $termination);

             if ($attendance) {
                 $attendance->fill(collect($attendance_data)->only([
                     "is_self_clocked_in",
                     'contractual_hours',
                     'note',
                     "in_geolocation",
                     "out_geolocation",
                     'user_id',
                     'in_date',
                     'does_break_taken',
                     'consider_overtime',
                     "behavior",
                     "capacity_hours",
                     "work_hours_delta",
                     "break_type",
                     "break_hours",
                     "paid_break_hours",
                     "unpaid_break_hours",
                     "total_paid_hours",
                     "regular_work_hours",
                     // "work_shift_start_at",
                     // "work_shift_end_at",
                     "work_shift_history_id",
                     "holiday_id",
                     "leave_record_id",
                     "is_weekend",
                     "overtime_hours",
                     "leave_hours",
                     "punch_in_time_tolerance",
                     "tolerance_time",
                     "status",
                     'work_location_id',

                     "is_active",
                     "business_id",
                     "created_by",
                     "regular_hours_salary",
                     "overtime_hours_salary",
                     // "attendance_records",
                     "is_present"

                 ])->toArray());

                 $attendance->save();


                 if (isset($attendance_data['attendance_records'])) {
                     // First, delete old attendance history records
                     $attendance->attendance_records()->delete();

                     $attendanceRecords = $attendance_data['attendance_records'];
                     $totalRecords = count($attendanceRecords);
                     // Now, add new attendance history records
                     foreach ($attendanceRecords as $index => $attendanceRecordData) {

                         $attendanceRecord = AttendanceRecord::create([
                             'attendance_id' => $attendance->id,
                             'in_time' => $attendanceRecordData['in_time'],
                             'out_time' => $attendanceRecordData['out_time'],
                             'break_hours' => $attendanceRecordData['break_hours'],
                             'is_paid_break' => $attendanceRecordData['is_paid_break'],
                             'note' => $attendanceRecordData['note'] ?? null,
                             'work_location_id' => $attendanceRecordData['work_location_id'],
                             'in_latitude' => $attendanceRecordData['in_latitude'] ?? "",
                             'in_longitude' => $attendanceRecordData['in_longitude'] ?? "",
                             'out_latitude' => $attendanceRecordData['out_latitude'] ?? "",
                             'out_longitude' => $attendanceRecordData['out_longitude'] ?? "",
                             'in_ip_address' => $attendanceRecordData['in_ip_address'] ?? "",
                             'out_ip_address' => $attendanceRecordData['out_ip_address'] ?? "",
                             'clocked_in_by' => $attendanceRecordData['clocked_in_by'] ?? NULL,
                             'clocked_out_by' => $attendanceRecordData['clocked_out_by'] ?? NULL,

                             'time_zone'  => $attendanceRecordData["time_zone"] ?? "",
                         ]);



                         // Sync the projects for each attendance history record
                         if (isset($attendanceRecordData['project_ids'])) {
                             $attendanceRecord->projects()->sync($attendanceRecordData['project_ids']);
                         }
                     }
                 }
             }


             $observer = new AttendanceObserver();
             $observer->updated_action($attendance, 'update');

             $this->send_notification($attendance, $attendance->employee, "Attendance updated", "update", "attendance");
 $this->send_notification($attendance, $attendance->employee, "Attendance Taken", "clocked_in", "attendance");



                 $responseData = $attendance->toArray();

             DB::commit();
             return response($responseData, 201);
         } catch (Exception $e) {
             DB::rollBack();
             return $this->sendError($e);
         }
     }


    /**
     *
     * @OA\PUT(
     *      path="/v1.0/attendances/self/check-out/request",
     *      operationId="createSelfAttendanceCheckOutRequest",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This endpoint allows an employee to record their check-out time and attendance data after a self-clock-in. It updates the attendance record with the check-out details, including the break hours, work location, and project assignments. The request must include the attendance record and relevant information like out time, location, and whether a break was taken.",
     *      description="This endpoint allows an employee to record their check-out time and attendance data after a self-clock-in. It updates the attendance record with the check-out details, including the break hours, work location, and project assignments. The request must include the attendance record and relevant information like out time, location, and whether a break was taken.",
     *
     * @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *         @OA\Property(property="attendance_id", type="number", format="int64", example=1),
 *         @OA\Property(property="attendance_record_id", type="number", format="int64", example=10),
 *         @OA\Property(property="note", type="string", format="string", example="Left early for appointment"),
 *         @OA\Property(property="out_time", type="string", format="date-time", example="2025-05-13T17:30:00Z"),
 *         @OA\Property(property="out_latitude", type="string", format="string", example="23.8103"),
 *         @OA\Property(property="out_longitude", type="string", format="string", example="90.4125")
 *     )
 * ),
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

    public function createSelfAttendanceCheckOutRequest(SelfAttendanceCheckOutRequestCreateRequest $request)
    {
        DB::beginTransaction();
        try {



            $this->isModuleEnabled("employee_login");

            $request_data = $request->validated();

                $user = User::where("id",auth()->user()->id)->first();

            $this->touchUserUpdatedAt([$user->id]);

          $checkout_request = CheckoutRequest::create($request_data);

            Notification::create([
                "entity_id" => $checkout_request->id,
                "entity_ids" => [$checkout_request->id],
                "entity_name" => "checkout_request",
                'notification_title' => "New Checkout Request Submitted",
                'notification_description' => $user->title . " ". $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name  . " submitted a new checkout request.",
                'notification_link' => "/checkout-requests/" . $checkout_request->id,
                "sender_id" => $user->id, // Assuming you have a variable for the updater's ID
                "receiver_id" => optional($user->departments()->first())->manager_id ?? $user->business->owner_id,
                "business_id" => $user->business_id,
                "is_system_generated" => 1,
                "status" => "unread",
            ]);





            DB::commit();
            return response([
                "ok" => true
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\PUT(
     *      path="/v1.0/attendances/manager/check-out",
     *      operationId="createManagerAttendanceCheckOut",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This endpoint allows an employee to record their check-out time and attendance data after a self-clock-in. It updates the attendance record with the check-out details, including the break hours, work location, and project assignments. The request must include the attendance record and relevant information like out time, location, and whether a break was taken.",
     *      description="This endpoint allows an employee to record their check-out time and attendance data after a self-clock-in. It updates the attendance record with the check-out details, including the break hours, work location, and project assignments. The request must include the attendance record and relevant information like out time, location, and whether a break was taken.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *  *     @OA\Property(property="id", type="number",  format="number", example="1"),
     *     @OA\Property(property="user_id", type="string",  format="string", example="r"),
     *   *    *     @OA\Property(property="out_time", type="string",  format="string", example="r"),
     *      *   *    *     @OA\Property(property="outDate", type="string",  format="string", example="r"),
     *
     *
     *
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

    public function createManagerAttendanceCheckOut(ManagerAttendanceCheckOutCreateRequest $request)
    {

        DB::beginTransaction();
        try {

             ;
            $this->isModuleEnabled("employee_login");

            $request_data = $request->validated();
            $request_data["break_hours"] = 0;
            $request_data["does_break_taken"] = 0;
            $request_data["consider_overtime"] = 1;

            $out_time = $request_data["out_time"];
            $note = $request_data["note"] ?? "";

            // Ensure the authenticated user exists and has a business_id
            $user = User::where(
                [
                    "id" => $request_data["user_id"]
                ]
            )
                ->first();

            if (!$user || !$user->business_id) {
                // Handle the error as needed, e.g., throw an exception or return an error response
                throw new Exception("User or business ID not found.");
            }
            $this->touchUserUpdatedAt([$user->id]);
 $setting_attendance = $this->attendanceComponent->get_attendance_setting();
            // Create the query parameters
            $attendance_query_params = [
                "id" => $request_data["id"],
                "business_id" => $user->business_id,
            ];


            // Find the attendance record
            $attendance = Attendance::where($attendance_query_params)->first();

            if (!$attendance) {
                // Handle the case where the attendance record is not found, e.g., throw an exception or return an error response
                throw new Exception("Attendance record not found.");
            }

            if (!count($attendance->attendance_records)) {
                throw new Exception("Attendance records is empty", 401);
            }

                     if (!$attendance->is_clocked_in) {
        throw new Exception("The employee has already clocked out and cannot clock out again.", 409);
}
           $attendance->is_clocked_in=0;


            // Convert the attendance record to an array
            $attendance_data = $attendance->toArray();




         $previous_attendance_records = $attendance->attendance_records;
          foreach ($previous_attendance_records as $previous_attendance_record) {
                 $previous_attendance_record->project_ids = $previous_attendance_record->projects->pluck("id")->toArray();
             }
         $previous_attendance_records = $previous_attendance_records->toArray();



            $request_data_update = array_replace($attendance_data, $request_data);
            $request_data_update["attendance_records"] = [$previous_attendance_records[count($previous_attendance_records) - 1]];
            $request_data_update["attendance_records"][0]["out_time"] = $out_time;
            $request_data_update["attendance_records"][0]["note"] = $note;





            $request_data_update["attendance_records"] = collect($request_data_update["attendance_records"])
                ->map(function ($item) use (&$previous_attendance_records) {


                    if (empty($item["out_latitude"])) {
                        $item["out_latitude"] = "";
                    }
                    if (empty($item["out_longitude"])) {
                        $item["out_longitude"] = "";
                    }
                    if (empty($item["out_time"])) {
                        $item["out_time"] = $item["in_time"];
                    }

                    $item["break_hours"] = 0;
                    $item["is_paid_break"] = 0;

                      if ($item["in_time"] == $item["out_time"]) {
                        $item["clocked_in_by"] = auth()->user()->id;
                        $item["in_ip_address"] = request()->ip();
                     } else {
                         $previous_attendance_record = end($previous_attendance_records);
                         $item["in_latitude"] = $previous_attendance_record["in_latitude"];
                         $item["in_longitude"] = $previous_attendance_record["in_longitude"];
                         $item["in_time"] = $previous_attendance_record["in_time"];
                         $item["in_ip_address"] = $previous_attendance_record["in_ip_address"];
                         $item["clocked_in_by"] = $previous_attendance_record["clocked_in_by"]??NULL;
                         $item["clocked_out_by"] = auth()->user()->id;
                         $item["out_ip_address"] = request()->ip();
                     }

                    if (!empty($item["out_latitude"]) && !empty($item["out_longitude"])) {
                        $this->attendanceComponent->validateWorkLocation($item["work_location_id"], $item["out_latitude"], $item["out_longitude"]);
                    }

                    return $item;
                })
                ->toArray();



            $previous_attendance_records[count($previous_attendance_records) - 1] =  $request_data_update["attendance_records"][0];
            // Set the modified attendance records back into the update data
            $request_data_update["attendance_records"] = $previous_attendance_records;



            $request_data_update["is_present"] =  $this->attendanceComponent->calculate_total_present_hours($request_data_update["attendance_records"]) > 0;


            $termination = $user->lastTermination;


            // Process attendance data for update
            $attendance_data = $this->attendanceComponent->process_attendance_data($request_data_update, $setting_attendance, $user, $termination);


            if ($attendance) {
                $attendance->fill(collect($attendance_data)->only([
                    'contractual_hours',
                    'note',
                    "in_geolocation",
                    "out_geolocation",
                    'user_id',
                    'in_date',
                    'does_break_taken',
                    "consider_overtime",
                    "behavior",
                    "capacity_hours",
                    "work_hours_delta",
                    "break_type",
                    "break_hours",
                    "paid_break_hours",
                    "unpaid_break_hours",
                    "total_paid_hours",
                    "regular_work_hours",
                    // "work_shift_start_at",
                    // "work_shift_end_at",
                    "work_shift_history_id",
                    "holiday_id",
                    "leave_record_id",
                    "is_weekend",
                    "overtime_hours",
                    "leave_hours",
                    "punch_in_time_tolerance",
                    "tolerance_time",
                    "status",
                    'work_location_id',

                    "is_active",
                    "business_id",
                    "created_by",
                    "regular_hours_salary",
                    "overtime_hours_salary",
                    // "attendance_records",
                    "is_present"

                ])->toArray());
                $attendance->save();






                if (isset($attendance_data['attendance_records'])) {
                    // First, delete old attendance history records
                    $attendance->attendance_records()->delete();
                    $attendanceRecords = $attendance_data['attendance_records'];


                    // Now, add new attendance history records
                    foreach ($attendanceRecords as $attendanceRecordData) {

                        $attendanceRecord = AttendanceRecord::create([
                            'attendance_id' => $attendance->id,
                            'in_time' => $attendanceRecordData['in_time'],
                            'out_time' => $attendanceRecordData['out_time'],
                            'break_hours' => $attendanceRecordData['break_hours'],
                            'is_paid_break' => $attendanceRecordData['is_paid_break'],
                            'note' => $attendanceRecordData['note'] ?? null,
                            'work_location_id' => $attendanceRecordData['work_location_id'],
                            'in_latitude' => $attendanceRecordData['in_latitude'] ?? "",
                            'in_longitude' => $attendanceRecordData['in_longitude'] ?? "",
                            'out_latitude' => $attendanceRecordData['out_latitude'] ?? "",
                            'out_longitude' => $attendanceRecordData['out_longitude'] ?? "",
                            'in_ip_address' => $attendanceRecordData['in_ip_address'] ?? "",
                            'out_ip_address' => $attendanceRecordData['out_ip_address'] ?? "",
                            'clocked_in_by' => $attendanceRecordData['clocked_in_by'] ?? NULL,
                            'clocked_out_by' => $attendanceRecordData['clocked_out_by'] ?? NULL,
                            'time_zone'  => $attendanceRecordData["time_zone"] ?? "",
                        ]);


                        // Sync the projects for each attendance history record
                        if (isset($attendanceRecordData['project_ids'])) {
                            $attendanceRecord->projects()->sync($attendanceRecordData['project_ids']);
                        }
                    }
                }
            }

            $observer = new AttendanceObserver();
            $observer->updated_action($attendance, 'update');


         $type = $attendance->is_clocked_in ? "clocked_in" : "clocked_out";
$title = $attendance->is_clocked_in ? "✅ Clocked In" : "⏰ Clocked Out";
$body = $attendance->is_clocked_in
    ? "The employee {$attendance->employee->full_name} has clocked in."
    : "The employee {$attendance->employee->full_name} has clocked out.";

// Send notification
$this->send_notification($attendance, $attendance->employee, "Attendance updated", $type, $type);

$this->firebase->sendNotificationToUser(
    optional($attendance->employee->departments()->first())->manager_id ?? $attendance->employee->business->owner_id,
    $title,
    $body,
    ["type" => $type]
);

                $responseData = $attendance->toArray();




            DB::commit();
            return response($responseData, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/attendances/self/check-in",
     *      operationId="createSelfAttendanceCheckIn",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is used to store attendance details when an employee checks in. It records the check-in time, work location, and associated projects. The request includes the employee's in-time, work location ID, and project assignments, and the system validates the work location. It also calculates the attendance hours and determines whether the employee is present. The response includes the created attendance record, associated projects, and work location details.",
     *      description="This method is used to store attendance details when an employee checks in. It records the check-in time, work location, and associated projects. The request includes the employee's in-time, work location ID, and project assignments, and the system validates the work location. It also calculates the attendance hours and determines whether the employee is present. The response includes the created attendance record, associated projects, and work location details.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *    *     @OA\Property(property="in_geolocation", type="string",  format="string", example="r"),

     *
     *     @OA\Property(property="user_id", type="number", format="number", example="1"),
     *
     * *     @OA\Property(property="attendance_records", type="string", format="array", example={
     * {
     * "in_time":"00:44:00"
     * },
     * * {
     * "in_time":"00:48:00"
     * }
     *
     * }),
     *
     *     @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     *     @OA\Property(property="work_location_id", type="integer", format="int", example="1"),
     *
     *     @OA\Property(property="project_ids", type="string", format="array", example="{1,2,3}")
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

    public function createSelfAttendanceCheckIn(SelfAttendanceCheckInCreateRequest $request)
    {

        DB::beginTransaction();
        try {

             ;

            $this->isModuleEnabled("employee_login");



            $request_data = $request->validated();
            // Ensure the authenticated user exists and has a business_id
            $user = auth()->user();
            if (!$user || !$user->business_id) {
                // Handle the error as needed, e.g., throw an exception or return an error response
                throw new Exception("User or business ID not found.");
            }


            $this->touchUserUpdatedAt([$user->id]);




            $request_data["user_id"] = $user->id;
            $request_data["does_break_taken"] = 0;
            $request_data["consider_overtime"] = 1;


            $request_data["break_hours"] = 0;

            $request_data["is_self_clocked_in"] = 1;
            $request_data["is_clocked_in"] = 1;





            $request_data["attendance_records"] = collect($request_data["attendance_records"])
                ->map(function ($item) {
                    $item["out_time"] = $item["in_time"];

                    $item["break_hours"] = 0;
                    $item["is_paid_break"] = 0;

                        $item["clocked_in_by"] = auth()->user()->id;
                        $item["in_ip_address"] = request()->ip();

                    $this->attendanceComponent->validateWorkLocation($item["work_location_id"]??"", $item["in_latitude"]??"", $item["in_longitude"]??"");
                    return $item;
                })
                ->toArray();

            $request_data["is_present"] =  $this->attendanceComponent->calculate_total_present_hours($request_data["attendance_records"]) > 0;



            // Retrieve attendance setting
            $setting_attendance = $this->attendanceComponent->get_attendance_setting();


            $termination = $user->lastTermination;


            $attendance_data = $this->attendanceComponent->process_attendance_data($request_data, $setting_attendance, $user, $termination);


            // Assign additional data to request data for attendance creation
            $attendance =  Attendance::create($attendance_data);
            if (isset($attendance_data['attendance_records'])) {
                // First, delete old attendance history records
                $attendance->attendance_records()->delete();



                // Now, add new attendance history records
                foreach ($attendance_data['attendance_records'] as $attendanceRecordData) {
                    $attendanceRecord = AttendanceRecord::create([
                        'attendance_id' => $attendance->id,
                        'in_time' => $attendanceRecordData['in_time'],
                        'out_time' => $attendanceRecordData['out_time'],
                        'break_hours' => $attendanceRecordData['break_hours'],
                        'is_paid_break' => $attendanceRecordData['is_paid_break'],
                        'note' => $attendanceRecordData['note'] ?? null,
                        'work_location_id' => $attendanceRecordData['work_location_id'],
                        'in_latitude' => $attendanceRecordData['in_latitude'] ?? "",
                        'in_longitude' => $attendanceRecordData['in_longitude'] ?? "",
                        'out_latitude' => $attendanceRecordData['out_latitude'] ?? "",
                        'out_longitude' => $attendanceRecordData['out_longitude'] ?? "",
                        'in_ip_address' => $attendanceRecordData['in_ip_address'] ?? "",
                        'out_ip_address' => $attendanceRecordData['out_ip_address'] ?? "",
                        'clocked_in_by' => $attendanceRecordData['clocked_in_by'] ?? NULL,
                        'clocked_out_by' => $attendanceRecordData['clocked_out_by'] ?? NULL,

                        'time_zone'  => $attendanceRecordData["time_zone"] ?? "",
                    ]);

                    // Sync the projects for each attendance history record
                    if (isset($attendanceRecordData['project_ids'])) {
                        $attendanceRecord->projects()->sync($attendanceRecordData['project_ids']);
                    }
                }
            }

            // $attendance->projects()->sync($request_data["project_ids"]);

            $observer = new AttendanceObserver();
            $observer->updated_action($attendance, 'create');


            $this->adjust_payroll_on_attendance_update($attendance, 0);


            $this->send_notification($attendance, $attendance->employee, "Attendance Taken", "clocked_in", "attendance");


$this->firebase->sendNotificationToUser(
   optional($attendance->employee->departments()->first())->manager_id ?? $attendance->employee->business->owner_id,
     "✅ Clocked In",
    "The employee {$attendance->employee->full_name} has clocked in."
);

            DB::commit();

            foreach ($attendance->attendance_records as $record) {
                $record->project_ids = $record->projects->pluck('id')->toArray();
                unset($record->projects);
            }


            return response($attendance->toArray(), 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }





    /**
     * @OA\Post(
     *      path="/v1.0/attendances/multiple",
     *      operationId="createMultipleAttendance",
     *      tags={"attendances"},
     *      security={
     *          {"bearerAuth": {}}
     *      },
     *      summary="This method is used to store multiple attendance records for employees in a single request. It handles attendance details such as check-in and check-out times, work location, break hours, and associated projects for each employee. The system validates the provided data, calculates the total present hours, and checks the employee's permissions to ensure they are authorized to perform the action. It also updates the user's information, handles work location validation, and processes attendance data, including sending notifications for successful attendance creation. The response includes the created attendance records along with associated project details.",
     *      description="This method is used to store multiple attendance records for employees in a single request. It handles attendance details such as check-in and check-out times, work location, break hours, and associated projects for each employee. The system validates the provided data, calculates the total present hours, and checks the employee's permissions to ensure they are authorized to perform the action. It also updates the user's information, handles work location validation, and processes attendance data, including sending notifications for successful attendance creation. The response includes the created attendance records along with associated project details.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="user_id", type="number", format="number", example="1"),
     *              @OA\Property(
     *                  property="attendance_details",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="note", type="string", example="note"),
     *                      @OA\Property(property="in_geolocation", type="string", example="in_geolocation"),
     *                      @OA\Property(property="out_geolocation", type="string", example="out_geolocation"),
     *                      @OA\Property(property="attendance_records", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="break_hours", type="number", format="float", example="1.5"),
     *                              @OA\Property(property="is_paid_break", type="boolean", example=true),
     *                              @OA\Property(property="note", type="string", example="record note"),
     *                              @OA\Property(property="in_time", type="string", format="date-time", example="08:44:00"),
     *                              @OA\Property(property="out_time", type="string", format="date-time", example="12:44:00"),
     *                          )
     *                      ),
     *                      @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     *                      @OA\Property(property="is_present", type="boolean", example=true),
     *                      @OA\Property(property="does_break_taken", type="boolean", example=true),
     *      *                      @OA\Property(property="consider_overtime", type="boolean", example=true),
     *
     *                      @OA\Property(property="project_ids", type="array",
     *                          @OA\Items(
     *                              type="number",
     *                              example=1
     *                          )
     *                      ),
     *                      @OA\Property(property="work_location_id", type="number", example=1)
     *                  )
     *              ),
     *          ),
     *      ),
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
    public function createMultipleAttendance(AttendanceMultipleCreateRequest $request)
    {


        DB::beginTransaction();
        try {

             ;

            $request_data = $request->validated();

            if (!$request->user()->hasPermissionTo('attendance_create') && $request_data["user_id"] !== auth()->user()->id) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }


            $setting_attendance = $this->attendanceComponent->get_attendance_setting();

            $user = User::with("lastTermination")->where([
                "id" =>   $request_data["user_id"]
            ])
                ->first();

            if (!$user) {
                throw new Exception("Some thing went wrong getting user.");
            }



            $attendances_data = collect($request_data["attendance_details"])->map(function ($item) use ($request_data, $setting_attendance, $user) {


                if (empty($item["project_ids"])) {
                    $item["project_ids"] = [UserProject::where([
                        "user_id" => $user->id
                    ])
                        ->first()->project_id];
                }
                if (empty($item["work_location_id"])) {
                    $item["work_location_id"] = $user->work_locations[0]->id;
                }

                if (empty($item["is_present"])) {
                    $item["is_present"] = 0;
                    $item["attendance_records"] = [
                        [
                            "in_time" => Carbon::parse($item["in_date"] . ' ' . "00:00:00")->toDateTimeString(),
                            "out_time" => Carbon::parse($item["in_date"] . ' ' . "00:00:00")->toDateTimeString(),
                        ]
                    ];
                }

                $termination = $user->lastTermination;
                $item = $this->attendanceComponent->process_attendance_data($item, $setting_attendance, $user, $termination);

                return  $item;
            });




            $employee = User::where([
                "id" => $request_data["user_id"]
            ])
                ->first();

            if (!$employee) {
                return response()->json([
                    "message" => "someting_went_wrong",
                    500
                ]);
            }

            $created_attendances = [];
            foreach ($attendances_data as $attendance_data) {



                $attendance = $employee->attendances()->create($attendance_data);

                if ($attendance) {
                    // $created_attendance->projects()->sync($attendance_data["project_ids"]);
                    if (isset($attendance_data['attendance_records'])) {
                        // First, delete old attendance history records
                        $attendance->attendance_records()->delete();

                        // Now, add new attendance history records
                        foreach ($attendance_data['attendance_records'] as $attendanceRecordData) {
                            $attendanceRecord = AttendanceRecord::create([
                                'attendance_id' => $attendance->id,
                                'in_time' => $attendanceRecordData['in_time'],
                                'out_time' => $attendanceRecordData['out_time'],
                                'break_hours' => $attendanceRecordData['break_hours'],
                                'is_paid_break' => $attendanceRecordData['is_paid_break'],
                                'note' => $attendanceRecordData['note'] ?? null,
                                'work_location_id' => $attendanceRecordData['work_location_id'],
                            ]);

                            // Sync the projects for each attendance history record
                            if (isset($attendanceRecordData['project_ids'])) {
                                $attendanceRecord->projects()->sync($attendanceRecordData['project_ids']);
                            }
                        }
                    }


                    $observer = new AttendanceObserver();
                    $observer->updated_action($attendance, 'create');

                    $this->adjust_payroll_on_attendance_update($attendance, 0);

                    $created_attendances[] = $attendance;
                }
            }

            $this->send_notification($employee->attendances()
                ->orderByDesc("attendances.id")
                ->take(count($attendances_data))->get(), $employee, "Attendance Taken", "create", "attendance");


            DB::commit();
            if (!empty($created_attendances)) {
                return response(['attendances' => $created_attendances], 201);
            } else {
                // Handle the case where records were not successfully created
                return response(['error' => 'Failed to create attendance records'], 500);
            }
            return response([], 201);
        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     * @OA\Put(
     *      path="/v1.0/attendances",
     *      operationId="updateAttendance",
     *      tags={"attendances"},
     *      security={
     *          {"bearerAuth": {}}
     *      },
     *      summary="This method is used to update existing attendance records for employees. It allows the modification of attendance details such as check-in and check-out times, break details, work location, and associated projects. The method validates the attendance data, recalculates the total present hours, and checks the employee's permissions to ensure they are authorized to perform the action. Additionally, the system handles user updates, project assignments, and sends notifications upon successful attendance update. The response includes the updated attendance record with its associated project details.",
     *      description="This method is used to update existing attendance records for employees. It allows the modification of attendance details such as check-in and check-out times, break details, work location, and associated projects. The method validates the attendance data, recalculates the total present hours, and checks the employee's permissions to ensure they are authorized to perform the action. Additionally, the system handles user updates, project assignments, and sends notifications upon successful attendance update. The response includes the updated attendance record with its associated project details.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="number", format="number", example="1"),
     *             @OA\Property(property="note", type="string",  format="string", example="Updated note"),
     *             @OA\Property(property="in_geolocation", type="string",  format="string", example="37.7749,-122.4194"),
     *             @OA\Property(property="out_geolocation", type="string",  format="string", example="37.7749,-122.4294"),
     *             @OA\Property(property="user_id", type="number", format="number", example="1"),
     *             @OA\Property(property="attendance_records", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="in_time", type="string", format="time", example="08:44:00"),
     *                     @OA\Property(property="out_time", type="string", format="time", example="12:44:00"),
     *                     @OA\Property(property="break_hours", type="number", format="float", example="1.5"),
     *                     @OA\Property(property="is_paid_break", type="boolean", example=true),
     *                     @OA\Property(property="note", type="string", example="Lunch break")
     *                 )
     *             ),
     *             @OA\Property(property="in_date", type="string", format="date", example="2023-11-18"),
     *             @OA\Property(property="does_break_taken", type="boolean", format="boolean", example=true),
     *             @OA\Property(property="consider_overtime", type="boolean", format="boolean", example=true),
     *
     *             @OA\Property(property="work_location_id", type="integer", format="int", example="1"),
     *             @OA\Property(property="project_ids", type="array",
     *                 @OA\Items(type="integer", example={1, 2, 3})
     *             )
     *         ),
     *      ),
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
    public function updateAttendance(AttendanceUpdateRequest $request)
    {

        DB::beginTransaction();
        try {
             ;

            if (!$request->user()->hasPermissionTo('attendance_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $request_data = $request->validated();

            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }


            $request_data["is_present"] =  $this->attendanceComponent->calculate_total_present_hours($request_data["attendance_records"]) > 0;


            // Retrieve attendance setting
            $setting_attendance = $this->attendanceComponent->get_attendance_setting();


            $user = User::with("lastTermination")->where([
                "id" => $request_data["user_id"]
            ])
                ->first();

            $termination = $user->lastTermination;




            // Process attendance data for update
            $attendance_data = $this->attendanceComponent->process_attendance_data($request_data, $setting_attendance, $user, $termination);


            $attendance_query_params = [
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ];

            $attendance = $this->attendanceComponent->find_attendance($attendance_query_params);

            $this->restrict_attendance_modification_for_payroll($attendance);



            if ($attendance) {
                $attendance->fill(collect($attendance_data)->only([
                    'note',
                    "in_geolocation",
                    "out_geolocation",
                    'user_id',
                    'in_date',
                    'does_break_taken',
                    "consider_overtime",
                    "behavior",
                    "capacity_hours",
                    "work_hours_delta",
                    "break_type",
                    "break_hours",
                    "paid_break_hours",
                    "unpaid_break_hours",
                    "total_paid_hours",
                    "regular_work_hours",
                    // "work_shift_start_at",
                    // "work_shift_end_at",
                    "work_shift_history_id",
                    "holiday_id",
                    "leave_record_id",
                    "is_weekend",

                    "overtime_hours",
                    "leave_hours",
                    "punch_in_time_tolerance",
                    "tolerance_time",
                    "status",
                    'work_location_id',
                    "is_present",
                    "is_active",

                    // "business_id",
                    // "created_by",
                    "regular_hours_salary",
                    "overtime_hours_salary",
                ])->toArray());
                $attendance->save();
            }


            // $attendance->projects()->sync($request_data["project_ids"]);

            if (isset($attendance_data['attendance_records'])) {
                // First, delete old attendance history records
                $attendance->attendance_records()->delete();

                $attendanceRecords = $attendance_data['attendance_records'];
                $totalRecords = count($attendanceRecords);
                // Now, add new attendance history records
                foreach ($attendanceRecords as $index => $attendanceRecordData) {
                    $attendanceRecord = AttendanceRecord::create([
                        'attendance_id' => $attendance->id,
                        'in_time' => $attendanceRecordData['in_time'],
                        'out_time' => $attendanceRecordData['out_time'],
                        'break_hours' => $attendanceRecordData['break_hours'],
                        'is_paid_break' => $attendanceRecordData['is_paid_break'],
                        'note' => $attendanceRecordData['note'] ?? null,
                        'work_location_id' => $attendanceRecordData['work_location_id'],
                        'time_zone' => $attendanceRecordData['time_zone']??"",
                        'in_latitude' => $attendanceRecordData['in_latitude']??"",
                        'in_longitude' => $attendanceRecordData['in_longitude']??"",
                        'out_latitude' => $attendanceRecordData['out_latitude']??"",
                        'out_longitude' => $attendanceRecordData['out_longitude']??"",
                        'in_ip_address' => $attendanceRecordData['in_ip_address']??"",
                        'out_ip_address' => $attendanceRecordData['out_ip_address']??"",
                        'clocked_in_by' => $attendanceRecordData['clocked_in_by']??NULL,
                        'clocked_out_by' => $attendanceRecordData['clocked_out_by']??NULL,


                    ]);

                    if ($index == $totalRecords - 1) {
                        if ($attendanceRecord->in_time == $attendanceRecord->out_time) {
                            $attendance->is_clocked_in = 1;
                            $attendance->save();
                        } else {
                            $attendance->is_clocked_in = 0;
                            $attendance->save();
                        }
                    }

                    // Sync the projects for each attendance history record
                    if (isset($attendanceRecordData['project_ids'])) {
                        $attendanceRecord->projects()->sync($attendanceRecordData['project_ids']);
                    }
                }
            }




            $observer = new AttendanceObserver();
            $observer->updated_action($attendance, 'update');

            $this->adjust_payroll_on_attendance_update($attendance, 0);

            $this->send_notification($attendance, $attendance->employee, "Attendance updated", "update", "attendance");
            DB::commit();

            return response($attendance, 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/attendances/approve",
     *      operationId="approveAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method allows for approving or rejecting attendance records, based on the given approval status and user permissions.",
     *      description="This method allows for approving or rejecting attendance records, based on the given approval status and user permissions.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="attendance_id", type="number", format="number", example="1"),
     *   @OA\Property(property="is_approved", type="boolean", format="boolean", example="1"),
     *      *   @OA\Property(property="add_in_next_payroll", type="boolean", format="boolean", example="1"),
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

    public function approveAttendance(AttendanceApproveRequest $request)
    {

        DB::beginTransaction();
        try {

             ;

            // Check permission to approve attendance
            if (!$request->user()->hasPermissionTo("attendance_approve")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Extract data
            $request_data = $request->validated();


            $attendance_query_params = [
                "id" => $request_data["attendance_id"],
                "business_id" => auth()->user()->business_id
            ];
            $attendance = $this->attendanceComponent->find_attendance($attendance_query_params);
            $this->restrict_attendance_modification_for_payroll($attendance);

           if ($attendance->is_clocked_in == 1) {
            throw new Exception("Cannot approve or reject attendance while the user is clocked in.",409);
}

            $this->touchUserUpdatedAt([$attendance->user_id]);

            $attendance->status = $request_data["is_approved"] ? "approved" : "rejected";

            // Save the updated attendance
            $attendance->save();




            // Update observer with approval
            $observer = new AttendanceObserver();
            $observer->updated_action($attendance, $request_data["is_approved"] ? "approve" : "reject");


            // Adjust payroll based on attendance update
            $this->adjust_payroll_on_attendance_update($attendance, $request_data["add_in_next_payroll"]);


            if (!empty($request_data["add_in_next_payroll"]) && !empty($request_data["is_approved"])) {
                AttendanceArrear::where([
                    "attendance_id" => $attendance->id
                ])
                    ->update(["status" => "approved"]);
            }

            // Determine notification message based on attendance status
            $message = $attendance->status == "approved" ? "Attendance approved" : "Attendance rejected";

            // Send notification
            $this->send_notification($attendance, $attendance->employee, $message, $attendance->status, "attendance");



            DB::commit();
            return response($attendance, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/attendances-split/approve",
     *      operationId="approveAttendanceSplit",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method allows for approving or rejecting attendance records, based on the given approval status and user permissions.",
     *      description="This method allows for approving or rejecting attendance records, based on the given approval status and user permissions.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="attendance_id", type="number", format="number", example="1"),
     *   @OA\Property(property="is_approved", type="boolean", format="boolean", example="1"),
     *      *   @OA\Property(property="add_in_next_payroll", type="boolean", format="boolean", example="1"),
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

    public function approveAttendanceSplit(AttendanceApproveRequest $request)
    {

        DB::beginTransaction();
        try {

             ;

            // Check permission to approve attendance
            if (!$request->user()->hasPermissionTo("attendance_approve")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Extract data
            $request_data = $request->validated();


            // Fetch attendance and setting
            $setting_attendance = $this->attendanceComponent->get_attendance_setting();
            $attendance_query_params = [
                "id" => $request_data["attendance_id"],
                "business_id" => auth()->user()->business_id
            ];
            $attendance = $this->attendanceComponent->find_attendance($attendance_query_params);
            $this->restrict_attendance_modification_for_payroll($attendance);

             if ($attendance->is_clocked_in == 1) {
            throw new Exception("Cannot approve or attendance while the user is clocked in.",409);
}

            $this->touchUserUpdatedAt([$attendance->user_id]);

            // Fetch user details
            $user = User::where([
                "id" =>  $attendance->user_id
            ])
                ->first();

            // Update attendance status based on user's permissions and roles
            if ($this->attendanceComponent->is_special_user($setting_attendance) || $this->attendanceComponent->is_special_role($setting_attendance) || $user->hasRole("business_owner") || $setting_attendance->auto_approval) {
                $attendance->status = $request_data["is_approved"] ? "approved" : "rejected";
            }



            $termination = $user->lastTermination;
            $attendanceData = $this->attendanceComponent->split_attendance_records($attendance->id);

            $firstDayRecords = $attendanceData['first_day_data']['records'];

            $secondDayRecords = $attendanceData['second_day_data']['records'];
            $secondDayDate = $attendanceData['second_day_data']['date'];

            if (empty($secondDayRecords)) {
                return response($attendance, 200);
            }

            // Delete old attendance records
            AttendanceRecord::where('attendance_id', $attendance->id)->delete();

            // Create new records for the first day
            $this->attendanceComponent->storeAttendanceRecords($attendance->id, $firstDayRecords);

            // main attendance update
            $attendanceData = $attendance->toArray();
            $attendance->load('attendance_records');

            $attendance_records = $attendance->attendance_records;
            foreach ($attendance_records as $attendance_record) {
                $attendance_record->project_ids = $attendance_record->projects->pluck("id")->toArray();
            }

            $attendanceData["attendance_records"] = $attendance_records;

            $attendanceData["is_present"] = $this->attendanceComponent->calculate_total_present_hours($attendance_records) > 0;
            $attendanceData = $this->attendanceComponent->process_attendance_data($attendanceData, $setting_attendance, $user, $termination);

            $attendance->update($attendanceData);


            // Update observer with approval
            $observer = new AttendanceObserver();
            $observer->updated_action($attendance, $request_data["is_approved"] ? "approve" : "reject");


            // Adjust payroll based on attendance update
            $this->attendanceComponent->adjust_payroll_on_attendance_update($attendance, $request_data["add_in_next_payroll"]);


            if (!empty($request_data["add_in_next_payroll"]) && !empty($request_data["is_approved"])) {
                AttendanceArrear::where([
                    "attendance_id" => $attendance->id
                ])
                    ->update(["status" => "approved"]);
            }

            // Determine notification message based on attendance status
            $message = $attendance->status == "approved" ? "Attendance approved" : "Attendance rejected";

            // Send notification
            $this->send_notification($attendance, $attendance->employee, $message, $attendance->status, "attendance");


            // Check if second-day attendance exists
            $secondDayAttendance = Attendance::where([
                "user_id" => $attendance->user_id,
            ])->whereDate("in_date", $secondDayDate)->first();


            if ($secondDayAttendance) {
                // Update existing attendance
                $this->attendanceComponent->updateAttendanceSplit($secondDayAttendance, $secondDayRecords, $setting_attendance, $user, $termination);
            } else {
                // Create new attendance
                $attendanceData = $attendance->toArray();

                $attendanceData["in_date"] = Carbon::parse($attendance->in_date)->addDay();

                $this->attendanceComponent->createAttendanceSplit($attendanceData, $secondDayRecords, $setting_attendance, $user, $termination, $secondDayDate);
            }



            DB::commit();
            return response($attendance, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/attendances/approve/arrears",
     *      operationId="approveAttendanceArrear",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method allows for approving attendance arrears by updating the status of the arrears to 'approved' for the given attendance IDs.",
     *      description="This method allows for approving attendance arrears by updating the status of the arrears to 'approved' for the given attendance IDs.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="attendance_id", type="number", format="number", example="1"),
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

    public function approveAttendanceArrear(AttendanceArrearApproveRequest $request)
    {

        DB::beginTransaction();
        try {

             ;

            // Check permission to approve attendance
            if (!$request->user()->hasPermissionTo("attendance_approve")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Extract data
            $request_data = $request->validated();

            foreach ($request_data["attendance_ids"] as $attendance_id) {

                $attendance = Attendance::where([
                    "id" => $attendance_id
                 ])
                 ->first();

                 if ($attendance->status != "approved") {
                    throw new Exception("Cannot approve attendance arrear because the associated attendance is not approved.",409);
                }

                $attendance_arrear = AttendanceArrear::where([
                    "attendance_id" => $attendance_id
                ])
                    ->first();



                if ($attendance_arrear) {
                    if ($attendance_arrear->status == "pending_approval") {
                        $attendance_arrear->status = "approved";
                        $attendance_arrear->save();
                    }
                }
            }


            DB::commit();

            return response([
                "message" => "arrears approve"
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/attendances",
     *      operationId="getAttendances",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *   *     *     * *  @OA\Parameter(
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
     *   * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * *   * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *
     *     *   * *  @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *  *   * *  @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="project_id",
     * in="query",
     * description="project_id",
     * required=true,
     * example="1"
     * ),
     *  * @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *     *  *   * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="pending_approval"
     * ),
     *
     *
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *
     ** @OA\Parameter(
     *     name="attendance_date",
     *     in="query",
     *     description="Attendance Date",
     *     required=true,
     *     example="2024-02-13"
     * ),
     * @OA\Parameter(
     *     name="attendance_start_time",
     *     in="query",
     *     description="Attendance Start Time",
     *     required=true,
     *     example="08:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_end_time",
     *     in="query",
     *     description="Attendance End Time",
     *     required=true,
     *     example="17:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_break",
     *     in="query",
     *     description="Attendance Break Time",
     *     required=true,
     *     example="01:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_schedule",
     *     in="query",
     *     description="Attendance Schedule",
     *     required=true,
     *     example="Regular"
     * ),
     * @OA\Parameter(
     *     name="attendance_overtime",
     *     in="query",
     *     description="Attendance Overtime",
     *     required=true,
     *     example="02:00:00"
     * ),

     *      summary="This method retrieves attendances based on various query parameters, including attendance details, employee information, and work shifts, and returns the data in the requested format (PDF, CSV, or JSON).",
     *      description="This method retrieves attendances based on various query parameters, including attendance details, employee information, and work shifts, and returns the data in the requested format (PDF, CSV, or JSON).",
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

    public function getAttendances(Request $request)
    {
        try {
             ;


            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $attendancesQuery = Attendance::with([
                "employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "employee.departments" => function ($query) {
                    $query->select('departments.id', 'departments.name');
                },
                // "work_location",
                // "projects",
                "work_shift_history" => function ($query) {
                    $query->select(
                        'work_shift_histories.id',
                        'work_shift_histories.name',
                        'work_shift_histories.break_type',
                        'work_shift_histories.break_hours',
                        'work_shift_histories.type'
                    );
                },
                "attendance_records",
                "attendance_records.projects",
                "attendance_records.work_location",



            ])
            ->filterAttendance($all_manager_department_ids);


            $attendances = $this->retrieveData($attendancesQuery, "in_date", "attendances");

            foreach ($attendances as $attendance) {
                if (!empty($attendance->work_shift_history)) {
                    // Filter the details array to only keep the elements where day matches

                    $details = $attendance->work_shift_history->details;
                    unset($attendance->work_shift_history->details);
                    $attendance->work_shift_history->details = collect($details)
                        ->filter(function ($detail) use ($attendance) {
                            $day_number = Carbon::parse($attendance->in_date)->dayOfWeek;
                            return $day_number === $detail["day"];
                        })
                        ->values()  // Re-index the array
                        ->all();
                }
            }

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($attendances->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.attendances', ["attendances" => $attendances]);
                    }

                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new AttendancesExport($attendances), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($attendances, 200);
            }

            return response()->json($attendances, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v2.0/attendances",
     *      operationId="getAttendancesV2",
     *      tags={"attendances"},
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
     *     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This API endpoint retrieves attendance data, including work availability and leave hours, for the specified user(s) and date range. It provides highlights on total schedule hours, leave hours, and work availability status based on the configured work availability definition.",
     *      description="This API endpoint retrieves attendance data, including work availability and leave hours, for the specified user(s) and date range. It provides highlights on total schedule hours, leave hours, and work availability status based on the configured work availability definition.",
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

    public function getAttendancesV2(Request $request)
    {
        try {
             ;
            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $data = $this->attendanceComponent->getAttendanceV2Data();


            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v3.0/attendances",
     *      operationId="getAttendancesV3",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   * *  @OA\Parameter(
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method fetches attendance data for employees within a given date range, including leave records, attendance status, and holiday details. It returns detailed attendance information for each employee, such as hours worked, leave hours, and overtime hours.",
     *      description="This method fetches attendance data for employees within a given date range, including leave records, attendance status, and holiday details. It returns detailed attendance information for each employee, such as hours worked, leave hours, and overtime hours.",
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

    public function getAttendancesV3(Request $request)
    {
        try {
             ;
            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;

            $start_date = !empty($request->start_date) ? $request->start_date : Carbon::now()->startOfYear()->format('Y-m-d');
           $end_date = (!empty($request->end_date) && !Carbon::parse($request->end_date)->isFuture())
                ? $request->end_date
                : Carbon::now()->subDays(1);


            $usersQuery = User::with(
                [
                    "departments",

                    "work_shift_history" => function ($query) {
                        $query->select(
                            'work_shift_histories.id',
                            'work_shift_histories.name',
                            'work_shift_histories.break_type',
                            'work_shift_histories.break_hours',
                            'work_shift_histories.type'
                        );
                    },
                ]
            )
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->where(
                    [
                        "users.business_id" => $business_id
                    ]
                )

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("attendances", function ($q) use ($request) {
                        $idsArray = explode(',', $request->user_id);
                        $q->whereIn('attendances.user_id', $idsArray);
                    });
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })
                ->select(
                    "users.id",
                    'users.title',
                    "users.first_Name",
                    "users.middle_Name",
                    "users.last_Name",
                    "users.image",
                );


           $employees =  $this->retrieveData($usersQuery, "first_Name", "users");



            // Parse start and end dates
            $startDate = Carbon::parse(($start_date . ' 00:00:00'));
            $endDate = Carbon::parse(($end_date . ' 23:59:59'));

            // Create an array of dates within the given range
            $dateArray = [];
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $dateArray[] = $date->format('Y-m-d');
            }

            // Get employee IDs
            $employee_ids = $employees->pluck("id");
            // Retrieve leave records within the specified date range
            $leave_records = LeaveRecord::whereHas('leave',    function ($query) use ($employee_ids) {
                $query->whereIn("leaves.user_id",  $employee_ids)
                    ->where("leaves.status", "approved");
            })
                ->where('date', '>=', $start_date . ' 00:00:00')
                ->where('date', '<=', ($end_date . ' 23:59:59'))
                ->get();

            // Retrieve attendance records within the specified date range
            $attendances = Attendance::with([
                "attendance_records",
                "attendance_records.projects",
                "attendance_records.work_location",
            ])
                ->where("attendances.status", "approved")
                ->whereIn('attendances.user_id', $employee_ids)
                ->where('attendances.in_date', '>=', $start_date . ' 00:00:00')
                ->where('attendances.in_date', '<=', ($end_date . ' 23:59:59'))

                ->get();


            // Iterate over each employee
            $employees =   $employees->map(function ($employee) use ($dateArray, $attendances, $leave_records) {





                // Initialize total variables
                $total_paid_hours = 0;
                $total_paid_leave_hours = 0;
                $total_paid_holiday_hours = 0;
                $total_leave_hours = 0;
                $total_capacity_hours = 0;
                $total_balance_hours = 0;

                // Map date-wise attendance for the employee
                $employee->datewise_attendanes = collect($dateArray)->map(
                    function ($date) use ($attendances, $leave_records, &$total_balance_hours, &$total_paid_hours, &$total_capacity_hours, &$total_leave_hours, &$total_paid_leave_hours, &$total_paid_holiday_hours, $employee) {
                        // Get holiday details

                        $holiday = $this->workTimeManagementComponent->get_holiday_details($date,$employee->id);

                        // Find attendance record for the given date and employee
                        $attendance = $attendances->first(function ($attendance) use ($date, $employee) {
                            $in_date = Carbon::parse($attendance->in_date)->format("Y-m-d");
                            return (($in_date == $date) && ($attendance->user_id == $employee->id));
                        });

                        // Find leave record for the given date and employee, also calculate total leave hours
                        $leave_record = $leave_records->first(function ($leave_record) use ($date, $employee, &$total_leave_hours) {
                            $leave_date = Carbon::parse($leave_record->date)->format("Y-m-d");
                            if (($leave_record->user_id != $employee->id) || ($date != $leave_date)) {
                                return false;
                            }
                            $total_leave_hours += $leave_record->leave_hours;
                            return true;
                        });

                        // Initialize result variables
                        $result_is_present = 0;
                        $result_paid_hours = 0;
                        $result_balance_hours = 0;

                        // Calculate paid leave hours if leave record exists and it's a paid leave
                        if ($leave_record) {
                            if ($leave_record->leave->leave_type->type == "paid") {
                                $paid_leave_hours =  $leave_record->leave_hours;
                                $total_paid_leave_hours += $paid_leave_hours;
                                $result_paid_hours += $paid_leave_hours;
                                $total_paid_hours +=  $paid_leave_hours;
                            }
                        }
                        // Calculate holiday hours if holiday exists
                        if ($holiday) {
                            if (!$employee->weekly_contractual_hours || !$employee->minimum_working_days_per_week) {
                                $holiday_hours = 0;
                            } else {
                                $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;
                            }

                            $total_paid_holiday_hours += $holiday_hours;
                            $result_paid_hours += $holiday_hours;
                            $total_paid_hours += $holiday_hours;
                        }

                        // Update result variables based on attendance
                        if ($attendance) {
                            $total_capacity_hours += $attendance->capacity_hours;
                            if ($attendance->total_paid_hours > 0) {
                                $result_is_present = 1;
                                $result_balance_hours = $attendance->overtime_hours;
                                $total_paid_hours += $attendance->total_paid_hours;
                                $total_balance_hours += $attendance->overtime_hours;
                                $result_paid_hours += $attendance->total_paid_hours;
                            }
                        }
                        // Prepare and return the result array
                        if ($leave_record || $attendance || $holiday) {
                            return [
                                'date' => Carbon::parse($date)->format("Y-m-d"),
                                'is_present' => $result_is_present,
                                'paid_hours' => $result_paid_hours,
                                "result_balance_hours" => $result_balance_hours,
                                'capacity_hours' => $attendance ? $attendance->capacity_hours : 0,
                                "paid_leave_hours"   => $leave_record ? (($leave_record->leave->leave_type->type == "paid") ? $leave_record->leave_hours : 0) : 0
                            ];
                        }
                        // If no relevant record found, return null
                        return  null;
                    }
                )
                    ->filter()
                    ->values();

                // Assign total variables to employee object
                $employee->total_balance_hours = $total_balance_hours;
                $employee->total_leave_hours = $total_leave_hours;
                $employee->total_paid_leave_hours = $total_paid_leave_hours;
                $employee->total_paid_holiday_hours = $total_paid_holiday_hours;
                $employee->total_paid_hours = $total_paid_hours;
                $employee->total_capacity_hours = $total_capacity_hours;
                return $employee;
            });



            // Return JSON response with employees data
            return response()->json($employees, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v4.0/attendances",
     *      operationId="getAttendancesV4",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=false,
     * example="show_my_data"
     * ),
     *
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=false,
     *  example="6"
     *      ),

     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=false,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=false,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=false,
     * example="search_key"
     * ),
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=false,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=false,
     * example="ASC"
     * ),
     *
     * @OA\Parameter(
     * name="response_type",
     * in="query",
     * description="response_type",
     * required=false,
     * example="ASC"
     * ),
     *
     *
     *      summary="This method fetches attendance data for employees within a given date range, including leave records, attendance status, and holiday details. It returns detailed attendance information for each employee, such as hours worked, leave hours, and overtime hours.",
     *      description="This method fetches attendance data for employees within a given date range, including leave records, attendance status, and holiday details. It returns detailed attendance information for each employee, such as hours worked, leave hours, and overtime hours.",
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

    public function getAttendancesV4(Request $request)
    {
        try {
             ;
            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;

            $start_date = !empty($request->start_date)
                ? $request->start_date
                : Carbon::now()->startOfYear()->format('Y-m-d');


            $end_date = (!empty($request->end_date) && !Carbon::parse($request->end_date)->isFuture())
                ? $request->end_date
                : Carbon::now()->subDays(1);


            $usersQuery = User::with("departments")
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids)
                      ->when((request()->filled("department_id")), function ($query) {
                    $idsArray = explode(',', request()->department_id);
                    $query->whereIn("departments.id", $idsArray);

            });
                })
            ->when(!empty(request()->designation_ids), function ($query) {
                $idsArray = explode(',', request()->designation_ids);
                return $query->whereIn('designation_id', $idsArray);
            })
                ->whereDoesntHave("lastTermination", function ($query) use ($start_date) {
                    $query->where('terminations.date_of_termination', "<", $start_date)
                        ->whereRaw('terminations.date_of_termination > users.joining_date');
                })
                ->whereDate("joining_date","<=",$end_date)

                ->where(
                    [
                        "users.business_id" => $business_id
                    ]
                )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })
                ->when($request->boolean("is_leave_taken"), function ($query) use ($start_date,$end_date) {
                    return $query->whereHas("leaves.records", function($query) use($start_date,$end_date){
                           $query->whereDate("leave_records.date",">=",$start_date)
                           ->whereDate("leave_records.date","<=",$end_date);
                    });
                })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("attendances", function ($q) use ($request) {
                        $idsArray = explode(',', $request->user_id);
                        $q->whereIn('attendances.user_id', $idsArray);
                    });
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })

            ->when((
                request()->filled("employee_work_shift_id")
                ||
              request()->filled("project_id")
                ||
                request()->filled("work_location_id")

            ), function ($query) use($start_date,$end_date) {

              $query->whereHas("attendances", function($query) use($start_date,$end_date) {
                 $query
                    ->whereNotIn("status", ["rejected"])
            ->whereDate('attendances.in_date', ">=", $start_date)
            ->whereDate('attendances.in_date', "<=", $end_date)
                 ->when(request()->filled("project_id"), function ($query) {

                $idsArray = explode(',', request()->input("project_id"));

                return $query->whereHas('attendance_records.projects', function ($query) use ($idsArray) {
                    $query->whereIn("projects.id", $idsArray);
                });
            })
                        ->when(request()->filled("employee_work_shift_id"), function ($query) {
                $work_shift_ids = explode(',', request()->employee_work_shift_id);
                $work_shift_history_ids = WorkShiftHistory::whereIn("work_shift_id",$work_shift_ids)
                    ->get()
                    ->pluck("id")
                    ->toArray();
                return $query->whereIn('attendances.work_shift_history_id', $work_shift_history_ids);
            })
             ->when((request()->filled("work_location_id")), function ($query) {
                return $query->whereHas('attendance_records', function ($query) {
                    $query->where('attendance_records.work_location_id', request()->input("work_location_id"));
                });
            });
                 });
            })


                ->select(
                    "users.id",
                    'users.title',
                    "users.first_Name",
                    "users.middle_Name",
                    "users.last_Name",
                    "users.image",
                    "users.joining_date",
                    "users.designation_id"

                );
                $employees =  $this->retrieveData($usersQuery, "first_Name", "users");


            // Iterate over each employee
            $employees =   $employees->map(function ($employee) use ( $start_date, $end_date) {

                $date_of_termination = $employee->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($employee->joining_date, $date_of_termination, $start_date, $end_date);


                $employee = json_decode(json_encode($employee), true);
                $employee["employee_start_date1"] = $start_date;
                $employee["employee_end_date1"] = $end_date;
                $employee["employee_start_date"] = Carbon::parse($dates["start_date"]);
                $employee["employee_end_date"] = Carbon::parse($dates["end_date"]);
                $employee["message"] = $dates["message"];

                $employee = $this->attendanceComponent->processAttendanceSummaryData($employee, $start_date, $end_date);


                return $employee;
            }) ->filter(function ($employee)  {
            $requested_availability = request()->work_availability_percentage;
            $is_late_required = request()->is_late_employee;
                $highlights = $employee["data"]["data_highlights"];

                $meets_availability = true;
                $meets_late_status = true;

                if (!is_null($requested_availability)) {
                    $availability_range = explode(',', $requested_availability);

                    $min_availability = isset($availability_range[0]) && $availability_range[0] !== ''
                                        ? (float)$availability_range[0]
                                        : null;

                    $max_availability = isset($availability_range[1]) && $availability_range[1] !== ''
                                        ? (float)$availability_range[1]
                                        : null;

                    $value = $highlights["total_work_availability_per_centum"];

                    $meets_availability = true;

                    if (!is_null($min_availability)) {
                        $meets_availability = $meets_availability && ($value >= $min_availability);
                    }
                    if (!is_null($max_availability)) {
                        $meets_availability = $meets_availability && ($value <= $max_availability);
                    }
                }

                if (!is_null($is_late_required)) {
                    if ($is_late_required == 1) {
                        $meets_late_status = $highlights["total_late_days"] > 0;
                    } elseif ($is_late_required == 0) {
                        $meets_late_status = $highlights["total_late_days"] == 0;
                    }
                }

                return $meets_availability && $meets_late_status;
            })->values();




            $responseData = [
                'employees' => $employees,
            ];

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($employees->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.attendance_summary', ["employees" => $employees]);
                    }

                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new AttendancesExport($employees), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($responseData, 200);
            }
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v5.0/attendances",
     *      operationId="getAttendancesV5",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=false,
     * example="show_my_data"
     * ),
     *
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=false,
     *  example="6"
     *      ),

     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=false,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=false,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=false,
     * example="search_key"
     * ),
     *      *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=false,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=false,
     * example="ASC"
     * ),
     *
     * @OA\Parameter(
     * name="response_type",
     * in="query",
     * description="response_type",
     * required=false,
     * example="ASC"
     * ),
     *
     *
     *      summary="This method fetches attendance data for employees within a given date range, including leave records, attendance status, and holiday details. It returns detailed attendance information for each employee, such as hours worked, leave hours, and overtime hours.",
     *      description="This method fetches attendance data for employees within a given date range, including leave records, attendance status, and holiday details. It returns detailed attendance information for each employee, such as hours worked, leave hours, and overtime hours.",
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

    public function getAttendancesV5(Request $request)
    {
        try {
             ;
            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;

            $start_date = !empty($request->start_date)
                ? $request->start_date
                : Carbon::now()->startOfYear()->format('Y-m-d');

            $end_date = (!empty($request->end_date) && !Carbon::parse($request->end_date)->isFuture())
                ? $request->end_date
                : Carbon::now()->subDays(1);



            $usersQuery = User::with("departments")
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->whereDoesntHave("lastTermination", function ($query) use ($start_date) {
                    $query->where('terminations.date_of_termination', "<", $start_date)
                        ->whereRaw('terminations.date_of_termination > users.joining_date');
                })

                ->where(
                    [
                        "users.business_id" => $business_id
                    ]
                )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->whereHas("attendances", function ($q) use ($request) {
                        $idsArray = explode(',', $request->user_id);
                        $q->whereIn('attendances.user_id', $idsArray);
                    });
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })
                ->select(
                    "users.id",
                    'users.title',
                    "users.first_Name",
                    "users.middle_Name",
                    "users.last_Name",
                    "users.image",
                    "users.joining_date"
                );
                   $employees =  $this->retrieveData($usersQuery, "first_Name", "users");


            $data_highlights = [
                "total_active_hours" => 0,
                "total_extra_hours" => 0,
                "total_late_hours" => 0,
                "total_early_hours" => 0,
                "total_working_days" => 0,
                "total_absent_days" => 0,
                "total_leave_days" => 0,
                "total_late_days" => 0,
                "total_holiday_days" => 0,
                "total_schedule_days" => 0,
                "total_leave_hours" => 0,
                "behavior" => [
                    "absent" => 0,
                    "regular" => 0,
                    "early" => 0,
                    "late" => 0,
                ],
                "total_available_hours" => 0,
                "total_schedule_hours" => 0,
                "total_absent_hours" => 0,
                "total_pending_approval_hours" => 0,
                "total_work_availability_per_centum" => 0,
                "average_behavior" => "",
                "dates" =>[]

            ];

            // Iterate over each employee
            $employees =   $employees->map(function ($employee) use ($start_date, $end_date, &$data_highlights, $employees) {


                $date_of_termination = $employee->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($employee->joining_date, $date_of_termination, $start_date, $end_date);

                $employee = json_decode(json_encode($employee), true);
                $start_date = Carbon::parse($dates["start_date"]);
                $end_date = Carbon::parse($dates["end_date"]);

                $employee = $this->attendanceComponent->processAttendanceSummaryData($employee, $start_date, $end_date);


                $data_highlights["total_active_hours"] += $employee["data"]["data_highlights"]["total_active_hours"];
                $data_highlights["total_extra_hours"] += $employee["data"]["data_highlights"]["total_extra_hours"];
                $data_highlights["total_late_hours"] += $employee["data"]["data_highlights"]["total_late_hours"];
                $data_highlights["total_early_hours"] += $employee["data"]["data_highlights"]["total_early_hours"];
                $data_highlights["total_working_days"] += $employee["data"]["data_highlights"]["total_working_days"];

                 $data_highlights["total_pending_approval_hours"] += $employee["data"]["data_highlights"]["total_pending_approval_hours"];
                $data_highlights["total_absent_days"] += $employee["data"]["data_highlights"]["total_absent_days"];
                $data_highlights["total_leave_days"] += $employee["data"]["data_highlights"]["total_leave_days"];
                $data_highlights["total_late_days"] += $employee["data"]["data_highlights"]["total_late_days"];
                $data_highlights["total_holiday_days"] += $employee["data"]["data_highlights"]["total_holiday_days"];
                $data_highlights["total_schedule_days"] += $employee["data"]["data_highlights"]["total_schedule_days"];
                $data_highlights["total_leave_hours"] += $employee["data"]["data_highlights"]["total_leave_hours"];

                $data_highlights["behavior"]["regular"] += $employee["data"]["data_highlights"]["behavior"]["regular"];
                $data_highlights["behavior"]["early"] += $employee["data"]["data_highlights"]["behavior"]["early"];
                $data_highlights["behavior"]["late"] += $employee["data"]["data_highlights"]["behavior"]["late"];

                $data_highlights["total_available_hours"] += $employee["data"]["data_highlights"]["total_available_hours"];
                $data_highlights["total_schedule_hours"] += $employee["data"]["data_highlights"]["total_schedule_hours"];
                $data_highlights["total_absent_hours"] += $employee["data"]["data_highlights"]["total_absent_hours"];
                $data_highlights["total_work_availability_per_centum"] += $employee["data"]["data_highlights"]["total_work_availability_per_centum"];

                if (count($employees) == 1) {
                    $data_highlights["average_behavior"] = $employee["data"]["data_highlights"]["average_behavior"];
                }

                return $employee;
            });


            $responseData = [
                'data_highlights' => $data_highlights,
            ];

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($employees->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.attendance_summary', ["employees" => $employees]);
                    }

                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new AttendancesExport($employees), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($responseData, 200);
            }
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }






    /**
     *
     * @OA\Get(
     *      path="/v1.0/attendance-arrears",
     *      operationId="getAttendanceArrears",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
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
     *   * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *     *   * *  @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="project_id",
     * in="query",
     * description="project_id",
     * required=true,
     * example="1"
     * ),
     *  * @OA\Parameter(
     * name="work_location_id",
     * in="query",
     * description="work_location_id",
     * required=true,
     * example="1"
     * ),
     *     *  *   * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="pending_approval"
     * ),
     *
     *     *      *      * *  @OA\Parameter(
     * name="arrear_status",
     * in="query",
     * description="arrear_status",
     * required=true,
     * example="arrear_status"
     * ),
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *
     ** @OA\Parameter(
     *     name="attendance_date",
     *     in="query",
     *     description="Attendance Date",
     *     required=true,
     *     example="2024-02-13"
     * ),
     * @OA\Parameter(
     *     name="attendance_start_time",
     *     in="query",
     *     description="Attendance Start Time",
     *     required=true,
     *     example="08:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_end_time",
     *     in="query",
     *     description="Attendance End Time",
     *     required=true,
     *     example="17:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_break",
     *     in="query",
     *     description="Attendance Break Time",
     *     required=true,
     *     example="01:00:00"
     * ),
     * @OA\Parameter(
     *     name="attendance_schedule",
     *     in="query",
     *     description="Attendance Schedule",
     *     required=true,
     *     example="Regular"
     * ),
     * @OA\Parameter(
     *     name="attendance_overtime",
     *     in="query",
     *     description="Attendance Overtime",
     *     required=true,
     *     example="02:00:00"
     * ),

     *      summary="This endpoint allows you to retrieve attendance arrears information based on various filter parameters like dates, status, user, department, etc. The data can be returned in PDF, CSV, or JSON format.",
     *      description="This endpoint allows you to retrieve attendance arrears information based on various filter parameters like dates, status, user, department, etc. The data can be returned in PDF, CSV, or JSON format.",
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

    public function getAttendanceArrears(Request $request)
    {
        try {
             ;

            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $business_id =  auth()->user()->business_id;

            $attendancesQuery = Attendance::with([
                "employee" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "employee.departments" => function ($query) {
                    $query->select('departments.id', 'departments.name');
                },
                // "work_location",
                // "projects",
                "work_shift_history" => function ($query) {
                    $query->select(
                        'work_shift_histories.id',
                        'work_shift_histories.name',
                        'work_shift_histories.break_type',
                        'work_shift_histories.break_hours',
                        'work_shift_histories.type'
                    );
                },
                "attendance_records",
                "attendance_records.projects",
                "attendance_records.work_location",
                "arrear"
            ])
            ->where('attendances.status', "approved")

                ->where(
                    [
                        "attendances.business_id" => $business_id
                    ]
                )

                ->when(
                    !empty($request->arrear_status),
                    function ($query) use ($request) {
                        $query->whereHas("arrear", function ($query) use ($request) {
                            $query
                                ->where(
                                    "attendance_arrears.status",
                                    $request->arrear_status
                                );
                        });
                    },
                    function ($query) use ($request) {
                        $query->whereHas("arrear", function ($query) use ($request) {
                            $query
                                ->whereNotNull(
                                    "attendance_arrears.status"
                                );
                        });
                    }

                )
                ->filterAttendance($all_manager_department_ids);



            $attendances = $this->retrieveData($attendancesQuery, "in_date", "attendances");


            foreach ($attendances as $attendance) {
                if (!empty($attendance->work_shift_history)) {
                    // Filter the details array to only keep the elements where day matches

                    $details = $attendance->work_shift_history->details;
                    unset($attendance->work_shift_history->details);
                    $attendance->work_shift_history->details = collect($details)
                        ->filter(function ($detail) use ($attendance) {
                            $day_number = Carbon::parse($attendance->in_date)->dayOfWeek;
                            return $day_number === $detail["day"];
                        })
                        ->values()  // Re-index the array
                        ->all();
                }
            }




            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($attendances->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.attendances', ["attendances" => $attendances]);
                    }

                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new AttendancesExport($attendances), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($attendances, 200);
            }

            return response()->json($attendances, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/attendances/{id}",
     *      operationId="getAttendanceById",
     *      tags={"attendances"},
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
     *      summary="Fetches detailed attendance information by the specified attendance ID.",
     *      description="Fetches detailed attendance information by the specified attendance ID.",
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


    public function getAttendanceById($id, Request $request)
    {
        try {
             ;

            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('attendance_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;

            $attendance =  Attendance::with(

                [
                    "employee",
                    // "projects" => function ($query) {
                    //     $query->select(
                    //         'projects.id',
                    //         // 'projects.name',
                    //     );
                    // },
                    "work_shift_history" => function ($query) {
                        $query->select(
                            'work_shift_histories.id',
                            'work_shift_histories.name',
                            'work_shift_histories.break_type',
                            'work_shift_histories.break_hours',
                            'work_shift_histories.type'
                        );
                    },

                    "attendance_records",
                    "attendance_records.projects",
                    "attendance_records.work_location",

                ]

            )->where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->first();
            if (!$attendance) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($attendance, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

  /**
     *
     * @OA\Get(
     *      path="/v1.0/attendances/show/check-in-status",
     *      operationId="getCurrentAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method retrieves the current attendance details of the authenticated user, including whether they have checked in today and any overlapping attendance records.",
     *      description="This method retrieves the current attendance details of the authenticated user, including whether they have checked in today and any overlapping attendance records.",
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


    public function getCurrentAttendance(Request $request)
    {
        try {
             ;

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
                    "attendance_records.projects",
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
                return response()->json([], 200);
            }


            $attendance->is_overlapping_time = $this->attendanceComponent->validateAttendanceRecords($attendance->in_date, $attendance->attendance_records, false);








            return response()->json($attendance, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v2.0/attendances/show/check-in-status",
     *      operationId="getCurrentAttendanceV2",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *              @OA\Parameter(
     *         name="current_date",
     *         in="query",
     *         description="current_date",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method retrieves the current attendance details of the authenticated user, including whether they have checked in today and any overlapping attendance records.",
     *      description="This method retrieves the current attendance details of the authenticated user, including whether they have checked in today and any overlapping attendance records.",
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


    public function getCurrentAttendanceV2(Request $request)
    {
        try {
             ;

            if (!request()->has("current_date")) {
                return response()->json([
                    "message" => "current_date field is missing"
                ], 400);
            }

            $current_date = Carbon::parse(request()->input("current_date"));

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
                    "attendance_records.projects",
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
                return response()->json(["is_clocked_in"=>0], 200);
            }


            $attendance->is_overlapping_time = $this->attendanceComponent->validateAttendanceRecords($attendance->in_date, $attendance->attendance_records, false);








            return response()->json(["is_clocked_in"=>$attendance->is_clocked_in], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/attendances/{ids}",
     *      operationId="deleteAttendancesByIds",
     *      tags={"attendances"},
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
     *      summary="This method deletes attendance records based on the provided comma-separated list of attendance IDs. It checks if the user has permission to delete the records and ensures that the records belong to the user’s business and departments under their management. It also ensures that the attendance records are not linked to the current user, and handles related payroll deletions.",
     *      description="This method deletes attendance records based on the provided comma-separated list of attendance IDs. It checks if the user has permission to delete the records and ensures that the records belong to the user’s business and departments under their management. It also ensures that the attendance records are not linked to the current user, and handles related payroll deletions.",
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

    public function deleteAttendancesByIds(Request $request, $ids)
    {


        try {
             ;
            if (!$request->user()->hasPermissionTo('attendance_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("attendances", function ($query) use ($idsArray) {
                $query->whereIn('attendances.id', $idsArray);
            })
                ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);




            $existingIds = Attendance::where([
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



            $attendances =  Attendance::whereIn("id", $existingIds)->get();

            $this->restrict_attendances_modification_for_payroll($attendances);



            $payrolls = Payroll::whereHas("payroll_attendances", function ($query) use ($existingIds) {
                $query->whereIn("payroll_attendances.attendance_id", $existingIds);
            })->get();

            PayrollAttendance::whereIn("attendance_id", $existingIds)
                ->delete();



            Attendance::whereIn('id', $existingIds)->delete();








            $this->send_notification($attendances, $attendances->first()->employee, "Attendance deleted", "delete", "attendance");

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/attendances-leaves/{type}/{id}",
     *      operationId="deleteAttendancesLeaveRecordsById",
     *      tags={"attendances"},
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
     *      summary="This method deletes attendance records based on the provided comma-separated list of attendance IDs. It checks if the user has permission to delete the records and ensures that the records belong to the user’s business and departments under their management. It also ensures that the attendance records are not linked to the current user, and handles related payroll deletions.",
     *      description="This method deletes attendance records based on the provided comma-separated list of attendance IDs. It checks if the user has permission to delete the records and ensures that the records belong to the user’s business and departments under their management. It also ensures that the attendance records are not linked to the current user, and handles related payroll deletions.",
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

    public function deleteAttendancesLeaveRecordsById(Request $request, $type, $id)
    {


        try {
             ;
            if (!$request->user()->hasPermissionTo('attendance_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();


            if ($type == "attendance") {
                $attendance =  Attendance::where([
                    "business_id" =>  auth()->user()->business_id
                ])
                    ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    })
                    ->whereHas("employee", function ($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
                    })

                    ->where('id', $id)
                    ->first();

                if (empty($attendance)) {
                    return response()->json([
                        "message" => "Data not found"
                    ], 404);
                }

                $this->restrict_attendances_modification_for_payroll([$attendance]);
                $this->touchUserUpdatedAt([$attendance->user_id]);
                $this->send_notification($attendance, $attendance->employee, "Attendance updated", "delete", "attendance");

                $attendance->delete();
            } else if ($type == "leave_record") {


                $leave_record = LeaveRecord::whereHas("leave", function ($query) use ($all_manager_department_ids) {
                    $query->where([
                        "business_id" => auth()->user()->business_id
                    ])
                        ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })

                        ->whereHas("employee", function ($query) {
                            $query->whereNotIn("users.id", [auth()->user()->id]);
                        })
                    ;
                })

                    ->where('id', $id)
                    ->first();

                if (empty($leave_record)) {
                    return response()->json([
                        "message" => "Data not found"
                    ], 404);
                }

                $leave = $leave_record->leave;
                $employee = $leave->employee;

                $this->restrict_leaves_modification_for_payroll([$leave_record]);

                $this->touchUserUpdatedAt([$employee->user_id]);

                $leave->total_leave_hours = $leave->total_leave_hours - $leave_record->leave_hours;
                $leave->save();

                $this->leaveComponent->deleteLeaveAvailability($leave, $leave_record->leave_hours);


                $attendances = Attendance::where("leave_record_id", $leave_record->id)
                    ->where("consider_overtime", 1)
                    ->get();

                $this->attendanceComponent->updateAttendanceOverTime($attendances);

                $this->send_notification($leave, $leave->employee, "Leave record Request Deleted", "delete", "leave");
            }


            return response()->json(["message" => "data deleted sussfully"], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }





    /**
     *
     * @OA\Post(
     *      path="/v1.0/attendances/bypass/multiple",
     *      operationId="createMultipleBypassAttendance",
     *      tags={"attendances"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method allows for the creation of multiple attendance records for users based on a given date range. It checks user permissions, verifies that the records belong to the user's business and departments, and ensures that the attendance records are not linked to the current user. It also handles relevant payroll deletions and attendance validations such as checking for existing attendance, holiday dates, and leave records. Additionally, it ensures that termination and joining dates are considered before adding attendance data.",
     *      description="This method allows for the creation of multiple attendance records for users based on a given date range. It checks user permissions, verifies that the records belong to the user's business and departments, and ensures that the attendance records are not linked to the current user. It also handles relevant payroll deletions and attendance validations such as checking for existing attendance, holiday dates, and leave records. Additionally, it ensures that termination and joining dates are considered before adding attendance data.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="user_ids", type="string", format="array", example={1,2,3}),
     *    *    @OA\Property(property="start_date", type="string", format="string", example="date"),
     *    *    *    @OA\Property(property="end_date", type="string", format="string", example="date"),
     *
     *
     *
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

    public function createMultipleBypassAttendance(AttendanceBypassMultipleCreateRequest $request)
    {
        DB::beginTransaction();
        try {
             ;
            // Check if the user is authorized to perform this action
            if (!$request->user()->hasRole('business_owner')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Validate the request data
            $request_data = $request->validated();

            // Retrieve users based on request data
            if (empty($request_data["user_ids"])) {
                $users  =  User::with("lastTermination")->where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->select(
                        "id",
                        "joining_date",
                        'users.title',
                        'first_Name',
                        'last_Name',
                        'middle_Name',
                    )
                    ->get();
            } else {
                $users  =  User::with("lastTermination")->where([
                    "business_id" => auth()->user()->business_id
                ])
                    ->whereIn("id", $request_data["user_ids"])
                    ->select(
                        "id",
                        "joining_date",
                        'users.title',
                        'first_Name',
                        'last_Name',
                        'middle_Name',
                    )
                    ->get();
            }

            $userList = collect();
            $attendanceData = [];



            // Iterate over each user
            foreach ($users as $user) {
                // Parse start and end dates
                $start_date = Carbon::parse($request_data["start_date"]);
                $end_date = Carbon::parse($request_data["end_date"]);


                $joining_date = Carbon::parse($user->joining_date);

                if ($joining_date->gt($end_date)) {
                    $user->message = "Attendance for below employees not added for full duration as attendance " . Carbon::createFromFormat('Y-m-d', $request_data["start_date"])->format('d-m-Y') . " - " . Carbon::createFromFormat('Y-m-d', $request_data["end_date"])->format('d-m-Y') . " have below issues.";
                    // $user->message="User joining date is after the end date";
                    $userList->push($user);
                    continue;
                }

                if ($joining_date->gt($start_date)) {
                    $start_date = $joining_date;
                }


                // Retrieve salary information for the user and date
                $salaryHistories = $this->get_salary_infos($user->id, $start_date, $end_date);




                // Retrieve work shift history for the user and date
                $work_shift_histories =  $this->workTimeManagementComponent->get_work_shift_histories($start_date, $end_date, $user->id, false);

                if (collect($work_shift_histories)->count() == 0) {
                    $user->message = "No Work Shift Found for this Employee";
                    $userList->push($user);
                    continue;
                }






                // Get all existing attendance dates for the user within the date range
                $existingAttendanceDates = $this->attendanceComponent->get_existing_attendanceDates($start_date, $end_date, $user->id);


                $holiday_dates =  $this->workTimeManagementComponent->get_holiday_dates($start_date, $end_date, $user->id);

                $leave_dates =  $this->workTimeManagementComponent->get_already_taken_leave_record_dates($start_date, $end_date, $user->id);





                $user->existing_attendance_dates = $existingAttendanceDates;
                $user->existing_holiday_dates = $holiday_dates;
                $user->existing_leave_dates = $leave_dates;
                // Make sure dates are unique
                $uniqueRestrictedDates = collect($existingAttendanceDates)
                    ->merge($holiday_dates)
                    ->merge($leave_dates)
                    ->unique()
                    ->toArray();



                $attendance_dates = $this->workTimeManagementComponent->generateDateRange($start_date, $end_date);

                $attendance_details = [];
                // Map date range to create attendance details
                foreach ($attendance_dates as $date) {

                    // Check if the date is in restricted dates array
                    if (in_array($date, $uniqueRestrictedDates)) {
                        continue; // Skip this date
                    }

                    $temp_data["in_date"] = $date;
                    $temp_data["does_break_taken"] = 1;
                    $temp_data["consider_overtime"] = 1;


                    $temp_data["is_present"] = 1;

                    $temp_data["work_location_id"] = $request_data["work_location_id"];
                    $temp_data["user_id"] = $user->id;

                    $attendance_details[] = $temp_data;
                }


                // Map attendance details to create attendances data
                $attendances   = collect($attendance_details)->map(function ($item) use ($user, $work_shift_histories, $salaryHistories, $request_data) {

                    // check termination
                    $terminationCheck = $this->checkJoinAndTerminationDate($user->joining_date, $item["in_date"], $user->lastTermination);
                    if (empty($terminationCheck["success"])) {
                        return false;
                    }

                    $itemInDate = Carbon::parse($item["in_date"]);



                    $work_shift_history = $work_shift_histories->first(function ($history) use ($itemInDate) {
                        $fromDate = Carbon::parse($history->from_date);
                        $toDate = $history->to_date ? Carbon::parse($history->to_date) : null;

                        return $itemInDate->greaterThanOrEqualTo($fromDate)
                            && ($toDate === null || $itemInDate->lessThanOrEqualTo($toDate));
                    });


                    // Retrieve work shift history for the user and date
                    if (empty($work_shift_history)) {
                        return false;
                    }

                    // Retrieve work shift details based on work shift history and date
                    $work_shift_details =  $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $item["in_date"]);



                    if (empty($work_shift_details)) {
                        return false;
                    }
                    if ($work_shift_details->is_weekend) {
                        return false;
                    }

                    if (empty($work_shift_details->shifts)) {
                        return false;
                    }





                    foreach ($work_shift_details->shifts as $index => $shift) {
                        $item["attendance_records"][$index]["in_time"] = Carbon::parse(Carbon::parse($item["in_date"])->toDateString() . ' ' . $shift["start_at"])->toDateTimeString();

                        $item["attendance_records"][$index]["out_time"] = Carbon::parse(Carbon::parse($item["in_date"])->toDateString() . ' ' . $shift["end_at"])->toDateTimeString();


                        // Add other fields with default values or calculations as needed
                        $item["attendance_records"][$index]["break_hours"] = $work_shift_history->break_hours; // Example default value for break_hours
                        $item["attendance_records"][$index]["is_paid_break"] = $work_shift_history->break_type == "paid" ? 1 : 0; // Example default value for is_paid_break
                        $item["attendance_records"][$index]["note"] = ""; // Example default value for note
                        $item["attendance_records"][$index]["project_ids"] = []; // Example default value for project_ids
                        $item["attendance_records"][$index]["work_location_id"] = $request_data["work_location_id"]; // Example default value for work_location_id
                    }



                    // Prepare data for attendance creation
                    $attendance_data = $this->attendanceComponent->prepare_data_on_attendance_create($item, $user->id);
                    $attendance_data["status"] = "approved";



                    // Retrieve salary information for the user and date
                    $user_salary_info = $salaryHistories->first(function ($history) use ($itemInDate) {
                        $fromDate = Carbon::parse($history["from_date"]);

                        $toDate = $history["to_date"] ? Carbon::parse($history["to_date"]) : null;


                        return $itemInDate->greaterThanOrEqualTo($fromDate)
                            && ($toDate === null || $itemInDate->lessThanOrEqualTo($toDate));
                    });


                    // Calculate capacity hours based on work shift details
                    $capacity_hours = (!empty($work_shift_details->is_weekend))
                        ? 0
                        : (
                            ($work_shift_history->break_type != "paid")
                            ? ($work_shift_details->schedule_hour - $work_shift_history->break_hours)
                            : $work_shift_details->schedule_hour
                        );



                    $total_present_hours = $this->attendanceComponent->calculate_total_present_hours($attendance_data["attendance_records"]);


                    // Adjust paid hours based on break taken and work shift history
                    $total_paid_hours = $total_present_hours - ($attendance_data["unpaid_break_hours"] ?? 0);


                    // Prepare attendance data
                    $attendance_data["break_type"] = $work_shift_history->break_type;
                    $attendance_data["break_hours"] = $work_shift_history->break_hours;
                    $attendance_data["paid_break_hours"] = 0;
                    $attendance_data["unpaid_break_hours"] = 0;

                    if ($attendance_data["break_type"] == "paid") {
                        $attendance_data["paid_break_hours"] = $work_shift_history->break_hours;
                    } else {
                        $attendance_data["unpaid_break_hours"] = $work_shift_history->break_hours;
                    }

                    $attendance_data["behavior"] = "regular";
                    $attendance_data["capacity_hours"] = $capacity_hours;
                    $attendance_data["work_hours_delta"] = 0;
                    $attendance_data["total_paid_hours"] = $total_paid_hours;
                    $attendance_data["regular_work_hours"] = $total_paid_hours;
                    // $attendance_data["work_shift_start_at"] = $work_shift_details->start_at;
                    // $attendance_data["work_shift_end_at"] =  $work_shift_details->end_at;
                    $attendance_data["work_shift_history_id"] = $work_shift_history->id;

                    $attendance_data["is_weekend"] = $work_shift_details->is_weekend;
                    $attendance_data["overtime_hours"] = 0;
                    $attendance_data["leave_hours"] = 0;


                    $attendance_data["regular_hours_salary"] =   $total_paid_hours * $user_salary_info["hourly_salary"];

                    $attendance_data["contractual_hours"] =   $user_salary_info["holiday_considered_hours"];

                    $attendance_data["is_self_clocked_in"] =  0;
                    $attendance_data["is_clocked_in"] =  0;

                    $attendance_data["overtime_hours_salary"] =   0;
                    $attendance_data["created_at"] =   now();
                    $attendance_data["updated_at"] =   now();


                    $attendance =       Attendance::create(
                        collect($attendance_data)->only([
                            "is_self_clocked_in",
                            "is_clocked_in",
                            'contractual_hours',
                            'note',
                            "in_geolocation",
                            "out_geolocation",
                            'user_id',
                            'in_date',
                            'does_break_taken',
                            "consider_overtime",
                            "behavior",
                            "capacity_hours",
                            "work_hours_delta",
                            "break_type",
                            "break_hours",
                            "paid_break_hours",
                            "unpaid_break_hours",
                            "total_paid_hours",
                            "regular_work_hours",
                            // "work_shift_start_at",
                            // "work_shift_end_at",
                            "work_shift_history_id",
                            "holiday_id",
                            "leave_record_id",
                            "is_weekend",
                            "overtime_hours",
                            "leave_hours",
                            "punch_in_time_tolerance",
                            "tolerance_time",
                            "status",
                            // 'work_location_id',
                            "is_active",
                            "business_id",
                            "created_by",
                            "regular_hours_salary",
                            "overtime_hours_salary",
                            // "attendance_records",
                            "is_present"
                        ])
                            ->toArray()
                    );



                    if (isset($attendance_data['attendance_records'])) {
                        // First, delete old attendance history records
                        $attendance->attendance_records()->delete();

                        // Now, add new attendance history records
                        foreach ($attendance_data['attendance_records'] as $attendanceRecordData) {
                            $attendanceRecord = AttendanceRecord::create([
                                'attendance_id' => $attendance->id,
                                'in_time' => $attendanceRecordData['in_time'],
                                'out_time' => $attendanceRecordData['out_time'],
                                'break_hours' => $attendanceRecordData['break_hours'],
                                'is_paid_break' => $attendanceRecordData['is_paid_break'],
                                'note' => $attendanceRecordData['note'] ?? null,
                                'work_location_id' => $attendanceRecordData['work_location_id'],
                            ]);

                            // Sync the projects for each attendance history record
                            if (isset($attendanceRecordData['project_ids'])) {
                                $attendanceRecord->projects()->sync($attendanceRecordData['project_ids']);
                            }
                        }
                    }




                    $observer = new AttendanceObserver();
                    $observer->updated_action($attendance, 'update');


                    $this->adjust_payroll_on_attendance_update($attendance, 0);





                    $this->send_notification($attendance, $attendance->employee, "Attendance updated", "update", "attendance");

                    return $attendance;
                })->filter()->values();

                // Collect attendance data and IDs
                $attendance_ids = $attendances->pluck('id')->toArray();
                $attendanceData[] = [
                    'user_id' => $user->id,
                    'attendance_ids' => $attendance_ids,
                ];
                if (!$attendances->count()) {
                    $user->message = "No Attendance Record To Insert";
                    $userList->push($user);
                }
            }

            DB::commit();

            return response()->json([
                "ok" => true,
                "attendance_not_createdFor_users" => $userList->toArray(),
                "attendance_data" => $attendanceData
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }
}
