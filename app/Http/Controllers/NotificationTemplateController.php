<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationTemplateUpdateRequest;
use App\Http\Utils\ErrorUtil;

use App\Models\NotificationTemplate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationTemplateController extends Controller
{
    use ErrorUtil;

     /**
     *
     * @OA\Put(
     *      path="/v1.0/notification-templates",
     *      operationId="updateNotificationTemplate",
     *      tags={"template_management.notification"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update notification template",
     *      description="This method is to update notification template",
     *
     *  @OA\RequestBody(
     *         required=true,
     *  description="use [customer_name],[business_owner_name],[business_name],
     *  in the template and use [customer_id], [pre_booking_id],[booking_id],[job_id],[business_id],[bid_id] in link",
     *         @OA\JsonContent(
     *            required={"id","template","is_active"},
     *    @OA\Property(property="id", type="number", format="number", example="1"),
     *   * *    @OA\Property(property="name", type="string", format="string",example="emal v1"),
     * *   * *    @OA\Property(property="is_active", type="number", format="number",example="1"),
     *    @OA\Property(property="template", type="string", format="string",example="html template goes here"),
     *  *    @OA\Property(property="link", type="string", format="string",example="html template goes here"),
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

    public function updateNotificationTemplate(NotificationTemplateUpdateRequest $request)
    {
        try {

            return    DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('template_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $updatableData = $request->validated();

                $updatableData["template"] =  json_encode($updatableData["template"]);


                $template  =  tap(NotificationTemplate::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "name",
                        "template",
                        "link"
                    ])->toArray()
                )


                    ->first();
                    if(!$template) {

                        return response()->json([
                            "message" => "no template found"
                            ],404);

                }

                //    if the template is active then other templates of this type will deactive
                if ($template->is_active) {
                    NotificationTemplate::where("id", "!=", $template->id)
                        ->where([
                            "type" => $template->type
                        ])
                        ->update([
                            "is_active" => false
                        ]);
                }
                return response($template, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


   /**
     *
     * @OA\Get(
     *      path="/v1.0/notification-templates/{perPage}",
     *      operationId="getNotificationTemplates",
     *      tags={"template_management.notification"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
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
     *      summary="This method is to get notification templates ",
     *      description="This method is to get notification templates",
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

    public function getNotificationTemplates($perPage, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('template_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $templateQuery = new NotificationTemplate();

            if (!empty($request->search_key)) {
                $templateQuery = $templateQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("type", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $templateQuery = $templateQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $templateQuery = $templateQuery->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
            }

            $templates = $templateQuery->orderByDesc("id")->paginate($perPage);
            return response()->json($templates, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


 /**
     *
     * @OA\Get(
     *      path="/v1.0/notification-templates/single/{id}",
     *      operationId="getNotificationTemplateById",
     *      tags={"template_management.notification"},
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
     *      summary="This method is to get notification template by id",
     *      description="This method is to get notification template by id",
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

    public function getNotificationTemplateById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('template_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $template = NotificationTemplate::where([
                "id" => $id
            ])
            ->first();
            if(!$template){

                return response()->json([
                     "message" => "no data found"
                ], 404);
            }
            return response()->json($template, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


  /**
     *
     * @OA\Get(
     *      path="/v1.0/notification-template-types",
     *      operationId="getNotificationTemplateTypes",
     *      tags={"template_management.notification"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *
     *      summary="This method is to get notification template types ",
     *      description="This method is to get notification template types",
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

    public function getNotificationTemplateTypes(Request $request)
    {
        try {

             if (!$request->user()->hasPermissionTo('template_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
$types = [

    "bid_created_by_business_owner",
    "bid_updated_by_business_owner",
    "bid_accepted_by_client",
    "bid_rejected_by_client",

    "booking_created_by_business_owner",
    "booking_updated_by_business_owner",
    "booking_status_changed_by_business_owner",
    "booking_confirmed_by_business_owner",
    "booking_deleted_by_business_owner",
     "booking_rejected_by_business_owner",

    "booking_created_by_client",
    "booking_updated_by_client",
    "booking_deleted_by_client",
    "booking_accepted_by_client",
    "booking_rejected_by_client",


    "job_created_by_business_owner",
    "job_updated_by_business_owner",
    "job_status_changed_by_business_owner",
    "job_deleted_by_business_owner",
];

return response()->json($types, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }









}
