<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadUserLetterPdfRequest;
use App\Http\Requests\UserLetterCreateRequest;
use App\Http\Requests\UserLetterUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\UserLetterGenerateRequest;
use App\Http\Requests\UserLetterUpdateViewRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\EmailLogUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Mail\UserLetterMail;
use App\Models\UserLetter;
use App\Models\DisabledUserLetter;
use App\Models\LetterTemplate;
use App\Models\Termination;
use App\Models\User;
use App\Models\UserLetterEmailHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDF;

class UserLetterController extends Controller
{

    use ErrorUtil, BusinessUtil, BasicUtil, EmailLogUtil, ModuleUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-letters",
     *      operationId="createUserLetter",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user letters",
     *      description="This method is to store user letters",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="issue_date", type="string", format="string", example="issue_date"),
     * @OA\Property(property="letter_content", type="string", format="string", example="letter_content"),
     * @OA\Property(property="status", type="string", format="string", example="status"),
     * @OA\Property(property="sign_required", type="string", format="string", example="sign_required"),
     * @OA\Property(property="letter_view_required", type="string", format="string", example="letter_view_required"),
     *
     * @OA\Property(property="user_id", type="string", format="string", example="user_id"),
     * @OA\Property(property="attachments", type="string", format="string", example="attachments"),
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

    public function createUserLetter(UserLetterCreateRequest $request)
    {

        try {

            $this->isModuleEnabled("letter_template");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('user_letter_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }



                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $user_letter =  UserLetter::create($request_data);




                return response($user_letter, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-letters/generate",
     *      operationId="generateUserLetter",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to generate user letters",
     *      description="This method is to generate user letters",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="letter_template_id", type="string", format="string", example="sign_required"),
     * @OA\Property(property="letter_view_required", type="string", format="string", example="letter_view_required"),
     * @OA\Property(property="user_id", type="string", format="string", example="user_id"),

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

    public function generateUserLetter(UserLetterGenerateRequest $request)
    {

        try {

            $this->isModuleEnabled("letter_template");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('user_letter_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $business = auth()->user()->business;


                $employee = User::where([
                    "id" => $request_data["user_id"]
                ])
                    ->first();

                    $last_termination = $employee->lastTermination;

                $letter_template = LetterTemplate::where([
                    "id" => $request_data["letter_template_id"]
                ])->first();

                $template = $letter_template->template;

                $letterTemplateVariables = $this->getLetterTemplateVariablesFunc();

                foreach ($letterTemplateVariables as $item) {
                    if (strpos($item, '[') !== false) {
                        // Convert the placeholder to lowercase and remove square brackets
                        $variableName = strtolower(str_replace(['[', ']'], '', $item));

                        // Replace [FULL_NAME] with the concatenated full name
                        if ($item == "[FULL_NAME]") {
                            $fullName = trim($employee["title"] . " " .  $employee["first_Name"] . ' ' . $employee["middle_Name"] . ' ' . $employee["last_Name"]);
                            $template = str_replace($item, !empty($fullName) ? $fullName : '--', $template);
                        }
                        // Replace [DESIGNATION] with the designation name if it exists; otherwise, use "--"
                        else if ($item == "[DESIGNATION]") {
                            $designation = isset($employee->designation->name) ? $employee->designation->name : '--';
                            $template = str_replace($item, $designation, $template);
                        }
                        // Replace [EMPLOYMENT_STATUS] with the employment status name if it exists; otherwise, use "--"
                        else if ($item == "[EMPLOYMENT_STATUS]") {
                            $employmentStatus = isset($employee->employment_status->name) ? $employee->employment_status->name : '--';
                            $template = str_replace($item, $employmentStatus, $template);
                        }
                        // Replace [BANK_NAME] with the bank name if it exists; otherwise, use "--"
                        else if ($item == "[BANK_NAME]") {
                            $bankName = isset($employee->bank->name) ? $employee->bank->name : '--';
                            $template = str_replace($item, $bankName, $template);
                        }
                        // Replace [JOINING_DATE] with the formatted joining date if it exists; otherwise, use "--"
                        else if ($item == "[JOINING_DATE]") {
                            $joiningDate = isset($employee["joining_date"]) ? Carbon::parse($employee["joining_date"])->format("d-m-Y") : '--';
                            $template = str_replace($item, $joiningDate, $template);
                        }
                        else if ($item == "[NI_NUMBER]") {
                            $NI_number = isset($employee["NI_number"]) ? $employee["NI_number"] : '--';
                            $template = str_replace($item, $NI_number, $template);
                        }
                        else if (!empty($last_termination)) {
                             if ($item == "[TERMINATION_DATE]") {
                                $date_of_termination = isset($last_termination["date_of_termination"]) ? Carbon::parse($last_termination["date_of_termination"])->format("d-m-Y") : '--';
                                $template = str_replace($item, $date_of_termination, $template);
                            }
                            else if ($item == "[REASON_FOR_TERMINATION]") {
                                $terminationReason = isset($last_termination->terminationReason->name) ? $last_termination->terminationReason->name : '--';
                                $template = str_replace($item, $terminationReason, $template);
                            }
                            else if ($item == "[TERMINATION_TYPE]") {
                                $terminationType = isset($last_termination->terminationType->name) ? $last_termination->terminationType->name : '--';
                                $template = str_replace($item, $terminationType, $template);
                            }


                        }



                        else if ($item == "[COMPANY_NAME]") {
                            $NI_number = isset($business["name"]) ? $business["name"] : '[COMPANY_NAME]';
                            $template = str_replace($item, $NI_number, $template);
                        }
                        else if ($item == "[COMPANY_ADDRESS_LINE_1]") {
                            $NI_number = isset($business["address_line_1"]) ? $business["address_line_1"] : '[COMPANY_ADDRESS_LINE_1]';
                            $template = str_replace($item, $NI_number, $template);
                        }
                        else if ($item == "[COMPANY_CITY]") {
                            $NI_number = isset($business["city"]) ? $business["city"] : '[COMPANY_CITY]';
                            $template = str_replace($item, $NI_number, $template);
                        }
                        else if ($item == "[COMPANY_POSTCODE]") {
                            $NI_number = isset($business["postcode"]) ? $business["postcode"] : '[COMPANY_POSTCODE]';
                            $template = str_replace($item, $NI_number, $template);
                        }
                        else if ($item == "[COMPANY_COUNTRY]") {
                            $NI_number = isset($business["country"]) ? $business["country"] : '[COMPANY_COUNTRY]';
                            $template = str_replace($item, $NI_number, $template);
                        }

                         else {
                            $template = str_replace($item, $employee[$variableName], $template);
                        }
                    }
                }



                return response(["template" => $template], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-letters/download",
     *      operationId="downloadUserLetter",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to download pdf",
     *      description="This method is to download pdf",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name"},
     *             @OA\Property(property="user_letter_id", type="string", format="string",example="user_letter_id"),
     *             @OA\Property(property="user_id", type="string", format="string",example="user_id"),
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

    public function downloadUserLetter(DownloadUserLetterPdfRequest $request)
    {
        try {

            $this->isModuleEnabled("letter_template");
            $request_data = $request->validated();

            $user_letter =  UserLetter::where([
                "id" => $request_data["user_letter_id"]
            ])
                ->first();


            $pdf = PDF::loadView('email.dynamic_mail', ["html_content" => $user_letter->letter_content]);

            return $pdf->download(("letter" . '.pdf'));
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-letters/send",
     *      operationId="sendUserLetterEmail",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to send pdf via email",
     *      description="This method is to send pdf via email",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name"},
     *             @OA\Property(property="user_letter_id", type="string", format="string",example="user_letter_id"),
     *             @OA\Property(property="user_id", type="string", format="string",example="user_id"),
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
     *          description="Unprocessable Content",
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
     *      description="Not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */


    public function sendUserLetterEmail(DownloadUserLetterPdfRequest $request)
    {
        try {
            $request_data = $request->validated();

            $user_letter = UserLetter::where([
                "id" => $request_data["user_letter_id"]
            ])->first();

            $employee = User::where([
                "id" => $request_data["user_id"]
            ])
                ->first();

                $emailSent = true;
                $errorMessage = null;

                if (env('SEND_EMAIL') == true) {
                    // Log email sender actions
                    $this->checkEmailSender(auth()->user()->id, 0);

                    $pdf = PDF::loadView('email.dynamic_mail', ['html_content' => $user_letter->letter_content]);

                    try {
                        // Send the email
                        Mail::to($employee->email)->send(new UserLetterMail($pdf));

                    } catch (\Exception $e) {
                        // Set error message
                        $errorMessage = $e->getMessage();
                        $emailSent = false;
                    } finally {
                        // Ensure that email sender actions are always logged
                        $this->storeEmailSender(auth()->user()->id, 0);
                    }
                }

                // Update the user_letter record if email was sent
                if ($emailSent) {
                    $user_letter->email_sent = true;
                    $user_letter->save();
                }

                // Create a history record
                UserLetterEmailHistory::create([
                    'user_letter_id' => $user_letter->id,
                    'sent_at' => $emailSent ? now() : null,
                    'recipient_email' => $employee->email,
                    'email_content' => $user_letter->letter_content,
                    'status' => $emailSent ? 'sent' : 'failed',
                    'error_message' => $emailSent ? null : $errorMessage
                ]);


            return response()->json(['message' => 'Email sent successfully.'], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-letters",
     *      operationId="updateUserLetter",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user letters ",
     *      description="This method is to update user letters ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="issue_date", type="string", format="string", example="issue_date"),
     * @OA\Property(property="letter_content", type="string", format="string", example="letter_content"),
     * @OA\Property(property="status", type="string", format="string", example="status"),
     * @OA\Property(property="sign_required", type="string", format="string", example="sign_required"),
     * @OA\Property(property="letter_view_required", type="string", format="string", example="letter_view_required"),
     *
     * @OA\Property(property="user_id", type="string", format="string", example="user_id"),
     * @OA\Property(property="attachments", type="string", format="string", example="attachments"),
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

    public function updateUserLetter(UserLetterUpdateRequest $request)
    {

        try {

            $this->isModuleEnabled("letter_template");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('user_letter_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();
                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }



                $user_letter_query_params = [
                    "id" => $request_data["id"],
                ];

                $user_letter = UserLetter::where($user_letter_query_params)->first();

                if ($user_letter) {
                    $user_letter->fill(collect($request_data)->only([

                        "issue_date",
                        "letter_content",
                        "status",
                        "sign_required",
                        "letter_view_required",
                        "user_id",
                        "attachments",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $user_letter->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($user_letter, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
       /**
     *
     * @OA\Put(
     *      path="/v1.0/user-letters/view",
     *      operationId="updateUserLetterView",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user letters ",
     *      description="This method is to update user letters ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="letter_viewed", type="string", format="string", example="issue_date"),
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

     public function updateUserLetterView(UserLetterUpdateViewRequest $request)
     {

         try {

             $this->isModuleEnabled("letter_template");
             return DB::transaction(function () use ($request) {

                //  if (!$request->user()->hasPermissionTo('user_letter_update')) {
                //      return response()->json([
                //          "message" => "You can not perform this action"
                //      ], 401);
                //  }
                 $request_data = $request->validated();



                 $user_letter_query_params = [
                     "id" => $request_data["id"],
                     "user_id" => auth()->user()->id,
                 ];

                 $user_letter = UserLetter::where($user_letter_query_params)->first();

                 if ($user_letter) {
                     $user_letter->fill(collect($request_data)->only([
                         "letter_viewed",
                         // "is_default",
                         // "is_active",
                         // "business_id",
                         // "created_by"
                     ])->toArray());
                     $user_letter->save();
                 } else {
                     return response()->json([
                         "message" => "something went wrong."
                     ], 500);
                 }




                 return response($user_letter, 201);
             });
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-letters",
     *      operationId="getUserLetters",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *         @OA\Parameter(
     *         name="start_issue_date",
     *         in="query",
     *         description="start_issue_date",
     *         required=true,
     *  example="6"
     *      ),
     *         @OA\Parameter(
     *         name="end_issue_date",
     *         in="query",
     *         description="end_issue_date",
     *         required=true,
     *  example="6"
     *      ),



     *         @OA\Parameter(
     *         name="letter_content",
     *         in="query",
     *         description="letter_content",
     *         required=true,
     *  example="6"
     *      ),



     *         @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="status",
     *         required=true,
     *  example="6"
     *      ),






     *         @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),

     *     @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *     @OA\Parameter(
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
     * *  @OA\Parameter(
     * name="id",
     * in="query",
     * description="id",
     * required=true,
     * example="ASC"
     * ),

     *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="ASC"
     * ),
     *  *    * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="ASC"
     * ),
     *
     *
     *      summary="This method is to get user letters  ",
     *      description="This method is to get user letters ",
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

    public function getUserLetters(Request $request)
    {
        try {


            $this->isModuleEnabled("letter_template");

            if (request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('user_letter_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $user_lettersQuery = UserLetter::with([
                "user" => function ($query) {
                    $query->select("users.id", "users.title", "users.first_Name", "users.middle_Name", "users.last_Name");
                }
            ])
                ->where('user_letters.business_id', auth()->user()->business_id)

                ->when(request()->boolean("show_my_data"),function ($query) {
                    $query->where('user_letters.user_id', auth()->user()->id);
                }, function($query) use($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })

                ->when(!empty($request->id), function ($query) use ($request) {
                    return $query->where('user_letters.id', $request->id);
                })

                ->when(!empty($request->start_issue_date), function ($query) use ($request) {
                    return $query->where('user_letters.issue_date', ">=", $request->start_issue_date);
                })
                ->when(!empty($request->end_issue_date), function ($query) use ($request) {
                    return $query->where('user_letters.issue_date', "<=", ($request->end_issue_date . ' 23:59:59'));
                })



                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('user_letters.status', $request->status);
                })

                ->when(
                    !empty($request->user_id),
                    function ($query) use ($request) {
                        return $query->where('user_letters.user_id', $request->user_id);
                    }
                )

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query

                            ->where("user_letters.letter_content", "like", "%" . $term . "%")
                            ->orWhere("user_letters.status", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_letters.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_letters.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $user_letters =  $this->retrieveData($user_lettersQuery, "id", "user_letters");



            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    if (empty($user_letters->count())) {
                        $pdf = PDF::loadView('pdf.no_data', []);
                    } else {
                        $pdf = PDF::loadView('pdf.user_letters', ["user_letters" => $user_letters]);
                    }

                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'attendance') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    // return Excel::download(new AttendancesExport($attendances), ((!empty($request->file_name) ? $request->file_name : 'attendance') . '.csv'));
                }
            } else {
                return response()->json($user_letters, 200);
            }


        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


        /**
     *
     * @OA\Get(
     *      path="/v1.0/user-letters-histories",
     *      operationId="getUserLetterHistories",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

*     @OA\Parameter(
 *         name="user_letter_id",
 *         in="query",
 *         description="Filter by user letter ID.",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *  *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by status.",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 * *     @OA\Parameter(
 *         name="start_sent_at",
 *         in="query",
 *         description="Filter by start sent date. Format: YYYY-MM-DD",
 *         required=false,
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="end_sent_at",
 *         in="query",
 *         description="Filter by end sent date. Format: YYYY-MM-DD",
 *         required=false,
 *         @OA\Schema(type="string", format="date")
 *     ),
     *
     *         @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),

     *     @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *     @OA\Parameter(
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
     * *  @OA\Parameter(
     * name="id",
     * in="query",
     * description="id",
     * required=true,
     * example="ASC"
     * ),

     *    * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="ASC"
     * ),
     *
     *      summary="This method is to get user letters  ",
     *      description="This method is to get user letters ",
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

     public function getUserLetterHistories(Request $request)
     {
         try {

             $this->isModuleEnabled("letter_template");
             if (!$request->user()->hasPermissionTo('user_letter_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

             $all_manager_department_ids = $this->get_all_departments_of_manager();

             $user_letter_email_historiesQuery = UserLetterEmailHistory::
                 whereHas("user_letters.user.departments", function ($query) use ($all_manager_department_ids) {
                     $query->whereIn("departments.id", $all_manager_department_ids);
                 })

                 ->when(
                    empty($request->user_id),
                    function ($query) use ($request) {
                        return $query->whereHas("user_letters", function ($query)  {
                            $query->whereNotIn("users.id", [auth()->user()->id]);
                        });
                    },
                    function ($query) use ($request) {
                        return $query->whereHas("user_letters", function ($query) use($request) {
                            $query->whereIn("users.id", [$request->user_id]);
                        });

                    }
                )
                ->when(!empty($request->user_letter_id), function ($query) use ($request) {
                    return $query->where('user_letter_email_histories.user_letter_id', $request->user_letter_id);
                })
                 ->when(!empty($request->id), function ($query) use ($request) {
                     return $query->where('user_letter_email_histories.id', $request->id);
                 })
                 ->when(!empty($request->start_sent_at), function ($query) use ($request) {
                     return $query->where('user_letter_email_histories.sent_at', ">=", $request->start_sent_at);
                 })
                 ->when(!empty($request->end_sent_at), function ($query) use ($request) {
                     return $query->where('user_letter_email_histories.sent_at', "<=", ($request->end_sent_at . ' 23:59:59'));
                 })
                 ->when(!empty($request->status), function ($query) use ($request) {
                     return $query->where('user_letter_email_histories.status', $request->status);
                 })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query

                             ->where("user_letter_email_histories.letter_content", "like", "%" . $term . "%")
                             ->orWhere("user_letter_email_histories.recipient_email", "like", "%" . $term . "%");
                     });
                 })

                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('user_letter_email_histories.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('user_letter_email_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 });

                  $user_letter_email_histories =  $this->retrieveData($user_letter_email_historiesQuery, "id", "user_letter_email_histories");


             return response()->json($user_letter_email_histories, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-letters/{ids}",
     *      operationId="deleteUserLettersByIds",
     *      tags={"user_letters"},
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
     *      summary="This method is to delete user letter by id",
     *      description="This method is to delete user letter by id",
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

    public function deleteUserLettersByIds(Request $request, $ids)
    {

        try {

            $this->isModuleEnabled("letter_template");
            if (!$request->user()->hasPermissionTo('user_letter_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("letters",function($query) use($idsArray) {
                $query->whereIn('user_letters.id', $idsArray);
              })
              ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);




            $existingIds = UserLetter::whereIn('id', $idsArray)
                ->where('user_letters.business_id', auth()->user()->business_id)

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





            UserLetter::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
