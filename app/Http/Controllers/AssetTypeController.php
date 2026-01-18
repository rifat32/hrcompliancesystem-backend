<?php




namespace App\Http\Controllers;

use App\Http\Requests\AssetTypeCreateRequest;
use App\Http\Requests\AssetTypeUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\AssetType;
use App\Models\DisabledAssetType;
use App\Models\User;
use App\Models\UserAsset;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetTypeController extends Controller
{

    use ErrorUtil, BusinessUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/asset-types",
     *      operationId="createAssetType",
     *      tags={"asset_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store asset types",
     *      description="This method is to store asset types",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="name", type="string", format="string", example="name"),
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

    public function createAssetType(AssetTypeCreateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('asset_type_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["is_active"] = 1;

                $request_data["is_default"] = 0;




                $request_data["created_by"] = auth()->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if (auth()->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $asset_type =  AssetType::create($request_data);




                return response($asset_type, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/asset-types",
     *      operationId="updateAssetType",
     *      tags={"asset_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update asset types ",
     *      description="This method is to update asset types ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="name", type="string", format="string", example="name"),
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

    public function updateAssetType(AssetTypeUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('asset_type_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $asset_type_query_params = [
                    "id" => $request_data["id"],
                ];

                $asset_type = AssetType::where($asset_type_query_params)->first();

                if ($asset_type) {
                    $asset_type->fill(collect($request_data)->only([

                        "name",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $asset_type->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($asset_type, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/asset-types/toggle-active",
     *      operationId="toggleActiveAssetType",
     *      tags={"asset_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle asset types",
     *      description="This method is to toggle asset types",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

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

    public function toggleActiveAssetType(GetIdRequest $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('asset_type_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $this->toggleActivation(
                AssetType::class,
                DisabledAssetType::class,
                'asset_type_id',
                $request_data["id"],
                auth()->user()
            );


            return response()->json(['message' => 'asset type status updated successfully'], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/asset-types",
     *      operationId="getAssetTypes",
     *      tags={"asset_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *         @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="name",
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
  




     *      summary="This method is to get asset types  ",
     *      description="This method is to get asset types ",
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

    public function getAssetTypes(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('asset_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }


            $asset_typesQuery = AssetType::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('asset_types');
                }, function ($query) use ($request, $created_by) {
                    $query->forNonSuperAdmin('asset_types', $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query)  {
                $query->forBusiness('asset_types');
            })



                ->when(!empty($request->id), function ($query) use ($request) {
                    return $query->where('asset_types.id', $request->id);
                })

                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where('asset_types.id', $request->string);
                })





                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query

                            ->orWhere("asset_types.name", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('asset_types.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('asset_types.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

              $asset_types =  $this->retrieveData($asset_typesQuery, "id", "asset_types");


            return response()->json($asset_types, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/asset-types/{ids}",
     *      operationId="deleteAssetTypesByIds",
     *      tags={"asset_types"},
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
     *      summary="This method is to delete asset type by id",
     *      description="This method is to delete asset type by id",
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

    public function deleteAssetTypesByIds(Request $request, $ids)
    {

        try {
            if (!$request->user()->hasPermissionTo('asset_type_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = AssetType::whereIn('id', $idsArray)
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('asset_types.business_id', NULL)
                            ->where('asset_types.is_default', 1);
                    } else {
                        return $query->where('asset_types.business_id', NULL)
                            ->where('asset_types.is_default', 0)
                            ->where('asset_types.created_by', $request->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('asset_types.business_id', auth()->user()->business_id)
                        ->where('asset_types.is_default', 0);
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


            $conflicts = [];

            // Check for conflicts in Users
            // $conflictingUserAssetExists = UserAsset::whereIn("type", $existingIds)->exists();
            // if ($conflictingUserAssetExists) {
            //     $conflicts[] = "Asset";
            // }

            // Check for additional conflicts if there are other models related to designation_id
            // Add other checks here as needed

            // Return combined error message if conflicts exist
            if (!empty($conflicts)) {
                $conflictList = implode(', ', $conflicts);
                return response()->json([
                    "message" => "Cannot delete this data as there are records associated with it in the following areas: $conflictList. Please update these records before attempting to delete.",
                ], 409);
            }

            // Proceed with the deletion process if no conflicts are found.



            AssetType::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
