<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegisterBusinessRequest;
use App\Http\Requests\BusinessCreateRequest;
use App\Http\Requests\BusinessTakeOverRequest;
use App\Http\Requests\BusinessUpdatePart1Request;
use App\Http\Requests\BusinessUpdatePart2Request;
use App\Http\Requests\BusinessUpdatePart2RequestV2;
use App\Http\Requests\BusinessUpdatePart3Request;
use App\Http\Requests\BusinessUpdatePensionRequest;
use App\Http\Requests\BusinessUpdateRequest;
use App\Http\Requests\BusinessUpdateRequestPart4;
use App\Http\Requests\BusinessUpdateSeparateRequest;

use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\EmailLogUtil;



use App\Models\Business;
use App\Models\BusinessPensionHistory;
use App\Models\BusinessSubscription;
use App\Models\BusinessTime;
use App\Models\ServicePlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EncryptionService;
// use App\Models\WorkShift;
// use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Stripe\Stripe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusinessController extends Controller
{
    use ErrorUtil, BusinessUtil, DiscountUtil, BasicUtil, EmailLogUtil;


    protected EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/businesses",
     *      operationId="createBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business",
     *      description="This method is to store  business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *  "owner_id":"1",
     *  "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}},
     * * "start_date":"start_date",
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     * "business_start_day":"business_start_day",
     * "pin_code":"pin_code",
     *
     * "enable_auto_business_setup":"enable_auto_business_setup",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
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
    public function createBusiness(BusinessCreateRequest $request)
    {
        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $request_data["business"] = $this->businessImageStore($request_data["business"]);


            $user = User::where([
                "id" =>  $request_data['business']['owner_id']
            ])
                ->first();

            if (!$user) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["owner_id" => ["No User Found"]]
                ];
                throw new Exception(json_encode($error), 422);
            }

            if (!$user->hasRole('business_owner')) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["owner_id" => ["The user is not a businesses Owner"]]
                ];
                throw new Exception(json_encode($error), 422);
            }



            $request_data['business']['status'] = "pending";

            $request_data['business']['created_by'] = $request->user()->id;
            $request_data['business']['reseller_id'] = $request->user()->id;
            $request_data['business']['is_active'] = true;
            $request_data['business']['is_self_registered_businesses'] = false;
            $request_data['business']["pension_scheme_letters"] = [];
            $business =  Business::create($request_data['business']);

            $this->storeDefaultsToBusiness($business);


            DB::commit();

            return response([
                "business" => $business
            ], 201);
        } catch (Exception $e) {
            $this->businessImageRollBack($request_data);

            DB::rollBack();
            return $this->sendError($e);
        }
    }









    /**
     *
     * @OA\Post(
     *      path="/v1.0/auth/register-with-business",
     *      operationId="registerUserWithBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with business",
     *      description="This method is to store user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     * "title":"title",
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "middle_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "send_password":1,
     * "gender":"male"
     *
     *
     * }),
     *
     *   *      *    @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),
     *
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   "number_of_employees_allowed" : 1,
     *
     *   "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}},
     * "name":"ABCD businesses",
     * "start_date":"start_date",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     * "business_start_day":"business_start_day",
     *
     * "pin_code":"pin_code",
     * "enable_auto_business_setup":"enable_auto_business_setup",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
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
    public function registerUserWithBusiness(AuthRegisterBusinessRequest $request)
    {
        DB::beginTransaction();
        try {



            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();





            // $request_data["business"] = $this->businessImageStore($request_data["business"]);

            $request_data["enable_auto_business_setup"] = 1;

            $data = $this->createUserWithBusiness($request_data);

            $this->createTicketingSystemUser($data["user"], $data["business"]);



            DB::commit();

            return response(
                [
                    "user" => $data["user"],
                    "business" => $data["business"]
                ],
                201
            );
        } catch (Exception $e) {
            $this->businessImageRollBack($request_data);
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function createTicketingSystemUser($user, $business)
    {
        $claims = [
            'ticketing_system_user_id' => $user->id,
            'ticketing_system_name' => $user->full_name,
            'ticketing_system_email' => $user->email,
            'ticketing_system_business_id' => $business->id ?? null,
            'ticketing_system_business_name' => $business->name ?? null,
            'ticketing_system_business_identifier_prefix' => $business->identifier_prefix ?? null,
            'ticketing_system_business_web_page' => $business->web_page ?? null,
            'ticketing_system_business_phone' => $business->phone ?? null,
            'ticketing_system_business_email' => $business->email ?? null,
            'ticketing_system_business_address_line_1' => $business->address_line_1 ?? null,
            'ticketing_system_business_address_line_2' => $business->address_line_2 ?? null,
            'ticketing_system_business_city' => $business->city ?? null,
            'ticketing_system_business_country' => $business->country ?? null,
            'ticketing_system_business_postcode' => $business->postcode ?? null,
            'ticketing_system_business_currency' => $business->currency ?? null,
            'ticketing_system_business_logo' => env("APP_URL") . ($business->logo ?? ''),
            'ticketing_system_app_id' => env('APP_ID'),
            'ticketing_system_app_url' => env('APP_URL'),
            'ticketing_system_front_end_url' => env('FRONT_END_URL'),
            'ip' => request()->ip(),
        ];

        $payload = $this->encryptionService->generateEncryptedToken($claims);


        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . request()->bearerToken(),
            ])->post(env('TICKETING_SYSTEM_BACKEND') . '/api/auth/token-login', $payload);
        } catch (Throwable $e) {
            Log::warning('Ticketing system user creation failed: ' . $e->getMessage());

            // Flow will continue without interruption
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/auth/register-with-business",
     *      operationId="registerUserWithBusinessClient",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with business",
     *      description="This method is to store user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     * "title":"title",
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "middle_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "send_password":1,
     * "gender":"male"
     *
     *
     * }),
     *
     *  @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),
     *
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   * "number_of_employees_allowed" : 0,
     * "service_plan_id" : 0,
     * "service_plan_discount_code" : 0,
     *   "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}},
     * "name":"ABCD businesses",
     * "start_date":"start_date",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     *      * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     * "business_start_day":"business_start_day",
     *
     *
     *  "pin_code":"pin_code",
     * "enable_auto_business_setup":"enable_auto_business_setup",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
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
    public function registerUserWithBusinessClient(AuthRegisterBusinessRequest $request)
    {
        DB::beginTransaction();
        try {



            $request_data = $request->validated();



            // $request_data["business"] = $this->businessImageStore($request_data["business"]);

            // $request_data['business']["active_module_ids"] = [];


            $data = $this->createUserWithBusiness($request_data);

            $this->createTicketingSystemUser($data["user"], $data["business"]);

            DB::commit();

            return response(
                [
                    "user" => $data["user"],
                    "business" => $data["business"]
                ],
                201
            );
        } catch (Exception $e) {


            $this->businessImageRollBack($request_data);

            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses",
     *      operationId="updateBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     *  * "id":1,
     * "title":"title",
     * "first_Name":"Rifat",
     *  * "middle_Name":"Al-Ashwad",
     *
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "gender":"male"
     *
     *
     * }),
     *
     *    *   *      *    @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     * "business_start_day":"business_start_day",
     *
     * "pin_code":"pin_code",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "currency":"BDT",
     * "number_of_employees_allowed":20
     *
     * }),
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
    public function updateBusiness(BusinessUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            $business = $this->businessOwnerCheck($request_data['business']["id"], FALSE);

            if (isset($request_data["business"]["service_plan_id"]) && $business->service_plan_id !== $request_data["business"]["service_plan_id"]) {
                $this->checkServicePlanAvailability($request_data["business"]["service_plan_id"], $business);
            }

            $request_data["business"] = $this->businessImageStore($request_data["business"], $business->id);

            $request_data["user"]["image"] = $this->storeUploadedFiles($request_data["user"]["image"], "", "business_images");
            $this->makeFilePermanent($request_data["user"]["image"], "");


            //    user email check
            $userPrev = User::where([
                "id" => $request_data["user"]["id"]
            ])->first();

            if (!$userPrev) {
                throw new Exception("no user found with this id", 404);
            }



            if (!empty($request_data['user']['password'])) {
                $request_data['user']['password'] = Hash::make($request_data['user']['password']);
            } else {
                unset($request_data['user']['password']);
            }
            $request_data['user']['is_active'] = true;
            $request_data['user']['remember_token'] = Str::random(10);
            $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
            $request_data['user']['address_line_2'] = $request_data['business']['address_line_2'];
            $request_data['user']['country'] = $request_data['business']['country'];
            $request_data['user']['city'] = $request_data['business']['city'];
            $request_data['user']['postcode'] = $request_data['business']['postcode'];
            $request_data['user']['lat'] = $request_data['business']['lat'];
            $request_data['user']['long'] = $request_data['business']['long'];

            $user  =  tap(User::where([
                "id" => $request_data['user']["id"]
            ]))->update(
                collect($request_data['user'])->only([
                    "title",
                    'first_Name',
                    'middle_Name',
                    'last_Name',
                    'phone',
                    'image',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    'email',
                    'password',
                    "lat",
                    "long",
                    "gender"
                ])->toArray()
            )


                ->first();
            if (!$user) {
                throw new Exception("something went wrong updating user.", 500);
            }




            if (!empty($request_data["business"]["is_self_registered_businesses"])) {
                $request_data['business']['service_plan_discount_amount'] = $this->getDiscountAmount($request_data['business']);
            }


            $valid_stripe = false;
            $systemSetting = SystemSetting::where("reseller_id", $business->reseller_id)
                ->first();

            if (!empty($systemSetting) && $systemSetting->self_registration_enabled) {
                $valid_stripe = true;
            }


            if ($valid_stripe) {
                Stripe::setApiKey($systemSetting->STRIPE_SECRET);
                Stripe::setClientId($systemSetting->STRIPE_KEY);

                if (isset($request_data["business"]["service_plan_id"]) && $business->service_plan_id !== $request_data["business"]["service_plan_id"]) {

                    if (!empty($user->stripe_id)) {

                        $subscriptions = \Stripe\Subscription::all([
                            'customer' => $user->stripe_id,
                            'status' => 'active',
                        ]);

                        foreach ($subscriptions->data as $subscription) {
                            // Cancel the subscription
                            \Stripe\Subscription::update($subscription->id, [
                                'cancel_at_period_end' => true,

                            ]);
                        }
                    }
                }
            }



            if (auth()->user()->id == $business->owner_id) {
                $request_data['business']["trail_end_date"] = $business->trail_end_date;
            }


            $business->fill(collect($request_data['business'])->only([
                "name",
                "start_date",
                "trail_end_date",
                "about",
                "web_page",
                "identifier_prefix",
                "delete_read_notifications_after_30_days",
                "business_start_day",
                "pin_code",
                "phone",
                "email",
                "additional_information",
                "address_line_1",
                "address_line_2",
                "lat",
                "long",
                "country",
                "city",
                "currency",
                "postcode",
                "logo",
                "image",
                "background_image",
                "status",
                "is_active",

                "is_self_registered_businesses",
                "service_plan_id",
                "service_plan_discount_code",
                "service_plan_discount_amount",
                "pension_scheme_registered",
                "pension_scheme_name",
                "pension_scheme_letters",
                "number_of_employees_allowed",
                "owner_id",


                // 'created_by'
            ])->toArray());

            $business->save();


            if (empty($business)) {
                return response()->json([
                    "massage" => "something went wrong"
                ], 500);
            }


            // end business info ##############

            if (!empty($request_data["times"])) {

                $timesArray = collect($request_data["times"])->unique("day");


                BusinessTime::where([
                    "business_id" => $business->id
                ])
                    ->delete();

                $timesArray = collect($request_data["times"])->unique("day");
                foreach ($timesArray as $business_time) {
                    BusinessTime::create([
                        "business_id" => $business->id,
                        "day" => $business_time["day"],
                        "start_at" => $business_time["start_at"],
                        "end_at" => $business_time["end_at"],
                        "is_weekend" => $business_time["is_weekend"],
                    ]);
                }
            }

            $business->service_plan = $business->service_plan;


            DB::commit();

            return response([
                "user" => $user,
                "business" => $business
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses-part-4",
     *      operationId="updateBusinessPart4",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     *      * "trail_end_date" : "",
     * "is_self_registered_businesses":1,
     * "service_plan_id" : 0,
     * "service_plan_discount_code" : 0,

     * "number_of_employees_allowed":20
     *
     * }),
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
    public function updateBusinessPart4(BusinessUpdateRequestPart4 $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();


            $business = $this->businessOwnerCheck($request_data['business']["id"], FALSE);


            // $user->syncRoles(["business_owner"]);


            if (!empty($request_data["business"]["is_self_registered_businesses"])) {
                $request_data['business']['service_plan_discount_amount'] = $this->getDiscountAmount($request_data['business']);
            }

            $valid_stripe = false;
            $systemSetting = SystemSetting::where("reseller_id", $business->reseller_id)
                ->first();

            if (!empty($systemSetting) && $systemSetting->self_registration_enabled) {
                $valid_stripe = true;
            }

            if ($valid_stripe) {
                Stripe::setApiKey($systemSetting->STRIPE_SECRET);
                Stripe::setClientId($systemSetting->STRIPE_KEY);

                if (isset($request_data["business"]["service_plan_id"]) && $business->service_plan_id !== $request_data["business"]["service_plan_id"]) {
                    if (!empty($business->owner->stripe_id)) {
                        $subscriptions = \Stripe\Subscription::all([
                            'customer' => $business->owner->stripe_id,
                            'status' => 'active',
                        ]);

                        foreach ($subscriptions->data as $subscription) {
                            // Cancel the subscription
                            \Stripe\Subscription::update($subscription->id, [
                                'cancel_at_period_end' => true, // Optional: Keep the subscription active until the end of the current billing period
                            ]);
                        }
                    }
                }
            }


            if (auth()->user()->id == $business->owner_id) {
                $request_data['business']["trail_end_date"] = $business->trail_end_date;
            }

            $business->fill(collect($request_data['business'])->only([
                "trail_end_date",
                "is_self_registered_businesses",
                "service_plan_id",
                "service_plan_discount_code",
                "service_plan_discount_amount",
                "number_of_employees_allowed",
                "is_active"
            ])->toArray());

            $business->save();


            if (empty($business)) {
                return response()->json([
                    "massage" => "something went wrong"
                ], 500);
            }




            DB::commit();

            return response([

                "business" => $business
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses-take-over",
     *      operationId="takeOverBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to take over business",
     *      description="This method is to  take over business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","id"},

     *
     *  @OA\Property(property="id", type="string", format="array",example="1"),
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
    public function takeOverBusiness(BusinessTakeOverRequest $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasRole('superadmin')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();


            $business = $this->businessOwnerCheck($request_data["id"], FALSE);


            $business->reseller_id = auth()->user()->id;


            $business->save();


            if (empty($business)) {
                return response()->json([
                    "massage" => "something went wrong"
                ], 500);
            }




            DB::commit();

            return response([

                "business" => $business
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses-part-1",
     *      operationId="updateBusinessPart1",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     *  * "id":1,
     *
     * "title":"title",
     * "first_Name":"Rifat",
     *  * "middle_Name":"Al-Ashwad",
     *
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "gender":"male"
     *
     *
     * }),
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
    public function updateBusinessPart1(BusinessUpdatePart1Request $request)
    {

        DB::beginTransaction();
        try {



            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business = $this->businessOwnerCheck(auth()->user()->business_id, FALSE);

            $request_data = $request->validated();
            //    user email check
            $userPrev = User::where([
                "id" => $request_data["user"]["id"]
            ]);
            // if (!$request->user()->hasRole('superadmin')) {
            //     $userPrev  = $userPrev->where(function ($query) {
            //         return  $query->where('created_by', auth()->user()->id)
            //             ->orWhere('id', auth()->user()->id);
            //     });
            // }
            $userPrev = $userPrev->first();

            if (!$userPrev) {
                throw new Exception("no user found with this id", 404);
            }




            //  $businessPrev = Business::where([
            //     "id" => $request_data["business"]["id"]
            //  ]);

            // $businessPrev = $businessPrev->first();
            // if(!$businessPrev) {
            //     return response()->json([
            //        "message" => "no business found with this id"
            //     ],404);
            //   }


            if (!empty($request_data['user']['password'])) {
                $request_data['user']['password'] = Hash::make($request_data['user']['password']);
            } else {
                unset($request_data['user']['password']);
            }
            $request_data['user']['is_active'] = true;
            $request_data['user']['remember_token'] = Str::random(10);
            $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
            $request_data['user']['address_line_2'] = $request_data['business']['address_line_2'];
            $request_data['user']['country'] = $request_data['business']['country'];
            $request_data['user']['city'] = $request_data['business']['city'];
            $request_data['user']['postcode'] = $request_data['business']['postcode'];
            $request_data['user']['lat'] = $request_data['business']['lat'];
            $request_data['user']['long'] = $request_data['business']['long'];
            $user  =  tap(User::where([
                "id" => $request_data['user']["id"]
            ]))->update(
                collect($request_data['user'])->only([
                    "title",
                    'first_Name',
                    'middle_Name',
                    'last_Name',
                    'phone',
                    'image',
                    'address_line_1',
                    'address_line_2',
                    'country',
                    'city',
                    'postcode',
                    'email',
                    'password',
                    "lat",
                    "long",
                    "gender"
                ])->toArray()
            )
                // ->with("somthing")

                ->first();
            if (!$user) {
                throw new Exception("something went wrong updating user.", 500);
            }


            DB::commit();
            return response([
                "user" => $user,

            ], 201);
        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses-part-2",
     *      operationId="updateBusinessPart2",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     * "business_start_day":"business_start_day",
     *
     *
     * "pin_code":"pin_code",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "currency":"BDT",

     * "number_of_employees_allowed":1
     *
     * }),
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
    public function updateBusinessPart2(BusinessUpdatePart2Request $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $request_data = $request->validated();

            $business = $this->businessOwnerCheck($request_data['business']["id"], FALSE);


            if (!empty($request_data["business"]["images"])) {
                $request_data["business"]["images"] = $this->storeUploadedFiles($request_data["business"]["images"], "", "business_images");
                $this->makeFilePermanent($request_data["business"]["images"], "");
            }
            if (!empty($request_data["business"]["image"])) {
                $request_data["business"]["image"] = $this->storeUploadedFiles([$request_data["business"]["image"]], "", "business_images")[0];
                $this->makeFilePermanent([$request_data["business"]["image"]], "");
            }
            if (!empty($request_data["business"]["logo"])) {
                $request_data["business"]["logo"] = $this->storeUploadedFiles([$request_data["business"]["logo"]], "", "business_images")[0];
                $this->makeFilePermanent([$request_data["business"]["logo"]], "");
            }
            if (!empty($request_data["business"]["background_image"])) {
                $request_data["business"]["background_image"] = $this->storeUploadedFiles([$request_data["business"]["background_image"]], "", "business_images")[0];
                $this->makeFilePermanent([$request_data["business"]["background_image"]], "");
            }





            $business->fill(collect($request_data['business'])->only([
                "name",
                "start_date",
                "about",
                "web_page",
                "identifier_prefix",

                "delete_read_notifications_after_30_days",
                "business_start_day",

                "pin_code",

                "phone",
                "email",
                "additional_information",
                "address_line_1",
                "address_line_2",
                "lat",
                "long",
                "country",
                "city",
                "postcode",
                "logo",
                "image",
                "status",
                "background_image",
                "currency",
                "number_of_employees_allowed"
            ])->toArray());

            $business->save();




            if (empty($business)) {
                return response()->json([
                    "massage" => "something went wrong"
                ], 500);
            }




            DB::commit();
            return response([
                "business" => $business
            ], 201);
        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v2.0/businesses-part-2",
     *      operationId="updateBusinessPart2V2",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     * "business_start_day":"business_start_day",
     *
     * "pin_code":"pin_code",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "currency":"BDT",
     * "number_of_employees_allowed":1
     *
     * }),
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
    public function updateBusinessPart2V2(BusinessUpdatePart2RequestV2 $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $request_data = $request->validated();

            $business = $this->businessOwnerCheck($request_data['business']["id"], FALSE);

            if (!empty($request_data["business"]["images"])) {
                $request_data["business"]["images"] = $this->storeUploadedFiles($request_data["business"]["images"], "", "business_images");
                $this->makeFilePermanent($request_data["business"]["images"], "");
            }



            if (!empty($request_data["business"]["image"])) {
                $request_data["business"]["image"] = $this->storeUploadedFiles([$request_data["business"]["image"]], "", "business_images")[0];
                $this->makeFilePermanent([$request_data["business"]["image"]], "");
            }
            if (!empty($request_data["business"]["logo"])) {
                $request_data["business"]["logo"] = $this->storeUploadedFiles([$request_data["business"]["logo"]], "", "business_images")[0];
                $this->makeFilePermanent([$request_data["business"]["logo"]], "");
            }
            if (!empty($request_data["business"]["background_image"])) {
                $request_data["business"]["background_image"] = $this->storeUploadedFiles([$request_data["business"]["background_image"]], "", "business_images")[0];
                $this->makeFilePermanent([$request_data["business"]["background_image"]], "");
            }





            $business->fill(collect($request_data['business'])->only([
                "name",
                "email",
                "phone",
                "address_line_1",
                "city",
                "country",
                "postcode",
                "start_date",
                "web_page",
                "identifier_prefix",

                "delete_read_notifications_after_30_days",
                "business_start_day",


                "pin_code",



            ])->toArray());

            $business->save();




            if (empty($business)) {
                return response()->json([
                    "massage" => "something went wrong"
                ], 500);
            }




            DB::commit();
            return response([
                "business" => $business
            ], 201);
        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses-part-3",
     *      operationId="updateBusinessPart3",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     *  * "id":1,
     *  "title":"title",
     * "first_Name":"Rifat",
     *  * "middle_Name":"Al-Ashwad",
     *
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "gender":"male"
     *
     *
     * }),
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     *      * * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     *"business_start_day":"business_start_day",

     * "pin_code":"pin_code",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "currency":"BDT",
     * "number_of_employees_allowed":10
     *
     * }),
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
    public function updateBusinessPart3(BusinessUpdatePart3Request $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business = $this->businessOwnerCheck(auth()->user()->business_id, FALSE);




            $request_data = $request->validated();



            if (!empty($request_data["times"])) {

                $timesArray = collect($request_data["times"])->unique("day");



                BusinessTime::where([
                    "business_id" => $business->id
                ])
                    ->delete();

                $timesArray = collect($request_data["times"])->unique("day");
                foreach ($timesArray as $business_time) {
                    BusinessTime::create([
                        "business_id" => $business->id,
                        "day" => $business_time["day"],
                        "start_at" => $business_time["start_at"],
                        "end_at" => $business_time["end_at"],
                        "is_weekend" => $business_time["is_weekend"],
                    ]);
                }
            }




            DB::commit();
            return response([

                "business" => $business
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/business-pension-information",
     *      operationId="updateBusinessPensionInformation",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business pension information",
     *      description="This method is to update pension information",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     *   "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}}
     *
     * }),
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
    public function updateBusinessPensionInformation(BusinessUpdatePensionRequest $request)
    {


        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $request_data = $request->validated();
            $business = $this->businessOwnerCheck($request_data['business']["id"], FALSE);

            $request_data["business"]["pension_scheme_letters"] = $this->storeUploadedFiles($request_data["business"]["pension_scheme_letters"], "", "pension_scheme_letters");

            $this->makeFilePermanent($request_data["business"]["pension_scheme_letters"], "");



            $pension_scheme_data =  collect($request_data['business'])->only([
                "pension_scheme_registered",
                "pension_scheme_name",
                "pension_scheme_letters",

            ])->toArray();


            $fields_to_check = [
                "pension_scheme_registered",
                "pension_scheme_name",
                "pension_scheme_letters",
            ];
            $date_fields = [];


            $fields_changed = $this->fieldsHaveChanged($fields_to_check, $business, $pension_scheme_data, $date_fields);

            if (
                $fields_changed
            ) {
                BusinessPensionHistory::create(array_merge(["created_by" => auth()->user()->id, "business_id" => $request_data['business']["id"]], $pension_scheme_data));
            }





            $business
                ->fill($pension_scheme_data)
                ->save();









            if (empty($business)) {
                return response()->json([
                    "massage" => "something went wrong"
                ], 500);
            }







            // $this->moveUploadedFiles(collect($request_data["business"]["pension_scheme_letters"])->pluck("file"),"pension_scheme_letters");



            DB::commit();
            return response([
                "business" => $business
            ], 201);
        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses/toggle-active",
     *      operationId="toggleActiveBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle business",
     *      description="This method is to toggle business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
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

    public function toggleActiveBusiness(GetIdRequest $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $business = $this->businessOwnerCheck($request_data["id"], FALSE);


            if (empty($business)) {
                throw new Exception("no business found", 404);
            }


            $business->update([
                'is_active' => !$business->is_active
            ]);

            return response()->json(['message' => 'business status updated successfully'], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }





    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses/separate",
     *      operationId="updateBusinessSeparate",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business",
     *      description="This method is to update business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     * "identifier_prefix":"identifier_prefix",
     *      * "delete_read_notifications_after_30_days":"delete_read_notifications_after_30_days",
     *      * "business_start_day":"business_start_day",
     * "pin_code":"pin_code",
     *
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     * *  "currency":"BDT"
     *
     * }),
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
    public function updateBusinessSeparate(BusinessUpdateSeparateRequest $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $request_data = $request->validated();
            $business = $this->businessOwnerCheck($request_data['business']["id"], FALSE);

            //  business info ##############
            // $request_data['business']['status'] = "pending";
            $business->fill(collect($request_data['business'])->only([
                "name",
                "start_date",
                "about",
                "web_page",
                "identifier_prefix",
                'delete_read_notifications_after_30_days',
                'business_start_day',
                "pin_code",
                "phone",
                "email",
                "additional_information",
                "address_line_1",
                "address_line_2",
                "lat",
                "long",
                "country",
                "city",
                "postcode",
                "logo",
                "image",
                "status",
                "background_image",
                "currency",

                "number_of_employees_allowed"
            ])->toArray());

            $business->save();


            if (empty($business)) {

                return response()->json([
                    "massage" => "no business found"
                ], 404);
            }








            DB::commit();
            return response([
                "business" => $business
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses",
     *      operationId="getBusinesses",
     *      tags={"business_management"},
     * *  @OA\Parameter(
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
     * name="country_code",
     * in="query",
     * description="country_code",
     * required=true,
     * example="country_code"
     * ),
     * *  @OA\Parameter(
     * name="address",
     * in="query",
     * description="address",
     * required=true,
     * example="address"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
     * ),
     * *  @OA\Parameter(
     * name="start_lat",
     * in="query",
     * description="start_lat",
     * required=true,
     * example="3"
     * ),
     * *  @OA\Parameter(
     * name="end_lat",
     * in="query",
     * description="end_lat",
     * required=true,
     * example="2"
     * ),
     * *  @OA\Parameter(
     * name="start_long",
     * in="query",
     * description="start_long",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="end_long",
     * in="query",
     * description="end_long",
     * required=true,
     * example="4"
     * ),
     *
     * * *  @OA\Parameter(
     * name="is_frozen",
     * in="query",
     * description="is_frozen",
     * required=false,
     * example=""
     * ),
     *  * @OA\Parameter(
     *     name="is_trail_ended",
     *     in="query",
     *     description="Filter businesses by whether the trial has ended.",
     *     required=false,
     * example=""
     * ),
     * @OA\Parameter(
     *     name="is_recurring_billing_business",
     *     in="query",
     *     description="Filter businesses by whether they use recurring billing.",
     *     required=false,
     * example=""
     * ),
     * @OA\Parameter(
     *     name="is_active",
     *     in="query",
     *     description="Filter businesses by their active status.",
     *     required=false,
     * example=""
     * ),
     * @OA\Parameter(
     *     name="reseller_id",
     *     in="query",
     *     description="Filter businesses by their reseller ID.",
     *     required=false,
     * example=""
     * ),
     *
     * *  @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="per_page",
     * required=true,
     * example="10"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *
     *      summary="This method is to get businesses",
     *      description="This method is to get businesses",
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

    public function getBusinesses(Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $businessesQuery = Business::with([
                "owner" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name',
                        "users.email"
                    );
                },
                "reseller" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name',
                        "users.email"
                    );
                },


            ])
                ->withCount([
                    'users as users_count' => function ($query) {
                        $query->where("users.is_active", 1)
                            ->whereDoesntHave("lastTermination", function ($query) {
                                $query->where('terminations.date_of_termination', "<", today())
                                    ->whereRaw('terminations.date_of_termination > users.joining_date');
                            })
                            ->whereColumn('users.id', '!=', 'businesses.owner_id'); // Exclude the owner
                    }
                ])
                ->when(
                    !$request->user()->hasRole('superadmin'),
                    function ($query) use ($request) {
                        $query->where(function ($query) {
                            $query
                                // ->where('id', auth()->user()->business_id)
                                // ->orWhere('created_by', auth()->user()->id)
                                ->orWhere('owner_id', auth()->user()->id)
                                ->orWhere('reseller_id', auth()->user()->id)
                            ;
                        });
                    },
                )
                ->when(!empty($request->number_of_employee), function ($query) use ($request) {
                    $range = explode(',', $request->number_of_employee);
                    if (count($range) === 2) {
                        $min = (int) $range[0];
                        $max = (int) $range[1];
                        $query->having('users_count', '>=', $min)
                            ->having('users_count', '<=', $max);
                    }
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($query) use ($term) {
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("phone", "like", "%" . $term . "%")
                            ->orWhere("email", "like", "%" . $term . "%")
                            ->orWhere("city", "like", "%" . $term . "%")
                            ->orWhere("postcode", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->name), function ($query) use ($request) {
                    $name_parts = preg_split('/\s+/', trim($request->name)); // Split by space(s), remove extra whitespace

                    $query->where(function ($q) use ($name_parts) {
                        foreach ($name_parts as $part) {
                            $q->where('name', 'like', '%' . $part . '%');
                        }
                    });
                })
                ->when(!empty($request->email), function ($query) use ($request) {
                    return $query->where('email', $request->email);
                })
                ->when(!empty($request->owner_name), function ($query) use ($request) {
                    $owner_name_parts = preg_split('/\s+/', trim($request->owner_name));

                    $query->whereHas('owner', function ($q) use ($owner_name_parts) {
                        foreach ($owner_name_parts as $part) {
                            $q->where(function ($q2) use ($part) {
                                $q2->where('users.first_name', 'like', '%' . $part . '%')
                                    ->orWhere('users.middle_name', 'like', '%' . $part . '%')
                                    ->orWhere('users.last_name', 'like', '%' . $part . '%');
                            });
                        }
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })

                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->start_lat), function ($query) use ($request) {
                    return $query->where('lat', ">=", $request->start_lat);
                })
                ->when(!empty($request->end_lat), function ($query) use ($request) {
                    return $query->where('lat', "<=", $request->end_lat);
                })
                ->when(!empty($request->start_long), function ($query) use ($request) {
                    return $query->where('long', ">=", $request->start_long);
                })
                ->when(!empty($request->end_long), function ($query) use ($request) {
                    return $query->where('long', "<=", $request->end_long);
                })

                ->when(request()->filled("is_frozen"), function ($query) use ($request) {
                    if (request()->boolean("is_frozen")) {
                        return  $query->activeStatus(0);
                    } else {
                        return $query->activeStatus(1);
                    }
                })

                ->when(request()->filled("is_active"), function ($query) use ($request) {
                    if (request()->boolean("is_active")) {
                        return  $query->activeStatus(1);
                    } else {
                        return $query->activeStatus(0);
                    }
                })



                ->when(request()->filled("is_trail_ended"), function ($query) {

                    if (request()->boolean("is_trail_ended")) {
                        return $query->whereDate('trail_end_date', "<", today());
                    } else {
                        return $query->whereDate('trail_end_date', ">=", today());
                    }
                })

                ->when(request()->filled("is_recurring_billing_business"), function ($query) {

                    if (request()->boolean("is_recurring_billing_business")) {
                        return $query->where('is_self_registered_businesses', 1);
                    } else {
                        return $query->where('is_self_registered_businesses', 0);
                    }
                })


                ->when(request()->filled("reseller_id"), function ($query) {

                    return $query->where('reseller_id', request()->input("reseller_id"));
                })



                ->when(!empty($request->address), function ($query) use ($request) {
                    $term = $request->address;
                    return $query->where(function ($query) use ($term) {
                        $query->where("country", "like", "%" . $term . "%")
                            ->orWhere("city", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->country_code), function ($query) use ($request) {
                    return $query->orWhere("country", "like", "%" . $request->country_code . "%");
                })
                ->when(!empty($request->city), function ($query) use ($request) {
                    return $query->orWhere("city", "like", "%" . $request->city . "%");
                })



                ->when(!empty($request->created_by), function ($query) use ($request) {
                    return $query->where("created_by", $request->created_by);
                });


            $businesses =  $this->retrieveData($businessesQuery, "id", "businesses");

         $businessesSummaryQuery = Business::when(
                    !$request->user()->hasRole('superadmin'),
                    function ($query) use ($request) {
                        $query->where(function ($query) {
                            $query
                                // ->where('id', auth()->user()->business_id)
                                // ->orWhere('created_by', auth()->user()->id)
                                ->orWhere('owner_id', auth()->user()->id)
                                ->orWhere('reseller_id', auth()->user()->id)
                            ;
                        });
                    },
                );

            $totalBusinesses = clone $businessesSummaryQuery;
            $totalBusinesses = $totalBusinesses->count();

            $totalActive = clone $businessesSummaryQuery;
            $totalActive = $totalActive->activeStatus(1)->count();

             $totalInactive = clone $businessesSummaryQuery;
            $totalInactive = $totalInactive->activeStatus(0)->count();

             $recurringBillingBusinesses = clone $businessesSummaryQuery;
            $recurringBillingBusinesses = $recurringBillingBusinesses->where('is_self_registered_businesses', 1)->count();

            $trailEndedBusinesses = clone $businessesSummaryQuery;
            $trailEndedBusinesses = $trailEndedBusinesses->whereDate('trail_end_date', "<", today())->count();

               $frozenBusinesses = clone $businessesSummaryQuery;
            $frozenBusinesses = $frozenBusinesses->activeStatus(0)->count();

            return response()->json([
                'summary' => [
                    'total_businesses' => $totalBusinesses,
                    'total_active' => $totalActive,
                    'total_inactive' => $totalInactive,
                    'recurring_billing_businesses' => $recurringBillingBusinesses,
                    'trail_ended_businesses' => $trailEndedBusinesses,
                    "frozen_businesses" => $frozenBusinesses
                ],
                'businesses' => $businesses
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses/{id}",
     *      operationId="getBusinessById",
     *      tags={"business_management"},
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
     *      summary="This method is to get business by id",
     *      description="This method is to get business by id",
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

    public function getBusinessById($id, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business = $this->businessOwnerCheck($id, FALSE);

            $business->load('owner', 'times', 'service_plan');





            return response()->json($business, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/businesses/{id}",
     *      operationId="getBusinessByIdClient",
     *      tags={"business_management"},
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
     *      summary="This method is to get business by id",
     *      description="This method is to get business by id",
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

    public function getBusinessByIdClient($id, Request $request)
    {

        try {



            $business  = Business::where(["id" => $id])
                ->select(
                    "id",
                    "name",
                    "email",
                    "phone",
                    "address_line_1",
                    "city",
                    "country",
                    "postcode",
                    "logo",
                    "image",
                    "background_image",
                    // "start_date",
                    "web_page",
                    // 'identifier_prefix',
                    // 'delete_read_notifications_after_30_days',
                    // 'business_start_day',
                    // "pin_code",
                    // "reseller_id"
                )
                ->first();



            if (empty($business)) {
                throw new Exception("you are not the owner of the business or the requested business does not exist.", 401);
            }




            return response()->json($business, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-subscriptions/{id}",
     *      operationId="getSubscriptionsByBusinessId",
     *      tags={"business_management"},
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
     *      *              @OA\Parameter(
     *         name="per_page",
     *         in="path",
     *         description="per_page",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get subscriptions by id",
     *      description="This method is to get subscriptions by id",
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

    public function getSubscriptionsByBusinessId($id, Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business = $this->businessOwnerCheck($id, FALSE);

            $valid_stripe = false;
            $systemSetting = SystemSetting::where("reseller_id", auth()->user()->id)
                ->first();

            if (!empty($systemSetting) && $systemSetting->self_registration_enabled) {
                $valid_stripe = true;
            }

            $business_subscriptions = [];
            $upcoming_business_subscriptions = [];
            $failed_attempts = [];

            if ($valid_stripe) {
                Stripe::setApiKey($systemSetting->STRIPE_SECRET);
                Stripe::setClientId($systemSetting->STRIPE_KEY);

                $stripeCustomerId = $business?->owner?->stripe_id ?? null;

                if (!empty($stripeCustomerId)) {
                    // Fetch all paid invoices from Stripe
                    $stripeInvoices = \Stripe\Invoice::all([
                        'customer' => $stripeCustomerId,
                        'status' => 'paid', // Only fetch paid invoices
                        'limit' => 100, // Adjust limit as needed
                    ]);

                    foreach ($stripeInvoices->data as $invoice) {
                        $subscriptionId = $invoice->subscription;
                        $subscriptionDetails = \Stripe\Subscription::retrieve($subscriptionId);

                        $business_subscriptions[] = [
                            'id' => $subscriptionId,
                            'start_date' => Carbon::createFromTimestamp($subscriptionDetails->current_period_start)->toDateTimeString(),
                            'end_date' => Carbon::createFromTimestamp($subscriptionDetails->current_period_end)->toDateTimeString(),
                            'status' => $subscriptionDetails->status,
                            'amount' => $invoice->amount_paid / 100, // Convert cents to dollars
                            'service_plan_id' => $subscriptionDetails?->metadata?->service_plan_id ?? "",
                            'service_plan_name' => $subscriptionDetails?->metadata?->service_plan_name ?? "",
                            'url' => "https://dashboard.stripe.com/subscriptions/{$subscriptionId}",
                        ];
                    }

                    // Fetch the upcoming invoice (for upcoming subscription details)
                    $upcomingInvoice = null;
                    try {
                        $upcomingInvoice = \Stripe\Invoice::upcoming([
                            'customer' => $stripeCustomerId,
                        ]);
                    } catch (\Stripe\Exception\InvalidRequestException $e) {
                        // Handle case where no upcoming invoice exists
                        $upcomingInvoice = null;
                    }

                    if (!empty($upcomingInvoice) && !empty($upcomingInvoice->lines->data)) {
                        foreach ($upcomingInvoice->lines->data as $subscriptionDetails) {
                            $upcoming_business_subscriptions[] = [
                                'service_plan_id' => $subscriptionDetails->price->id,
                                'start_date' => Carbon::createFromTimestamp($subscriptionDetails->period->start ?? $upcomingInvoice->period_start),
                                'end_date' => Carbon::createFromTimestamp($subscriptionDetails->period->end ?? $upcomingInvoice->period_end),
                                'amount' => $subscriptionDetails->amount / 100, // Convert cents to dollars
                                'service_plan_id' => $subscriptionDetails?->metadata?->service_plan_id ?? "",
                                'service_plan_name' => $subscriptionDetails?->metadata?->service_plan_name ?? "",
                                'url' => "https://dashboard.stripe.com/subscriptions/{$subscriptionDetails->subscription}",
                            ];
                        }
                    }

                    // Fetch failed payment attempts
                    $events = \Stripe\Event::all([
                        'type' => 'invoice.payment_failed', // Event type for failed payments
                        'created' => [
                            'gte' => Carbon::now()->subMonths(6)->timestamp, // Fetch events from the past 6 months
                        ],
                    ]);

                    foreach ($events->data as $event) {
                        $invoice = $event->data->object;
                        $failed_attempts[] = [
                            'invoice_id' => $invoice->id,
                            'amount_due' => $invoice->amount_due / 100, // Convert cents to dollars
                            'attempt_count' => $invoice->attempt_count,
                            'failure_reason' => $invoice->failure_reason,
                            'failed_at' => Carbon::createFromTimestamp($invoice->created),
                            'url' => "https://dashboard.stripe.com/invoices/{$invoice->id}",
                        ];
                    }
                }
            }

            $responseData = [
                "subscriptions" => $business_subscriptions,
                "upcoming_subscriptions" => $upcoming_business_subscriptions,
                "failed_attempts" => $failed_attempts
            ];

            return response()->json($responseData, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v2.0/businesses/{id}",
     *      operationId="getBusinessByIdV2",
     *      tags={"business_management"},
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
     *      summary="This method is to get business by id",
     *      description="This method is to get business by id",
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

    public function getBusinessByIdV2($id, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business  = Business::where(["id" => $id])
                ->when(
                    !$request->user()->hasRole('superadmin'),
                    function ($query) use ($request) {
                        $query->where(function ($query) {
                            $query
                                // ->where('id', auth()->user()->business_id)
                                // ->orWhere('created_by', auth()->user()->id)
                                ->orWhere('owner_id', auth()->user()->id)
                                ->orWhere('reseller_id', auth()->user()->id)
                            ;
                        });
                    },
                )
                ->select(
                    "id",
                    "name",
                    "email",
                    "phone",
                    "address_line_1",
                    "city",
                    "country",

                    "postcode",
                    "start_date",
                    "web_page",
                    'identifier_prefix',
                    'delete_read_notifications_after_30_days',
                    'business_start_day',
                    "pin_code",
                    "reseller_id"
                )
                ->first();



            if (empty($business)) {
                throw new Exception("you are not the owner of the business or the requested business does not exist.", 401);
            }

            return response()->json($business, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses-id-by-email/{email}",
     *      operationId="getBusinessIdByEmail",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="email",
     *         in="path",
     *         description="email",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get business id by email",
     *      description="This method is to get business id by email",
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

    public function getBusinessIdByEmail($email, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business  = Business::where(["email" => $email])
                ->when(
                    !$request->user()->hasRole('superadmin'),
                    function ($query) use ($request) {
                        $query->where(function ($query) {
                            $query
                                // ->where('id', auth()->user()->business_id)
                                // ->orWhere('created_by', auth()->user()->id)
                                ->orWhere('owner_id', auth()->user()->id)
                                ->orWhere('reseller_id', auth()->user()->id)
                            ;
                        });
                    },
                )
                ->select(
                    "id"
                )
                ->first();

            if (empty($business)) {
                throw new Exception("you are not the owner of the business or the requested business does not exist.", 401);
            }

            return response()->json($business, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }








    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses-pension-information/{id}",
     *      operationId="getBusinessPensionInformationById",
     *      tags={"business_management"},
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
     *      *              @OA\Parameter(
     *         name="per_page",
     *         in="path",
     *         description="per_page",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get business pension information by id",
     *      description="This method is to get business pension information by id",
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

    public function getBusinessPensionInformationById($id, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business = $this->businessOwnerCheck($id, FALSE);

            if (!is_array($business->pension_scheme_letters) || empty($business->pension_scheme_letters)) {
                $business->pension_scheme_letters = [];
            } else {

                if (!is_string($business->pension_scheme_letters[0])) {
                    $business->pension_scheme_letters = [];
                }
            }

            $businessData = [
                'pension_scheme_registered' => $business->pension_scheme_registered,
                'pension_scheme_name' => $business->pension_scheme_name,
                'pension_scheme_letters' => $business->pension_scheme_letters,
            ];


            return response()->json($businessData, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses-pension-information-history/{id}",
     *      operationId="getBusinessPensionInformationHistoryByBusinessId",
     *      tags={"business_management"},
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
     *      summary="This method is to get business pension information history by business id",
     *      description="This method is to get business pension information history by business id",
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

    public function getBusinessPensionInformationHistoryByBusinessId($id, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $this->businessOwnerCheck($id, FALSE);

            $businessPensionHistoriesQuery =  BusinessPensionHistory::where([
                "business_id" => $id
            ]);


            $businessPensionHistories = $this->retrieveData($businessPensionHistoriesQuery, "id", "business_pension_histories");





            return response()->json($businessPensionHistories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Delete(
     *      path="/v1.0/businesses-pension-information-history/{ids}",
     *      operationId="deleteBusinessPensionInformationHistoryByIds",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="6,7,8"
     *      ),
     *      summary="This method is to delete business pension history by id",
     *      description="This method is to delete business pension history by id",
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

    public function deleteBusinessPensionInformationHistoryByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = BusinessPensionHistory::whereIn('business_pension_histories.id', $idsArray)

                ->where(function ($query) {
                    $query
                        // ->where('id', auth()->user()->business_id)
                        // ->orWhere('created_by', auth()->user()->id)
                        ->orWhere('owner_id', auth()->user()->id)
                        ->orWhere('reseller_id', auth()->user()->id)
                    ;
                })


                ->select('business_pension_histories.id')
                ->get()
                ->pluck('business_pension_histories.id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);


            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the specified data do not exist.",
                    "non_existing_ids" => $nonExistingIds
                ], 404);
            }


            BusinessPensionHistory::whereIn('id', $existingIds)->delete();

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Delete(
     *      path="/v1.0/businesses/{ids}",
     *      operationId="deleteBusinessesByIds",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="6,7,8"
     *      ),
     *      summary="This method is to delete business by id",
     *      description="This method is to delete business by id",
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

    public function deleteBusinessesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = Business::whereIn('id', $idsArray)
                ->where(function ($query) {
                    $query
                        // ->where('id', auth()->user()->business_id)
                        // ->orWhere('created_by', auth()->user()->id)
                        // ->where('owner_id', auth()->user()->id)
                        ->where('reseller_id', auth()->user()->id);
                })
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);


            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the specified data do not exist.",
                    "non_existing_ids" => $nonExistingIds
                ], 404);
            }

            // Disable foreign key checks
            // DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Business::whereIn('id', $existingIds)->delete();
            User::whereIn('business_id', $existingIds)->delete();
            // Re-enable foreign key checks
            // DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }







    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses/by-business-owner/all",
     *      operationId="getAllBusinessesByBusinessOwner",
     *      tags={"business_management"},

     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to get businesses",
     *      description="This method is to get businesses",
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

    public function getAllBusinessesByBusinessOwner(Request $request)
    {

        try {

            if (!$request->user()->hasRole('business_owner')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $businessesQuery = Business::where([
                "owner_id" => $request->user()->id
            ]);



            $businesses = $businessesQuery->orderByDesc("id")->get();
            return response()->json($businesses, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
