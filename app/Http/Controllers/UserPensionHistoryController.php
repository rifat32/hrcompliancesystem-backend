<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPensionHistoryCreateRequest;
use App\Http\Requests\UserPensionHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\EmployeePensionHistory;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UserPensionHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;




    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-pension-histories",
     *      operationId="createUserPensionHistory",
     *      tags={"user_pension_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user pension history",
     *      description="This method is to store user pension history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *  * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
     *      @OA\Property(property="pension_eligible", type="boolean", format="boolean", example="1"),
     *      @OA\Property(property="pension_letters", type="string", format="array", example={{"file_name":"sss"}}),
     *      @OA\Property(property="pension_scheme_status", type="string", format="string", example="pension_scheme_status"),
     *      @OA\Property(property="pension_enrollment_issue_date", type="string", format="string", example="pension_enrollment_issue_date"),
     *      @OA\Property(property="pension_scheme_opt_out_date", type="string", format="string", example="pension_scheme_opt_out_date"),
     *      @OA\Property(property="pension_re_enrollment_due_date", type="string", format="date", example="pension_re_enrollment_due_date"),
     *
     *      @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
     *      @OA\Property(property="to_date", type="string", format="date", example="Your To Date")
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

    public function createUserPensionHistory(UserPensionHistoryCreateRequest $request)
    {



        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('employee_pension_history_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            $this->touchUserUpdatedAt([$request_data["user_id"]]);


            $request_data["pension_letters"] =   $this->storeUploadedFiles($request_data["pension_letters"], "file_name", "pension_letters");
            $this->makeFilePermanent($request_data["pension_letters"], "file_name");


            $request_data["created_by"] = $request->user()->id;
            $request_data["is_manual"] = 1;
            $request_data["business_id"] = auth()->user()->business_id;



            $user_pension_history =  EmployeePensionHistory::create($request_data);

            $this->manipulateCurrentData("EmployeePensionHistory","pension_enrollment_issue_date","pension_enrollment_issue_date",$request_data["user_id"]);



            // $this->moveUploadedFiles(collect($request_data["pension_letters"])->pluck("file_name"),"pension_letters");


            DB::commit();
            return response($user_pension_history, 201);
        } catch (Exception $e) {
            DB::rollBack();



            try {


                $this->moveUploadedFilesBack($request_data["pension_letters"], "file_name", "pension_letters");
            } catch (Exception $innerException) {

            }


            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-pension-histories",
     *      operationId="updateUserPensionHistory",
     *      tags={"user_pension_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user pension history ",
     *      description="This method is to update user pension history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *  * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
     *      @OA\Property(property="pension_eligible", type="boolean", format="boolean", example="1"),
     *      @OA\Property(property="pension_letters", type="string", format="array", example={{"file_name":"sss"}}),
     *      @OA\Property(property="pension_scheme_status", type="string", format="string", example="pension_scheme_status"),
     *      @OA\Property(property="pension_enrollment_issue_date", type="string", format="string", example="pension_enrollment_issue_date"),
     *      @OA\Property(property="pension_scheme_opt_out_date", type="string", format="string", example="pension_scheme_opt_out_date"),
     *      @OA\Property(property="pension_re_enrollment_due_date", type="string", format="date", example="pension_re_enrollment_due_date"),
     *
     *      @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
     *      @OA\Property(property="to_date", type="string", format="date", example="Your To Date")
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

    public function updateUserPensionHistory(UserPensionHistoryUpdateRequest $request)
    {
        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('employee_pension_history_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();


            $this->touchUserUpdatedAt([$request_data["user_id"]]);


            $request_data["pension_letters"] =   $this->storeUploadedFiles($request_data["pension_letters"], "file_name", "pension_letters");
            $this->makeFilePermanent($request_data["pension_letters"], "file_name");



            $request_data["created_by"] = auth()->user()->id;
            $request_data["is_manual"] = 1;
            $request_data["business_id"] = auth()->user()->business_id;


            $pension = EmployeePensionHistory::where([
                "id" => $request_data["id"]
            ])
                ->first();


            // Fill the object with the new data
            $pension->fill($request_data);
            $pension->save();


            $this->manipulateCurrentData("EmployeePensionHistory","pension_enrollment_issue_date","pension_enrollment_issue_date",$request_data["user_id"]);


                DB::commit();
            return response()->json($pension, 200);

            // $this->moveUploadedFiles(collect($request_data["pension_letters"])->pluck("file_name"),"pension_letters");

        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-pension-histories",
     *      operationId="getUserPensionHistories",
     *      tags={"user_pension_histories"},
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

     *      summary="This method is to get user pension histories  ",
     *      description="This method is to get user pension histories ",
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

    public function getUserPensionHistories(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_pension_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $employee_pension_historiesQuery = EmployeePensionHistory::with([
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
                        $query->where("employee_pension_histories.name", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

 $employee_pension_histories =  $this->retrieveData($employee_pension_historiesQuery, "pension_re_enrollment_due_date", "employee_pension_histories");





            return response()->json($employee_pension_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-pension-histories/{id}",
     *      operationId="getUserPensionHistoryById",
     *      tags={"user_pension_histories"},
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
     *      summary="This method is to get user pension history by id",
     *      description="This method is to get user pension history by id",
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


    public function getUserPensionHistoryById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_pension_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $user_pension_history =  EmployeePensionHistory::where([
                "id" => $id,
                // "is_manual" => 1
            ])
                ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->first();

            if (!$user_pension_history) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }



            return response()->json($user_pension_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-pension-histories/{ids}",
     *      operationId="deleteUserPensionHistoriesByIds",
     *      tags={"user_pension_histories"},
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
     *      summary="This method is to delete user pension history by id",
     *      description="This method is to delete user pension history by id",
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

    public function deleteUserPensionHistoriesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_pension_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("all_pension_details", function ($query) use ($idsArray) {
                $query->whereIn('employee_pension_histories.id', $idsArray);
            })
                ->pluck("id");

            $this->touchUserUpdatedAt($user_ids);



            $existingIds = EmployeePensionHistory::whereIn('id', $idsArray)
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

            EmployeePensionHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
