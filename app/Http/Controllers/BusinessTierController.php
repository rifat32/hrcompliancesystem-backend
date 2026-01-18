<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessTierCreateRequest;
use App\Http\Requests\BusinessTierUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\BusinessTier;
use App\Models\BusinessTierModule;
use App\Models\Module;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessTierController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/business-tiers",
     *      operationId="createBusinessTier",
     *      tags={"business_tiers"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business tier",
     *      description="This method is to store business tier",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;")
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

    public function createBusinessTier(BusinessTierCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('business_tier_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();




                $request_data["is_active"] = 1;
                $request_data["created_by"] = $request->user()->id;


                $business_tier =  BusinessTier::create($request_data);

                $default_modules = Module::where([

                     "is_enabled" => 1,
                ])
                ->get();

                if ($default_modules->isNotEmpty()) {
                    // Transform the collection to an array for createMany
                    $default_modules->map(function ($module) use ($business_tier, $request) {

                       BusinessTierModule::create([
                        "name" => $module->name,
                        "is_enabled" => 1,
                        "business_tier_id" => $business_tier->id,
                        "module_id" => $module->id,
                        'created_by' => $request->user()->id,
                    ]);
                    return 0;

                    })->toArray();


                }

                return response($business_tier, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/business-tiers",
     *      operationId="updateBusinessTier",
     *      tags={"business_tiers"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business tier ",
     *      description="This method is to update business tier",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;")


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

    public function updateBusinessTier(BusinessTierUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('business_tier_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                // $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();





                $business_tier  =  tap(BusinessTier::where([
                    "id" => $request_data["id"],

                ]))->update(
                    collect($request_data)->only([
                        'name',
                        // "is_active",
                        // 'created_by'
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$business_tier) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($business_tier, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-tiers",
     *      operationId="getBusinessTiers",
     *      tags={"business_tiers"},
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

     *      summary="This method is to get business tiers  ",
     *      description="This method is to get business tiers ",
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

    public function getBusinessTiers(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('business_tier_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $business_tiersQuery = BusinessTier::with("modules")
            ->when(!empty($request->search_key), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("business_tiers.name", "like", "%" . $term . "%");
                });
            })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('business_tiers.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('business_tiers.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
              ;
                 $business_tiers =  $this->retrieveData($business_tiersQuery, "id", "business_tiers");



            return response()->json($business_tiers, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-tiers/{id}",
     *      operationId="getBusinessTierById",
     *      tags={"business_tiers"},
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
     *      summary="This method is to get business tier by id",
     *      description="This method is to get business tier by id",
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


    public function getBusinessTierById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('business_tier_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business_tier =  BusinessTier::where([
                "id" => $id,
            ])
            // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('business_tiers.business_id', NULL)
            //                  ->where('business_tiers.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('business_tiers.business_id', auth()->user()->business_id);
            // })
                ->first();
            if (!$business_tier) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($business_tier, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/business-tiers/{ids}",
     *      operationId="deleteBusinessTiersByIds",
     *      tags={"business_tiers"},
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
     *      summary="This method is to delete business tier by id",
     *      description="This method is to delete business tier by id",
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

    public function deleteBusinessTiersByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_tier_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = BusinessTier::whereIn('id', $idsArray)
            // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('business_tiers.business_id', NULL)
            //                  ->where('business_tiers.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('business_tiers.business_id', auth()->user()->business_id)
            //     ->where('business_tiers.is_default', 0);
            // })
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



            BusinessTier::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
