<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentStatusCreateRequest;
use App\Http\Requests\EmploymentStatusUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\DisabledEmploymentStatus;
use App\Models\EmploymentStatus;
use App\Models\LeaveTypeEmploymentStatus;
use App\Models\SettingPaidLeaveEmploymentStatus;
use App\Models\SettingUnpaidLeaveEmploymentStatus;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmploymentStatusController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/employment-statuses",
     *      operationId="createEmploymentStatus",
     *      tags={"employee.employment_statuses"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store employment status",
     *      description="This method is to store employment status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     *  * @OA\Property(property="color", type="string", format="string", example="red"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;")
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

    public function createEmploymentStatus(EmploymentStatusCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employment_status_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["is_active"] = 1;
                $request_data["is_default"] = 0;
                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }



                $employment_status =  EmploymentStatus::create($request_data);




                return response($employment_status, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/employment-statuses",
     *      operationId="updateEmploymentStatus",
     *      tags={"employee.employment_statuses"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update employment status ",
     *      description="This method is to update employment status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     *  *  * @OA\Property(property="color", type="string", format="string", example="red"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;")


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

    public function updateEmploymentStatus(EmploymentStatusUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employment_status_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();



                $employment_status_query_params = [
                    "id" => $request_data["id"],
                ];


                $employment_status  =  tap(EmploymentStatus::where($employment_status_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'color',
                        'description',
                        // "is_active",
                        // "business_id",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$employment_status) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($employment_status, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/employment-statuses/toggle-active",
     *      operationId="toggleActiveEmploymentStatus",
     *      tags={"employee.employment_statuses"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle employment status",
     *      description="This method is to toggle employment status",
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

    public function toggleActiveEmploymentStatus(GetIdRequest $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('employment_status_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $this->toggleActivation(
                EmploymentStatus::class,
                DisabledEmploymentStatus::class,
                'employment_status_id',
                $request_data["id"],
                auth()->user()
            );


            return response()->json(['message' => 'employment status status updated successfully'], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/employment-statuses",
     *      operationId="getEmploymentStatuses",
     *      tags={"employee.employment_statuses"},
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
     *
     *
     *    * * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *
     *
     *
     *
     * * *  @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *
     *
     *
     *
     *
     *
     *
     *
     *
     * *      * *  @OA\Parameter(
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

     *      summary="This method is to get employment statuses  ",
     *      description="This method is to get employment statuses ",
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

    public function getEmploymentStatuses(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employment_status_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }


            $employment_statusesQuery = EmploymentStatus::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('employment_statuses');
                }, function ($query) use ($request, $created_by) {
                    $query->forNonSuperAdmin('employment_statuses', $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query)  {
                $query->forBusiness('employment_statuses');
            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employment_statuses.name", "like", "%" . $term . "%")
                            ->orWhere("employment_statuses.description", "like", "%" . $term . "%");
                    });
                })
              
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employment_statuses.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employment_statuses.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })


                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where('employment_statuses.name', $request->name);
                })
                ->when(!empty($request->description), function ($query) use ($request) {
                    return $query->where('employment_statuses.description', $request->description);
                });

                      $employment_statuses =  $this->retrieveData($employment_statusesQuery, "id", "employment_statuses");




            return response()->json($employment_statuses, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/employment-statuses/{id}",
     *      operationId="getEmploymentStatusById",
     *      tags={"employee.employment_statuses"},
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
     *      summary="This method is to get employment status by id",
     *      description="This method is to get employment status by id",
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


    public function getEmploymentStatusById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employment_status_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $employment_status =  EmploymentStatus::where([
                "id" => $id,

            ])
                ->first();
            if (!$employment_status) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($employment_status->business_id != NULL )) {

                        return response()->json([
                            "message" => "You do not have permission to update this employment status due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($employment_status->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this employment status due to role restrictions."
                        ], 403);
                    } else if ($employment_status->is_default == 0 && $employment_status->created_by != auth()->user()->id) {

                        return response()->json([
                            "message" => "You do not have permission to update this employment status due to role restrictions."
                        ], 403);
                    }
                }
            } else {
                if ($employment_status->business_id != NULL) {
                    if (($employment_status->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this employment status due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($employment_status->is_default == 0) {
                        if ($employment_status->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this employment status due to role restrictions."
                            ], 403);
                        }
                    }
                }
            }


            return response()->json($employment_status, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/employment-statuses/{ids}",
     *      operationId="deleteEmploymentStatusesByIds",
     *      tags={"employee.employment_statuses"},
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
     *      summary="This method is to delete employment statuses by ids",
     *      description="This method is to delete employment statuses by ids",
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

    public function deleteEmploymentStatusesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employment_status_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = EmploymentStatus::whereIn('id', $idsArray)
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('employment_statuses.business_id', NULL)
                            ->where('employment_statuses.is_default', 1);
                    } else {
                        return $query->where('employment_statuses.business_id', NULL)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.created_by', $request->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('employment_statuses.business_id', auth()->user()->business_id)
                        ->where('employment_statuses.is_default', 0);
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

            // Check for conflicts in Users
            $conflictingUsersExists = User::whereIn("employment_status_id", $existingIds)->exists();
            if ($conflictingUsersExists) {
                $conflicts[] = "Employees";
            }

            // Check for conflicts in Paid Leave Employment Statuses
            $conflictingPaidEmploymentStatusesExists = SettingPaidLeaveEmploymentStatus::whereIn("employment_status_id", $existingIds)->exists();
            if ($conflictingPaidEmploymentStatusesExists) {
                $conflicts[] = "Paid Leave Employment Statuses";
            }

            // Check for conflicts in Unpaid Leave Employment Statuses
            $conflictingUnpaidEmploymentStatusesExists = SettingUnpaidLeaveEmploymentStatus::whereIn("employment_status_id", $existingIds)->exists();
            if ($conflictingUnpaidEmploymentStatusesExists) {
                $conflicts[] = "Unpaid Leave Employment Statuses";
            }

            // Check for conflicts in Leave Type Employment Statuses
            $conflictingLeaveTypeEmploymentStatusesExists = LeaveTypeEmploymentStatus::whereIn("employment_status_id", $existingIds)->exists();
            if ($conflictingLeaveTypeEmploymentStatusesExists) {
                $conflicts[] = "Leave Type Employment Statuses";
            }

            // Return combined error message if conflicts exist
            if (!empty($conflicts)) {
                $conflictList = implode(', ', $conflicts);
                return response()->json([
                    "message" => "Cannot delete this data as there are records associated with it in the following areas: $conflictList. Please update these records before attempting to delete.",
                ], 409);
            }

            // Proceed with the deletion process if no conflicts are found.


            EmploymentStatus::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
