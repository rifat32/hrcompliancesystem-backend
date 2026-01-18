<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\JobPlatformCreateRequest;
use App\Http\Requests\JobPlatformUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Business;
use App\Models\CandidateJobPlatform;
use App\Models\DisabledJobPlatform;
use App\Models\JobListing;
use App\Models\JobListingJobPlatforms;
use App\Models\JobPlatform;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobPlatformController extends Controller
{
    use ErrorUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/job-platforms",
     *      operationId="createJobPlatform",
     *      tags={"job_platforms"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store job platform",
     *      description="This method is to store job platform",
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

    public function createJobPlatform(JobPlatformCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('job_platform_create')) {
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




                // $request_data["business_id"] = NULL;
                // $request_data["is_active"] = 1;
                // $request_data["is_default"] = 1;

                // $request_data["created_by"] = $request->user()->id;

                // if ($request->user()->hasRole('superadmin')) {
                //     $request_data["business_id"] = NULL;
                // $request_data["is_active"] = 1;
                // $request_data["is_default"] = 1;
                // $request_data["created_by"] = $request->user()->id;
                // }
                // else {
                //     $request_data["business_id"] = auth()->user()->business_id;
                //     $request_data["is_active"] = 1;
                //     $request_data["is_default"] = 0;
                //     // $request_data["created_by"] = $request->user()->id;
                // }




                $job_platform =  JobPlatform::create($request_data);




                return response($job_platform, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/job-platforms",
     *      operationId="updateJobPlatform",
     *      tags={"job_platforms"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update job platform ",
     *      description="This method is to update job platform",
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

    public function updateJobPlatform(JobPlatformUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('job_platform_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                // $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();



                $job_platform_query_params = [
                    "id" => $request_data["id"],
                    // "business_id" => $business_id
                ];


                // if ($request->user()->hasRole('superadmin')) {
                //     if(!($job_platform_prev->business_id == NULL && $job_platform_prev->is_default == 1)) {
                //         return response()->json([
                //             "message" => "You do not have permission to update this job platform due to role restrictions."
                //         ], 403);
                //     }

                // }
                // else {
                //     if(!($job_platform_prev->business_id == auth()->user()->business_id)) {
                //         return response()->json([
                //             "message" => "You do not have permission to update this job platform due to role restrictions."
                //         ], 403);
                //     }
                // }
                $job_platform  =  tap(JobPlatform::where($job_platform_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                         // "is_default",
                        // "is_active",
                        // "business_id",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$job_platform) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($job_platform, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

  /**
     *
     * @OA\Put(
     *      path="/v1.0/job-platforms/toggle-active",
     *      operationId="toggleActiveJobPlatform",
     *      tags={"job_platforms"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle job platform",
     *      description="This method is to toggle job platform",
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

     public function toggleActiveJobPlatform(GetIdRequest $request)
     {

         try {

             if (!$request->user()->hasPermissionTo('job_platform_activate')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $this->toggleActivation(
                JobPlatform::class,
                DisabledJobPlatform::class,
                'job_platform_id',
                $request_data["id"],
                auth()->user()
            );


             return response()->json(['message' => 'Job platform status updated successfully'], 200);
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }
        /**
     *
     * @OA\Get(
     *      path="/v1.0/job-platforms",
     *      operationId="getJobPlatforms",
     *      tags={"job_platforms"},
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
     *   *    *      * *  @OA\Parameter(
     * name="job_listing_id",
     * in="query",
     * description="job_listing_id",
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

     *      summary="This method is to get job platforms  ",
     *      description="This method is to get job platforms ",
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

    public function getJobPlatforms(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('job_platform_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if(auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }

            $job_platformsQuery = JobPlatform::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('job_platforms');
                }, function ($query) use ($request, $created_by) {
                    $query->forNonSuperAdmin('job_platforms',  $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query)  {
                $query->forBusiness('job_platforms');
            })


                ->when(!empty($request->search_key), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("job_platforms.name", "like", "%" . $term . "%")
                        ->orWhere("job_platforms.description", "like", "%" . $term . "%");
                });
            })


                ->when(!empty($request->job_listing_id), function ($query) use ($request) {
                    return $query->whereHas('job_listings',function($query) use($request) {
                        $query->where('job_listings.id',$request->job_listing_id);
                    });
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('job_platforms.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('job_platforms.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $job_platforms =  $this->retrieveData($job_platformsQuery, "id", "job_platforms");





            return response()->json($job_platforms, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/job-platforms",
     *      operationId="getJobPlatformsClient",
     *      tags={"job_platforms"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

        *              @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="business_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *   *    *      * *  @OA\Parameter(
     * name="job_listing_id",
     * in="query",
     * description="job_listing_id",
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

     *      summary="This method is to get job platforms  ",
     *      description="This method is to get job platforms ",
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

    public function getJobPlatformsClient(Request $request)
    {
        try {


            $business_id =  $request->business_id;
            if(!$business_id) {
               $error = [ "message" => "The given data was invalid.",
               "errors" => ["business_id"=>["The business id field is required."]]
               ];
                   throw new Exception(json_encode($error),422);
            }

            $business = Business::where([
                "id" => $business_id
            ])
            ->first();

           $created_by = $business->created_by;

            $job_platformsQuery = JobPlatform::

           where(function($query) use($request, $created_by, $business_id) {


                        $query->where('job_platforms.business_id', NULL)
                        ->where('job_platforms.is_default', 1)
                        ->where('job_platforms.is_active', 1)
                        ->whereDoesntHave("disabled", function($q) use($created_by) {
                            $q->whereIn("disabled_job_platforms.created_by", [$created_by]);
                        })

                        ->whereDoesntHave("disabled", function($q) use($business_id)  {
                                    $q->whereIn("disabled_job_platforms.business_id",[$business_id]);
                        })


                        ->orWhere(function ($query) use($request, $created_by, $business_id){
                            $query->where('job_platforms.business_id', NULL)
                                ->where('job_platforms.is_default', 0)
                                ->where('job_platforms.created_by', $created_by)
                                ->where('job_platforms.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($business_id){
                                            $q->whereIn("disabled_job_platforms.business_id",[$business_id]);
                                });
                        })
                        ->orWhere(function ($query) use($request, $business_id) {
                            $query->where('job_platforms.business_id', $business_id)
                                ->where('job_platforms.is_default', 0)
                                ->where('job_platforms.is_active', request()->boolean("is_active"));
                        });
                    })





                ->when(!empty($request->search_key), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("job_platforms.name", "like", "%" . $term . "%")
                        ->orWhere("job_platforms.description", "like", "%" . $term . "%");
                });
            })


                ->when(!empty($request->job_listing_id), function ($query) use ($request) {
                    return $query->whereHas('job_listings',function($query) use($request) {
                        $query->where('job_listings.id',$request->job_listing_id);
                    });
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('job_platforms.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('job_platforms.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
               ;

                     $job_platforms =  $this->retrieveData($job_platformsQuery, "id", "job_platforms");



            return response()->json($job_platforms, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/job-platforms/{id}",
     *      operationId="getJobPlatformById",
     *      tags={"job_platforms"},
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
     *      summary="This method is to get job platform by id",
     *      description="This method is to get job platform by id",
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


    public function getJobPlatformById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('job_platform_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $job_platform =  JobPlatform::where([
                "id" => $id,
            ])
            // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('job_platforms.business_id', NULL)
            //                  ->where('job_platforms.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('job_platforms.business_id', auth()->user()->business_id);
            // })
                ->first();
            if (!$job_platform) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($job_platform->business_id != NULL)) {

                        return response()->json([
                            "message" => "You do not have permission to update this job platform due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($job_platform->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this job platform due to role restrictions."
                        ], 403);
                    } else if ($job_platform->is_default == 0 && $job_platform->created_by != auth()->user()->id) {

                            return response()->json([
                                "message" => "You do not have permission to update this job platform due to role restrictions."
                            ], 403);

                    }
                }
            } else {
                if ($job_platform->business_id != NULL) {
                    if (($job_platform->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this job platform due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($job_platform->is_default == 0) {
                        if ($job_platform->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this job platform due to role restrictions."
                            ], 403);
                        }
                    }
                }
            }

            return response()->json($job_platform, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/job-platforms/{ids}",
     *      operationId="deleteJobPlatformsByIds",
     *      tags={"job_platforms"},
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
     *      summary="This method is to delete job platform by id",
     *      description="This method is to delete job platform by id",
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

    public function deleteJobPlatformsByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('job_platform_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = JobPlatform::whereIn('id', $idsArray)
            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                if ($request->user()->hasRole("superadmin")) {
                    return $query->where('job_platforms.business_id', NULL)
                        ->where('job_platforms.is_default', 1);
                } else {
                    return $query->where('job_platforms.business_id', NULL)
                        ->where('job_platforms.is_default', 0)
                        ->where('job_platforms.created_by', $request->user()->id);
                }
            })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where('job_platforms.business_id', auth()->user()->business_id)
                    ->where('job_platforms.is_default', 0);
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

            // Check for conflicts in Job Listings on Job Platforms
            $job_post_exists = JobListingJobPlatforms::whereIn("job_platform_id", $existingIds)->exists();
            if ($job_post_exists) {
                $conflicts[] = "Job Listings on Job Platforms";
            }

            // Check for conflicts in Candidates on Job Platforms
            $job_candidate_exists = CandidateJobPlatform::whereIn("job_platform_id", $existingIds)->exists();
            if ($job_candidate_exists) {
                $conflicts[] = "Candidates on Job Platforms";
            }

            // Return combined error message if conflicts exist
            if (!empty($conflicts)) {
                $conflictList = implode(', ', $conflicts);
                return response()->json([
                    "message" => "Cannot delete this data as there are records associated with it in the following areas: $conflictList. Please update these records before attempting to delete.",
                ], 409);
            }

            // Proceed with the deletion process if no conflicts are found.


            JobPlatform::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
