<?php

namespace App\Http\Controllers;

use App\Http\Components\UserManagementComponent;
use App\Http\Components\WorkShiftHistoryComponent;
use App\Http\Requests\UserUpdateWorkShiftRequest;
use App\Http\Requests\UserUpdateWorkShiftRequestV2;
use App\Http\Requests\AddUpdateWorkShiftRequest;

use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRecord;
use App\Models\Notification;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelExcel;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class WorkShiftHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, ModuleUtil;

    protected $workShiftHistoryComponent;
    protected $userManagementComponent;

    public function __construct(WorkShiftHistoryComponent $workShiftHistoryComponent,  UserManagementComponent $userManagementComponent)
    {
        $this->workShiftHistoryComponent = $workShiftHistoryComponent;
        $this->userManagementComponent = $userManagementComponent;
    }

    public function conflicted_work_shift_name($current_overlapping_work_shift_history)
    {
        $conflict_name = $current_overlapping_work_shift_history->name ?? 'Unnamed Work Shift';
        $conflict_start_date = $current_overlapping_work_shift_history->from_date ?? 'N/A';
        $conflict_end_date = $current_overlapping_work_shift_history->to_date ?? '';
        return [
            "conflict_name" => $conflict_name,
            "conflict_start_date" => $conflict_start_date,
            "conflict_end_date" => $conflict_end_date,
        ];
    }

    public function handleShiftConflicts($user, $request_data, $work_shift_history_id = NULL)
    {
        $current_overlapping_work_shift_history = $this->workShiftHistoryComponent->getCurrentOverlappingWorkShift($user->id, $request_data["from_date"], $work_shift_history_id);


        if (!empty($current_overlapping_work_shift_history)) {



            $attendance_exists =  Attendance::whereIn(
                "work_shift_history_id",
                [$current_overlapping_work_shift_history->id]
            )
                ->exists();

            $leave_record_exists = LeaveRecord::whereIn(
                "work_shift_history_id",
                [$current_overlapping_work_shift_history->id]
            )
                ->exists();



            if (empty($attendance_exists) && empty($leave_record_exists)) {
                $current_overlapping_work_shift_history->delete();
            } else if (!empty($attendance_exists)) {
                $conflicted_work_shift_name = $this->conflicted_work_shift_name($current_overlapping_work_shift_history);
                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name . " is assigned to the work shift '" . $conflicted_work_shift_name["conflict_name"] .
                        "' from " . Carbon::parse($conflicted_work_shift_name["conflict_start_date"])->format('d F Y') . " to " . ($conflicted_work_shift_name["conflict_end_date"] ? Carbon::parse($conflicted_work_shift_name["conflict_end_date"])->format('d F Y') : "Ongoing") . ". " .
                        "And there is attendance associated with the work shift '" . $conflicted_work_shift_name["conflict_name"] . "'. " .
                        "Remove all attendance data for the employee related to '" . $conflicted_work_shift_name["conflict_name"] . "' shift or please adjust the start date (" .
                        Carbon::parse($request_data["from_date"])->format('d F Y') . ") according to the attendance record to avoid overlapping shifts.",
                    400
                );
            } else if (!empty($leave_record_exists)) {
                $conflicted_work_shift_name = $this->conflicted_work_shift_name($current_overlapping_work_shift_history);
                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name . " is assigned to the work shift '" . $conflicted_work_shift_name["conflict_name"] .
                        "' from " . Carbon::parse($conflicted_work_shift_name["conflict_start_date"])->format('d F Y') . " to " . ($conflicted_work_shift_name["conflict_end_date"] ? Carbon::parse($conflicted_work_shift_name["conflict_end_date"])->format('d F Y') : "Ongoing") . ". " .
                        "And there is leave record associated with the work shift '" . $conflicted_work_shift_name["conflict_name"] . "'. " .
                        "Remove all leave record data for the employee related to '" . $conflicted_work_shift_name["conflict_name"] . "' shift or please adjust the start date (" .
                        Carbon::parse($request_data["from_date"])->format('d F Y') . ") according to the leave record to avoid overlapping shifts.",
                    400
                );
            }
        }
    }

    public function checkAndHandleFutureShift($user, $request_data, $is_creating_schedule = false, $work_shift_history_id = NULL)
    {
        $future_work_shift_history =  $this->workShiftHistoryComponent->getFutureWorkShift($user->id, $request_data["from_date"], $work_shift_history_id);
        if (!empty($future_work_shift_history)) {

            if ($future_work_shift_history->work_shift_id == $request_data["work_shift_id"]) {
                $conflict_name = $future_work_shift_history->name ?? 'Unnamed Work Shift';
                $conflict_start_date = $future_work_shift_history->from_date ?? 'N/A';
                $conflict_end_date = $future_work_shift_history->to_date ?? '';

                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name . " is already assigned to the work shift '" . $conflict_name .
                        "' scheduled from " . Carbon::parse($conflict_start_date)->format('d F Y') . " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") .
                        ". Consider extending the work shift's end date instead.",
                    400
                );
            }
            // only for whor shift history create
            if ($is_creating_schedule) {
                $after_date = Carbon::parse($future_work_shift_history->from_date);
                if ($after_date->isFuture()) {
                    $request_data["to_date"] = $after_date->copy()->subDay();
                } else {
                    $attendance_exists =  Attendance::whereIn(
                        "work_shift_history_id",
                        [$future_work_shift_history->id]
                    )
                        ->exists();
                    $leave_record_exists =  LeaveRecord::whereIn(
                        "work_shift_history_id",
                        [$future_work_shift_history->id]
                    )
                        ->exists();
                    if (empty($attendance_exists) && empty($leave_record_exists)) {
                        $request_data["to_date"] = $future_work_shift_history->to_date;
                        $future_work_shift_history->delete();
                    } else {
                        $request_data["to_date"] = $after_date->copy()->subDay();
                    }
                }
            }
        }
        return $request_data;
    }

    private function checkAndHandlePastShift($user, $request_data, $work_shift_history_id = NULL)
    {
        $past_work_shift_history = $this->workShiftHistoryComponent->getPastWorkShift($user->id, $request_data["from_date"], $work_shift_history_id);

        if (!empty($past_work_shift_history)) {

            if ($past_work_shift_history->work_shift_id == $request_data["work_shift_id"]) {
                $conflict_name = $past_work_shift_history->name ?? 'Unnamed Work Shift';
                $conflict_start_date = $past_work_shift_history->from_date ?? 'N/A';
                $conflict_end_date = $past_work_shift_history->to_date ?? '';

                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name . " is already assigned to the work shift '" . $conflict_name .
                        "' scheduled from " . Carbon::parse($conflict_start_date)->format('d F Y') . " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") .
                        ". Consider extending the work shift's end date instead.",
                    400
                );
            }
        }
    }


    private function checkAndHandleInnerShift($user, $request_data, $work_shift_history_id = NULL)
    {
        $inner_work_shift_history =  $this->workShiftHistoryComponent->getInnerWorkShift($user->id, $request_data["from_date"], $work_shift_history_id);

        if (!empty($inner_work_shift_history)) {

            if ($inner_work_shift_history->work_shift_id == $request_data["work_shift_id"]) {
                $conflict_name = $inner_work_shift_history->name ?? 'Unnamed Work Shift';
                $conflict_start_date = $inner_work_shift_history->from_date ?? 'N/A';
                $conflict_end_date = $inner_work_shift_history->to_date ?? '';


                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name .
                        " is already assigned to the work shift '" . $conflict_name .
                        "' scheduled from " . Carbon::parse($conflict_start_date)->format('d F Y') . " to " .
                        ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") .
                        ". And the date " . Carbon::parse($request_data["from_date"])->format('d F Y') . " is in this period, so duplicate work shifts are not allowed. " .
                        "If you want to make any changes to the date, use 'Edit Work Shift' in Work Shift History.",
                    400
                );
            }
            $attendance_exists = Attendance::whereDate("in_date", ">=", $request_data["from_date"])
                ->where("work_shift_history_id", $inner_work_shift_history->id)->exists();

            $leave_record_exists = LeaveRecord::whereDate("date", ">=", $request_data["from_date"])
                ->where("work_shift_history_id", $inner_work_shift_history->id)->exists();

            $conflict_name = $inner_work_shift_history->name ?? 'Unnamed Work Shift';
            $conflict_start_date = $inner_work_shift_history->from_date ?? 'N/A';
            $conflict_end_date = $inner_work_shift_history->to_date ?? '';

            if (!empty($attendance_exists)) {
                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name . " is assigned to the work shift '" . $conflict_name .
                        "' from " . Carbon::parse($conflict_start_date)->format('d F Y') . " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                        "And there is attendance associated with the work shift '" . $conflict_name . "'. " .
                        "Remove all attendance data for the employee related to '" . $conflict_name . "' shift or please adjust the start date (" .
                        Carbon::parse($request_data["from_date"])->format('d F Y') . ") according to the attendance record to avoid overlapping shifts.",
                    400
                );
            } else if (!empty($leave_record_exists)) {
                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name . " is assigned to the work shift '" . $conflict_name .
                        "' from " . Carbon::parse($conflict_start_date)->format('d F Y') . " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                        "And there is leave record associated with the work shift '" . $conflict_name . "'. " .
                        "Remove all leave record data for the employee related to '" . $conflict_name . "' shift or please adjust the start date (" .
                        Carbon::parse($request_data["from_date"])->format('d F Y') . ") according to the leave record to avoid overlapping shifts.",
                    400
                );
            } else {

                $inner_work_shift_history_from_date = Carbon::parse($inner_work_shift_history->from_date);

                if ($inner_work_shift_history->from_date == $inner_work_shift_history->to_date) {
                    $inner_work_shift_history->delete();
                } else {
                    $inner_work_shift_history_to_date = Carbon::parse($request_data["from_date"])->copy()->subDay();

                    if($inner_work_shift_history_from_date->gte($inner_work_shift_history_to_date)) {
                       $inner_work_shift_history->delete();
                    } else {
                    $inner_work_shift_history->to_date = $inner_work_shift_history_to_date;
                    $inner_work_shift_history->save();
                    }



                }
            }
        }
    }
    private function sendWorkShiftUpdateNotification($work_shift_history)
    {
        $employee_id = $work_shift_history->user_id;
        if (!empty($employee_id)) {
            $notification_description = "Your work schedule has been updated. Please review the new schedule.";
            $notification_link = "http://example.com/workshifts/1";
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

     private function processUpdateShift($user,$request_data,$work_shift) {
           $this->handleShiftConflicts($user, $request_data);
            $user_data = $this->checkAndHandleFutureShift($user, $request_data, true);

            $this->checkAndHandlePastShift($user, $user_data);
            $this->checkAndHandleInnerShift($user, $user_data);

            $work_shift_data = $work_shift->toArray();
            $work_shift_data["from_date"] = Carbon::parse($user_data["from_date"]);
            if (!empty($user_data["to_date"])) {
                $work_shift_data["to_date"] = Carbon::parse($user_data["to_date"]);
            }
            $work_shift_data["work_shift_id"] = $work_shift->id;
            $work_shift_data["user_id"] = $user_data["id"];
            $work_shift_history = WorkShiftHistory::create($work_shift_data);
            $work_shift_history->details()->createMany($work_shift->details->toArray());

            $this->sendWorkShiftUpdateNotification($work_shift_history);
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/update-work-shift",
     *      operationId="updateUserWorkShift",
     *      tags={"work_shift_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *      *    @OA\Property(property="work_shift_id", type="number", format="number",example="1"),
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

    public function updateUserWorkShift(UserUpdateWorkShiftRequest $request)
    {
        DB::beginTransaction();
        try {

            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();
            $request_data["from_date"] = Carbon::parse($request_data["from_date"]);
            $request_data["to_date"] = NULL;
            if (!empty($request_data["id"])) {
                $this->touchUserUpdatedAt([$request_data["id"]]);
            }

            $user = User::where([
                "id" => $request_data["id"],
            ])->first();



            $work_shift = $this->workShiftHistoryComponent->getWorkShiftById($request_data["work_shift_id"]);

            $this->processUpdateShift($user,$request_data,$work_shift);

            DB::commit();
            return response($user, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/update-work-shift-history",
     *      operationId="updateUserWorkShiftHistory",
     *      tags={"work_shift_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *      *    @OA\Property(property="work_shift_id", type="number", format="number",example="1"),
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

    public function updateUserWorkShiftHistory(UserUpdateWorkShiftRequestV2 $request)
    {
        DB::beginTransaction();
        try {

            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();
            $request_data["from_date"] = Carbon::parse($request_data["from_date"]);
            $request_data["to_date"] = NULL;
            if (!empty($request_data["id"])) {
                $this->touchUserUpdatedAt([$request_data["id"]]);
            }
            $user = User::where([
                "id" => $request_data["id"],
            ])->first();
            $work_shift = $this->workShiftHistoryComponent->getWorkShiftById($request_data["work_shift_id"]);

            $work_shift_history =  WorkShiftHistory::where([
                "id" => $request_data["work_shift_history_id"],
            ])
                ->where("user_id", $request_data["id"])
                ->where(function ($query) {
                    $query->where([
                        "work_shift_histories.business_id" => auth()->user()->business_id
                    ]);
                })
                ->orderByDesc("id")
                ->first();
            if (!$work_shift_history) {
                throw new Exception("no work shift history found", 403);
            }

            $attendance =  Attendance::whereIn(
                "work_shift_history_id",
                [$work_shift_history->id]
            )
                ->orderBy(
                    "attendances.in_date",
                    "ASC"
                )
                ->first();

            $leave_record =  LeaveRecord::whereIn(
                "work_shift_history_id",
                [$work_shift_history->id]
            )
                ->orderBy(
                    "leave_records.date",
                    "ASC"
                )
                ->first();


            $conflict_name = $work_shift_history->name ?? 'Unnamed Work Shift';
            $conflict_start_date = $work_shift_history->from_date ?? 'N/A';
            $conflict_end_date = $work_shift_history->to_date ?? '';
            if (!empty($attendance)) {

                $attendance_in_date = Carbon::parse($attendance->in_date);
                $request_data_from_date = Carbon::parse($request_data["from_date"]);

                if ($request_data["work_shift_id"] !== $work_shift_history->work_shift_id) {
                    throw new Exception(
                        "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name .
                            " is assigned to the work shift '" . $conflict_name . "' from " . Carbon::parse($conflict_start_date)->format('d F Y') .
                            " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                            "There is attendance associated with the work shift '" . $conflict_name . "'. " .
                            "Please remove all attendance data for the employee related to '" . $conflict_name . "' shift to resolve the conflict, " .
                            "or adjust the start date (" . Carbon::parse($request_data["from_date"])->format('d F Y') . ") to avoid overlapping shifts.",
                        400
                    );
                }
                if ($attendance_in_date->lt($request_data_from_date)) {
                    throw new Exception(
                        "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name .
                            " is assigned to the work shift '" . $conflict_name . "' from " . Carbon::parse($conflict_start_date)->format('d F Y') .
                            " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                            "There is attendance associated with the work shift '" . $conflict_name . "'. " .
                            "Please remove all attendance data for the employee related to '" . $conflict_name . "' shift to resolve the conflict, " .
                            "or adjust the start date (" . Carbon::parse($request_data["from_date"])->format('d F Y') . ") to avoid overlapping shifts.",
                        400
                    );
                }
            } else if (!empty($leave_record)) {

                $leave_record_date = Carbon::parse($leave_record->date);
                $request_data_from_date = Carbon::parse($request_data["from_date"]);

                if ($request_data["work_shift_id"] !== $work_shift_history->work_shift_id) {
                    throw new Exception(
                        "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name .
                            " is assigned to the work shift '" . $conflict_name . "' from " . Carbon::parse($conflict_start_date)->format('d F Y') .
                            " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                            "There is attendance associated with the work shift '" . $conflict_name . "'. " .
                            "Please remove all attendance data for the employee related to '" . $conflict_name . "' shift to resolve the conflict, " .
                            "or adjust the start date (" . Carbon::parse($request_data["from_date"])->format('d F Y') . ") to avoid overlapping shifts.",
                        400
                    );
                }
                if ($leave_record_date->lt($request_data_from_date)) {
                    throw new Exception(
                        "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name .
                            " is assigned to the work shift '" . $conflict_name . "' from " . Carbon::parse($conflict_start_date)->format('d F Y') .
                            " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                            "There is leave record associated with the work shift '" . $conflict_name . "'. " .
                            "Please remove all leave record data for the employee related to '" . $conflict_name . "' shift to resolve the conflict, " .
                            "or adjust the start date (" . Carbon::parse($request_data["from_date"])->format('d F Y') . ") to avoid overlapping shifts.",
                        400
                    );
                }
            }
            // only for whor shift history update
            $work_shift_history_inner_conflicted =  WorkShiftHistory::where("user_id", $request_data["id"])
                ->whereDate("from_date", ">", $request_data["from_date"])
                ->whereDate("from_date", "<", $work_shift_history->from_date)
                ->orderByDesc("id")
                ->first();
            if (!empty($work_shift_history_inner_conflicted)) {
                $conflict_name = $work_shift_history_inner_conflicted->name ?? 'Unnamed Work Shift';
                $conflict_start_date = $work_shift_history_inner_conflicted->from_date ?? 'N/A';
                $conflict_end_date = $work_shift_history_inner_conflicted->to_date ?? '';
                throw new Exception(
                    "Employee " . $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name .
                        " is assigned to the work shift '" . $conflict_name . "' from " . Carbon::parse($conflict_start_date)->format('d F Y') .
                        " to " . ($conflict_end_date ? Carbon::parse($conflict_end_date)->format('d F Y') : "Ongoing") . ". " .
                        "Please adjust the start date (" . Carbon::parse($request_data["from_date"])->format('d F Y') .
                        ") or resolve the conflict to avoid overlapping shifts.",
                    400
                );
            }

            $this->handleShiftConflicts($user, $request_data, $request_data["work_shift_history_id"]);
            $this->checkAndHandleFutureShift($user, $request_data, false, $request_data["work_shift_history_id"]);
            $this->checkAndHandlePastShift($user, $request_data, $request_data["work_shift_history_id"]);
            $this->checkAndHandleInnerShift($user, $request_data, $request_data["work_shift_history_id"]);



            if (empty($request_data["to_date"])) {
                $request_data["to_date"] = NULL;
            }
            $work_shift->work_shift_id = $work_shift->id;
            $work_shift->from_date = $request_data["from_date"];
            $work_shift->to_date = $request_data["to_date"];
            $work_shift_history->fill(
                collect($work_shift->toArray())->only([
                    'name',
                    'type',
                    'description',
                    'is_personal',
                    'break_type',
                    'break_hours',
                    'from_date',
                    'to_date',
                    'work_shift_id'

                ])->toArray()
            )
                ->save();
            $work_shift_history->details()->delete();
            foreach ($work_shift->details as $details) {
                $details_data = $details->toArray();
                $details_data["work_shift_id"] = $work_shift_history->id;
                $work_shift_history->details()->create($details_data);
            }

            $this->sendWorkShiftUpdateNotification($work_shift_history);


            DB::commit();
            return response($user, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/add-work-shift",
     *      operationId="addUserWorkShift",
     *      tags={"work_shift_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user",
     *      description="This method is to update user",
     *
      * @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *         required={"users", "user_data", "name", "is_personal", "break_type", "break_hours", "type", "work_locations", "details"},
 *
 *         @OA\Property(
 *             property="users",
 *             type="array",
 *             @OA\Items(type="integer", example=1)
 *         ),
 *
 *         @OA\Property(
 *             property="user_data",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 required={"id", "start_date"},
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="start_date", type="string", format="date", example="2025-05-08")
 *             )
 *         ),
 *
 *         @OA\Property(property="name", type="string", example="Morning Shift"),
 *         @OA\Property(property="description", type="string", nullable=true, example="Shift for the morning team"),
 *         @OA\Property(property="is_personal", type="boolean", example=true),
 *         @OA\Property(property="break_type", type="string", enum={"paid", "unpaid"}, example="paid"),
 *         @OA\Property(property="break_hours", type="number", example=1),
 *         @OA\Property(property="type", type="string", enum={"regular", "scheduled", "flexible"}, example="regular"),
 *
 *         @OA\Property(
 *             property="work_locations",
 *             type="array",
 *             @OA\Items(type="integer", example=101)
 *         ),
 *
 *         @OA\Property(
 *             property="details",
 *             type="array",
 *             minItems=7,
 *             maxItems=7,
 *             @OA\Items(
 *                 type="object",
 *                 required={"day", "is_weekend", "shifts"},
 *                 @OA\Property(property="day", type="integer", example=0, description="0 = Sunday, 6 = Saturday"),
 *                 @OA\Property(property="is_weekend", type="boolean", example=false),
 *                 @OA\Property(
 *                     property="shifts",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="start_at", type="string", format="time", nullable=true, example="09:00:00"),
 *                         @OA\Property(property="end_at", type="string", format="time", nullable=true, example="17:00:00"),
 *  @OA\Property(property="work_location_id", type="integer", nullable=true, example="1")
 *                     )
 *                 )
 *             )
 *         )
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

    public function addUserWorkShift(AddUpdateWorkShiftRequest  $request)
    {
        DB::beginTransaction();
        try {

            if (!$request->user()->hasPermissionTo('work_shift_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $request_data['departments'] = Department::where(
                [
                    "business_id" => auth()->user()->business_id,
                    "manager_id" => auth()->user()->id
                ]

            )
                ->pluck("id");


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

            $work_shift->refresh();


            foreach ($request_data["user_data"] as $user_data) {
                $user = User::find($user_data['id']);

                if (!$user) {
                    throw new Exception("User not found.");
                }

            $user_data["from_date"] = $user_data["start_date"];
            $user_data["work_shift_id"] = $work_shift->id;

            $this->processUpdateShift($user,$user_data,$work_shift);


            }



            DB::commit();
            return response($work_shift, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/work-shift-histories/{ids}",
     *      operationId="deleteWorkShiftHistoriesById",
     *      tags={"work_shift_histories"},
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

    public function deleteWorkShiftHistoriesById(Request $request, $ids)
    {

        try {


            if (!$request->user()->hasPermissionTo('work_shift_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $work_shift_history = WorkShiftHistory::where([
                "business_id" => auth()->user()->business_id
            ])
                ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->where('id', $ids)
                ->first();





            if (empty($work_shift_history)) {
                return response()->json([
                    "message" => "Work shift not found"
                ], 404);
            }

            $attendance_exists =    Attendance::where(
                "work_shift_history_id",
                $work_shift_history->id
            )
                ->exists();

            if (!empty($attendance_exists)) {
                return response()->json([
       'message' => 'Attendance records exist for the selected work shift(s). Please remove associated attendance records before proceeding.',
                ], 404);
            }

            $leave_record_exists =    Attendance::where(
                "work_shift_history_id",
                $work_shift_history->id
            )
                ->exists();

            if (!empty($leave_record_exists)) {
                return response()->json([
                    "message" => "Some leave record exists for this work shift."
                ], 404);
            }

            $previous_work_shift =  WorkShiftHistory::whereDate("to_date", Carbon::parse($work_shift_history->from_date)->subDay())
            ->where("user_id", $work_shift_history->user_id)
            ->first();

            if (!empty($previous_work_shift)) {
                $previous_work_shift->to_date = $work_shift_history->to_date ?? NULL;
                $previous_work_shift->save();
            }


            $work_shift_history->delete();

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $ids], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

     /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shift-histories-last-activity-dates",
     *      operationId="getLastActivityDates",
     *      tags={"work_shift_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_ids",
     *         in="query",
     *         description="user_ids",
     *         required=true,
     *  example="1,2"
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


    public function getLastActivityDates(Request $request)
    {
        try {
            $all_manager_department_ids = $this->get_all_departments_of_manager();

             if (!request()->filled("user_ids")) {
                return response()->json([
                    "message" => "User Ids are required"
                ], 422);
            }

            $users  = User::whereIn('users.id',explode(',', request()->input("user_ids")) )
            ->where('users.business_id', auth()->user()->business_id)
           ->whereHas('departments', function ($query) use($all_manager_department_ids) {
                $query->whereIn('departments.id', $all_manager_department_ids);
            })
            ->get()->map(function ($user) {

                return [
                    "id" => $user->id,
                    "last_activity_date" => $this->userManagementComponent->getLastActivityDate($user)
                ];
            });


            return response()->json($users, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shift-histories/{id}",
     *      operationId="getWorkShiftHistoryById",
     *      tags={"work_shift_histories"},
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


    public function getWorkShiftHistoryById($id, Request $request)
    {
        try {


            //  if (!$request->user()->hasPermissionTo('work_shift_view')) {
            //      return response()->json([
            //          "message" => "You can not perform this action"
            //      ], 401);
            //  }




            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shift =  WorkShiftHistory::with("details")
                ->where([
                    "id" => $id
                ])
                ->where(function ($query) use ($all_manager_department_ids) {
                    $query
                        ->where([
                            "work_shift_histories.business_id" => auth()->user()->business_id
                        ])
                        ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids)
                                ->orWhere("users.id", auth()->user()->id);
                        });
                })
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
     *      path="/v1.0/current-work-shift-history/{employee_id}",
     *      operationId="getCurrentWorkShiftHistory",
     *      tags={"work_shift_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="employee_id",
     *         in="path",
     *         description="employee_id",
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


    public function getCurrentWorkShiftHistory($employee_id, Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('work_shift_view') && $employee_id != auth()->user()->id) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shift_history =  WorkShiftHistory::with("details")
                ->where(function ($query) use ($all_manager_department_ids) {
                    $query
                        ->where([
                            "work_shift_histories.business_id" => auth()->user()->business_id
                        ])
                        ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids)->orWhere("users.id", auth()->user()->id);
                        });
                })
                ->where("user_id", $employee_id)
                ->where(function ($query)  {
                    $query->where("from_date", "<=", today())
                        ->where(function ($query) {
                            $query->where("to_date", ">=", today())
                                ->orWhereNull("to_date");
                        });
                })

                ->orderByDesc("work_shift_histories.id")

                ->first();

            if (empty($work_shift_history)) {
                throw new Exception("no work shift found for the user", 404);
            }

            return response()->json($work_shift_history, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
