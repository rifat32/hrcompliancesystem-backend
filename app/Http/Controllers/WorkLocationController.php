<?php

namespace App\Http\Controllers;

use App\Http\Components\WorkLocationComponent;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\WorkLocationCreateRequest;
use App\Http\Requests\WorkLocationUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\AttendanceHistoryRecord;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\DisabledWorkLocation;
use App\Models\JobListing;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShiftLocation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WorkLocationController extends Controller
{
    use ErrorUtil, BusinessUtil;

    protected $workLocationComponent;


    public function __construct(WorkLocationComponent $workLocationComponent)
    {
        $this->workLocationComponent = $workLocationComponent;

    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/work-locations",
     *      operationId="createWorkLocation",
     *      tags={"work_locations"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store work location",
     *      description="This method is to store work location",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="address", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="is_location_enabled", type="string", format="string", example="test"),
     * @OA\Property(property="is_geo_location_enabled", type="string", format="string", example="test"),
     * @OA\Property(property="is_ip_enabled", type="string", format="string", example="test"),
     * @OA\Property(property="max_radius", type="string", format="string", example="test"),
     * @OA\Property(property="ip_address", type="string", format="string", example="test"),
     *
     *
     *
     * @OA\Property(property="latitude", type="string", format="string", example="test"),
     * @OA\Property(property="longitude", type="string", format="string", example="test"),
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

    public function createWorkLocation(WorkLocationCreateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('work_location_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["is_active"] = 1;
                $request_data["is_default"] = 0;
                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $work_location =  WorkLocation::create($request_data);




                return response($work_location, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/work-locations",
     *      operationId="updateWorkLocation",
     *      tags={"work_locations"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update work location ",
     *      description="This method is to update work location",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="address", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="is_location_enabled", type="string", format="string", example="test"),
     *    * @OA\Property(property="latitude", type="string", format="string", example="test"),
     * @OA\Property(property="longitude", type="string", format="string", example="test"),
     *    @OA\Property(property="is_geo_location_enabled", type="string", format="string", example="test"),
     * @OA\Property(property="is_ip_enabled", type="string", format="string", example="test"),
     * @OA\Property(property="max_radius", type="string", format="string", example="test"),
     * @OA\Property(property="ip_address", type="string", format="string", example="test"),
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

    public function updateWorkLocation(WorkLocationUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('work_location_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $work_location_query_params = [
                    "id" => $request_data["id"],
                ];

                $work_location  =  tap(WorkLocation::where($work_location_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        'address',
                        "is_location_enabled",
                        "latitude",
                        "longitude",
                        "is_geo_location_enabled",
                        "is_ip_enabled",
                        "max_radius",
                        "ip_address",

                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$work_location) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($work_location, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/work-locations/toggle-active",
     *      operationId="toggleActiveWorkLocation",
     *      tags={"work_locations"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle work location",
     *      description="This method is to toggle work location",
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

    public function toggleActiveWorkLocation(GetIdRequest $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('work_location_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $this->toggleActivation(
                WorkLocation::class,
                DisabledWorkLocation::class,
                'work_location_id',
                $request_data["id"],
                auth()->user()
            );

            return response()->json(['message' => 'WorkLocation status updated successfully'], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-locations",
     *      operationId="getWorkLocations",
     *      tags={"work_locations"},
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
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
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

     *      summary="This method is to get work locations  ",
     *      description="This method is to get work locations ",
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

    public function getWorkLocations(Request $request)
    {
        try {

            // if (!$request->user()->hasPermissionTo('work_location_view')) {
            //     return response()->json([
            //         "message" => "You can not perform this action"
            //     ], 401);
            // }

   $work_locations = $this->workLocationComponent->getWorkLocations();


            return response()->json($work_locations, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
 /**
     *
     * @OA\Get(
     *      path="/v1.0/work-locations/{id}",
     *      operationId="getWorkLocationById",
     *      tags={"work_locations"},
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
     *      summary="This method is to get work location by id",
     *      description="This method is to get work location by id",
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


    public function getWorkLocationById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('work_location_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $work_location =  WorkLocation::where([
                "work_locations.id" => $id,
                "business_id" => auth()->user()->business_id
            ])

                ->first();

                if (!$work_location) {

                    return response()->json([
                        "message" => "no data found"
                    ], 404);
                }




            return response()->json($work_location, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-locations-current-time/{id}",
     *      operationId="getCurrentTimeWorkLocationId",
     *      tags={"work_locations"},
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
     *      summary="This method is to get current time by work location id",
     *      description="This method is to get current time by work location id",
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


   public function getCurrentTimeWorkLocationId($id, Request $request)
{
    try {
        // Permission check (commented)
        // if (!$request->user()->hasPermissionTo('work_location_view')) {
        //     return response()->json([
        //         "message" => "You can not perform this action"
        //     ], 401);
        // }

        $work_location = WorkLocation::where([
                "work_locations.id" => $id,
                // "business_id" => auth()->user()->business_id
            ])->first();

        if (!$work_location) {
            return response()->json([
                "message" => "no data found"
            ], 404);
        }

        if (!$work_location->latitude || !$work_location->longitude) {
            return response()->json([
                "message" => "invalid latitude or longitude"
            ], 404);
        }

        $latitude = $work_location->latitude;
        $longitude = $work_location->longitude;
        $timestamp = time(); // current UNIX timestamp

        // Directly using the API key
        $api_key = "AIzaSyDDYMTvjZTukYsyOyvw7Kp_a0vrHd6vQAo";
        $url = "https://maps.googleapis.com/maps/api/timezone/json?location={$latitude},{$longitude}&timestamp={$timestamp}&key={$api_key}";

        $response = Http::get($url);

        if (!$response->successful()) {
            return response()->json([
                "message" => "Failed to fetch timezone data"
            ], 500);
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            return response()->json([
                "message" => "Timezone API returned error: " . $data['status']
            ], 500);
        }

        $dst_offset = $data['dstOffset'];
        $raw_offset = $data['rawOffset'];
        $total_offset = $dst_offset + $raw_offset;
        $local_time = gmdate("Y-m-d H:i:s", $timestamp + $total_offset);

        return response()->json([
            "timezone" => $data['timeZoneId'],
            "local_time" => $local_time,
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            "message" => "Internal Server Error",
            "error" => $e->getMessage()
        ], 500);
    }
}



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/work-locations/{ids}",
     *      operationId="deleteWorkLocationsByIds",
     *      tags={"work_locations"},
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
     *      summary="This method is to delete work location by id",
     *      description="This method is to delete work location by id",
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

    public function deleteWorkLocationsByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('work_location_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = WorkLocation::whereIn('id', $idsArray)
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('work_locations.business_id', NULL)
                            ->where('work_locations.is_default', 1);
                    } else {
                        return $query->where('work_locations.business_id', NULL)
                            ->where('work_locations.is_default', 0)
                            ->where('work_locations.created_by', $request->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('work_locations.business_id', auth()->user()->business_id)
                        ->where('work_locations.is_default', 0);
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

            $conflictingUsersExists = User::whereHas("work_locations", function($query) use($existingIds) {
                $query->whereIn("work_location_id", $existingIds);
            })->exists();

            if ($conflictingUsersExists) {
                $conflicts[] = "Employees";
            }

            $conflictingDepartmentExists = Department::whereIn("work_location_id", $existingIds)->exists();

            if ($conflictingDepartmentExists) {
                $conflicts[] = "Departments";
            }

            $conflictingAttendanceExists = AttendanceRecord::whereIn("work_location_id", $existingIds)->exists();

            if ($conflictingAttendanceExists) {
                $conflicts[] = "Attendance records";
            }

            $conflictingAttendanceHistoryExists = AttendanceHistoryRecord::whereIn("work_location_id", $existingIds)->exists();

            if ($conflictingAttendanceHistoryExists) {
                $conflicts[] = "Attendance Log";
            }

            $conflictingJobListingExists = JobListing::whereIn("work_location_id", $existingIds)->exists();

            if ($conflictingJobListingExists) {
                $conflicts[] = "Job listings";
            }

            $conflictingWorkShiftExists = WorkShiftLocation::whereIn("work_location_id", $existingIds)->exists();

            if ($conflictingWorkShiftExists) {
                $conflicts[] = "Work shifts";
            }

            if (!empty($conflicts)) {
                $conflictList = implode(', ', $conflicts);
                return response()->json([
                    "message" => "Cannot delete this data as there are records associated with it in the following areas: $conflictList. First update the records, then try to delete.",
                ], 409);
            }

            // Proceed with the deletion process if no conflicts are found.



            WorkLocation::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}

