<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\SettingLeaveTypeCreateRequest;
use App\Http\Requests\SettingLeaveTypeUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\DisabledSettingLeaveType;
use App\Models\EmployeeLeaveAllowance;
use App\Models\Leave;
use App\Models\LeaveHistory;
use App\Models\LeaveTypeEmploymentStatus;
use App\Models\SettingLeaveType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingLeaveTypeController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-leave-types",
     *      operationId="createSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting leave type",
     *      description="This method is to store setting leave type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="is_active", type="boolean", example="1"),
 *      *   *     @OA\Property(property="carry_over_limit", type="number", example="0"),
     *   *     @OA\Property(property="leave_rollover_type", type="string", example="nonee"),
 *     @OA\Property(property="is_earning_enabled", type="boolean", example="1"),
 *     @OA\Property(property="name", type="string", format="string", example="yy"),
 *     @OA\Property(property="type", type="string", format="string", example="paid"),
 *     @OA\Property(property="amount", type="string", format="string", example="30")
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

    public function createSettingLeaveType(SettingLeaveTypeCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_leave_type_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();



                $request_data["is_default"] = 0;
                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }





                $setting_leave_type =  SettingLeaveType::create($request_data);



                $setting_leave_type->employment_statuses()->sync($request_data["employment_statuses"]);



                return response($setting_leave_type, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/setting-leave-types",
     *      operationId="updateSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update setting leave type ",
     *      description="This method is to update setting leave type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 *     @OA\Property(property="name", type="string", format="string", example="yy"),
 *     @OA\Property(property="type", type="string", format="string", example="paid"),
 *     @OA\Property(property="amount", type="string", format="string", example="30"),



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

    public function updateSettingLeaveType(SettingLeaveTypeUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_leave_type_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                $setting_leave_type  =  SettingLeaveType::where([
                    "id" => $request_data["id"],

                ])
                ->first();

                if (!$setting_leave_type) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

               $non_zero_allowance_exists = EmployeeLeaveAllowance::where('setting_leave_type_id', $setting_leave_type->id)
                ->where(function ($query) {
                    $query->where('used_leave_hours', '!=', 0)
                          ->orWhere('carry_over_hours', '!=', 0);
                })
                ->exists();

            if ($non_zero_allowance_exists) {
                return response()->json([
                    "message" => "You cannot update the leave type because it is already in use."
                ], 403);
            }

             EmployeeLeaveAllowance::where('setting_leave_type_id', $setting_leave_type->id)
             ->delete();


                $setting_leave_type->fill($request_data);
                $setting_leave_type->save();





                $setting_leave_type->employment_statuses()->sync($request_data["employment_statuses"]);
                return response($setting_leave_type, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

   /**
     *
     * @OA\Put(
     *      path="/v1.0/setting-leave-types/toggle-active",
     *      operationId="toggleActiveSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle user leave type",
     *      description="This method is to toggle user leave type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

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

     public function toggleActiveSettingLeaveType(GetIdRequest $request)
     {

         try {

             if (!$request->user()->hasPermissionTo('setting_leave_type_activate')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $this->toggleActivation(
                SettingLeaveType::class,
                DisabledSettingLeaveType::class,
                'setting_leave_type_id',
                $request_data["id"],
                auth()->user()
            );


            return response()->json(['message' => 'leave type status updated successfully'], 200);
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }
      /**
     *
     * @OA\Put(
     *      path="/v1.0/setting-leave-types/toggle-earning-enabled",
     *      operationId="toggleEarningEnabledSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle Earning Enabled ",
     *      description="This method is to toggle Earning Enabled",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

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

     public function toggleEarningEnabledSettingLeaveType(GetIdRequest $request)
     {

         try {

             if (!$request->user()->hasPermissionTo('setting_leave_type_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $setting_leave_type =  SettingLeaveType::where([
                "id" => $request_data["id"],
            ])
            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', NULL)
                             ->where('setting_leave_types.is_default', 1);
            })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', auth()->user()->business_id);
            })
                ->first();
            if (!$setting_leave_type) {

                return response()->json([
                    "message" => "no data found"
                ] , 404);
            }



             $setting_leave_type->update([
                 'is_earning_enabled' => !$setting_leave_type->is_earning_enabled
             ]);

             return response()->json(['message' => 'User status updated successfully'], 200);
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-leave-types",
     *      operationId="getSettingLeaveTypes",
     *      tags={"settings.setting_leave_types"},
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
     * @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
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

     *      summary="This method is to get setting leave types  ",
     *      description="This method is to get setting leave types ",
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

    public function getSettingLeaveTypes(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('setting_leave_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $created_by  = NULL;
            if(auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }


            $setting_leave_typesQuery = SettingLeaveType::with("employment_statuses")
            ->when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('setting_leave_types');
                }, function ($query) use ($created_by) {
                    $query->forNonSuperAdmin('setting_leave_types', $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query)  {
                $query->forBusiness('setting_leave_types');
            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("setting_leave_types.name", "like", "%" . $term . "%")
                            ->orWhere("setting_leave_types.type", "like", "%" . $term . "%");
                    });
                })
              
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_leave_types.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_leave_types.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $setting_leave_types =  $this->retrieveData($setting_leave_typesQuery, "id", "setting_leave_types");




            return response()->json($setting_leave_types, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-leave-types/{id}",
     *      operationId="getSettingLeaveTypeById",
     *      tags={"settings.setting_leave_types"},
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
     *      summary="This method is to get setting leave type by id",
     *      description="This method is to get setting leave type by id",
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


    public function getSettingLeaveTypeById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('setting_leave_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $setting_leave_type =  SettingLeaveType::with("employment_statuses")->where([
                "id" => $id,
            ])
                ->first();


                if (!$setting_leave_type) {

                    return response()->json([
                        "message" => "no data found"
                    ], 404);
                }
                if (empty(auth()->user()->business_id)) {

                    if (auth()->user()->hasRole('superadmin')) {
                        if (($setting_leave_type->business_id != NULL )) {

                            return response()->json([
                                "message" => "You do not have permission to update this leave type due to role restrictions."
                            ], 403);
                        }
                    } else {
                        if ($setting_leave_type->business_id != NULL) {

                            return response()->json([
                                "message" => "You do not have permission to update this leave type due to role restrictions."
                            ], 403);
                        } else if ($setting_leave_type->is_default == 0 && $setting_leave_type->created_by != auth()->user()->id) {

                                return response()->json([
                                    "message" => "You do not have permission to update this leave type due to role restrictions."
                                ], 403);

                        }
                    }
                } else {
                    if ($setting_leave_type->business_id != NULL) {
                        if (($setting_leave_type->business_id != auth()->user()->business_id)) {

                            return response()->json([
                                "message" => "You do not have permission to update this leave type due to role restrictions."
                            ], 403);
                        }
                    } else {
                        if ($setting_leave_type->is_default == 0) {
                            if ($setting_leave_type->created_by != auth()->user()->created_by) {

                                return response()->json([
                                    "message" => "You do not have permission to update this leave type due to role restrictions."
                                ], 403);
                            }
                        }
                    }
                }




            return response()->json($setting_leave_type, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/setting-leave-types/{ids}",
     *      operationId="deleteSettingLeaveTypesByIds",
     *      tags={"settings.setting_leave_types"},
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
     *      summary="This method is to delete setting leave type by id",
     *      description="This method is to delete setting leave type by id",
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

    public function deleteSettingLeaveTypesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('setting_leave_type_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = SettingLeaveType::whereIn('id', $idsArray)
            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                if ($request->user()->hasRole("superadmin")) {
                    return $query->where('setting_leave_types.business_id', NULL)
                        ->where('setting_leave_types.is_default', 1);
                } else {
                    return $query->where('setting_leave_types.business_id', NULL)
                        ->where('setting_leave_types.is_default', 0)
                        ->where('setting_leave_types.created_by', $request->user()->id);
                }
            })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                    ->where('setting_leave_types.is_default', 0);
            })
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

            $conflicts = [];

            // Check for conflicts in Leaves with Leave Types
            $leave_exists = Leave::whereIn("leave_type_id", $existingIds)->exists();
            if ($leave_exists) {
                $conflicts[] = "Leaves associated with the specified Leave Types";
            }

            // Check for conflicts in Leave History with Leave Types
            $leave_history_exists = LeaveHistory::whereIn("leave_type_id", $existingIds)->exists();
            if ($leave_history_exists) {
                $conflicts[] = "Leave History records associated with the specified Leave Types";
            }

            // Return combined error message if conflicts exist
            if (!empty($conflicts)) {
                $conflictList = implode(', ', $conflicts);
                return response()->json([
                    "message" => "Cannot delete this data as there are records associated with it in the following areas: $conflictList. Please update these records before attempting to delete.",
                ], 409);
            }

            // Proceed with the deletion process if no conflicts are found.

            // $leave_type_employment_statuses_exists =  LeaveTypeEmploymentStatus::whereIn("setting_leave_type_id",$existingIds)->exists();
            // if($leave_type_employment_statuses_exists) {
            //     // $conflictingLeaves = Leave::whereIn("leave_type_id", $existingIds)->get(['id']);
            //     return response()->json([
            //         "message" => "Some users are using some of the specified leave types",
            //         // "conflicting_leaves" => $conflictingLeaves
            //     ], 409);

            // }


            SettingLeaveType::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
