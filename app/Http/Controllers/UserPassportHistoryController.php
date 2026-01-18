<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPassportHistoryCreateRequest;
use App\Http\Requests\UserPassportHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\EmployeePassportDetail;
use App\Models\EmployeePassportDetailHistory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPassportHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;






    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-passport-histories",
     *      operationId="createUserPassportHistory",
     *      tags={"user_passport_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user passport history",
     *      description="This method is to store user passport history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="passport_number", type="string", format="string", example="Your Passport Number"),
     * @OA\Property(property="passport_issue_date", type="string", format="date", example="Your Passport Issue Date"),
     * @OA\Property(property="passport_expiry_date", type="string", format="date", example="Your Passport Expiry Date"),
     * @OA\Property(property="place_of_issue", type="string", format="string", example="Place of Passport Issue"),
     * @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
     * @OA\Property(property="to_date", type="string", format="date", example="Your To Date"),
     * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
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

    public function createUserPassportHistory(UserPassportHistoryCreateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_passport_history_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $this->touchUserUpdatedAt([$request_data["user_id"]]);

                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_manual"] = 1;



                $user_passport_history =  EmployeePassportDetailHistory::create($request_data);

                $this->manipulateCurrentData("EmployeePassportDetailHistory","passport_issue_date","passport_expiry_date",$request_data["user_id"]);

                DB::commit();
                return response($user_passport_history, 201);




        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-passport-histories",
     *      operationId="updateUserPassportHistory",
     *      tags={"user_passport_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user passport history ",
     *      description="This method is to update user passport history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     * @OA\Property(property="passport_number", type="string", format="string", example="Your Passport Number"),
     * @OA\Property(property="passport_issue_date", type="string", format="date", example="Your Passport Issue Date"),
     * @OA\Property(property="passport_expiry_date", type="string", format="date", example="Your Passport Expiry Date"),
     * @OA\Property(property="place_of_issue", type="string", format="string", example="Place of Passport Issue"),
     * @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
     * @OA\Property(property="to_date", type="string", format="date", example="Your To Date"),
     * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
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

    public function updateUserPassportHistory(UserPassportHistoryUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_passport_history_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                if(!empty($request_data["user_id"])){
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }

                $request_data["created_by"] = auth()->user()->id;
                $request_data["is_manual"] = 1;
                $request_data["business_id"] = auth()->user()->business_id;



                $user_passport_history = EmployeePassportDetailHistory::where([
                    "id" => $request_data["id"]
                ])
                    ->first();


                // Fill the object with the new data
                $user_passport_history->fill($request_data);
                $user_passport_history->save();

                $this->manipulateCurrentData("EmployeePassportDetailHistory","passport_issue_date","passport_expiry_date",$request_data["user_id"]);

                DB::commit();
                return response($user_passport_history, 201);






        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-passport-histories",
     *      operationId="getUserPassportHistories",
     *      tags={"user_passport_histories"},
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

     *      summary="This method is to get user passport histories  ",
     *      description="This method is to get user passport histories ",
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

    public function getUserPassportHistories(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_passport_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $employee_passport_detail_historiesQuery = EmployeePassportDetailHistory::with([
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        "users.title",
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },

            ])
                // ->where(["is_manual" => 1])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_passport_detail_histories.name", "like", "%" . $term . "%");
                        //     ->orWhere("employee_passport_detail_histories.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_passport_detail_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_passport_detail_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_passport_detail_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_passport_detail_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $employee_passport_detail_histories =  $this->retrieveData($employee_passport_detail_historiesQuery, "passport_expiry_date", "employee_passport_detail_histories");



            return response()->json($employee_passport_detail_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-passport-histories/{id}",
     *      operationId="getUserPassportHistoryById",
     *      tags={"user_passport_histories"},
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
     *      summary="This method is to get user passport history by id",
     *      description="This method is to get user passport history by id",
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


    public function getUserPassportHistoryById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_passport_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_passport_history =  EmployeePassportDetailHistory::where([
                "id" => $id,
                // "is_manual" => 1
            ])

                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->first();
            if (!$user_passport_history) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_passport_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-passport-histories/{ids}",
     *      operationId="deleteUserPassportHistoriesByIds",
     *      tags={"user_passport_histories"},
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
     *      summary="This method is to delete user passport history by id",
     *      description="This method is to delete user passport history by id",
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

    public function deleteUserPassportHistoriesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_passport_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("all_passport_details",function($query) use($idsArray) {
                $query->whereIn('employee_passport_detail_histories.id', $idsArray);
              })
              ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);

            $existingIds = EmployeePassportDetailHistory::whereIn('id', $idsArray)
                // ->where(["is_manual" => 1])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
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
            EmployeePassportDetailHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
