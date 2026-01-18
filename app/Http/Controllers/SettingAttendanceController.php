<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingAttendanceCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingAttendanceController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-attendance",
     *      operationId="createSettingAttendance",
     *      tags={"settings.setting_attendance"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting attendance",
     *      description="This method is to store setting attendance",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="punch_in_time_tolerance", type="number", format="number", example="15"),
 *     @OA\Property(property="work_availability_definition", type="number", format="number", example="80"),
 *     @OA\Property(property="punch_in_out_alert", type="boolean", format="boolean", example="1"),
 *     @OA\Property(property="punch_in_out_interval", type="number", format="number", example="30"),
 *     @OA\Property(property="alert_area", type="string", format="array", example={"web","system"}),
 *
 *  *     @OA\Property(property="service_name", type="string", format="string", example="map"),
 *  *     @OA\Property(property="api_key", type="string", format="string", example="sdthsd@hrhfgf"),
 *
 *     @OA\Property(property="auto_approval", type="boolean", format="boolean", example="1"),
 *  *     @OA\Property(property="is_geolocation_enabled", type="boolean", format="boolean", example="1"),
 *
*     @OA\Property(property="special_users", type="string", format="array", example={1,2,3}),
 *     @OA\Property(property="special_roles", type="string", format="array", example={1,2,3})
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

    public function createSettingAttendance(SettingAttendanceCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_attendance_create')) {
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

                $setting_attendance =     SettingAttendance::updateOrCreate($check_data, $request_data);





                 $setting_attendance->special_users()->sync($request_data['special_users']);
                 $setting_attendance->special_roles()->sync($request_data['special_roles']);

                 $permission = 'attendance_approve';

                 foreach($request_data['special_users'] as $special_user_id){
                    $special_user = User::where([
                        "id" => $special_user_id
                    ])->first();
                    if(!$special_user) {
                          throw new Exception("no special user found");
                    }
                    if (!$special_user->hasPermissionTo($permission)) {
                        $special_user->givePermissionTo($permission);
                    }
                 }

                 foreach($request_data['special_roles'] as $special_role_id){
                    $special_role = Role::where([
                        "id" => $special_role_id
                    ])->first();
                    if(!$special_role) {
                          throw new Exception("no special role found");
                    }

                 if (!$special_role->hasPermissionTo($permission)) {
    $special_role->givePermissionTo($permission);
}
                 }







                return response($setting_attendance, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



      /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-attendance",
     *      operationId="getSettingAttendance",
     *      tags={"settings.setting_attendance"},
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

     *      summary="This method is to get setting attendance  ",
     *      description="This method is to get setting attendance ",
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

     public function getSettingAttendance(Request $request)
     {
         try {



             if (!$request->user()->hasPermissionTo('setting_attendance_create')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $setting_attendancesQuery = SettingAttendance::with("special_users","special_roles")
             ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                if (auth()->user()->hasRole('superadmin')) {
                    return $query->where('setting_attendances.business_id', NULL)
                        ->where('setting_attendances.is_default', 1)
                        ->when(request()->filled("is_active"), function ($query) use ($request) {
                            return $query->where('setting_attendances.is_active', request()->boolean("is_active"));
                        });
                } else {
                    return   $query->where('setting_attendances.business_id', NULL)
                    ->where('setting_attendances.is_default', 0)
                    ->where('setting_attendances.created_by', auth()->user()->id);
                }
            })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                 return   $query->where('setting_attendances.business_id', auth()->user()->business_id)
                    ->where('setting_attendances.is_default', 0);


                })


                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                        //  $query->where("setting_attendances.name", "like", "%" . $term . "%");
                     });
                 })

                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('setting_attendances.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('setting_attendances.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 });

                  $setting_attendances =  $this->retrieveData($setting_attendancesQuery, "id", "setting_attendances");




             return response()->json($setting_attendances, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }
}
