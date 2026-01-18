<?php

namespace App\Http\Controllers;

use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserDocumentCreateRequest;
use App\Http\Requests\UserDocumentUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\User;
use App\Models\UserDocument;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDocumentController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;





    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-documents",
     *      operationId="createUserDocument",
     *      tags={"user_documents"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user document",
     *      description="This method is to store user document",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="Your Name"),
     *     @OA\Property(property="file_name", type="string", format="string", example="Your File Name"),
     *     @OA\Property(property="user_id", type="integer", format="int", example=1),
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

    public function createUserDocument(UserDocumentCreateRequest $request)
    {
        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('employee_document_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();
            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }

            if (!empty($request_data["file_name"])) {
                $request_data["file_name"] = $this->storeUploadedFiles([$request_data["file_name"]], "", "documents")[0];
                $this->makeFilePermanent($request_data["file_name"], "");
            }


            $request_data["created_by"] = $request->user()->id;

            $user_document =  UserDocument::create($request_data);


            $user_document = $this->generateResponseMessage($user_document, $user_document->user, "Document", "create");


            DB::commit();

            return response($user_document, 201);
        } catch (Exception $e) {
            DB::rollBack();
            try {
                if (!empty($request_data["file_name"])) {
                    $request_data["file_name"] = $this->moveUploadedFilesBack([$request_data["file_name"]], "", "documents")[0];
                }
            } catch (Exception $innerException) {
            }

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-documents",
     *      operationId="updateUserDocument",
     *      tags={"user_documents"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user document ",
     *      description="This method is to update user document",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="name", type="string", format="string", example="Your Name"),
     *     @OA\Property(property="file_name", type="string", format="string", example="Your File Name"),
     *     @OA\Property(property="user_id", type="integer", format="int", example=1)
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

    public function updateUserDocument(UserDocumentUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('employee_document_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();
            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }


            $user_document_query_params = [
                "id" => $request_data["id"],
            ];
            $user_document = UserDocument::where($user_document_query_params)->first();


            if (!empty($user_document->file_name)) {
                $this->moveUploadedFilesBack([$user_document->file_name], "", "documents");
            }

            if (!empty($request_data["file_name"])) {
                $request_data["file_name"] = $this->storeUploadedFiles([$request_data["file_name"]], "", "documents")[0];
                $this->makeFilePermanent($request_data["file_name"], "");
            }






            if ($user_document) {
                $user_document->fill(collect($request_data)->only([
                    'user_id',
                    'name',
                    'file_name',
                    // 'created_by',

                ])->toArray());
                $user_document->save();
            } else {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }

            // $this->moveUploadedFiles($request_data["file_name"],"documents");
            $user_document = $this->generateResponseMessage($user_document, $user_document->user, "Document", "update");
            DB::commit();

            return response($user_document, 201);
        } catch (Exception $e) {

            DB::rollBack();

            try {
                if (!empty($request_data["file_name"])) {
                    $request_data["file_name"] = $this->moveUploadedFilesBack([$request_data["file_name"]], "", "documents")[0];
                }
            } catch (Exception $innerException) {
            }


            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-documents",
     *      operationId="getUserDocuments",
     *      tags={"user_documents"},
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
     *    * *    *  *    * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get user documents  ",
     *      description="This method is to get user documents ",
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

    public function getUserDocuments(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_document_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $user_documentsQuery = UserDocument::with([
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
                ->when(request()->boolean("show_my_data"), function ($query) {
                    $query->where('user_documents.user_id', auth()->user()->id);
                }, function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("user_documents.name", "like", "%" . $term . "%");
                        //     ->orWhere("user_documents.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_documents.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_documents.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_documents.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_documents.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $user_documents =  $this->retrieveData($user_documentsQuery, "id", "user_documents");





            return response()->json($user_documents, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-documents/{id}",
     *      operationId="getUserDocumentById",
     *      tags={"user_documents"},
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
     *      summary="This method is to get user document by id",
     *      description="This method is to get user document by id",
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


    public function getUserDocumentById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_document_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;


            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $user_document =  UserDocument::where([
                "id" => $id,

            ])
                ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->first();
            if (!$user_document) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_document, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-documents/{ids}",
     *      operationId="deleteUserDocumentsByIds",
     *      tags={"user_documents"},
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
     *      summary="This method is to delete user document by id",
     *      description="This method is to delete user document by id",
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

    public function deleteUserDocumentsByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_document_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $idsArray = explode(',', $ids);

            $users = User::whereHas('documents', function ($query) use ($idsArray) {
                $query->whereIn('user_documents.id', $idsArray);
            })
                ->select('id', 'title', 'first_name', 'middle_name', 'last_name')
                ->distinct()
                ->get();

            // user_ids from distinct rows — unique() here is not needed
            $user_ids = $users->pluck('id')->values();

            $this->touchUserUpdatedAt($user_ids);

            $existingIds = UserDocument::whereIn('id', $idsArray)
                ->select('id')
                ->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->get()
                ->pluck('id')
                ->toArray();

            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {

                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }

            UserDocument::destroy($existingIds);


            return response()->json(["message" => $this->generateResponseMessageForBulkDelete($users, "Document"), "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
