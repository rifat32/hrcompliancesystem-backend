<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\SettingPayrunCreateRequest;
use App\Http\Requests\SettingPayslipCreateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\SettingPayrun;
use App\Models\SettingPayslip;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingPayrollController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-payrun",
     *      operationId="createSettingPayrun",
     *      tags={"settings.setting_payroll.payrun"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting payrun",
     *      description="This method is to store setting payrun",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="payrun_period", type="string", example="monthly"),
     *     @OA\Property(property="consider_type", type="string", example="hour"),
     *     @OA\Property(property="consider_overtime", type="boolean", example=true),
     * *     @OA\Property(property="restricted_users", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="restricted_departments", type="string", format="array", example={1,2,3})
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

    public function createSettingPayrun(SettingPayrunCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();
                $request_data["is_active"] = 1;





                if (empty(auth()->user()->business_id)) {

                    $request_data["business_id"] = NULL;
                    $request_data["is_default"] = 0;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                    $check_data =     [
                        "business_id" => $request_data["business_id"],
                        "is_default" => $request_data["is_default"]
                    ];
                    if (!$request->user()->hasRole('superadmin')) {
                        $check_data["created_by"] =    auth()->user()->id;
                    }
                } else {
                    $request_data["business_id"] = auth()->user()->business_id;
                    $request_data["is_default"] = 0;
                    $check_data =     [
                        "business_id" => $request_data["business_id"],
                        "is_default" => $request_data["is_default"]
                    ];
                }

                $setting_payrun =     SettingPayrun::updateOrCreate($check_data, $request_data);

                $setting_payrun->restricted_users()->sync($request_data['restricted_users']);
                $setting_payrun->restricted_departments()->sync($request_data['restricted_departments']);




                return response($setting_payrun, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-payrun",
     *      operationId="getSettingPayrun",
     *      tags={"settings.setting_payroll.payrun"},
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

     *      summary="This method is to get setting payrun  ",
     *      description="This method is to get setting payrun ",
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

    public function getSettingPayrun(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $setting_payrunsQuery = SettingPayrun::with("restricted_users", "restricted_departments")
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if (auth()->user()->hasRole('superadmin')) {
                        return $query->where('setting_payruns.business_id', NULL)
                            ->where('setting_payruns.is_default', 1)
                            ->when(request()->filled("is_active"), function ($query) use ($request) {
                                return $query->where('setting_payruns.is_active', request()->boolean("is_active"));
                            });
                    } else {
                        return   $query->where('setting_payruns.business_id', NULL)
                            ->where('setting_payruns.is_default', 0)
                            ->where('setting_payruns.created_by', auth()->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return   $query->where('setting_payruns.business_id', auth()->user()->business_id)
                        ->where('setting_payruns.is_default', 0);
                })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        //  $query->where("setting_payruns.name", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_payruns.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_payruns.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $setting_payruns =  $this->retrieveData($setting_payrunsQuery, "id", "setting_payruns");




            return response()->json($setting_payruns, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-payslip",
     *      operationId="createSettingPayslip",
     *      tags={"settings.setting_payroll.payslip"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting payslip",
     *      description="This method is to store setting payslip",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="logo", type="string", format="string", example="test.jpg"),
     *     @OA\Property(property="title", type="string", format="string", example="test"),
     *     @OA\Property(property="address", type="string", format="string", example="dhaka")
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

    public function createSettingPayslip(SettingPayslipCreateRequest $request)
    {
        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_active"] = 1;

                if(!empty($request_data["logo"])) {
                    $request_data["logo"]= $this->storeUploadedFiles([$request_data["logo"]],"","payslip_logo")[0];
                    $this->makeFilePermanent($request_data["logo"],"");
                  }


                if (empty(auth()->user()->business_id)) {

                    $request_data["business_id"] = NULL;
                    $request_data["is_default"] = 1;

                    $setting_payslip  =  SettingPayslip::updateOrCreate(
                        [
                            "business_id" => $request_data["business_id"],
                            "is_default" => $request_data["is_default"]
                        ],

                        $request_data



                    );
                } else {
                    $request_data["business_id"] = auth()->user()->business_id;
                    $request_data["is_default"] = 0;
                    $setting_payslip =     SettingPayslip::updateOrCreate(
                        [
                            "business_id" => $request_data["business_id"],
                            "is_default" => $request_data["is_default"]
                        ],
                        $request_data
                    );
                }



                DB::commit();
                return response($setting_payslip, 201);

        } catch (Exception $e) {
            DB::rollBack();
            try {
                if(!empty($request_data["image"])) {

                    $this->moveUploadedFilesBack([$request_data["image"]],"","payslip_logo");
                       }
            } catch (Exception $innerException) {

            }

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-payslip",
     *      operationId="getSettingPayslip",
     *      tags={"settings.setting_payroll.payslip"},
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

     *      summary="This method is to get setting payslip  ",
     *      description="This method is to get setting payslip ",
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

    public function getSettingPayslip(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $setting_payslipsQuery = SettingPayslip::when(empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_payslips.business_id', NULL)
                    ->where('setting_payslips.is_default', 1);
            })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('setting_payslips.business_id', auth()->user()->business_id)
                        ->where('setting_payslips.is_default', 0);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        //  $query->where("setting_payslips.name", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_payslips.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_payslips.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $setting_payslips =  $this->retrieveData($setting_payslipsQuery, "id", "setting_payslips");




            return response()->json($setting_payslips, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
