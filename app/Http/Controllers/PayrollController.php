<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollCreateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PayrunUtil;

use App\Models\Department;
use App\Models\Payroll;
use App\Models\Payrun;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    use ErrorUtil, BusinessUtil, PayrunUtil, BasicUtil;


























    /**
     *
     * @OA\Get(
     *      path="/v1.0/payrolls/report",
     *      operationId="getPayrollsReport",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      * *  @OA\Parameter(
     * name="payrun_id",
     * in="query",
     * description="payrun_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="user_ids",
     * in="query",
     * description="user_ids",
     * required=true,
     * example="1,2,3"
     * ),
     *
     *
     *
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
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *   * *  @OA\Parameter(
     * name="months",
     * in="query",
     * description="months",
     * required=true,
     * example="12"
     * ),

     *      summary="This method is to get payrolls  report ",
     *      description="This method is to get payrolls report ",
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


    public function getPayrollsReport(Request $request)
    {
        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('payrun_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $numberOfMonths = !empty($request->months) ? $request->months : 12;
            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $departments = Department::whereIn("id", $all_manager_department_ids)->get();

            // Initialize an array to store the report data
            $reportData = [];

            // Get the current date
            $currentDate = Carbon::now();

            // Loop through the specified number of months
            for ($i = 0; $i < $numberOfMonths; $i++) {
                // Calculate the start and end dates for each month
                $startDate = $currentDate->copy()->startOfMonth()->subMonths($i);
                $endDate = $currentDate->copy()->endOfMonth()->subMonths($i);

                $data = [];
                foreach ($departments as $department) {
                    $departmentData = [];

                    $departmentData["department"] = $department;
                    $departmentData["dates"] = [$startDate, $endDate];

                    $payrolls = Payroll::whereBetween('payrolls.end_date', [$startDate, $endDate])
                        ->whereHas("user.departments", function ($query) use ($department) {
                            $query->whereIn("departments.id", [$department->id]);
                        })

                        ->get();

                    $total_salary = $payrolls->sum(function ($payroll) {
                        return $payroll->regular_hours_salary + $payroll->overtime_hours_salary; // Add more columns if needed
                    });

                    $departmentData["total_salary"] = $total_salary;
                    // Push the department data into the $data array
                    $data[] = $departmentData;
                }



                // Store the data for the current month in the report array
                $reportData[$startDate->format('F Y')] = $data;
            }

            DB::commit();

            return response()->json($reportData, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/pending-payroll-users",
     *      operationId="getPendingPayrollUsers",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      * *  @OA\Parameter(
     * name="payrun_id",
     * in="query",
     * description="payrun_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="user_ids",
     * in="query",
     * description="user_ids",
     * required=true,
     * example="1,2,3"
     * ),
     *    * @OA\Parameter(
     * name="departments",
     * in="query",
     * description="departments",
     * required=true,
     * example="1,2,3"
     * ),
     *
     *
     *
     *
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
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get payrolls  ",
     *      description="This method is to get payrolls ",
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


    public function getPendingPayrollUsers(Request $request)
    {
        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('payrun_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            if (empty($request->start_date)) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => ["start_date" => ["The start_date field is required."]]
                ];
                throw new Exception(json_encode($error), 422);
            }
            if (empty($request->end_date)) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => ["end_date" => ["The end_date field is required."]]
                ];
                throw new Exception(json_encode($error), 422);
            }


            $employeesQuery = User::where([
                "business_id" => auth()->user()->business_id,
                "is_active" => 1
            ])
                ->whereDoesntHave("payrolls", function ($q) use ($request) {
                    $q->where("payrolls.start_date", ">=", $request->start_date)
                        ->where("payrolls.end_date", "<=", $request->end_date);
                })
                ->whereDate("users.joining_date", "<=", request()->end_date)
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", request()->start_date)
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    })


                ->whereNotIn("id", [auth()->user()->id])

                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->when(!empty($request->departments), function ($query) use ($request) {
                    $department_ids = explode(',', $request->departments);
                    $query->whereHas("departments", function ($query) use ($department_ids) {
                        $query->whereIn("departments.id", $department_ids);
                    });
                })

                ->when(!empty($request->user_ids), function ($query) use ($request, $all_manager_department_ids) {
                    $user_ids = explode(',', $request->user_ids);
                    $query->whereIn("users.id", $user_ids);
                })
                ->select("users.id",  "users.title", "users.first_Name", "users.middle_Name", "users.last_Name");

                 $employees =  $this->retrieveData($employeesQuery, "first_Name", "users");

            $processed_employees = collect($this->estimate_payrun_data($employees, $request->start_date, $request->end_date));

            // // Filter out users with both regular_hours_salary and overtime_hours_salary as 0
            // $filtered_employees = $processed_employees->items()->filter(function ($employee) {
            //     return $employee['payroll']['regular_hours_salary'] != 0 || $employee['payroll']['overtime_hours_salary'] != 0;
            // });

            // Convert the filtered collection back to an array if needed
            // $filtered_employees_array = $filtered_employees->values()->all();


            DB::commit();

            return response()->json($processed_employees, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/payroll-list",
     *      operationId="getPayrollList",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      * *  @OA\Parameter(
     * name="payrun_id",
     * in="query",
     * description="payrun_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="user_ids",
     * in="query",
     * description="user_ids",
     * required=true,
     * example="1,2,3"
     * ),
     *
     *
     *
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
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get payrolls  ",
     *      description="This method is to get payrolls ",
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


    public function getPayrollList(Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('payrun_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();
            //  if(!$request->payrun_id) {
            //     $error = [ "message" => "The given data was invalid.",
            //     "errors" => ["payrun_id"=>["The payrun_id field is required."]]
            //     ];
            //         throw new Exception(json_encode($error),422);
            //  }







            if (!empty($request->payrun_id)) {
                $payrun = Payrun::where([

                    "business_id" => auth()->user()->business_id
                ])


                    ->where(function ($query) use ($all_manager_department_ids) {
                        $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })
                            ->orWhereHas("users.departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            });
                    })
                    ->first();

                if (!$payrun) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => ["payrun_id" => ["The payrun_id field is invalid."]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }
            }



            $payrollsQuery = Payroll::with("user", "payrun")
                ->when(!empty($request->payrun_id), function ($query) use ($request) {
                    $query->where([
                        "payrun_id" => $request->payrun_id
                    ]);
                })

                ->when(!empty($request->user_ids), function ($query) use ($request) {
                    $user_ids = explode(',', $request->user_ids);
                    $query->orWhereHas("user", function ($query) use ($user_ids) {
                        $query->whereIn("users..id", $user_ids);
                    });
                })
                ->where(function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                });

                 $payrolls =  $this->retrieveData($payrollsQuery, "id", "payrolls");


            return response()->json($payrolls, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
