<?php

namespace App\Http\Controllers;

use App\Http\Requests\HrmLocalizedFieldCreateRequest;
use App\Http\Requests\HrmLocalizedFieldUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Models\HrmLocalizedField;
use App\Models\DisabledHrmLocalizedField;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrmLocalizedFieldController extends Controller
{

    use ErrorUtil, BusinessUtil;


    /**
     *
     * @OA\Post(
     * path="/v1.0/hrm-localized-fields",
     * operationId="createHrmLocalizedField",
     * tags={"hrm_localized_fields"},
     * security={
     * {"bearerAuth": {}}
     * },
     * summary="This method is to store hrm localized fields",
     * description="This method is to store hrm localized fields",
     *
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="country_code", type="string", format="string", example="country_code"),
     * @OA\Property(property="fields_json", type="string", format="string", example="fields_json"),

     *
     *
     *
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocesseble Content",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden",
     * @OA\JsonContent()
     * ),
     * * @OA\Response(
     * response=400,
     * description="Bad Request",
     * *@OA\JsonContent()
     * ),
     * @OA\Response(
     * response=404,
     * description="not found",
     * *@OA\JsonContent()
     * )
     * )
     * )
     */

    public function createHrmLocalizedField(HrmLocalizedFieldCreateRequest $request)
    {

        DB::beginTransaction();
        try {

            if (!auth()->user()->hasPermissionTo('hrm_localized_field_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            $request_data["created_by"] = auth()->user()->id;
            $request_data["business_id"] = auth()->user()->business_id;

            if (empty(auth()->user()->business_id)) {
                $request_data["business_id"] = NULL;
                if (auth()->user()->hasRole('superadmin')) {
                    $request_data["is_default"] = 1;
                }
            }

            $hrm_localized_field = HrmLocalizedField::create($request_data);

            DB::commit();
            return response($hrm_localized_field, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Put(
     * path="/v1.0/hrm-localized-fields",
     * operationId="updateHrmLocalizedField",
     * tags={"hrm_localized_fields"},
     * security={
     * {"bearerAuth": {}}
     * },
     * summary="This method is to update hrm localized fields ",
     * description="This method is to update hrm localized fields ",
     *
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="country_code", type="string", format="string", example="country_code"),
     * @OA\Property(property="fields_json", type="string", format="string", example="fields_json"),
     *
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocesseble Content",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden",
     * @OA\JsonContent()
     * ),
     * * @OA\Response(
     * response=400,
     * description="Bad Request",
     * *@OA\JsonContent()
     * ),
     * @OA\Response(
     * response=404,
     * description="not found",
     * *@OA\JsonContent()
     * )
     * )
     * )
     */

    public function updateHrmLocalizedField(HrmLocalizedFieldUpdateRequest $request)
    {
        DB::beginTransaction();
        try {


            if (!auth()->user()->hasPermissionTo('hrm_localized_field_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $hrm_localized_field_query_params = [
                "id" => $request_data["id"],
            ];

            $hrm_localized_field =
                HrmLocalizedField::where($hrm_localized_field_query_params)->first();

            if ($hrm_localized_field) {
                $hrm_localized_field->fill($request_data);
                $hrm_localized_field->save();
            } else {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }

            DB::commit();
            return response($hrm_localized_field, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }




    public function query_filters($query)
    {



        return $query->where('hrm_localized_fields.business_id', auth()->user()->business_id)

            ->when(request()->filled("country_code"), function ($query) {
                return $query->where(
                    'hrm_localized_fields.country_code',
                    request()->input("country_code")
                );
            })
            ->when(request()->filled("fields_json"), function ($query) {
                return $query->where(
                    'hrm_localized_fields.fields_json',
                    request()->input("fields_json")
                );
            })

            ->when(request()->filled("reseller_ids"), function ($query) {
                return $query->whereHas('user', function ($q) {
                    $reseller_ids = explode(',', request()->input("reseller_ids"));
                    $q->whereIn('users.id', $reseller_ids);
                });
            })

            ->when(request()->filled("search_key"), function ($query) {
                return $query->where(function ($query) {
                    $term = request()->input("search_key");
                    $query

                        ->orWhere("hrm_localized_fields.country_code", "like", "%" . $term . "%")
                        ->where("hrm_localized_fields.fields_json", "like", "%" . $term . "%")
                    ;
                });
            })

            ->when(request()->filled("start_date"), function ($query) {
                return $query->whereDate('hrm_localized_fields.created_at', ">=", request()->input("start_date"));
            })
            ->when(request()->filled("end_date"), function ($query) {
                return $query->whereDate('hrm_localized_fields.created_at', "<=", request()->input("end_date"));
            });
    }



    /**
     *
     * @OA\Get(
     * path="/v1.0/hrm-localized-fields",
     * operationId="getHrmLocalizedFields",
     * tags={"hrm_localized_fields"},
     * security={
     * {"bearerAuth": {}}
     * },

     * @OA\Parameter(
     * name="country_code",
     * in="query",
     * description="country_code",
     * required=false,
     * example=""
     * ),
     * @OA\Parameter(
     * name="fields_json",
     * in="query",
     * description="fields_json",
     * required=false,
     * example=""
     * ),
     * @OA\Parameter(
     * name="reseller_ids",
     * in="query",
     * description="reseller_id",
     * required=false,
     * example=""
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="per_page",
     * required=false,
     * example=""
     * ),

     * @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=false,
     * example=""
     * ),
     * @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=false,
     * example=""
     * ),
     * * @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=false,
     * example=""
     * ),
     * * @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=false,
     * example=""
     * ),
     * * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=false,
     * example="ASC"
     * ),
     * * @OA\Parameter(
     * name="id",
     * in="query",
     * description="id",
     * required=false,
     * example=""
     * ),




     * summary="This method is to get hrm localized fields ",
     * description="This method is to get hrm localized fields ",
     *

     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocesseble Content",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden",
     * @OA\JsonContent()
     * ),
     * * @OA\Response(
     * response=400,
     * description="Bad Request",
     * *@OA\JsonContent()
     * ),
     * @OA\Response(
     * response=404,
     * description="not found",
     * *@OA\JsonContent()
     * )
     * )
     * )
     */

    public function getHrmLocalizedFields(Request $request)
    {
        try {

            if (!auth()->user()->hasPermissionTo('hrm_localized_field_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $query = HrmLocalizedField::query();
            $query = $this->query_filters($query);
            $hrm_localized_fields = $this->retrieveData($query, "id", "hrm_localized_fields");

            return response()->json($hrm_localized_fields, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Delete(
     * path="/v1.0/hrm-localized-fields/{ids}",
     * operationId="deleteHrmLocalizedFieldsByIds",
     * tags={"hrm_localized_fields"},
     * security={
     * {"bearerAuth": {}}
     * },
     * @OA\Parameter(
     * name="ids",
     * in="path",
     * description="ids",
     * required=true,
     * example="1,2,3"
     * ),
     * summary="This method is to delete hrm localized field by id",
     * description="This method is to delete hrm localized field by id",
     *

     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocesseble Content",
     * @OA\JsonContent(),
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden",
     * @OA\JsonContent()
     * ),
     * * @OA\Response(
     * response=400,
     * description="Bad Request",
     * *@OA\JsonContent()
     * ),
     * @OA\Response(
     * response=404,
     * description="not found",
     * *@OA\JsonContent()
     * )
     * )
     * )
     */

    public function deleteHrmLocalizedFieldsByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('hrm_localized_field_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = HrmLocalizedField::whereIn('id', $idsArray)
                ->where('hrm_localized_fields.business_id', auth()->user()->business_id)

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

            HrmLocalizedField::destroy($existingIds);

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
