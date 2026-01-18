<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegenerateTokenRequest;


use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\EmailVerifyTokenRequest;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\ForgetPasswordV2Request;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\UserInfoUpdateRequest;
use App\Http\Utils\BasicEmailUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\EmailLogUtil;
use App\Http\Utils\ModuleUtil;

use App\Http\Utils\UserDetailsUtil;
use App\Mail\ResetPasswordMail;
use App\Mail\EmailVerificationMail;
use App\Mail\OtpMail;
use App\Models\Business;

use App\Models\Otp;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use ErrorUtil, BusinessUtil, EmailLogUtil, UserDetailsUtil, ModuleUtil, BasicEmailUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/register",
     *      operationId="z.unused",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user",
     *      description="This method is to store user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode"},
     *      *             @OA\Property(property="title", type="string", format="string",example="title"),
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="Al"),
     *            @OA\Property(property="email", type="string", format="string",example="rifat@g.c"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="string",example="12345678"),
     *  * *  @OA\Property(property="phone", type="string", format="string",example="01771034383"),
     *  * *  @OA\Property(property="address_line_1", type="string", format="string",example="dhaka"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="string",example="dinajpur"),
     *  * *  @OA\Property(property="country", type="string", format="string",example="bangladesh"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="dhaka"),
     *  * *  @OA\Property(property="postcode", type="string", format="string",example="1207"),
     *      *  * *  @OA\Property(property="lat", type="string", format="string",example="1207"),
     *      *  * *  @OA\Property(property="long", type="string", format="string",example="1207"),
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

    public function register(AuthRegisterRequest $request)
    {
        try {

            $this->checkEmployeeCreationLimit(true);


            $request_data = $request->validated();

            $request_data['password'] = Hash::make($request['password']);
            $request_data['remember_token'] = Str::random(10);
            $request_data['is_active'] = true;






            $user =  User::create($request_data);

            // verify email starts
            $email_token = Str::random(30);
            $user->email_verify_token = $email_token;
            $user->email_verify_token_expires = Carbon::now()->subDays(-1);
            $user->save();


            $user->assignRole("customer");

            $user->token = $user->createToken('authToken')->accessToken;
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');


            if (env("SEND_EMAIL") == true) {

                $this->checkEmailSender($user->id, 0);

                $this->emailConfigure($user);

                try {
                    Mail::to($user->email)->send(new EmailVerificationMail($user));
                } catch (\Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }



                $this->storeEmailSender($user->id, 0);
            }

            // verify email ends

            return response($user, 201);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Post(
     *      path="/v1.0/login",
     *      operationId="login",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to login user",
     *      description="This method is to login user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email","password"},
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
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
    public function login(Request $request)
    {


        try {


            $loginData = $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);
            $user = User::where('email', $loginData['email'])->first();

            if ($user && $user->login_attempts >= 5) {
                $now = Carbon::now();
                $lastFailedAttempt = Carbon::parse($user->last_failed_login_attempt_at);
                $diffInMinutes = $now->diffInMinutes($lastFailedAttempt);

                if ($diffInMinutes < 15) {
                    return response(['message' => 'You have 5 failed attempts. Reset your password or wait for 15 minutes to access your account.'], 403);
                } else {
                    $user->login_attempts = 0;
                    $user->last_failed_login_attempt_at = null;
                    $user->save();
                }
            }


            if (!auth()->attempt($loginData)) {
                if ($user) {
                    $user->login_attempts++;
                    $user->last_failed_login_attempt_at = Carbon::now();
                    $user->save();
                }

                return response(['message' => 'Invalid Credentials'], 403);
            }



            if (empty($user->is_active)) {

                return response(['message' => 'User not active'], 403);
            }







            if ($user->business_id) {
                $business = Business::where([
                    "id" => $user->business_id
                ])
                    ->first();
                if (empty($business)) {
                    return response(['message' => 'Your business not found'], 403);
                }
                if (!$business->is_active) {

                    return response(['message' => 'Business not active'], 403);
                }
            }






            $now = time(); // or your date as well
            $user_created_date = strtotime($user->created_at);
            $datediff = $now - $user_created_date;

            // if (!$user->email_verified_at && (($datediff / (60 * 60 * 24)) > 1)) {
            //     $email_token = Str::random(30);
            //     $user->email_verify_token = $email_token;
            //     $user->email_verify_token_expires = Carbon::now()->subDays(-1);

            //     if (env("SEND_EMAIL") == true) {


            //         $this->checkEmailSender($user->id, 0);

            //         $this->emailConfigure($user);
            //         try {
            //             Mail::to($user->email)->send(new EmailVerificationMail($user));
            //         } catch (\Exception $e) {
            //             // Optionally log the error message if needed
            //             Log::error("Failed to send email: " . $e->getMessage());
            //             // Continue processing without interrupting the flow
            //         }

            //         $this->storeEmailSender($user->id, 0);
            //     }

            //     $user->save();

            //     return response(['message' => 'please activate your email first'], 409);
            // }


            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;


            $site_redirect_token = Str::random(30);
            $site_redirect_token_data["created_at"] = $now;
            $site_redirect_token_data["token"] = $site_redirect_token;
            $user->site_redirect_token = json_encode($site_redirect_token_data);
            $user->save();

            $user->redirect_token = $site_redirect_token;

            $user->token = auth()->user()->createToken('authToken')->accessToken;
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');
            $user->business = $user->business;

            Auth::login($user);





            return response()->json(['data' => $user,   "ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v2.0/login",
     *      operationId="loginV2",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to login user",
     *      description="This method is to login user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email","password"},
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
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
    public function loginV2(Request $request)
    {


        try {


            $loginData = $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);
            $user = User::where('email', $loginData['email'])->first();

            if (!$user) {
                return response(['message' => 'Invalid Credentials'], 403);
            }


            if ($user->login_attempts >= 5) {
                $now = Carbon::now();
                $lastFailedAttempt = Carbon::parse($user->last_failed_login_attempt_at);
                $diffInMinutes = $now->diffInMinutes($lastFailedAttempt);

                if ($diffInMinutes < 15) {
                    return response(['message' => 'Too many failed attempts. Please wait for 15 minutes or reset your password.'], 403);
                } else {
                    $user->login_attempts = 0;
                    $user->last_failed_login_attempt_at = null;
                    $user->save();
                }
            }


            if (!auth()->attempt($loginData)) {

                $user->login_attempts++;
                $user->last_failed_login_attempt_at = Carbon::now();
                $user->save();

                return response(['message' => 'Unable to authenticate, please check your credentials and try again.'], 403);
            }




            if (empty($user->is_active)) {
                return response(['message' => 'User not active'], 403);
            }







            if ($user->business_id) {
                $business = Business::where([
                    "id" => $user->business_id
                ])
                    ->first();
                if (empty($business)) {


                    return response(['message' => 'Your business not found'], 403);
                }
                if (!$business->is_active) {

                    return response(['message' => 'Business not active'], 403);
                }
            }




            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;


            $site_redirect_token = Str::random(30);
            $site_redirect_token_data["created_at"] = now();
            $site_redirect_token_data["token"] = $site_redirect_token;
            $user->site_redirect_token = json_encode($site_redirect_token_data);
            $user->save();




            $user = $user->load(['manager_departments', 'roles.permissions', 'permissions', 'business.service_plan.modules']);

            // Creating token only once
            $token = $user->createToken('authToken')->accessToken;
            // Transforming manager departments and roles
            $manager_departments = $user->manager_departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                ];
            });


            $roles = $user->roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });
            $user->permissions = $user->permissions->pluck("name");

            $manager_roles = [];
            if (!empty($user->manager_departments)) {

                $manager_roles = Role::with('permissions:name,id', "users")
                    ->where('business_id', $user->business_id)
                    ->where("id", ">", $this->getMainRoleId($user))
                    ->orderBy("id", "DESC")
                    ->get(["id", "name"])
                    ->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    });
            }

            $business = $user->business;

            $modules = [];

            if (!empty($business)) {
                $modules = $this->getModulesFunc($business);
            }



            // Extracting only the required data
            $responseData = [
                'id' => $user->id,
                "manager_roles" => $manager_roles,
                "manager_departments" => $manager_departments,
                "token" =>  $token,
                'business_id' => $user->business_id,
                'title' => $user->title,
                'first_Name' => $user->first_Name,
                'middle_Name' => $user->middle_Name,
                'last_Name' => $user->last_Name,
                'image' => $user->image,
                'roles' => $roles,
                'permissions' => $user->permissions,
                'manages_department' => $user->manages_department,
                'color_theme_name' => $user->color_theme_name,
                'email_verified_at' => $user->email_verified_at,
                'email' => $user->email,
                'joining_date' => $user->joining_date,
                'business' => [
                    'is_subscribed' => $business ? $business->is_subscribed : null,
                    'name' => $business ? $business->name : null,
                    'is_active' => $business ? $business->is_active : null,
                    'logo' => $business ? $business->logo : null,
                    'start_date' => $business ? $business->start_date : null,
                    'currency' => $business ? $business->currency : null,
                    'modules' => $modules,
                    'is_self_registered_businesses' => $business ? $business->is_self_registered_businesses : 0,
                    'trail_end_date' => $business ? $business->trail_end_date : "",
                    'business_start_day' => $business ? $business->business_start_day : "",
                    'current_subscription' =>  $business ? $business->current_subscription : "",
                    "stripe_subscription_enabled" =>   $business ? $business->stripe_subscription_enabled : 0,
                    'reseller_id' => $business ? $business->reseller_id : null,

                ]
            ];

            Auth::login($user);



            return response()->json(['data' => $responseData,   "ok" => true], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/login-with-otp",
     *      operationId="loginWithOtp",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to login user",
     *      description="This method is to login user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email","password"},
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
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
    public function loginWithOtp(Request $request)
    {


        try {


            // Validate the request data
            $loginData = $request->validate([
                'email' => 'email|required',
                'otp' => 'required|numeric|digits:6', // OTP should be 6 digits
            ]);

            $user = User::where('email', $loginData['email'])->first();

            if (!$user) {
                return response(['message' => 'Unable to authenticate, please check your credentials and try again.'], 403);
            }


            if ($user->login_attempts >= 5) {
                $now = Carbon::now();
                $lastFailedAttempt = Carbon::parse($user->last_failed_login_attempt_at);
                $diffInMinutes = $now->diffInMinutes($lastFailedAttempt);

                if ($diffInMinutes < 15) {
                    return response(['message' => 'Too many failed attempts. Please wait for 15 minutes or reset your password.'], 403);
                } else {
                    $user->login_attempts = 0;
                    $user->last_failed_login_attempt_at = null;
                    $user->save();
                }
            }

            // Check if OTP exists in the database and is valid
            $otpRecord = Otp::where('user_id', $user->id)
                ->where('otp', $loginData['otp'])
                ->where('expires_at', '>=', now())  // Check if the OTP is not expired
                ->where('is_used', 0)  // Ensure the OTP has not already been used
                ->first();


            if (!$otpRecord) {
                $user->login_attempts++;
                $user->last_failed_login_attempt_at = Carbon::now();
                $user->save();
                return response(['message' => 'Unable to authenticate, please check your credentials and try again.'], 403);
            }


            if (empty($user->is_active)) {
                return response(['message' => 'User not active'], 403);
            }





            if ($user->business_id) {
                $business = Business::where([
                    "id" => $user->business_id
                ])
                    ->first();
                if (empty($business)) {


                    return response(['message' => 'Your business not found'], 403);
                }
                if (!$business->is_active) {

                    return response(['message' => 'Business not active'], 403);
                }


                // if ($business->owner_id != $user->id) {

                //     if ($user->hasRole(("business_employee#" . $business->id))) {
                //         $this->isModuleEnabled("employee_login");
                //     }
                // }

            }


            $otpRecord->markAsUsed();

            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;


            $site_redirect_token = Str::random(30);
            $site_redirect_token_data["created_at"] = now();
            $site_redirect_token_data["token"] = $site_redirect_token;
            $user->site_redirect_token = json_encode($site_redirect_token_data);
            $user->save();




            $user = $user->load(['manager_departments', 'roles.permissions', 'permissions', 'business.service_plan.modules']);

            // Creating token only once
            $token = $user->createToken('authToken')->accessToken;
            // Transforming manager departments and roles
            $manager_departments = $user->manager_departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                ];
            });


            $roles = $user->roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });
            $user->permissions = $user->permissions->pluck("name");

            $manager_roles = [];
            if (!empty($user->manager_departments)) {

                $manager_roles = Role::with('permissions:name,id', "users")
                    ->where('business_id', $user->business_id)
                    ->where("id", ">", $this->getMainRoleId($user))
                    ->orderBy("id", "DESC")
                    ->get(["id", "name"])
                    ->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    });
            }

            $business = $user->business;

            $modules = [];

            if (!empty($business)) {
                $modules = $this->getModulesFunc($business);
            }



            // Extracting only the required data
            $responseData = [
                'id' => $user->id,
                "manager_roles" => $manager_roles,
                "manager_departments" => $manager_departments,
                "token" =>  $token,
                'business_id' => $user->business_id,
                'title' => $user->title,
                'first_Name' => $user->first_Name,
                'middle_Name' => $user->middle_Name,
                'last_Name' => $user->last_Name,
                'image' => $user->image,
                'roles' => $roles,
                'permissions' => $user->permissions,
                'manages_department' => $user->manages_department,
                'color_theme_name' => $user->color_theme_name,
                'email_verified_at' => $user->email_verified_at,
                'email' => $user->email,
                'joining_date' => $user->joining_date,
                'business' => [
                    'is_subscribed' => $business ? $business->is_subscribed : null,
                    'name' => $business ? $business->name : null,
                    'is_active' => $business ? $business->is_active : null,
                    'logo' => $business ? $business->logo : null,
                    'start_date' => $business ? $business->start_date : null,
                    'currency' => $business ? $business->currency : null,
                    'modules' => $modules,
                    'is_self_registered_businesses' => $business ? $business->is_self_registered_businesses : 0,
                    'trail_end_date' => $business ? $business->trail_end_date : "",
                    'business_start_day' => $business ? $business->business_start_day : "",
                    'current_subscription' =>  $business ? $business->current_subscription : "",
                    "stripe_subscription_enabled" =>   $business ? $business->stripe_subscription_enabled : 0,
                    'reseller_id' => $business ? $business->reseller_id : null,

                ]
            ];

            Auth::login($user);



            return response()->json(['data' => $responseData,   "ok" => true], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/send-otp",
     *      operationId="sendOtp",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to login user",
     *      description="This method is to login user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email","password"},
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
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
    public function sendOtp(Request $request)
    {


        try {

            $loginData = $request->validate([
                'email' => 'email|required',
                'send_otp' => 'required|boolean',
            ]);

            $user = User::where('email', $loginData['email'])->first();

            if (!$user) {
                return response(['message' => 'Please check your email'], 403);
            }

            if ($user->login_attempts >= 5) {
                $now = Carbon::now();
                $lastFailedAttempt = Carbon::parse($user->last_failed_login_attempt_at);
                $diffInMinutes = $now->diffInMinutes($lastFailedAttempt);

                if ($diffInMinutes < 15) {
                    return response(['message' => 'You have 5 failed attempts. Reset your password or wait for 15 minutes to access your account.'], 403);
                } else {
                    $user->login_attempts = 0;
                    $user->last_failed_login_attempt_at = null;
                    $user->save();
                }
            }

            if (empty($user->is_active)) {
                return response(['message' => 'User not active'], 403);
            }




            if ($user->business_id) {
                $business = Business::where([
                    "id" => $user->business_id
                ])
                    ->first();
                if (empty($business)) {
                    return response(['message' => 'Your business not found'], 403);
                }
                if (!$business->is_active) {
                    return response(['message' => 'Business not active'], 403);
                }
            }

            // Generate OTP (for example, a random 6-digit number)
            $otp = rand(100000, 999999);

            // Calculate expiration time (e.g., 5 minutes from now)
            $expiresAt = Carbon::now()->addMinutes(5);

            // Store the OTP in the database
            $otpEntry = Otp::create([
                'user_id' => $user->id,  // Assuming $user is the authenticated user or the user for whom the OTP is being generated
                'otp' => $otp,
                'expires_at' => $expiresAt,
            ]);


            // Send OTP email

            if (env("SEND_EMAIL") == true) {
                if (!empty($loginData["send_otp"])) {
                    Mail::to($user->email)->send(new OtpMail($otp));
                } else {
                    Mail::to([
                        "rifatbilalphilips@gmail.com",
                        "asjadtariq@gmail.com",
                    ])->send(new OtpMail($otp));
                }
            } else {
                return response()->json(["otp" => $otp], 200);
            }


            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }










    /**
     *
     * @OA\Post(
     *      path="/v1.0/logout",
     *      operationId="logout",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to logout user",
     *      description="This method is to logout user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
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
    public function logout(Request $request)
    {


        try {

            $request->user()->token()->revoke();

            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/forgetpassword",
     *      operationId="storeToken",
     *      tags={"auth"},

     *      summary="This method is to store token",
     *      description="This method is to store token",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",* example="test@g.c"),
     *    *             @OA\Property(property="client_site", type="string", format="string",* example="client"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */

    public function storeToken(ForgetPasswordRequest $request)
    {

        DB::beginTransaction();
        try {

            $request_data = $request->validated();

            $user = User::where(["email" => $request_data["email"]])->first();
            if (!$user) {

                return response()->json(["message" => "no user found"], 404);
            }

            $token = Str::random(30);

            $user->resetPasswordToken = $token;
            $user->resetPasswordExpires = Carbon::now()->subDays(-1);
            $user->save();



            if (env("SEND_EMAIL") == true) {
                $this->checkEmailSender($user->id, 1);

                $this->emailConfigure($user);

                try {
                    $result = Mail::to($request_data["email"])->send(new ResetPasswordMail($user, $request_data["client_site"]));
                } catch (\Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }



                $this->storeEmailSender($user->id, 1);
            }

            if (count(Mail::failures()) > 0) {
                // Handle failed recipients and log the error messages
                foreach (Mail::failures() as $emailFailure) {
                }
                throw new Exception("Failed to send email to:" . $emailFailure);
            }

            DB::commit();
            return response()->json([
                "message" => "Please check your email."
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Post(
     *      path="/v2.0/forgetpassword",
     *      operationId="storeTokenV2",
     *      tags={"auth"},

     *      summary="This method is to store token",
     *      description="This method is to store token",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",* example="test@g.c"),
     *    *             @OA\Property(property="client_site", type="string", format="string",* example="client"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */

    public function storeTokenV2(ForgetPasswordV2Request $request)
    {

        DB::beginTransaction();
        try {

            $request_data = $request->validated();

            $user = User::where(["id" => $request_data["id"]])->first();
            if (!$user) {

                return response()->json(["message" => "no user found"], 404);
            }

            $token = Str::random(30);

            $user->resetPasswordToken = $token;
            $user->resetPasswordExpires = Carbon::now()->subDays(-1);
            $user->save();



            if (env("SEND_EMAIL") == true) {

                $this->checkEmailSender($user->id, 1);

                $this->emailConfigure($user);


                try {
                    $result = Mail::to($user->email)->send(new ResetPasswordMail($user, $request_data["client_site"]));
                } catch (\Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }

                $this->storeEmailSender($user->id, 1);
            }

            if (count(Mail::failures()) > 0) {
                // Handle failed recipients and log the error messages
                foreach (Mail::failures() as $emailFailure) {
                }
                throw new Exception("Failed to send email to:" . $emailFailure);
            }

            DB::commit();

            return response()->json([
                "message" => "Please check your email."
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/resend-email-verify-mail",
     *      operationId="resendEmailVerifyToken",
     *      tags={"auth"},

     *      summary="This method is to resend email verify mail",
     *      description="This method is to resend email verify mail",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",* example="test@g.c"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */

    public function resendEmailVerifyToken(EmailVerifyTokenRequest $request)
    {

        DB::beginTransaction();
        try {

            $request_data = $request->validated();

            $user = User::where(["email" => $request_data["email"]])->first();
            if (!$user) {

                return response()->json(["message" => "no user found"], 404);
            }



            $email_token = Str::random(30);
            $user->email_verify_token = $email_token;
            $user->email_verify_token_expires = Carbon::now()->subDays(-1);
            if (env("SEND_EMAIL") == true) {

                $this->checkEmailSender($user->id, 0);

                $this->emailConfigure($user);


                try {
                    Mail::to($user->email)->send(new EmailVerificationMail($user));
                } catch (Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }


                $this->storeEmailSender($user->id, 0);
            }

            $user->save();

            DB::commit();
            return response()->json([
                "message" => "please check email"
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Patch(
     *      path="/forgetpassword/reset/{token}",
     *      operationId="changePasswordByToken",
     *      tags={"auth"},
     *  @OA\Parameter(
     * name="token",
     * in="path",
     * description="token",
     * required=true,
     * example="1"
     * ),
     *      summary="This method is to change password",
     *      description="This method is to change password",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"password"},
     *
     *     @OA\Property(property="password", type="string", format="string",* example="aaaaaaaa"),

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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */





    public function changePasswordByToken($token, ChangePasswordRequest $request)
    {
        DB::beginTransaction();
        try {

            $request_data = $request->validated();
            $user = User::where([
                "resetPasswordToken" => $token,
            ])
                ->where("resetPasswordExpires", ">", now())
                ->first();
            if (!$user) {

                return response()->json([
                    "message" => "Invalid Token Or Token Expired"
                ], 400);
            }

            $password = Hash::make($request_data["password"]);
            $user->password = $password;

            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;


            $user->save();

            DB::commit();
            return response()->json([
                "message" => "password changed"
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }







    /**
     *
     * @OA\Get(
     *      path="/v1.0/user",
     *      operationId="getUser",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get  user ",
     *      description="This method is to get user",
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


    public function getUser(Request $request)
    {
        try {

            $user = $this->manageTokens();
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');
            $user->business = $user->business;

            return response()->json(
                $user,
                200
            );
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v2.0/user",
     *      operationId="getUserV2",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get  user ",
     *      description="This method is to get user",
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


    public function getUserV2(Request $request)
    {
        try {


            // Eager load relationships
            $user = $this->manageTokens();

            $user = $user->load(['manager_departments', 'roles.permissions', 'permissions', 'business.service_plan.modules']);



            // Transforming manager departments and roles
            // Transforming manager departments and roles
            $manager_departments = $user->manager_departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                ];
            });

            $roles = $user->roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });
            $user->permissions = $user->permissions->pluck("name");
            // Extracting only the required data
            $manager_roles = [];
            if (!empty($user->manager_departments)) {

                $manager_roles = Role::with('permissions:name,id', "users")
                    ->where('business_id', $user->business_id)
                    ->where("id", ">", $this->getMainRoleId($user))
                    ->orderBy("id", "DESC")
                    ->get(["id", "name"])
                    ->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    });
            }


            $business = $user->business;

            $modules = [];

            if (!empty($business)) {
                $modules = $this->getModulesFunc($business);
            }

            // Extracting only the required data
            $responseData = [
                'id' => $user->id,
                "manager_roles" => $manager_roles,
                "manager_departments" => $manager_departments,
                "token" =>  $user->token,
                'business_id' => $user->business_id,
                'title' => $user->title,
                'first_Name' => $user->first_Name,
                'middle_Name' => $user->middle_Name,
                'last_Name' => $user->last_Name,
                'image' => $user->image,
                'roles' => $roles,
                'permissions' => $user->permissions,
                'manages_department' => $user->manages_department,
                'color_theme_name' => $user->color_theme_name,
                'email_verified_at' => $user->email_verified_at,
                'email' => $user->email,
                'joining_date' => $user->joining_date,

                'business' => [
                    'is_subscribed' => $business ? $business->is_subscribed : null,
                    'is_active' => $business ? $business->is_active : null,
                    'name' => $business ? $business->name : null,
                    'logo' => $business ? $business->logo : null,
                    'start_date' => $business ? $business->start_date : null,
                    'currency' => $business ? $business->currency : null,
                    'modules' => $modules,
                    'is_self_registered_businesses' => $business ? $business->is_self_registered_businesses : 0,
                    'trail_end_date' => $business ? $business->trail_end_date : "",
                    'business_start_day' => $business ? $business->business_start_day : "",
                    'current_subscription' =>  $business ? $business->current_subscription : "",
                    "stripe_subscription_enabled" =>   $business ? $business->stripe_subscription_enabled : 0,
                    'reseller_id' => $business ? $business->reseller_id : null,
                ]
            ];



            return response()->json(
                $responseData,
                200
            );
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v3.0/user",
     *      operationId="getUserV3",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get  user ",
     *      description="This method is to get user",
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


    public function getUserV3(Request $request)
    {
        try {

            $user = $this->manageTokens();



            $user->roles = $user->roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });

            $user->permissions = $user->permissions->pluck("name");

            // Extracting only the required data
            $responseData = [
                'id' => $user->id,
                "token" =>  $user->token,
                'business_id' => $user->business_id,
                'title' => $user->title,
                'first_Name' => $user->first_Name,
                'middle_Name' => $user->middle_Name,
                'last_Name' => $user->last_Name,
                'image' => $user->image,
                'roles' => $user->roles,
                'permissions' => $user->permissions,
                'manages_department' => $user->manages_department,
                'color_theme_name' => $user->color_theme_name,
                'email_verified_at' => $user->email_verified_at,

                'email' => $user->email,
            ];



            return response()->json(
                $responseData,
                200
            );
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/auth/check/email",
     *      operationId="checkEmail",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check user",
     *      description="This method is to check user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",example="test@g.c"),
     *     *  *             @OA\Property(property="user_id", type="string", format="string",example="1"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */


    public function checkEmail(Request $request)
    {
        try {

            $user = User::where([
                "email" => $request->email
            ])
                ->when(
                    !empty($request->user_id),
                    function ($query) use ($request) {
                        $query->whereNotIn("id", [$request->user_id]);
                    }
                )

                ->first();
            if ($user) {
                return response()->json(["data" => true], 200);
            }
            return response()->json(["data" => false], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/auth/check/business/email",
     *      operationId="checkBusinessEmail",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check user",
     *      description="This method is to check user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",example="test@g.c"),
     *     *  *             @OA\Property(property="business_id", type="string", format="string",example="1"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */


    public function checkBusinessEmail(Request $request)
    {
        try {

            $user = Business::where([
                "email" => $request->email
            ])
                ->when(
                    !empty($request->business_id),
                    function ($query) use ($request) {
                        $query->whereNotIn("id", [$request->business_id]);
                    }
                )

                ->first();
            if ($user) {
                return response()->json(["data" => true], 200);
            }
            return response()->json(["data" => false], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Patch(
     *      path="/auth/changepassword",
     *      operationId="changePassword",
     *      tags={"auth"},
     *
     *      summary="This method is to change password",
     *      description="This method is to change password",
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"password","cpassword"},
     *
     *     @OA\Property(property="password", type="string", format="string",* example="aaaaaaaa"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="string",example="aaaaaaaa"),
     *     @OA\Property(property="current_password", type="string", format="string",* example="aaaaaaaa"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */





    public function changePassword(PasswordChangeRequest $request)
    {
        try {

            $client_request = $request->validated();

            $user = $request->user();



            if (!Hash::check($client_request["current_password"], $user->password)) {

                return response()->json([
                    "message" => "Invalid password"
                ], 400);
            }

            $password = Hash::make($client_request["password"]);
            $user->password = $password;



            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;
            $user->save();



            return response()->json([
                "message" => "password changed"
            ], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }






    /**
     *
     * @OA\Put(
     *      path="/v1.0/update-user-info",
     *      operationId="updateUserInfo",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user by user",
     *      description="This method is to update user by user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode"},
     *
     *     *             @OA\Property(property="title", type="string", format="string",example="title"),
     *             @OA\Property(property="first_Name", type="string", format="string",example="tsa"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="ts"),
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="boolean", format="boolean",example="12345678"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="string",example="12345678"),
     *  * *  @OA\Property(property="phone", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="address_line_1", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="country", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="postcode", type="string", format="string",example="1"),
     *  *  * *  @OA\Property(property="lat", type="string", format="string",example="1"),
     *  *  * *  @OA\Property(property="long", type="string", format="string",example="1"),

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

    public function updateUserInfo(UserInfoUpdateRequest $request)
    {

        try {

            $request_data = $request->validated();


            if (!empty($request_data['password'])) {
                $request_data['password'] = Hash::make($request_data['password']);
            } else {
                unset($request_data['password']);
            }
            // $request_data['is_active'] = true;
            $request_data['remember_token'] = Str::random(10);

            $user  =  tap(User::where(["id" => $request->user()->id]))->update(
                collect($request_data)->only([
                    "title",
                    'first_Name',
                    'middle_Name',
                    'last_Name',
                    'password',
                    'phone',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    "lat",
                    "long",
                    'gender',
                    "image"
                ])->toArray()
            )
                // ->with("somthing")

                ->first();
            if (!$user) {
                return response()->json([
                    "message" => "no user found"
                ]);
            }


            $user->roles = $user->roles->pluck('name');



            return response($user, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
