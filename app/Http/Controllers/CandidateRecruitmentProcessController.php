<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateCreateRecruitmentProcessRequest;
use App\Http\Requests\CandidateUpdateRecruitmentProcessRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;


use App\Models\Candidate;
use App\Models\CandidateRecruitmentProcess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateRecruitmentProcessController extends Controller
{
    use ErrorUtil, BusinessUtil, ModuleUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/candidate-recruitment-processes",
     *      operationId="createCandidateRecruitmentProcess",
     *      tags={"employee.recruitment_process"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update candidate recruitment process",
     *      description="This method is to update candidate recruitment process",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="candidate_id", type="string", format="number",example="1"),

     *     * @OA\Property(property="recruitment_processes", type="string", format="array", example={
     * {
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * },
     *      * {
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * }
     *
     *
     * }),

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

    public function createCandidateRecruitmentProcess(CandidateCreateRecruitmentProcessRequest $request)
    {

        DB::beginTransaction();
        try {


            if (!$request->user()->hasPermissionTo('candidate_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();





            $request_data["recruitment_processes"] = $this->storeUploadedFiles($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
            $this->makeFilePermanent($request_data["recruitment_processes"], "attachments", []);


            $candidate = Candidate::where([
                "id" => $request_data["candidate_id"],
                "business_id" => auth()->user()->business_id
            ])
            ->first();


            if (!$candidate) {
                return response()->json([
                    "message" => "no candidate found"
                ], 404);
            }

            if (!empty($request_data["recruitment_processes"])) {

                foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {


                     $recruitment_processes =  $candidate->recruitment_processes()->create($recruitment_process_data);
                        foreach ($recruitment_process_data["tasks"] as $task_data) {
                            $recruitment_processes->tasks()->create($task_data);
                        }

                }
            }




            // $this->moveUploadedFiles(collect($request_data["recruitment_processes"])->pluck("attachments"),"recruitment_processes");


            DB::commit();
            return response($candidate, 201);
        } catch (Exception $e) {
            DB::rollBack();




            try {
                $this->moveUploadedFilesBack($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
            } catch (Exception $innerException) {

            }



            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/candidate-recruitment-processes",
     *      operationId="updateCandidateRecruitmentProcess",
     *      tags={"employee.recruitment_process"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update candidate address",
     *      description="This method is to update candidate address",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="id", type="string", format="number",example="1"),

     *     * @OA\Property(property="recruitment_processes", type="string", format="array", example={
     * {
     * "id":1,
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * },
     *      * {
     *  "id":1,
     * "recruitment_process_id":1,
     * "description":"description",
     * "attachments":{"/abcd.jpg","/efgh.jpg"}
     * }
     *
     *
     *
     * }),

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

    public function updateCandidateRecruitmentProcess(CandidateUpdateRecruitmentProcessRequest $request)
    {

        DB::beginTransaction();
        try {

            if (!$request->user()->hasPermissionTo('candidate_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();



            $request_data["recruitment_processes"] = $this->storeUploadedFiles($request_data["recruitment_processes"], "attachments", "recruitment_processes", []);
            $this->makeFilePermanent($request_data["recruitment_processes"], "attachments", []);


            $candidate = Candidate::where([
                "id" => $request_data["candidate_id"],
                "business_id"=> auth()->user()->business_id
            ])
            ->first();

            if (!$candidate) {

                return response()->json([
                    "message" => "no candidate found"
                ], 404);
            }


            if (!empty($request_data["recruitment_processes"])) {

                foreach ($request_data["recruitment_processes"] as $recruitment_process_data) {


                        $candidateRecruitmentProcess =   CandidateRecruitmentProcess::where([
                            "id" => $recruitment_process_data["id"],
                            "candidate_id"  => $candidate->id
                        ])
                            ->first();

                        $candidateRecruitmentProcess->fill($recruitment_process_data);
                        $candidateRecruitmentProcess->save();
                        $candidateRecruitmentProcess->tasks()->delete();

                        foreach($recruitment_process_data["tasks"] as $task_data) {
                            $candidateRecruitmentProcess->tasks()->create($task_data);
                         }

                }
            }


            // $this->moveUploadedFiles(collect($request_data["recruitment_processes"])->pluck("attachments"),"recruitment_processes");

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
     *      path="/v1.0/candidate-recruitment-processes/{id}",
     *      operationId="getCandidateRecruitmentProcessesById",
     *      tags={"employee.recruitment_process"},
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
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *         example="start_date"
     *      ),
     *
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=true,
     *         example="end_date"
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

    public function getCandidateRecruitmentProcessesById($id, Request $request)
    {
        //  $logPath = storage_path('logs');
        //  foreach (File::glob($logPath . '/*.log') as $file) {
        //      File::delete($file);
        //  }
        try {



            if (!$request->user()->hasPermissionTo('candidate_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $candidate_recruitment_process = CandidateRecruitmentProcess::with("recruitment_process","tasks")

            ->whereHas("candidate", function($query) {
                $query->where("candidates.business_id", auth()->user()->business_id);
         })

                ->where([
                    "id" => $id
                ])

                ->first();


            if (!$candidate_recruitment_process) {
                return response()->json([
                    "message" => "no recruitment process found"
                ], 404);
            }





            return response()->json($candidate_recruitment_process, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Delete(
     *      path="/v1.0/candidate-recruitment-processes/{ids}",
     *      operationId="deleteCandidateRecruitmentProcess",
     *      tags={"employee.recruitment_process"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update candidate address",
     *      description="This method is to update candidate address",
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

    public function deleteCandidateRecruitmentProcess($ids, Request $request)
    {

        try {




            if (!$request->user()->hasPermissionTo('candidate_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $idsArray = explode(',', $ids);

            $existingIds = CandidateRecruitmentProcess::whereIn('id', $idsArray)
                 ->whereHas("candidate", function($query) {
                        $query->where("candidates.business_id", auth()->user()->business_id);
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

            CandidateRecruitmentProcess::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
