<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserJobHistoryCreateRequest;
use App\Http\Requests\UserJobHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\User;
use App\Models\UserJobHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class UserJobHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;






      /**
       *
       * @OA\Post(
       *      path="/v1.0/user-job-histories",
       *      operationId="createUserJobHistory",
       *      tags={"user_job_histories"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to store user job history",
       *      description="This method is to store user job history",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="company_name", type="string", format="string", example="Your Company Name"),
 *  *     @OA\Property(property="country", type="string", format="string", example="country"),
 *
 *     @OA\Property(property="job_title", type="string", format="string", example="Your Job Title"),
 *     @OA\Property(property="employment_start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="employment_end_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="responsibilities", type="string", format="string", example="Your Responsibilities"),
 *     @OA\Property(property="supervisor_name", type="string", format="string", example="Supervisor Name"),
 *     @OA\Property(property="contact_information", type="string", format="string", example="Contact Information"),
 *     @OA\Property(property="work_location", type="string", format="string", example="Work Location"),
 *     @OA\Property(property="achievements", type="string", format="string", example="Your Achievements")
   *
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

      public function createUserJobHistory(UserJobHistoryCreateRequest $request)
      {
          try {

              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_job_history_create')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }

                  $request_data = $request->validated();

                  if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }







                  $request_data["created_by"] = $request->user()->id;

                  $user_job_history =  UserJobHistory::create($request_data);



                  return response($user_job_history, 201);
              });
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }

      /**
       *
       * @OA\Put(
       *      path="/v1.0/user-job-histories",
       *      operationId="updateUserJobHistory",
       *      tags={"user_job_histories"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to update  user job history ",
       *      description="This method is to update user job history",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 *     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="company_name", type="string", format="string", example="Your Company Name"),
 *  *  *     @OA\Property(property="country", type="string", format="string", example="country"),
 *     @OA\Property(property="job_title", type="string", format="string", example="Your Job Title"),
 *     @OA\Property(property="employment_start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="employment_end_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="responsibilities", type="string", format="string", example="Your Responsibilities"),
 *     @OA\Property(property="supervisor_name", type="string", format="string", example="Supervisor Name"),
 *     @OA\Property(property="contact_information", type="string", format="string", example="Contact Information"),

 *     @OA\Property(property="work_location", type="string", format="string", example="Work Location"),
 *     @OA\Property(property="achievements", type="string", format="string", example="Your Achievements")
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

      public function updateUserJobHistory(UserJobHistoryUpdateRequest $request)
      {

          try {

              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_job_history_update')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }
                  $business_id =  auth()->user()->business_id;
                  $request_data = $request->validated();

                  if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }


                  $user_job_history_query_params = [
                      "id" => $request_data["id"],
                  ];
                  // $user_job_history_prev = UserJobHistory::where($user_job_history_query_params)
                  //     ->first();
                  // if (!$user_job_history_prev) {
                  //     return response()->json([
                  //         "message" => "no user job history found"
                  //     ], 404);
                  // }

                  $user_job_history  =  tap(UserJobHistory::where($user_job_history_query_params))->update(
                      collect($request_data)->only([
                        'user_id',
                        'company_name',
                        'country',
                        'job_title',
                        'employment_start_date',
                        'employment_end_date',
                        'responsibilities',
                        'supervisor_name',
                        'contact_information',
                        'work_location',
                        'achievements',
                        // 'created_by',

                      ])->toArray()
                  )
                      // ->with("somthing")

                      ->first();
                  if (!$user_job_history) {
                      return response()->json([
                          "message" => "something went wrong."
                      ], 500);
                  }

                  return response($user_job_history, 201);
              });
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }


      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-job-histories",
       *      operationId="getUserJobHistories",
       *      tags={"user_job_histories"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *   *   *              @OA\Parameter(
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
       *
       *   * @OA\Parameter(
     *     name="job_history_job_title",
     *     in="query",
     *     description="Job History Job Title",
     *     required=true,
     *     example="Software Engineer"
     * ),
     *   * @OA\Parameter(
     *     name="job_history_company",
     *     in="query",
     *     description="Job History Company",
     *     required=true,
     *     example="Tech Solutions Inc."
     * ),
     * @OA\Parameter(
     *     name="job_history_start_on",
     *     in="query",
     *     description="Job History Start Date",
     *     required=true,
     *     example="2020-03-15"
     * ),
     * @OA\Parameter(
     *     name="job_history_end_at",
     *     in="query",
     *     description="Job History End Date",
     *     required=true,
     *     example="2022-05-30"
     * ),
     * @OA\Parameter(
     *     name="job_history_supervisor",
     *     in="query",
     *     description="Job History Supervisor",
     *     required=true,
     *     example="John Smith"
     * ),
     * @OA\Parameter(
     *     name="job_history_country",
     *     in="query",
     *     description="Job History Country",
     *     required=true,
     *     example="United States"
     * ),
     * *    *  *    * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="ASC"
     * ),
       * *  @OA\Parameter(
       * name="order_by",
       * in="query",
       * description="order_by",
       * required=true,
       * example="ASC"
       * ),
       *

       *      summary="This method is to get user job histories  ",
       *      description="This method is to get user job histories ",
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

      public function getUserJobHistories(Request $request)
      {
          try {

              if (!$request->user()->hasPermissionTo('employee_job_history_view')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $all_manager_department_ids = $this->get_all_departments_of_manager();

              $user_job_historiesQuery = UserJobHistory::with([
                  "creator" => function ($query) {
                      $query->select('users.id', "users.title", 'users.first_Name','users.middle_Name',
                      'users.last_Name');
                  },

              ])
               ->when(request()->boolean("show_my_data"),function ($query) {
                    $query->where('user_job_histories.user_id', auth()->user()->id);
                }, function($query) use($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })
              ->when(!empty($request->search_key), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                          $term = $request->search_key;
                          $query->where("user_job_histories.name", "like", "%" . $term . "%");
                          //     ->orWhere("user_job_histories.description", "like", "%" . $term . "%");
                      });
                  })


                  ->when(!empty($request->user_id), function ($query) use ($request) {
                      return $query->where('user_job_histories.user_id', $request->user_id);
                  })
                  ->when(empty($request->user_id), function ($query) use ($request) {
                      return $query->where('user_job_histories.user_id', $request->user()->id);
                  })
                  ->when(!empty($request->start_date), function ($query) use ($request) {
                      return $query->where('user_job_histories.created_at', ">=", $request->start_date);
                  })
                  ->when(!empty($request->end_date), function ($query) use ($request) {
                      return $query->where('user_job_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                  });
                   $user_job_histories =  $this->retrieveData($user_job_historiesQuery, "id", "user_job_histories");


                  if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                    if (strtoupper($request->response_type) == 'PDF') {
                        if(empty($user_job_histories->count())) {
                            $pdf = PDF::loadView('pdf.no_data', []);
                        }else {
                            $pdf = PDF::loadView('pdf.users', ["user_job_histories" => $user_job_histories]);
                        }

                        return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                    } elseif (strtoupper($request->response_type) === 'CSV') {

                        // return Excel::download(new UsersExport($users), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));

                    }
                } else {
                    return response()->json($user_job_histories, 200);
                }


          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }

      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-job-histories/{id}",
       *      operationId="getUserJobHistoryById",
       *      tags={"user_job_histories"},
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
       *      summary="This method is to get user job history by id",
       *      description="This method is to get user job history by id",
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


      public function getUserJobHistoryById($id, Request $request)
      {
          try {

              if (!$request->user()->hasPermissionTo('employee_job_history_view')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }
              $business_id =  auth()->user()->business_id;
              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $user_job_history =  UserJobHistory::where([
                  "id" => $id,
              ])
              ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
                  ->first();
              if (!$user_job_history) {

                  return response()->json([
                      "message" => "no data found"
                  ], 404);
              }

              return response()->json($user_job_history, 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }



      /**
       *
       *     @OA\Delete(
       *      path="/v1.0/user-job-histories/{ids}",
       *      operationId="deleteUserJobHistoriesByIds",
       *      tags={"user_job_histories"},
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
       *      summary="This method is to delete user job history by id",
       *      description="This method is to delete user job history by id",
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

      public function deleteUserJobHistoriesByIds(Request $request, $ids)
      {

          try {

              if (!$request->user()->hasPermissionTo('employee_job_history_delete')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $business_id =  auth()->user()->business_id;
              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $idsArray = explode(',', $ids);

              $user_ids = User::whereHas("job_histories",function($query) use($idsArray) {
                $query->whereIn('user_job_histories.id', $idsArray);
              })
              ->pluck("id");
              $this->touchUserUpdatedAt($user_ids);


              $existingIds = UserJobHistory::whereIn('id', $idsArray)
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
              UserJobHistory::destroy($existingIds);


              return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }
}
