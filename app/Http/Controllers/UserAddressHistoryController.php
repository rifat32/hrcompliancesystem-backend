<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddressHistoryCreateRequest;
use App\Http\Requests\UserAddressHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\EmployeeAddressHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAddressHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;






    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-address-histories",
     *      operationId="createUserAddressHistory",
     *      tags={"user_address_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user address history",
     *      description="This method is to store user address history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *  * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
* @OA\Property(property="address", type="string", format="string", example="Your address"),
 * @OA\Property(property="address_line_1", type="string", format="string", example="Your address_line_1"),
 * @OA\Property(property="address_line_2", type="string", format="string", example="Your address_line_2"),
 * @OA\Property(property="country", type="string", format="string", example="Your Country"),
 * @OA\Property(property="city", type="string", format="string", example="Your City"),
 * @OA\Property(property="postcode", type="string", format="string", example="Your State"),
 * @OA\Property(property="lat", type="string", format="string", example="Your Latitude"),
 * @OA\Property(property="long", type="string", format="string", example="Your Longitude"),
 * @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
 * @OA\Property(property="to_date", type="string", format="date", example="Your To Date"),

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

    public function createUserAddressHistory(UserAddressHistoryCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_address_history_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["created_by"] = $request->user()->id;
                $request_data["is_manual"] = 1;

                $user_address_history =  EmployeeAddressHistory::create($request_data);



                return response($user_address_history, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-address-histories",
     *      operationId="updateUserAddressHistory",
     *      tags={"user_address_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user address history ",
     *      description="This method is to update user address history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *  * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
* @OA\Property(property="address", type="string", format="string", example="Your address"),
 * @OA\Property(property="address_line_1", type="string", format="string", example="Your address_line_1"),
 * @OA\Property(property="address_line_2", type="string", format="string", example="Your address_line_2"),
 * @OA\Property(property="country", type="string", format="string", example="Your Country"),
 * @OA\Property(property="city", type="string", format="string", example="Your City"),
 * @OA\Property(property="postcode", type="string", format="string", example="Your State"),
 * @OA\Property(property="lat", type="string", format="string", example="Your Latitude"),
 * @OA\Property(property="long", type="string", format="string", example="Your Longitude"),
 * @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
 * @OA\Property(property="to_date", type="string", format="date", example="Your To Date"),
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

    public function updateUserAddressHistory(UserAddressHistoryUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_address_history_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();




                $user_address_history_query_params = [
                    "id" => $request_data["id"],
                    // "is_manual" => 1
                ];
                // $user_address_history_prev = UserAddressHistory::where($user_address_history_query_params)
                //     ->first();
                // if (!$user_address_history_prev) {
                //     return response()->json([
                //         "message" => "no user address history found"
                //     ], 404);
                // }

                $user_address_history  =  tap(EmployeeAddressHistory::where($user_address_history_query_params))->update(
                    collect($request_data)->only([
                        "address_line_1",
                        "address_line_2",
                        "country",
                        "city",
                        "postcode",
                        "lat",
                        "long",
                        'user_id',
                        "from_date",
                        "to_date",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$user_address_history) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($user_address_history, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-address-histories",
     *      operationId="getUserAddressHistories",
     *      tags={"user_address_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="user_id",
     *         required=true,
     *  example="1"
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get user address histories  ",
     *      description="This method is to get user address histories ",
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

    public function getUserAddressHistories(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_address_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $user_address_historiesQuery = EmployeeAddressHistory::with([
                "creator" => function ($query) {
                    $query->select('users.id', "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            // ->where(["is_manual" => 1])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("user_address_histories.name", "like", "%" . $term . "%");
                        //     ->orWhere("user_address_histories.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_address_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_address_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_address_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_address_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $user_address_histories =  $this->retrieveData($user_address_historiesQuery, "id", "user_address_histories");




            return response()->json($user_address_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-address-histories/{id}",
     *      operationId="getUserAddressHistoryById",
     *      tags={"user_address_histories"},
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
     *      summary="This method is to get user address history by id",
     *      description="This method is to get user address history by id",
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


    public function getUserAddressHistoryById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_address_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $user_address_history =  EmployeeAddressHistory::where([
                "id" => $id,
                // "is_manual" => 1
            ])

            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->first();
            if (!$user_address_history) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_address_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-address-histories/{ids}",
     *      operationId="deleteUserAddressHistoriesByIds",
     *      tags={"user_address_histories"},
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
     *      summary="This method is to delete user address history by id",
     *      description="This method is to delete user address history by id",
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

    public function deleteUserAddressHistoriesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_address_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;


           $all_manager_department_ids = $this->get_all_departments_of_manager();


            $idsArray = explode(',', $ids);
            $existingIds = EmployeeAddressHistory::whereIn('id', $idsArray)
            // ->where(["is_manual" => 1])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
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
            EmployeeAddressHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

}
