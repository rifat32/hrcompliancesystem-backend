<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailSettingRequest;
use App\Http\Requests\UpdateSystemSettingRequest;
use App\Http\Utils\BasicEmailUtil;
use App\Http\Utils\EmailLogUtil;
use App\Http\Utils\ErrorUtil;

use App\Mail\TestEmail;
use App\Models\BusinessEmailSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BusinessEmailSettingController extends Controller
{
    use ErrorUtil, EmailLogUtil;
 /**
     *
     * @OA\Put(
     *      path="/v1.0/email-settings",
     *      operationId="updateEmailSetting",
     *      tags={"email_setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle module active",
     *      description="This method is to toggle module active",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *
 *   @OA\Property(property="mail_driver", type="string", example="smtp"),
 *   @OA\Property(property="mail_host", type="string", example="mail.example.com"),
 *   @OA\Property(property="mail_port", type="integer", example=465),
 *   @OA\Property(property="mail_username", type="string", example="_mainaccount@example.com"),
 *   @OA\Property(property="mail_password", type="string", example="?x(mujD}h}ZV"),
 *   @OA\Property(property="mail_encryption", type="string", example="ssl"),
 *   @OA\Property(property="mail_from_address", type="string", format="email", example="_mainaccount@example.com"),
 *   @OA\Property(property="mail_from_name", type="string", example="Your App Name"),
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

     public function updateEmailSetting(UpdateEmailSettingRequest $request)
     {

         try {

             if (!$request->user()->hasPermissionTo('email_setting_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();
             $user = auth()->user();

             Config::set('mail.driver', $request_data["mail_driver"]);
             Config::set('mail.host', $request_data["mail_host"]);
             Config::set('mail.port', $request_data["mail_port"]);
             Config::set('mail.username', $request_data["mail_username"]);
             Config::set('mail.password', $request_data["mail_password"]);
             Config::set('mail.encryption', $request_data["mail_encryption"]);
             Config::set('mail.from.address', $request_data["mail_from_address"]);
             Config::set('mail.from.name', $request_data["mail_from_name"]);

             try {
                // Check if SEND_EMAIL is true in the environment and send a test email
                if (env('SEND_EMAIL', false)) {
                    $user = auth()->user();

                    $this->checkEmailSender($user->id, 0);
                    try {
                        Mail::to($user->email)->send(new TestEmail($user));
                    } catch (\Exception $e) {
                        // Optionally log the error message if needed
                        Log::error("Failed to send email: " . $e->getMessage());
                        // Continue processing without interrupting the flow
                    }
                    // Send test email


                    $this->storeEmailSender($user->id, 0);
                }

                // Save or update the email settings in the database
                $emailSettings = BusinessEmailSetting::updateOrCreate(
                    ['business_id' => auth()->user()->business_id],
                    $request_data
                );

                return response()->json(['message' => 'Email settings saved successfully, and test email sent!'], 200);

            } catch (Exception $e) {
                return response()->json(['message' => 'Failed to send test email. Please check your settings and try again.',

                "info" => $e->getMessage()
            ], 400);
            }


         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

 /**
     *
     * @OA\Get(
     *      path="/v1.0/email-settings",
     *      operationId="getEmailSetting",
     *      tags={"email_setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },



     *      summary="This method is to get email setting",

     *      description="This method is to get  setting",
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

     public function getEmailSetting(Request $request)
     {
         try {

             if (!$request->user()->hasPermissionTo('email_setting_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $emailSettings = BusinessEmailSetting::where('business_id', auth()->user()->business_id)->first();


             return response()->json($emailSettings,200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

}
