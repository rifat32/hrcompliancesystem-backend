<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Models\AttendanceHistory;

use App\Models\EmployeeAddressHistory;

use App\Models\EmployeeProjectHistory;


use App\Models\WorkShiftHistory;
use App\Models\LeaveHistory;
use App\Models\UserAssetHistory;
use Exception;
use Illuminate\Http\Request;

use PDF;

class HistoryDetailsController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, ModuleUtil;

    /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-assets",
     *      operationId="getUserAssetHistory",
     *      tags={"histories"},
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
     *   *   * *  @OA\Parameter(
     * name="user_asset_id",
     * in="query",
     * description="user_asset_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get asset history",
     *      description="This method is to get asset history",
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

    public function getUserAssetHistory(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $user_asset_historiesQuery = UserAssetHistory::with([
                    "user" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                ])
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_asset_histories.user_id', $request->user_id);
                })
                ->when(!empty($request->user_asset_id), function ($query) use ($request) {
                    return $query->where('user_asset_histories.user_asset_id', $request->user_asset_id);
                })
                ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->whereHas('user_asset', function ($query) use ($term) {

                            return   $query->where(function ($query) use ($term) {

                                $query->where("user_assets.name", "like", "%" . $term . "%")
                                    ->orWhere("user_assets.code", "like", "%" . $term . "%")
                                    ->orWhere("user_assets.serial_number", "like", "%" . $term . "%")
                                    ->orWhere("user_assets.type", "like", "%" . $term . "%");
                            });
                        });
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_asset_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_asset_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

            $user_asset_histories =  $this->retrieveData($user_asset_historiesQuery, "id", "user_asset_histories");




            return response()->json($user_asset_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-address-details",
     *      operationId="getUserAddressDetailsHistory",
     *      tags={"histories"},
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
     *    *    *  *    * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="ASC"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee address history",
     *      description="This method is to get address history",
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

    public function getUserAddressDetailsHistory(Request $request)
    {
        try {

            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('user_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $employee_address_historiesQuery = EmployeeAddressHistory::where(["is_manual" => 0])
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_address_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_address_histories.user_id', $request->user()->id);
                })
                ->when(request()->boolean("show_my_data"), function ($query) {
                    $query->where('employee_address_histories.user_id', auth()->user()->id);
                }, function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_address_histories.address_line_1", "like", "%" . $term . "%");
                        $query->orWhere("employee_address_histories.address_line_2", "like", "%" . $term . "%");
                        $query->orWhere("employee_address_histories.country", "like", "%" . $term . "%");
                        $query->orWhere("employee_address_histories.city", "like", "%" . $term . "%");
                        $query->orWhere("employee_address_histories.postcode", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_address_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_address_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
            $employee_address_histories =  $this->retrieveData($employee_address_historiesQuery, "id", "employee_address_histories");



            return response()->json($employee_address_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-attendance-details",
     *      operationId="getUserAttendanceDetailsHistory",
     *      tags={"histories"},
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
     *      *   * *  @OA\Parameter(
     * name="attendance_id",
     * in="query",
     * description="attendance_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee attendance history",
     *      description="This method is to get attendance history",
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

    public function getUserAttendanceDetailsHistory(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $employee_attendance_details_history = AttendanceHistory::with([
                    "employee" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "actor" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "approved_by_users.actor" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "rejected_by_users.actor" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "attendance_records",
                    "attendance_records.projects",
                    "attendance_records.work_location",

                    // "work_location",
                    // "projects",


                ])
                ->when(!empty($request->attendance_id), function ($query) use ($request) {
                    return $query->where('attendance_histories.attendance_id', $request->attendance_id);
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('attendance_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('attendance_histories.user_id', $request->user()->id);
                })
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        //  $query->where("attendance_histories.address_line_1", "like", "%" . $term . "%");
                        //  $query->orWhere("attendance_histories.address_line_2", "like", "%" . $term . "%");
                        //  $query->orWhere("attendance_histories.country", "like", "%" . $term . "%");
                        //  $query->orWhere("attendance_histories.city", "like", "%" . $term . "%");
                        //  $query->orWhere("attendance_histories.postcode", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('attendance_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('attendance_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });



            return response()->json($employee_attendance_details_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-leave-details",
     *      operationId="getUserLeaveDetailsHistory",
     *      tags={"histories"},
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
     *      *   * *  @OA\Parameter(
     * name="leave_id",
     * in="query",
     * description="leave_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee leave history",
     *      description="This method is to get leave history",
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

    public function getUserLeaveDetailsHistory(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $leave_historiesQuery = LeaveHistory::with(
                [
                    "records",
                    "employee" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "actor" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "approved_by_users.actor" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "rejected_by_users.actor" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                ]

            )
                ->when(!empty($request->leave_id), function ($query) use ($request) {
                    return $query->where('leave_histories.leave_id', $request->leave_id);
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('leave_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('leave_histories.user_id', $request->user()->id);
                })
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('leave_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('leave_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
            $leave_histories =  $this->retrieveData($leave_historiesQuery, "id", "leave_histories");


            return response()->json($leave_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-work-shift",
     *      operationId="getUserWorkShiftHistory",
     *      tags={"histories"},
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
     *      *   * *  @OA\Parameter(
     * name="leave_id",
     * in="query",
     * description="leave_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee work shift history",
     *      description="This method is to get work shift history",
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

    public function getUserWorkShiftHistory(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('user_view') && $request->user_id != auth()->user()->id) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $work_shift_historiesQuery = WorkShiftHistory::with([
                    "details",
                ])
                ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids)
                        ->when(!empty(request()->user_id), function ($query) {
                            $query->orWhere('user_id', request()->user_id);
                        });
                })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    $query->where('user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    $query->where('user_id', auth()->user()->id);
                })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('work_shift_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('work_shift_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
            $work_shift_histories =  $this->retrieveData($work_shift_historiesQuery, "from_date", "work_shift_histories");


            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($work_shift_histories->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.employee_work_shift_histories', ["employee_work_shift_histories" => $work_shift_histories]);
                    }

                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    // return Excel::download(new AttendancesExport($attendances), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($work_shift_histories, 200);
            }
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-project",
     *      operationId="getUserProjectHistory",
     *      tags={"histories"},
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
     *      *   * *  @OA\Parameter(
     * name="leave_id",
     * in="query",
     * description="leave_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee work shift history",
     *      description="This method is to get work shift history",
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

    public function getUserProjectHistory(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $employee_project_historiesQuery = EmployeeProjectHistory::when(!empty($request->user_id), function ($query) use ($request) {
                $query->where('employee_project_histories.user_id', $request->user_id);
            })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    $query->where('employee_project_histories.user_id', auth()->user()->id);
                })
                ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_project_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_project_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

            $employee_project_histories =  $this->retrieveData($employee_project_historiesQuery, "id", "employee_project_histories");


            return response()->json($employee_project_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
