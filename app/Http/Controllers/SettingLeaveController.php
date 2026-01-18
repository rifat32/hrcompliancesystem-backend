<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingLeaveCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\EmployeeLeaveAllowance;
use App\Models\SettingLeave;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingLeaveController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-leave",
     *      operationId="createSettingLeave",
     *      tags={"settings.setting_leave"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting leave",
     *      description="This method is to store setting leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="start_month", type="number", example="1"),

     *     @OA\Property(property="approval_level", type="string", example="single"),
     *     @OA\Property(property="allow_bypass", type="boolean", format="boolean", example="1"),
     *     @OA\Property(property="special_users", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="special_roles", type="string", format="array", example={1,2,3}),
     **    @OA\Property(property="paid_leave_employment_statuses", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="unpaid_leave_employment_statuses", type="string", format="array", example={1,2,3})
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

    public function createSettingLeave(SettingLeaveCreateRequest $request)
    {
                DB::beginTransaction();
        try {

                if (!$request->user()->hasPermissionTo('setting_leave_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["is_active"] = 1;

                if (empty(auth()->user()->business_id)) {

                    $request_data["business_id"] = NULL;
                    $request_data["is_default"] = 0;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                    $check_data =     [
                        "business_id" => $request_data["business_id"],
                        "is_default" => $request_data["is_default"]
                    ];
                    if (!$request->user()->hasRole('superadmin')) {
                        $check_data["created_by"] =    auth()->user()->id;
                    }
                } else {
                    $request_data["business_id"] = auth()->user()->business_id;
                    $request_data["is_default"] = 0;
                    $check_data =     [
                        "business_id" => $request_data["business_id"],
                        "is_default" => $request_data["is_default"]
                    ];
                }

                $setting_leave =     SettingLeave::updateOrCreate($check_data, $request_data);
                $current_year_start_date = Carbon::create(now()->year, 1, 1);
                $leave_start_date = Carbon::create(now()->year, $setting_leave->start_month, 1);
                $leave_expiry_date = $leave_start_date->copy()->addYear()->subDay();

                $last_year_start_date = Carbon::create(now()->year - 1, 1, 1)->subDay();


                EmployeeLeaveAllowance::whereDate("leave_start_date", ">=", $current_year_start_date)
                ->update([
                    "leave_start_date" => $leave_start_date,
                    "leave_expiry_date" => $leave_expiry_date,

                ]);

                EmployeeLeaveAllowance::whereDate("leave_start_date", ">=", $last_year_start_date)
                ->whereDate("leave_start_date", "<", $leave_start_date)
                ->update([
                    "leave_expiry_date" => $leave_start_date->subDay(),
                ]);

                $setting_leave->special_users()->sync($request_data['special_users']);
                $setting_leave->special_roles()->sync($request_data['special_roles']);
                $setting_leave->paid_leave_employment_statuses()->sync($request_data['paid_leave_employment_statuses']);
                $setting_leave->unpaid_leave_employment_statuses()->sync($request_data['unpaid_leave_employment_statuses']);

               DB::commit();
                return response($setting_leave, 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-leave",
     *      operationId="getSettingLeave",
     *      tags={"settings.setting_leave"},
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get setting leave  ",
     *      description="This method is to get setting leave ",
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

    public function getSettingLeave(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('setting_leave_create') && !$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $setting_leavesQuery = SettingLeave::with("special_users","special_roles","paid_leave_employment_statuses","unpaid_leave_employment_statuses")

            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                if (auth()->user()->hasRole('superadmin')) {
                    return $query->where('setting_leaves.business_id', NULL)
                        ->where('setting_leaves.is_default', 1)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('setting_leaves.is_active', request()->boolean("is_active"));
                        });
                } else {
                    return   $query->where('setting_leaves.business_id', NULL)
                    ->where('setting_leaves.is_default', 0)
                    ->where('setting_leaves.created_by', auth()->user()->id);
                }
            })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                 return   $query->where('setting_leaves.business_id', auth()->user()->business_id)
                    ->where('setting_leaves.is_default', 0);


                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("setting_leaves.name", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_leaves.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_leaves.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $setting_leaves =  $this->retrieveData($setting_leavesQuery, "id", "setting_leaves");




            return response()->json($setting_leaves, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




















}
