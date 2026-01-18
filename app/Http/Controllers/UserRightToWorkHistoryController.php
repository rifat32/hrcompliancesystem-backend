<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRightToWorkHistoryCreateRequest;
use App\Http\Requests\UserRightToWorkHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\EmployeeRightToWork;
use App\Models\EmployeeRightToWorkHistory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRightToWorkHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;






    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-right-to-work-histories",
     *      operationId="createUserRightToWorkHistory",
     *      tags={"user_right_to_work_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user right to work history",
     *      description="This method is to store user right to work history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
* @OA\Property(property="right_to_work_code", type="string", format="string", example="Your BRP Number"),
* @OA\Property(property="right_to_work_check_date", type="string", format="date", example="Your right_to_work Issue Date"),
* @OA\Property(property="right_to_work_expiry_date", type="string", format="date", example="Your right_to_work Expiry Date"),
* @OA\Property(property="right_to_work_docs", type="string", format="string", example="Your right_to_work Documents"),
* @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
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

    public function createUserRightToWorkHistory(UserRightToWorkHistoryCreateRequest $request)
    {
        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_right_to_work_history_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }


                $request_data = $request->validated();


                    $this->touchUserUpdatedAt([$request_data["user_id"]]);


                $request_data["right_to_work_docs"] =   $this->storeUploadedFiles($request_data["right_to_work_docs"],"file_name","right_to_work_docs");
                $this->makeFilePermanent($request_data["right_to_work_docs"],"file_name");


                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_manual"] = 1;




                $user_right_to_work_history =  EmployeeRightToWorkHistory::create($request_data);


                $this->manipulateCurrentData("EmployeeRightToWorkHistory","right_to_work_check_date","right_to_work_expiry_date",$request_data["user_id"]);

                // $this->moveUploadedFiles(collect($request_data["right_to_work_docs"])->pluck("file_name"),"right_to_work_docs");

              DB::commit();


                return response($user_right_to_work_history, 201);

        } catch (Exception $e) {
           DB::rollBack();


       try {


        $this->moveUploadedFilesBack($request_data["right_to_work_docs"],"file_name","right_to_work_docs");



    } catch (Exception $innerException) {



    }




            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-right-to-work-histories",
     *      operationId="updateRightToWorkHistory",
     *      tags={"user_right_to_work_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user right to work history ",
     *      description="This method is to update user right to work history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
* @OA\Property(property="right_to_work_code", type="string", format="string", example="Your BRP Number"),
* @OA\Property(property="right_to_work_check_date", type="string", format="date", example="Your right_to_work Issue Date"),
* @OA\Property(property="right_to_work_expiry_date", type="string", format="date", example="Your right_to_work Expiry Date"),
* @OA\Property(property="right_to_work_docs", type="string", format="string", example="Your right_to_work Documents"),
* @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
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

    public function updateRightToWorkHistory(UserRightToWorkHistoryUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_right_to_work_history_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }


                $request_data = $request->validated();

                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }

                $request_data["right_to_work_docs"] =   $this->storeUploadedFiles($request_data["right_to_work_docs"],"file_name","right_to_work_docs");
                $this->makeFilePermanent($request_data["right_to_work_docs"],"file_name");
                $request_data["created_by"] = auth()->user()->id;
                $request_data["is_manual"] = 1;
                $request_data["business_id"] = auth()->user()->business_id;



                $user_right_to_work_history = EmployeeRightToWorkHistory::where([
                    "id" => $request_data["id"]
                ])
                    ->first();


                // Fill the object with the new data
                $user_right_to_work_history->fill($request_data);
                $user_right_to_work_history->save();


                $this->manipulateCurrentData("EmployeeRightToWorkHistory","right_to_work_check_date","right_to_work_expiry_date",$request_data["user_id"]);
                // $this->moveUploadedFiles(collect($request_data["right_to_work_docs"])->pluck("file_name"),"right_to_work_docs");



                DB::commit();

                return response($user_right_to_work_history, 201);




        } catch (Exception $e) {
           DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-right-to-work-histories",
     *      operationId="getUserRightToWorkHistories",
     *      tags={"user_right_to_work_histories"},
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

     *      summary="This method is to get user right to work histories  ",
     *      description="This method is to get user right to work histories ",
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

    public function getUserRightToWorkHistories(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_right_to_work_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();






            $employee_right_to_work_historiesQuery = EmployeeRightToWorkHistory::with([
                "creator" => function ($query) {
                    $query->select('users.id',   "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            // ->where(["is_manual" => 1])
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_right_to_work_histories.name", "like", "%" . $term . "%");
                        //     ->orWhere("employee_right_to_work_histories.description", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $employee_right_to_work_histories =  $this->retrieveData($employee_right_to_work_historiesQuery, "right_to_work_expiry_date", "employee_right_to_work_histories");




            return response()->json($employee_right_to_work_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-right-to-work-histories/{id}",
     *      operationId="getUserRightToWorkHistoryById",
     *      tags={"user_right_to_work_histories"},
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
     *      summary="This method is to get user right to work history by id",
     *      description="This method is to get user right to work history by id",
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


    public function getUserRightToWorkHistoryById($id, Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('employee_right_to_work_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_right_to_work_history =  EmployeeRightToWorkHistory::where([
                "id" => $id,
                // "is_manual" => 1
            ])

            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->first();
            if (!$user_right_to_work_history) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_right_to_work_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-right-to-work-histories/{ids}",
     *      operationId="deleteUserRightToWorkHistoriesByIds",
     *      tags={"user_right_to_work_histories"},
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
     *      summary="This method is to delete user right to work history by id",
     *      description="This method is to delete user right to work history by id",
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

    public function deleteUserRightToWorkHistoriesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_right_to_work_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("all_right_to_works",function($query) use($idsArray) {
                $query->whereIn('employee_right_to_work_histories.id', $idsArray);
              })
              ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);


            $existingIds = EmployeeRightToWorkHistory::whereIn('id', $idsArray)
            // ->where(["is_manual" => 1])
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
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
            EmployeeRightToWorkHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
