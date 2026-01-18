<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingPaymentDateCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\SettingPaymentDate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingPaymentDateController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-payment-dates",
     *      operationId="createSettingPaymentDate",
     *      tags={"settings.setting_payment_date"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting payment date",
     *      description="This method is to store setting payment date",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *     @OA\Property(property="payment_type", type="string", format="string"),
 *     @OA\Property(property="day_of_week", type="integer", format="string"),
 *     @OA\Property(property="day_of_month", type="integer", format="string"),
 *  *     @OA\Property(property="custom_date", type="string", format="string"),
 *
 *     @OA\Property(property="custom_frequency_interval", type="integer", format="string"),
 *     @OA\Property(property="custom_frequency_unit", type="string", format="string"),
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

    public function createSettingPaymentDate(SettingPaymentDateCreateRequest $request)
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

                $setting_payment_date =     SettingPaymentDate::updateOrCreate($check_data, $request_data);





                return response($setting_payment_date, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-payment-dates",
     *      operationId="getSettingPaymentDate",
     *      tags={"settings.setting_payment_date"},
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

     *      summary="This method is to get setting payment date  ",
     *      description="This method is to get setting payment date ",
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

    public function getSettingPaymentDate(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $setting_payment_datesQuery = SettingPaymentDate::
                when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if (auth()->user()->hasRole('superadmin')) {
                        return $query->where('setting_payment_dates.business_id', NULL)
                            ->where('setting_payment_dates.is_default', 1)
                            ->when(request()->filled("is_active"), function ($query) use ($request) {
                                return $query->where('setting_payment_dates.is_active', request()->boolean("is_active"));
                            });
                    } else {
                        return   $query->where('setting_payment_dates.business_id', NULL)
                            ->where('setting_payment_dates.is_default', 0)
                            ->where('setting_payment_dates.created_by', auth()->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return   $query->where('setting_payment_dates.business_id', auth()->user()->business_id)
                        ->where('setting_payment_dates.is_default', 0);
                })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        //  $query->where("setting_payment_dates.name", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_payment_dates.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_payment_dates.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $setting_payment_dates =  $this->retrieveData($setting_payment_datesQuery, "id", "setting_payment_dates");




            return response()->json($setting_payment_dates, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


}
