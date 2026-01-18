<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserEducationHistoryCreateRequest;
use App\Http\Requests\UserEducationHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Models\Department;
use App\Models\User;
use App\Models\UserEducationHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserEducationHistoryController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, ModuleUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-education-histories",
     *      operationId="createUserEducationHistory",
     *      tags={"user_education_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user education history",
     *      description="This method is to store user education history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
 * @OA\Property(property="degree", type="string", format="string", example="Your Degree"),
 * @OA\Property(property="major", type="string", format="string", example="Your Major"),
 * @OA\Property(property="school_name", type="string", format="string", example="Your School Name"),
 * @OA\Property(property="graduation_date", type="string", format="date", example="2023-05-01"),
 * @OA\Property(property="start_date", type="string", format="date", example="2019-09-01"),
 * @OA\Property(property="achievements", type="string", format="string", example="Your Achievements"),
 * @OA\Property(property="description", type="string", format="string", example="Your Description"),
 *  * @OA\Property(property="address", type="string", format="string", example="Your address"),
 * @OA\Property(property="country", type="string", format="string", example="Your Country"),
 * @OA\Property(property="city", type="string", format="string", example="Your City"),
 * @OA\Property(property="postcode", type="string", format="string", example="Your State"),
 * @OA\Property(property="is_current", type="boolean", format="boolean", example=false),
 *  *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
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

    public function createUserEducationHistory(UserEducationHistoryCreateRequest $request)
    {

        DB::beginTransaction();
        try {

            // return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_education_history_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"],"","education_docs");
                $this->makeFilePermanent($request_data["attachments"],"");



                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }



                $request_data["created_by"] = $request->user()->id;

                $user_education_history =  UserEducationHistory::create($request_data);


                // $this->moveUploadedFiles($request_data["attachments"],"education_docs");

                DB::commit();

                return response($user_education_history, 201);

        } catch (Exception $e) {



             try {
                $this->moveUploadedFilesBack($request_data["attachments"],"","education_docs");
            } catch (Exception $innerException) {

            }




        DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-education-histories",
     *      operationId="updateUserEducationHistory",
     *      tags={"user_education_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user education history ",
     *      description="This method is to update user education history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
 * @OA\Property(property="degree", type="string", format="string", example="Your Degree"),
 * @OA\Property(property="major", type="string", format="string", example="Your Major"),
 * @OA\Property(property="school_name", type="string", format="string", example="Your School Name"),
 * @OA\Property(property="graduation_date", type="string", format="date", example="2023-05-01"),
 * @OA\Property(property="start_date", type="string", format="date", example="2019-09-01"),
 * @OA\Property(property="achievements", type="string", format="string", example="Your Achievements"),
 * @OA\Property(property="description", type="string", format="string", example="Your Description"),
 *  * @OA\Property(property="address", type="string", format="string", example="Your address"),
 * @OA\Property(property="country", type="string", format="string", example="Your Country"),
 * @OA\Property(property="city", type="string", format="string", example="Your City"),
 * @OA\Property(property="postcode", type="string", format="string", example="Your State"),
 * @OA\Property(property="is_current", type="boolean", format="boolean", example=false),
 *  *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
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

    public function updateUserEducationHistory(UserEducationHistoryUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_education_history_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }


                $user_education_history_query_params = [
                    "id" => $request_data["id"],
                ];
             $user_education_history = UserEducationHistory::where($user_education_history_query_params)->first();

                    $this->moveUploadedFilesBack($user_education_history->attachments,"","education_docs");

                    $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"],"","education_docs");
                    $this->makeFilePermanent($request_data["attachments"],"");


             if($user_education_history) {
                $user_education_history->fill( collect($request_data)->only([
                    'user_id',
                    'degree',
                    'major',
                    'school_name',
                    'graduation_date',
                    'start_date',

                    'achievements',
                    'description',
                    'address',
                    'country',
                    'city',
                    'postcode',
                    'is_current',
                    "attachments"

                ])->toArray());
                $user_education_history->save();

             } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
             }
                // $this->moveUploadedFiles($request_data["attachments"],"education_docs");

                DB::commit();
                return response($user_education_history, 201);

        } catch (Exception $e) {
            DB::rollBack();
            try {
                $this->moveUploadedFilesBack($request_data["attachments"],"","education_docs");
            } catch (Exception $innerException) {

            }
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-education-histories",
     *      operationId="getUserEducationHistories",
     *      tags={"user_education_histories"},
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
     *    *  *    * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get user education histories  ",
     *      description="This method is to get user education histories ",
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

    public function getUserEducationHistories(Request $request)
    {
        try {

                if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('employee_education_history_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $user_education_historiesQuery = UserEducationHistory::with([
                "creator" => function ($query) {
                    $query->select('users.id', "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            ->when(request()->boolean("show_my_data"),function ($query) {
                    $query->where('user_education_histories.user_id', auth()->user()->id);
                }, function($query) use($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })
            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("user_education_histories.name", "like", "%" . $term . "%");
                        //     ->orWhere("user_education_histories.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_education_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_education_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_education_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_education_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $user_education_histories =  $this->retrieveData($user_education_historiesQuery, "id", "user_education_histories");




            return response()->json($user_education_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-education-histories/{id}",
     *      operationId="getUserEducationHistoryById",
     *      tags={"user_education_histories"},
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
     *      summary="This method is to get user education history by id",
     *      description="This method is to get user education history by id",
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


    public function getUserEducationHistoryById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_education_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_education_history =  UserEducationHistory::where([
                "id" => $id,
            ])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->first();
            if (!$user_education_history) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_education_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-education-histories/{ids}",
     *      operationId="deleteUserEducationHistoriesByIds",
     *      tags={"user_education_histories"},
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
     *      summary="This method is to delete user education history by id",
     *      description="This method is to delete user education history by id",
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

    public function deleteUserEducationHistoriesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_education_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("education_histories",function($query) use($idsArray) {
                $query->whereIn('user_education_histories.id', $idsArray);
              })
              ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);

            $existingIds = UserEducationHistory::whereIn('id', $idsArray)
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
            UserEducationHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
