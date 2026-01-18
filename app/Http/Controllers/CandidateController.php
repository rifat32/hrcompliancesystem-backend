<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateCreateRequest;
use App\Http\Requests\CandidateCreateRequestClient;
use App\Http\Requests\CandidateUpdateRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\EmailLogUtil;
use App\Http\Utils\ErrorUtil;

use App\Mail\JobApplicationReceivedMail;
use App\Models\Business;
use App\Models\Candidate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CandidateController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, EmailLogUtil;




    /**
     *
     * @OA\Post(
     *      path="/v1.0/candidates",
     *      operationId="createCandidate",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store candidate",
     *      description="This method is to store candidate",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", format="string", example="123-456-7890"),
     *     @OA\Property(property="experience_years", type="integer", format="int", example=3),
     *     @OA\Property(property="education_level", type="string", format="string", example="Bachelor's Degree"),
     *     @OA\Property(property="job_platforms", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="cover_letter", type="string", format="string", example="Cover letter content..."),
     *     @OA\Property(property="application_date", type="string", format="date", example="2023-11-01"),

     *     @OA\Property(property="feedback", type="string", format="string", example="Positive feedback..."),
     *     @OA\Property(property="status", type="string", format="string", example="review"),
     *     @OA\Property(property="job_listing_id", type="integer", format="int", example=1),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
     *
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

    public function createCandidate(CandidateCreateRequest $request)
    {

        DB::beginTransaction();
        try {



            if (!$request->user()->hasPermissionTo('candidate_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            if (!empty($request_data["recruitment_processes"])) {
                $request_data["recruitment_processes"] = $this->storeUploadedFiles($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
                $this->makeFilePermanent($request_data["recruitment_processes"], "attachments", []);
            }

            $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"], "", "candidate_files");
            $this->makeFilePermanent($request_data["attachments"], "");

            $request_data["business_id"] = auth()->user()->business_id;
            $request_data["is_active"] = true;
            $request_data["created_by"] = $request->user()->id;
            $candidate =  Candidate::create($request_data);



            if (!empty($request_data["recruitment_processes"])) {

                foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {

                    if (!empty($recruitment_process_data["description"])) {
                        $recruitment_processes =  $candidate->recruitment_processes()->create($recruitment_process_data);
                        foreach ($recruitment_process_data["tasks"] as $task_data) {
                            $recruitment_processes->tasks()->create($task_data);
                        }
                    }
                }
            }



            $candidate->job_platforms()->sync($request_data['job_platforms']);



            if (env("SEND_EMAIL") == true) {
                $this->checkEmailSender(auth()->user()->id, 0);
                try {
                    Mail::to($candidate->email)->send(new JobApplicationReceivedMail($candidate));
                } catch (\Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }


                $this->storeEmailSender(auth()->user()->id, 0);
            }

            // $this->moveUploadedFiles($request_data["attachments"],"candidate_files");


            DB::commit();

            return response($candidate, 201);
        } catch (Exception $e) {
            DB::rollBack();
            try {
                $this->moveUploadedFilesBack($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
            } catch (Exception $innerException) {
            }

            try {
                $this->moveUploadedFilesBack($request_data["attachments"], "", "candidate_files");
            } catch (Exception $innerException) {

            }


            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/candidates",
     *      operationId="createCandidateClient",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store candidate",
     *      description="This method is to store candidate",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", format="string", example="123-456-7890"),
     *     @OA\Property(property="experience_years", type="integer", format="int", example=3),
     *     @OA\Property(property="education_level", type="string", format="string", example="Bachelor's Degree"),
     *     @OA\Property(property="job_platforms", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="cover_letter", type="string", format="string", example="Cover letter content..."),
     *     @OA\Property(property="application_date", type="string", format="date", example="2023-11-01"),

     *     @OA\Property(property="feedback", type="string", format="string", example="Positive feedback..."),
     *     @OA\Property(property="status", type="string", format="string", example="review"),
     *     @OA\Property(property="job_listing_id", type="integer", format="int", example=1),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
     *
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

    public function createCandidateClient(CandidateCreateRequestClient $request)
    {
        DB::beginTransaction();

        try {



            $request_data = $request->validated();

            $business = Business::where([
                "id" => $request_data["business_id"]
            ])
                ->first();

            if (empty($business)) {
                throw new Exception("No Business found", 401);
            }


            $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"], "", "candidate_files",
            NULL,
            $request_data["business_id"]

        );

            $this->makeFilePermanent($request_data["attachments"], "");

            if (!empty($request_data["recruitment_processes"])) {
                $request_data["recruitment_processes"] = $this->storeUploadedFiles($request_data["recruitment_processes"], "attachments", "recruitment_processes", [],
                $request_data["business_id"]

            );
                $this->makeFilePermanent($request_data["recruitment_processes"], "attachments", []);
            }


            $request_data["business_id"];
            $request_data["is_active"] = true;


            $candidate =  Candidate::create($request_data);

            $candidate->job_platforms()->sync($request_data['job_platforms']);


            if (!empty($request_data["recruitment_processes"])) {

                foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {

                    if (!empty($recruitment_process_data["description"])) {
                     $recruitment_processes =  $candidate->recruitment_processes()->create($recruitment_process_data);
                        foreach ($recruitment_process_data["tasks"] as $task_data) {
                            $recruitment_processes->tasks()->create($task_data);
                        }
                    }
                }
            }





            //  $this->moveUploadedFiles($request_data["attachments"],"candidate_files");

            if (env("SEND_EMAIL") == true) {
                // $this->checkEmailSender(auth()->user()->id, 0);

                try {
                    Mail::to($candidate->email)->send(new JobApplicationReceivedMail($candidate));
                } catch (\Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }


                // $this->storeEmailSender(auth()->user()->id, 0);
            }


            DB::commit();
            return response($candidate, 201);
        } catch (Exception $e) {
            DB::rollBack();

            try {
                $this->moveUploadedFilesBack($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
            } catch (Exception $innerException) {

            }


            try {
                $this->moveUploadedFilesBack($request_data["attachments"], "", "candidate_files");
            } catch (Exception $innerException) {

            }

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/candidates",
     *      operationId="updateCandidate",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update candidate ",
     *      description="This method is to update candidate",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="name", type="string", format="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *     @OA\Property(property="phone", type="string", format="string", example="123-456-7890"),
     *     @OA\Property(property="experience_years", type="integer", format="int", example=3),
     *     @OA\Property(property="education_level", type="string", format="string", example="Bachelor's Degree"),
     *  *     @OA\Property(property="job_platform", type="string", format="string", example="facebook"),
     *
     *     @OA\Property(property="cover_letter", type="string", format="string", example="Cover letter content..."),
     *     @OA\Property(property="application_date", type="string", format="date", example="2023-11-01"),

     *     @OA\Property(property="feedback", type="string", format="string", example="Positive feedback..."),
     *     @OA\Property(property="status", type="string", format="string", example="review"),
     *     @OA\Property(property="job_listing_id", type="integer", format="int", example=1),
     *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})

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

    public function updateCandidate(CandidateUpdateRequest $request)
    {

        DB::beginTransaction();
        try {




            if (!$request->user()->hasPermissionTo('candidate_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            $candidate_query_params = [
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ];

            $candidate  =  Candidate::where($candidate_query_params)->first();


            $this->moveUploadedFilesBack($candidate->attachments, "", "candidate_files");


            $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"], "", "candidate_files");
            $this->makeFilePermanent($request_data["attachments"], "");


            if (!empty($request_data["recruitment_processes"])) {
                $request_data["recruitment_processes"] = $this->storeUploadedFiles($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
                $this->makeFilePermanent($request_data["recruitment_processes"], "attachments", []);
            }


            if ($candidate) {
                $candidate->fill(collect($request_data)->only([
                    'name',
                    'email',
                    'phone',
                    'experience_years',
                    'education_level',

                    'cover_letter',
                    'application_date',

                    'feedback',
                    'status',
                    'job_listing_id',
                    'attachments',

                    // "is_active",
                    // "business_id",
                    // "created_by"

                ])->toArray());
                $candidate->save();
            } else {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }






            $candidate->job_platforms()->sync($request_data['job_platforms']);

            if (!empty($request_data["recruitment_processes"])) {
                $candidate->recruitment_processes()->delete();
                foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {
                    if (!empty($recruitment_process_data["description"])) {
                     $recruitment_processes =  $candidate->recruitment_processes()->create($recruitment_process_data);
                        foreach ($recruitment_process_data["tasks"] as $task_data) {
                            $recruitment_processes->tasks()->create($task_data);
                        }
                    }
                }
            }


            DB::commit();
            return response($candidate, 201);
        } catch (Exception $e) {







            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/candidates",
     *      operationId="getCandidates",
     *      tags={"candidates"},
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
     *    *      * *  @OA\Parameter(
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
     *
     *     * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *  *     * *  @OA\Parameter(
     * name="application_date",
     * in="query",
     * description="application_date",
     * required=true,
     * example="application_date"
     * ),
     *


     *
     *  @OA\Parameter(
     * name="job_platform_id",
     * in="query",
     * description="job_platform",
     * required=true,
     * example="job_platform_id"
     * ),
     *

     *     *  *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="status"
     * ),
     *
     * * @OA\Parameter(
*     name="recruitment_process_ids",
*     in="query",
*     description="Filter by Recruitment Process IDs (comma-separated). Example: 1,2,3",
*     required=false,
*     example="1,2,3"
* ),
* @OA\Parameter(
*     name="recruitment_task_owner_ids",
*     in="query",
*     description="Filter by recruitment Task Owner IDs (comma-separated). Example: 5,8",
*     required=false,
*     example="5,8"
* ),
* @OA\Parameter(
*     name="recruitment_task_statuses",
*     in="query",
*     description="Filter by recruitment Task Statuses (comma-separated). Example: pending,completed",
*     required=false,
*     example="pending,completed"
* ),
* @OA\Parameter(
*     name="recruitment_task_assigned_date",
*     in="query",
*     description="Filter by Assigned Date Range (comma-separated start and end date). Format: YYYY-MM-DD,YYYY-MM-DD",
*     required=false,
*     example="2024-01-01,2024-12-31"
* ),
* @OA\Parameter(
*     name="recruitment_task_due_date",
*     in="query",
*     description="Filter by Due Date Range (comma-separated start and end date). Format: YYYY-MM-DD,YYYY-MM-DD",
*     required=false,
*     example="2024-01-01,2024-12-31"
* ),
* @OA\Parameter(
*     name="recruitment_task_completion_date",
*     in="query",
*     description="Filter by Completion Date Range (comma-separated start and end date). Format: YYYY-MM-DD,YYYY-MM-DD",
*     required=false,
*     example="2024-01-01,2024-12-31"
* ),
     *
     *
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get candidates  ",
     *      description="This method is to get candidates ",
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

    public function getCandidates(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('candidate_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $candidatesQuery = Candidate::with("job_listing", "job_platforms")

                ->where(
                    [
                        "candidates.business_id" => $business_id
                    ]
                )

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("candidates.name", "like", "%" . $term . "%")
                            ->orWhere("candidates.email", "like", "%" . $term . "%")
                            ->orWhere("candidates.phone", "like", "%" . $term . "%")
                            ->orWhere("candidates.cover_letter", "like", "%" . $term . "%")
                            ->orWhere("candidates.feedback", "like", "%" . $term . "%")
                        ;
                    });
                })
                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->name;
                        $query->where("candidates.name", "like", "%" . $term . "%");
                        //     ->orWhere("candidates.description", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->status;
                        $query->where("candidates.status", "like", "%" . $term . "%");
                        //     ->orWhere("candidates.description", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('candidates.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('candidates.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })


                ->when(!empty($request->job_listing_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->job_listing_id);
                    return $query->whereIn('candidates.job_listing_id', $idsArray);
                })

                ->when(!empty($request->job_platform_id), function ($query) use ($request) {
                    $job_platform_ids = explode(',', $request->job_platform_id);
                    $query->whereHas("job_platforms", function ($query) use ($job_platform_ids) {
                        $query->whereIn("job_platforms.id", $job_platform_ids);
                    });
                })

                ->when(request()->filled("application_date"), function ($query) {
                    // Split the date range string into start and end dates
                    $dates = explode(',', request()->input("application_date"));
                    $startDate = !empty(trim($dates[0])) ? Carbon::parse(trim($dates[0])) : "";
                    $endDate = !empty(trim($dates[1])) ? Carbon::parse(trim($dates[1])) : "";

                    // Apply conditions based on which dates are available
                    if ($startDate) {
                        $query->whereDate('candidates.application_date', '>=', $startDate);
                    }

                    if ($endDate) {
                        $query->whereDate('candidates.application_date', '<=', $endDate);
                    }
                    return $query;
                })

                ->when((

                    request()->filled("recruitment_process_ids")
                    ||
                    request()->filled("recruitment_task_owner_ids")
                    ||
                    request()->filled("recruitment_task_statuses")
                    ||
                    request()->filled("recruitment_task_assigned_date")
                    ||
                    request()->filled("recruitment_task_due_date")

                    ||
                    request()->filled("recruitment_task_completion_date")
                ),
                function ($query) {
                    $query->whereHas("recruitment_processes", function ($query) {
                        $query->when(request()->filled("recruitment_process_ids"), function ($query) {
                            $idsArray = explode(',', request()->recruitment_process_ids);
                            $query->whereIn("candidate_recruitment_processes.recruitment_process_id", $idsArray);
                        })

                            ->when((
                                request()->filled("recruitment_task_owner_ids")
                                ||
                                request()->filled("recruitment_task_statuses")
                                ||
                                request()->filled("recruitment_task_assigned_date")
                                ||
                                request()->filled("recruitment_task_due_date")

                                ||
                                request()->filled("recruitment_task_completion_date")

                            ), function ($query) {
                                $query->whereHas("tasks", function ($query) {

                                    $query
                                        ->when(request()->filled("recruitment_task_owner_ids"), function ($query) {
                                            $recruitment_task_owner_ids = explode(',', request()->recruitment_task_owner_ids);
                                            $query->whereIn("recruitment_tasks.task_owner_id", $recruitment_task_owner_ids);
                                        })
                                        ->when(request()->filled("recruitment_task_statuses"), function ($query) {
                                            $recruitment_task_statuses = explode(',', request()->recruitment_task_statuses);
                                            $query->whereIn("recruitment_tasks.task_status", $recruitment_task_statuses);
                                        })
                                        ->when(request()->filled("recruitment_task_assigned_date"), function ($query) {
                                            // Split the date range string into start and end dates
                                            $dates = explode(',', request()->input("recruitment_task_assigned_date"));
                                            $startDate = !empty(trim($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(trim($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('recruitment_tasks.assigned_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('recruitment_tasks.assigned_date', '<=', $endDate);
                                                });
                                        })

                                        ->when(request()->filled("recruitment_task_due_date"), function ($query) {

                                            $dates = explode(',', request()->input("recruitment_task_due_date"));
                                            $startDate = !empty(trim($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(trim($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('recruitment_tasks.due_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('recruitment_tasks.due_date', '<=', $endDate);
                                                });
                                        })
                                        ->when(request()->filled("recruitment_task_completion_date"), function ($query) {
                                            $dates = explode(',', request()->input("recruitment_task_completion_date"));
                                            $startDate = !empty(trim($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(trim($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('recruitment_tasks.completion_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('recruitment_tasks.completion_date', '<=', $endDate);
                                                });
                                        });
                                });
                            })
                        ;
                    });
                }
            );

               $candidates =  $this->retrieveData($candidatesQuery, "id", "candidates");



            return response()->json($candidates, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/candidates/{id}",
     *      operationId="getCandidateById",
     *      tags={"candidates"},
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
     *      summary="This method is to get candidate by id",
     *      description="This method is to get candidate by id",
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


    public function getCandidateById($id, Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('candidate_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $business_id =  auth()->user()->business_id;
            $candidate =  Candidate::with(
                "job_listing", "job_platforms",
                "recruitment_processes",
                "recruitment_processes.recruitment_process",
                "recruitment_processes.tasks",

                )
                ->where([
                    "id" => $id,
                    "business_id" => $business_id
                ])
                ->first();
            if (!$candidate) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($candidate, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/candidates/{ids}",
     *      operationId="deleteCandidatesByIds",
     *      tags={"candidates"},
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
     *      summary="This method is to delete candidate by id",
     *      description="This method is to delete candidate by id",
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

    public function deleteCandidatesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('candidate_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Candidate::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
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
            Candidate::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
