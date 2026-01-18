<?php

namespace App\Http\Controllers;

use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserPayslipCreateRequest;
use App\Http\Requests\UserPayslipUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\Payslip;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPayslipController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;







    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-payslips",
     *      operationId="createUserPayslip",
     *      tags={"user_payslips"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user payslips",
     *      description="This method is to store user payslips",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="payroll_id", type="integer", format="int", example=null),
 *     @OA\Property(property="month", type="integer", format="int", example=1),
 *     @OA\Property(property="year", type="integer", format="int", example=2024),
 *  *     @OA\Property(property="payment_method", type="string", format="string", example="payment_method"),
 *
 *   @OA\Property(property="payment_notes", type="number", format="double", example=1000.00),
 *     @OA\Property(property="payment_amount", type="number", format="double", example=1000.00),
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-02"),
 *     @OA\Property(property="payslip_file", type="string", format="string", example="path/to/payslip.pdf"),
 *  *   @OA\Property(property="payment_record_file", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"}),
 *
 **     @OA\Property(
 *         property="gross_pay",
 *         type="number",
 *         format="double",
 *         example=1000.50,
 *         description="Gross pay amount"
 *     ),
 *     @OA\Property(
 *         property="tax",
 *         type="number",
 *         format="double",
 *         example=200.75,
 *         description="Tax amount"
 *     ),
 *     @OA\Property(
 *         property="employee_ni_deduction",
 *         type="number",
 *         format="double",
 *         example=50.25,
 *         description="Employee NI deduction amount"
 *     ),
 *     @OA\Property(
 *         property="employer_ni",
 *         type="number",
 *         format="double",
 *         example=75.30,
 *         description="Employer NI amount"
 *     )
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

    public function createUserPayslip(UserPayslipCreateRequest $request)
    {
        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_payslip_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }

                if(!empty($request_data["payment_record_file"])) {
                    $request_data["payment_record_file"] = $this->storeUploadedFiles($request_data["payment_record_file"],"","payment_record_file");
                    $this->makeFilePermanent($request_data["payment_record_file"],"");
                }
                if(!empty($request_data["payslip_file"])) {
                    $request_data["payslip_file"] = $this->storeUploadedFiles([$request_data["payslip_file"]],"","payment_record_file")[0];
                    $this->makeFilePermanent($request_data["payslip_file"],"");
                }

                $request_data["created_by"] = $request->user()->id;



                // Fetch user's bank details
                $user = User::find($request_data["user_id"]); // Assuming user_id is passed in the request

                if ($user) {
                    $request_data["bank_id"] = $user->bank_id;
                    $request_data["sort_code"] = $user->sort_code;
                    $request_data["account_number"] = $user->account_number;
                    $request_data["account_name"] = $user->account_name;
                } else {
                   throw new Exception ("User not found");
                }


                // Check if payment_method is bank_transfer and payment_notes is empty
                if ($request_data["payment_method"] === 'bank_transfer' && empty(trim($request_data["payment_notes"]))) {
                    // Build the payment notes with the user's bank details
                    $payment_notes = "Payment made to bank account number: " . $user->account_number . " Sort Code: " . $user->sort_code . " Account Name: " . $user->account_name;
                    $request_data["payment_notes"] = $payment_notes;
                }





                $user_payslip =  Payslip::create($request_data);






                // $this->moveUploadedFiles($request_data["payment_record_file"],"payment_record_file");




DB::commit();
                return response($user_payslip, 201);

        } catch (Exception $e) {
           DB::rollBack();



           try {

            if(!empty($request_data["payslip_file"])) {
                $this->moveUploadedFilesBack([$request_data["payslip_file"]],"","payment_record_file");
            }

        } catch (Exception $innerException) {



        }

        try {
            if(!empty($request_data["payment_record_file"])) {
                $this->moveUploadedFilesBack($request_data["payment_record_file"],"","payment_record_file");
            }


        } catch (Exception $innerException) {



        }






            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-payslips",
     *      operationId="updateUserPayslip",
     *      tags={"user_payslips"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user payslip  ",
     *      description="This method is to update user payslip ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="payroll_id", type="integer", format="int", example=null),
 *     @OA\Property(property="month", type="integer", format="int", example=1),
 *     @OA\Property(property="year", type="integer", format="int", example=2024),
 *     @OA\Property(property="payment_amount", type="number", format="double", example=1000.00),
 * *     @OA\Property(property="payment_notes", type="number", format="double", example=1000.00),
 *  *  *     @OA\Property(property="payment_method", type="string", format="string", example="payment_method"),
 *
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-02"),
 *     @OA\Property(property="payslip_file", type="string", format="string", example="path/to/payslip.pdf"),
 *  *   @OA\Property(property="payment_record_file", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"}),
 * *     @OA\Property(
 *         property="gross_pay",
 *         type="number",
 *         format="double",
 *         example=1000.50,
 *         description="Gross pay amount"
 *     ),
 *     @OA\Property(
 *         property="tax",
 *         type="number",
 *         format="double",
 *         example=200.75,
 *         description="Tax amount"
 *     ),
 *     @OA\Property(
 *         property="employee_ni_deduction",
 *         type="number",
 *         format="double",
 *         example=50.25,
 *         description="Employee NI deduction amount"
 *     ),
 *     @OA\Property(
 *         property="employer_ni",
 *         type="number",
 *         format="double",
 *         example=75.30,
 *         description="Employer NI amount"
 *     ),
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

    public function updateUserPayslip(UserPayslipUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_payslip_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }

                if(!empty($request_data["payment_record_file"])) {
                    $request_data["payment_record_file"] = $this->storeUploadedFiles($request_data["payment_record_file"],"","payment_record_file");
                    $this->makeFilePermanent($request_data["payment_record_file"],"");
                }

                if(!empty($request_data["payslip_file"])) {
                    $request_data["payslip_file"] = $this->storeUploadedFiles([$request_data["payslip_file"]],"","payment_record_file")[0];
                    $this->makeFilePermanent($request_data["payslip_file"],"");
                }





  // Fetch user's bank details
  $user = User::find($request_data["user_id"]); // Assuming user_id is passed in the request

  if ($user) {
      $request_data["bank_id"] = $user->bank_id;
      $request_data["sort_code"] = $user->sort_code;
      $request_data["account_number"] = $user->account_number;
      $request_data["account_name"] = $user->account_name;
  } else {
     throw new Exception ("User not found");
  }

  // Check if payment_method is bank_transfer and payment_notes is empty
  if ($request_data["payment_method"] === 'bank_transfer' && empty(trim($request_data["payment_notes"]))) {
    // Build the payment notes with the user's bank details
    $payment_notes = "Payment made to bank account number: " . $user->account_number . " Sort Code: " . $user->sort_code . " Account Name: " . $user->account_name;
    $request_data["payment_notes"] = $payment_notes;
}

                $user_payslip_query_params = [
                    "id" => $request_data["id"],
                ];
                // $user_education_history_prev = UserEducationHistory::where($user_education_history_query_params)
                //     ->first();
                // if (!$user_education_history_prev) {
                //     return response()->json([
                //         "message" => "no user education history found"
                //     ], 404);
                // }

                $user_payslip = Payslip::where($user_payslip_query_params)->first();

                if ($user_payslip) {
                    $user_payslip->fill(
                        collect($request_data)->only([
                            'user_id',
                            'month',
                            'year',
                            'payment_method',
                            'payment_notes',
                            'payment_amount',
                            'payment_date',
                            'payslip_file',
                            'payment_record_file',
                            'payroll_id',
                            'gross_pay',
                            'tax',
                            'employee_ni_deduction',
                            'employer_ni',
                            // 'created_by'
                        ])->toArray()
                    )->save();
                }


                if (!$user_payslip) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }



                // $this->moveUploadedFiles($request_data["payment_record_file"],"payment_record_file");

DB::commit();
                return response($user_payslip, 201);

        } catch (Exception $e) {
        DB::rollBack();

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-payslips",
     *      operationId="getUserPayslips",
     *      tags={"user_payslips"},
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

     *      summary="This method is to get user payslip ",
     *      description="This method is to get user  payslip ",
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

    public function getUserPayslips(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_payslip_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $payslipsQuery = Payslip::with([
                "creator" => function ($query) {
                    $query->select('users.id',   "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })

            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("payslips.payment_notes", "like", "%" . $term . "%");
                        //     ->orWhere("user_education_histories.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('payslips.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('payslips.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('payslips.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('payslips.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $payslips =  $this->retrieveData($payslipsQuery, "id", "payslips");




            return response()->json($payslips, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-payslips/{id}",
     *      operationId="getUserPayslipById",
     *      tags={"user_payslips"},
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
     *      summary="This method is to get user payslip by id",
     *      description="This method is to get user payslip by id",
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


    public function getUserPayslipById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_payslip_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_payslip =  Payslip::where([
                "id" => $id,
            ])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->first();
            if (!$user_payslip) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_payslip, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-payslips/{ids}",
     *      operationId="deleteUserPayslipsByIds",
     *      tags={"user_payslips"},
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
     *      summary="This method is to delete user payslip by id",
     *      description="This method is to delete user payslip by id",
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

    public function deleteUserPayslipsByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_payslip_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("payslips",function($query) use($idsArray) {
                $query->whereIn('payslips.id', $idsArray);
              })
              ->pluck("id");

            $this->touchUserUpdatedAt($user_ids);

            $existingIds = Payslip::whereIn('id', $idsArray)
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
            Payslip::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


}
