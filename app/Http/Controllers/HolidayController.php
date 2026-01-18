<?php

namespace App\Http\Controllers;

use App\Exports\HolidayExport;
use App\Http\Components\AttendanceComponent;
use App\Http\Components\DepartmentComponent;
use App\Http\Requests\HolidayCreateRequest;
use App\Http\Requests\HolidayUpdateRequest;
use App\Http\Requests\HolidayUpdateStatusRequest;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Http\Utils\ModuleUtil;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use PDF;
use Maatwebsite\Excel\Facades\Excel;

class HolidayController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, BasicUtil, ModuleUtil, BasicNotificationUtil;




    protected $attendanceComponent;
    public function __construct(AttendanceComponent $attendanceComponent)
    {



        $this->attendanceComponent = $attendanceComponent;

    }




    /**
     *
     * @OA\Post(
     *      path="/v1.0/holidays",
     *      operationId="createHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store holiday",
     *      description="This method is to store holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     * *            @OA\Property(property="is_paid_holiday", type="boolean", format="boolean", example=false),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false)
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

    public function createHoliday(HolidayCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('holiday_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["status"] = "approved";


                $holiday =  Holiday::create($request_data);

                if(!$holiday->is_holiday_for_all) {
                    $holiday->departments()->sync($request_data["department_ids"]);

                    if(empty($request_data["user_ids"])) {
                        $request_data["user_ids"] = User::whereHas("departments", function($query) use($request_data) {
                               $query->whereIn("departments.id",$request_data["department_ids"]);
                        })
                            ->select("users.id")
                        ->get()->pluck("id")->toArray();
                    }

                    $holiday->employees()->sync($request_data["user_ids"]);
                    foreach($holiday->employees as $user) {

                        $this->send_notification_for_department($holiday, $user, "Holiday Taken", "create", "holiday");
                    }

                } else {
                    foreach($holiday->business->users as $user) {
                        $this->send_notification_for_department($holiday, $user, "Holiday Taken", "create", "holiday");
                    }
                }














                return response()->json($holiday, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

     /**
     *
     * @OA\Put(
     *      path="/v1.0/holidays/approve",
     *      operationId="approveHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update holiday ",
     *      description="This method is to update holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *             @OA\Property(property="status", type="string", format="string", example="Updated Christmas"),


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

     public function approveHoliday(HolidayUpdateStatusRequest $request)
     {

         try {


             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('holiday_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }
                 $business_id =  auth()->user()->business_id;
                 $request_data = $request->validated();



                 $holiday_query_params = [
                     "id" => $request_data["id"],
                     "business_id" => $business_id
                 ];


                 $holiday = Holiday::where($holiday_query_params)->first();


                 if (!$holiday) {
                    return response()->json([
                        "message" => "Something went wrong."
                    ], 500);
                }

                     $holiday->fill(collect($request_data)->only(['status'])->toArray());
                     $holiday->save();



             if(!$holiday->is_holiday_for_all) {

                $holiday->employees()->sync($request_data["user_ids"]);

                foreach($holiday->employees as $user) {

                    $this->send_notification_for_department($holiday, $user, "Holiday Taken", "create", "holiday");
                }

            } else {
                foreach($holiday->business->users as $user) {
                    $this->send_notification_for_department($holiday, $user, "Holiday Taken", "create", "holiday");
                }
            }



            $attendances = Attendance::whereIn("holiday_id", [$holiday->id])
            ->where("consider_overtime",1)
            ->get();


            $this->attendanceComponent->updateAttendanceOverTime($attendances);


            return response()->json($holiday, 201);

             });
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/holidays",
     *      operationId="updateHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update holiday ",
     *      description="This method is to update holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     *
     *            @OA\Property(property="is_paid_holiday", type="boolean", format="boolean", example=false),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false)

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

    public function updateHoliday(HolidayUpdateRequest $request)
    {
DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('holiday_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();


                $holiday = Holiday::where([
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ])
                    ->first();

                    if (!$holiday) {
                        return response()->json([
                            "message" => "something went wrong."
                        ], 500);
                    }

                $holiday->fill($request_data);
                $holiday->save();

                if(!$holiday->is_holiday_for_all) {
                    $holiday->departments()->sync($request_data["department_ids"]);

                    if(empty($request_data["user_ids"])) {
                        $request_data["user_ids"] = User::whereHas("departments", function($query) use($request_data) {
                               $query->whereIn("departments.id",$request_data["department_ids"]);
                        })
                        ->select("users.id")
                        ->get()->pluck("id")->toArray();
                    }

                    $holiday->employees()->sync($request_data["user_ids"]);
                    foreach($holiday->employees as $user) {

                        $this->send_notification_for_department($holiday, $user, "Holiday Taken", "create", "holiday");
                    }

                } else {
                    $holiday->departments()->sync([]);
                    $holiday->employees()->sync([]);
                    foreach($holiday->business->users as $user) {
                        $this->send_notification_for_department($holiday, $user, "Holiday Taken", "create", "holiday");
                    }
                }



                $attendances = Attendance::whereIn("holiday_id", [$holiday->id])
                ->where("consider_overtime",1)
                ->get();

                $this->attendanceComponent->updateAttendanceOverTime($attendances);





DB::commit();
                return response($holiday, 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/holidays",
     *      operationId="getHolidays",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *   *              @OA\Parameter(
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
     *

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
     *     * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *     *     * *  @OA\Parameter(
     * name="repeat",
     * in="query",
     * description="repeat",
     * required=true,
     * example="repeat"
     * ),
     *   *     *     * *  @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *

     *
     *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
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
     *      summary="This method is to get holidays  ",
     *      description="This method is to get holidays ",
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

     public function getHolidays(Request $request)
     {
         try {


             if(request()->boolean("show_my_data")) {
                 $this->isModuleEnabled("employee_login");
             }

             if (!$request->user()->hasPermissionTo('holiday_view') && !request()->boolean("show_my_data")) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $business_id =  auth()->user()->business_id;


             $holidaysQuery = Holiday::with([
                 "creator" => function ($query) {
                     $query->select(
                         'users.id',
                         'users.title',
                         'users.first_Name',
                         'users.middle_Name',
                         'users.last_Name'
                     );
                 },
                 "departments" => function ($query) {
                    $query->select(
                        'departments.id',
                        'departments.name',

                    );
                },
                "employees" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                }
             ])
                 ->where(
                     [
                         "holidays.business_id" => $business_id
                     ]
                 )

                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("holidays.name", "like", "%" . $term . "%")
                             ->orWhere("holidays.description", "like", "%" . $term . "%");
                     });
                 })

                 ->when(!empty($request->name), function ($query) use ($request) {
                     return $query->where("holidays.name", "like", "%" . $request->name . "%");
                 })

                 ->when(isset($request->is_paid_holiday), function ($query) use ($request) {
                    return $query->where('holidays.is_paid_holiday', intval($request->is_paid_holiday));
                })
                 ->when(isset($request->repeats_annually), function ($query) use ($request) {
                     return $query->where('holidays.repeats_annually', intval($request->repeats_annually));
                 })
                 ->when(!empty($request->description), function ($query) use ($request) {
                     return $query->where("holidays.description", "like", "%" . $request->description . "%");
                 })




                 ->when(request()->filled("start_date"), function ($query)  {
                    return $query->where('holidays.end_date', '>=', request()->input("start_date"));
                })

                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('holidays.start_date', '<=', $request->end_date);
                })

                ->when(request()->filled("status"), function ($query) use ($request) {
                    return $query->where('holidays.status', request()->input("status"));
                });

                  $holidays =  $this->retrieveData($holidaysQuery, "start_date", "holidays");

             if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                 if (strtoupper($request->response_type) == 'PDF') {

                     if(empty($holidays->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    }else {
                        $pdf = PDF::loadView('pdf.holidays', ["holidays" => $holidays]);
                    }
                     return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                 } elseif (strtoupper($request->response_type) === 'CSV') {

                     return Excel::download(new HolidayExport($holidays), ((!empty($request->file_name) ? $request->file_name : 'leave') . '.csv'));
                 }
             } else {
                 return response()->json($holidays, 200);
             }


             return response()->json($holidays, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/holidays/{id}",
     *      operationId="getHolidayById",
     *      tags={"administrator.holiday"},
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
     *      summary="This method is to get holiday by id",
     *      description="This method is to get holiday by id",
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


    public function getHolidayById($id, Request $request)
    {
        try {


            if(request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('holiday_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }





            $holiday =  Holiday::
              with(
                ["departments" => function ($query) {
                    $query->select(
                        'departments.id',
                        'departments.name',

                    );
                },
                "employees" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                }
                ]
              )
            ->withCount(['attendances as attendance_count'])
            ->with([
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                }
            ])->where([
                "id" => $id,
                "business_id" => auth()->user()->business_id
            ])

                ->first();
            if (!$holiday) {

                return response()->json([
                    "message" => "no holiday found"
                ], 404);
            }

            return response()->json($holiday, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/holidays/{ids}",
     *      operationId="deleteHolidaysByIds",
     *      tags={"administrator.holiday"},
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
     *      summary="This method is to delete holiday by id",
     *      description="This method is to delete holiday by id",
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

    public function deleteHolidaysByIds(Request $request, $ids)
    {
        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('holiday_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business_id =  auth()->user()->business_id;

            $holiday = Holiday::with("departments","employees")->where([
                "business_id" => $business_id
            ])
                ->where('id', $ids)
                ->first();


            if (empty($holiday)) {
                return response()->json([
                    "message" => "Holiday not found!"
                ], 404);
            }



            foreach($holiday->business->users as $user) {
                $this->send_notification_for_department($holiday, $user, "Holiday Deleted", "delete", "holiday");
            }

            $attendances = Attendance::whereIn("holiday_id", [$holiday->id])
            ->get();

            $holiday->delete();

            foreach($attendances as $attendance) {
                $attendance->holiday_id = "";
                $attendance->save();
            }
            $this->attendanceComponent->updateAttendanceOverTime($attendances);

            DB::commit();
            return response()->json(["message" => "data deleted sussfully"], 200);

        } catch (Exception $e) {

            DB::rollBack();
            return $this->sendError($e);
        }
    }

}
