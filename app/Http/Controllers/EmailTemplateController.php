<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailTemplateCreateRequest;
use App\Http\Requests\EmailTemplateUpdateRequest;
use App\Http\Utils\ErrorUtil;

use App\Models\EmailTemplate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailTemplateController extends Controller
{
    use ErrorUtil;



    /**
     *
     * @OA\Post(
     *      path="/v1.0/email-templates",
     *      operationId="createEmailTemplate",
     *      tags={"z.unused"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store email template",
     *      description="This method is to store email template",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         description="use {{dynamic-username}} {{dynamic-verify-link}} in the template.",
     *         @OA\JsonContent(
     *            required={"type","template","is_active"},
     * *    @OA\Property(property="name", type="string", format="string",example="emal v1"),
     *    @OA\Property(property="type", type="string", format="string",example="email_verification_mail"),
     *    @OA\Property(property="template", type="string", format="string",example="html template goes here"),
     * *    @OA\Property(property="wrapper_id", type="number", format="number",example="1"),
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
     * @OA\Hidden,
     * @OA\Hidden

     */

    public function createEmailTemplate(EmailTemplateCreateRequest $request)
    {
        try {

            return    DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('template_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $request_data["wrapper_id"]  = !empty($request_data["wrapper_id"])?$request_data["wrapper_id"]:1;
                // $request_data["template"] = json_encode($request_data["template"]);

                $request_data["is_active"] = 1;
                $request_data["is_default"] = 0;
                $request_data["created_by"] = auth()->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $template =  EmailTemplate::create($request_data);



//  if the template is active then other templates of this type will deactive
                // if ($template->is_active) {
                //     EmailTemplate::where("id", "!=", $template->id)
                //         ->where([
                //             "type" => $template->type
                //         ])
                //         ->update([
                //             "is_active" => false
                //         ]);
                // }


                return response($template, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/email-templates",
     *      operationId="updateEmailTemplate",
     *      tags={"template_management.email"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update email template",
     *      description="This method is to update email template",
     *
     *  @OA\RequestBody(
     *         required=true,
     *  description="use [Title],[FirstName],[LastName],[FullName],[AccountVerificationLink],[ForgotPasswordLink]
     * [customer_FirstName],[customer_LastName],[customer_FullName],[business_owner_FirstName],[business_owner_LastName],[business_owner_FullName],[automobile_make],[automobile_model],[car_registration_no],[car_registration_year],[status],[payment_status],[additional_information],[discount_type],[discount_amount],[price],[job_start_date],[job_start_time],[job_end_time],[coupon_code],[fuel],[transmission]
     *  in the template",
     *         @OA\JsonContent(
     *            required={"id","template","is_active"},
     *    @OA\Property(property="id", type="number", format="number", example="1"),
     *   * *    @OA\Property(property="name", type="string", format="string",example="emal v1"),
     * *   * *    @OA\Property(property="is_active", type="number", format="number",example="1"),
     *    @OA\Property(property="template", type="string", format="string",example="html template goes here"),
     *  * *    @OA\Property(property="wrapper_id", type="number", format="number",example="1"),
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

    public function updateEmailTemplate(EmailTemplateUpdateRequest $request)
    {
        try {

            return    DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('template_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();
                $request_data["wrapper_id"]  = !empty($request_data["wrapper_id"])?$request_data["wrapper_id"]:1;
                // $request_data["template"] = json_encode($request_data["template"]);

$query = [
    "id" => $request_data["id"],
];

         if(!empty(auth()->user()->business_id)) {
           $query["business_id"] = auth()->user()->business_id;
           $query["is_default"] = 0;
         } else {
            $query["business_id"] = NULL;
            $query["is_default"] = 1;
         }

                $template  =  tap(EmailTemplate::where($query))->update(
                    collect($request_data)->only([
                        "name",
                        "template",
                        "wrapper_id"
                    ])->toArray()
                )
                    ->first();


                    if(!$template) {
                        return response()->json([
                            "message" => "no template found"
                            ],404);
                }

                //    if the template is active then other templates of this type will deactive
                // if ($template->is_active) {
                //     EmailTemplate::where("id", "!=", $template->id)
                //         ->where([
                //             "type" => $template->type
                //         ])
                //         ->update([
                //             "is_active" => false
                //         ]);
                // }
                return response($template, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/email-templates/{perPage}",
     *      operationId="getEmailTemplates",
     *      tags={"template_management.email"},
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
     *      summary="This method is to get email templates ",
     *      description="This method is to get email templates",
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

    public function getEmailTemplates($perPage, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('template_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $templateQuery = new EmailTemplate();

            if (!empty($request->search_key)) {
                $templateQuery = $templateQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("type", "like", "%" . $term . "%");
                });
            }

            if (!empty(auth()->user()->business_id)) {
                $templateQuery = $templateQuery
                ->where([
                    'business_id'=>auth()->user()->business_id,
                    "is_default" => 0

                ])


                ;
            } else {
                $templateQuery = $templateQuery
                ->where([
                    'business_id'=> NULL,
                    "is_default" => 1

                ]);
            }

            if (!empty($request->start_date)) {
                $templateQuery = $templateQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $templateQuery = $templateQuery->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
            }

            $templates = $templateQuery
            ->orderByDesc("id")
            ->paginate($perPage);




            return response()->json($templates, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


     /**
     *
     * @OA\Get(
     *      path="/v1.0/email-templates/single/{id}",
     *      operationId="getEmailTemplateById",
     *      tags={"template_management.email"},
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
     *      summary="This method is to get email template by id",
     *      description="This method is to get email template by id",
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

    public function getEmailTemplateById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('template_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $template = EmailTemplate::where([
                "id" => $id
            ])
            ->first();
            if(!$template){

                return response()->json([
                     "message" => "no email template found"
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
     *      path="/v1.0/email-template-types",
     *      operationId="getEmailTemplateTypes",
     *      tags={"template_management.email"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *
     *      summary="This method is to get email template types ",
     *      description="This method is to get email template types",
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

    public function getEmailTemplateTypes( Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('template_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

$types = [
    "email_verification_mail",
    "forget_password_mail",
    "welcome_message",

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

     /**
        *
     *     @OA\Delete(
     *      path="/v1.0/email-templates/{id}",
     *      operationId="deleteEmailTemplateById",
     *      tags={"z.unused"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete email template by id",
     *      description="This method is to delete email template by id",
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

    public function deleteEmailTemplateById($id,Request $request) {

        try{

            if(!$request->user()->hasPermissionTo('template_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           EmailTemplate::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e);
        }

    }
}
