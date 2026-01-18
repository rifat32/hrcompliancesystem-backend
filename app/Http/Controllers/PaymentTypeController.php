<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentTypeCreateRequest;
use App\Http\Requests\PaymentTypeUpdateRequest;
use App\Http\Utils\ErrorUtil;

use App\Models\PaymentType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTypeController extends Controller
{
    use ErrorUtil;

    /**
     *
     * @OA\Post(
     *      path="/v1.0/payment-types",
     *      operationId="createPaymentType",
     *      tags={"payment_type_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store payment type",
     *      description="This method is to store payment type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","is_active"},
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="description", type="string", format="string",example="car"),
     *    @OA\Property(property="is_active", type="boolean", format="boolean",example="true"),
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

    public function createPaymentType(PaymentTypeCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('payment_type_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $insertableData = $request->validated();

                $payment_type =  PaymentType::create($insertableData);


                return response($payment_type, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/payment-types",
     *      operationId="updatePaymentType",
     *      tags={"payment_type_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update payment type",
     *      description="This method is to update payment type",
     *
       *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description","is_active"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="description", type="string", format="string",example="car"),
     *    @OA\Property(property="is_active", type="boolean", format="boolean",example="true"),
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

    public function updatePaymentType(PaymentTypeUpdateRequest $request)
    {
        try {

            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('payment_type_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $updatableData = $request->validated();



                $payment_type  =  tap(PaymentType::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
        "name",
        "description",
        "is_active",
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$payment_type) {

                        return response()->json([
                            "message" => "no payment type found"
                            ],404);

                }

                return response($payment_type, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/payment-types/{perPage}",
     *      operationId="getPaymentTypes",
     *      tags={"payment_type_management"},
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
     *      summary="This method is to get payment types ",
     *      description="This method is to get payment types",
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

    public function getPaymentTypes($perPage, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('payment_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $paymentTypeQuery = new PaymentType();

            if (!empty($request->search_key)) {
                $paymentTypeQuery = $paymentTypeQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $paymentTypeQuery = $paymentTypeQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $paymentTypeQuery = $paymentTypeQuery->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
            }
            $payment_types = $paymentTypeQuery->orderByDesc("id")->paginate($perPage);
            return response()->json($payment_types, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/payment-types/{id}",
     *      operationId="deletePaymentTypeById",
     *      tags={"payment_type_management"},
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
     *      summary="This method is to delete fuel station by id",
     *      description="This method is to delete fuel station by id",
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

    public function deletePaymentTypeById($id, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('payment_type_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            PaymentType::where([
                "id" => $id
            ])
            ->delete();

            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
