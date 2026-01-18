<?php

namespace App\Http\Controllers;

use App\Http\Requests\LabelAssignRequest;
use App\Http\Requests\LabelCreateRequest;
use App\Http\Requests\LabelUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\Label;
use App\Models\Project;
use App\Models\TaskLabel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabelController extends Controller
{
    use ErrorUtil, BusinessUtil, ModuleUtil,BasicUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/labels",
     *      operationId="createLabel",
     *      tags={"label"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store label ",
     *      description="This method is to store label ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

 *     @OA\Property(property="name", type="string", format="string", example="Label X"),
 *     @OA\Property(property="color", type="string", format="string", example="A brief overview of Label X's objectives and scope."),
 *     @OA\Property(property="project_id", type="string", format="string", example=""),
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

    public function createLabel(LabelCreateRequest $request)
    {
        DB::beginTransaction();
        try {


            $this->isModuleEnabled("task_management");



                if (!$request->user()->hasPermissionTo('label_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;





                $request_data["unique_identifier"] = $this->generateUniqueId(Project::class,$request_data["project_id"],Label::class);


                $label =  Label::create($request_data);


                DB::commit();
                return response($label, 201);

        } catch (Exception $e) {

            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/labels",
     *      operationId="updateLabel",
     *      tags={"label"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update label listing ",
     *      description="This method is to update label listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
 * *     @OA\Property(property="name", type="string", format="string", example="Label X"),
 *     @OA\Property(property="color", type="string", format="string", example="A brief overview of Label X's objectives and scope."),
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

    public function updateLabel(LabelUpdateRequest $request)
    {

        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");

                if (!$request->user()->hasPermissionTo('label_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();




                $label_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                // $label_prev = Label::where($label_query_params)
                //     ->first();
                // if (!$label_prev) {
                //     return response()->json([
                //         "message" => "no label listing found"
                //     ], 404);
                // }

                $label  =  tap(Label::where($label_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'color',

                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$label) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                DB::commit();
                return response($label, 201);

        } catch (Exception $e) {
           DB::rollBack();
            return $this->sendError($e);
        }
    }




     /**
     *
     * @OA\Put(
     *      path="/v1.0/labels/assign",
     *      operationId="assignLabel",
     *      tags={"label"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to assign label listing ",
     *      description="This method is to assign label listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="label_id", type="number", format="number",example="1"),
 * *     @OA\Property(property="task_ids", type="string", format="array", example={1,2,3,4,5}),
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

     public function assignLabel(LabelAssignRequest $request)
     {

         DB::beginTransaction();
         try {

             $this->isModuleEnabled("task_management");

                 if (!$request->user()->hasPermissionTo('label_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();




                 foreach($request_data["label_ids"] as $label_id){


                    TaskLabel::create([
                        "label_id" => $label_id,
                        "task_id" => $request_data["task_id"]
                    ]);
                 }






                 DB::commit();
                 return response(["ok" => true], 201);

         } catch (Exception $e) {
            DB::rollBack();
             return $this->sendError($e);
         }
     }

       /**
     *
     * @OA\Put(
     *      path="/v1.0/labels/discharge",
     *      operationId="dischargeLabel",
     *      tags={"label"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to discharge label listing ",
     *      description="This method is to discharge label listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="label_id", type="number", format="number",example="1"),
 * *     @OA\Property(property="task_ids", type="string", format="array", example={1,2,3,4,5}),
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

     public function dischargeLabel(LabelAssignRequest $request)
     {

         DB::beginTransaction();
         try {

             $this->isModuleEnabled("task_management");

                 if (!$request->user()->hasPermissionTo('label_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();


                 TaskLabel::where([
                    "task_id" => $request_data["task_id"]
                ])
                ->whereIn("label_id",$request_data["label_ids"])
                ->delete();








                 DB::commit();
                 return response(["ok" => true], 201);

         } catch (Exception $e) {
            DB::rollBack();
             return $this->sendError($e);
         }
     }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/labels",
     *      operationId="getLabels",
     *      tags={"label"},
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
     *

     *      *    @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="status",
     *         required=true,
     *  example="pending"
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
     *     * *  @OA\Parameter(
     * name="project_id",
     * in="query",
     * description="project_id",
     * required=true,
     * example="project_id"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get label listings  ",
     *      description="This method is to get label listings ",
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

    public function getLabels(Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('label_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $labelsQuery = Label::where(
                [
                    "business_id" => auth()->user()->business_id
                ]
            )

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("color", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->project_id), function ($query) use ($request) {
                    return $query->where("labels.project_id", $request->project_id);
                }) ;

                 $labels =  $this->retrieveData($labelsQuery, "id", "labels");



            return response()->json($labels, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/labels/{id}",
     *      operationId="getLabelById",
     *      tags={"label"},
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
     *      summary="This method is to get label listing by id",
     *      description="This method is to get label listing by id",
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


    public function getLabelById($id, Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('label_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $label =  Label::where([
                "id" => $id,
                "business_id" => $business_id
            ])
            ->select('labels.*'
             )
                ->first();
            if (!$label) {

                return response()->json([
                    "message" => "no label listing found"
                ], 404);
            }

            return response()->json($label, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/labels/{ids}",
     *      operationId="deleteLabelsByIds",
     *      tags={"label"},
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
     *      summary="This method is to delete label listing by id",
     *      description="This method is to delete label listing by id",
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

    public function deleteLabelsByIds(Request $request, $ids)
    {

        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('label_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Label::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
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

            Label::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
