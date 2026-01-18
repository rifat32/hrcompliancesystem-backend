<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateTerminationRequest;

use App\Http\Requests\UserUpdateTerminationRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Http\Utils\UserDetailsUtil;
use App\Models\Termination;
use App\Models\TerminationProcess;
use App\Models\User;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerminationProcessController extends Controller
{
    use ErrorUtil, BusinessUtil, ModuleUtil, UserDetailsUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/termination-processes",
     *      operationId="createTerminationProcess",
     *      tags={"employee.recruitment_process"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user termination process",
     *      description="This method is to update user termination process",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="user_id", type="string", format="number",example="1"),

     *     * @OA\Property(property="termination_processes", type="string", format="array", example={
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

    public function createTerminationProcess(UserCreateTerminationRequest $request)
    {

        DB::beginTransaction();
        try {

            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }

            $request_data["termination_processes"] = $this->storeUploadedFiles($request_data["termination_processes"], "attachments", "termination_processes", []);
            $this->makeFilePermanent($request_data["termination_processes"], "attachments", []);


            $updatableUser = User::where([
                "id" => $request_data["user_id"]
            ])->first();

            if (!$updatableUser) {

                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            if ($updatableUser->hasRole("superadmin") && $request_data["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != auth()->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }

            $termination = Termination::where([
                "id" => $request_data["termination_id"]
            ])
            ->orderByDesc("terminations.id")
            ->first();

            if (!empty($request_data["termination_processes"])) {
                foreach ($request_data["termination_processes"] as $termination_process_data) {


                     $termination_process =  $termination->termination_processes()->create($termination_process_data);
                        foreach ($termination_process_data["tasks"] as $task_data) {
                            $termination_process->tasks()->create($task_data);
                        }

                }
            }




            DB::commit();
            return response($termination_process, 201);



        } catch (Exception $e) {
            DB::rollBack();




            try {
                $this->moveUploadedFilesBack($request_data["termination_processes"], "attachments", "termination_processes", []);
            } catch (Exception $innerException) {

            }



            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/termination-processes",
     *      operationId="updateTerminationProcess",
     *      tags={"employee.recruitment_process"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update termination ",
     *      description="This method is to update termination ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *           @OA\Property(property="id", type="string", format="number",example="1"),

     *     * @OA\Property(property="termination_processes", type="string", format="array", example={
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

    public function updateTerminationProcess(UserUpdateTerminationRequest $request)
    {

        DB::beginTransaction();
        try {



            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            if (!empty($request_data["user_id"])) {
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }


            $request_data["termination_processes"] = $this->storeUploadedFiles($request_data["termination_processes"], "attachments", "termination_processes", []);
            $this->makeFilePermanent($request_data["termination_processes"], "attachments", []);


            $updatableUser = User::where([
                "id" => $request_data["user_id"]
            ])->first();

            if (!$updatableUser) {

                return response()->json([
                    "message" => "no user found"
                ], 404);
            }


            if ($updatableUser->hasRole("superadmin") && $request_data["role"] != "superadmin") {
                return response()->json([
                    "message" => "You can not change the role of super admin"
                ], 401);
            }
            if (!$request->user()->hasRole('superadmin') && $updatableUser->business_id != auth()->user()->business_id && $updatableUser->created_by != $request->user()->id) {
                return response()->json([
                    "message" => "You can not update this user"
                ], 401);
            }



            $termination = Termination::where([
                "id" => $request_data["termination_id"]
            ])
            ->orderByDesc("terminations.id")
            ->first();

            if (!empty($request_data["termination_processes"])) {
                foreach ($request_data["termination_processes"] as $termination_process_data) {

                     $termination_process =  $termination->termination_processes()
                     ->where([
                         "id" => $termination_process_data["id"]
                     ])
                     ->first();


                     $termination_process->fill($termination_process_data);
                     $termination_process->save();
                     $termination_process->tasks()->delete();

                        foreach ($termination_process_data["tasks"] as $task_data) {
                            $termination_process->tasks()->create($task_data);
                        }


                }
            }

            // $this->moveUploadedFiles(collect($request_data["termination_processes"])->pluck("attachments"),"termination_processes");

            DB::commit();
            return response($updatableUser, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/termination-processes/{id}",
     *      operationId="getTerminationProcessesById",
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

     *      summary="This method is to get termination by id",
     *      description="This method is to get termination by id",
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

    public function getTerminationProcessesById($id, Request $request)
    {
        //  $logPath = storage_path('logs');
        //  foreach (File::glob($logPath . '/*.log') as $file) {
        //      File::delete($file);
        //  }
        try {

            if (!$request->user()->hasPermissionTo('user_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $termination_process = TerminationProcess::with("termination_process","tasks")
                ->where([
                    "id" => $id
                ])
                ->whereHas("termination.user.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })

                ->first();



            if (!$termination_process) {
                return response()->json([
                    "message" => "no recruitment process found"
                ], 404);
            }



            return response()->json($termination_process, 200);


        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Delete(
     *      path="/v1.0/termination-processes/{ids}",
     *      operationId="deleteTerminationProcess",
     *      tags={"employee.recruitment_process"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update termination process address",
     *      description="This method is to update termination process address",
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

    public function deleteTerminationProcess($ids, Request $request)
    {

        try {

            if (!$request->user()->hasPermissionTo('user_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("terminations.termination_processes", function ($query) use ($idsArray) {
                $query->whereIn('termination_processes.id', $idsArray);
            })
                ->pluck("id");

            $this->touchUserUpdatedAt($user_ids);





            $existingIds = TerminationProcess::whereHas("termination.user.departments", function ($query) use ($all_manager_department_ids) {
                $query->whereIn("departments.id", $all_manager_department_ids);
            })
                ->whereHas("user", function ($query) {
                    $query->whereNotIn("users.id", [auth()->user()->id]);
                })

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

            TerminationProcess::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
