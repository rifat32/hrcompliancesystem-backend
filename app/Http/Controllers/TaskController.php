<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskCreateRequest;
use App\Http\Requests\TaskPositionUpdateRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\Comment;
use App\Models\EmployeeProjectHistory;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\UserProject;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    use ErrorUtil, BusinessUtil, ModuleUtil, BasicUtil;

    // Function to handle the creation of UserProject and EmployeeProjectHistory
private function createUserProjectAndHistory($userId, $projectId, $startDate, $project) {
    $assigned_project = UserProject::firstOrCreate([
        "user_id" => $userId,
        "project_id" => $projectId,
    ]);

    // Create EmployeeProjectHistory if the project is assigned
    $employee_project_history_data = $project->toArray();
    $employee_project_history_data["user_id"] = $userId;
    $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
    $employee_project_history_data["from_date"] = $startDate;
    $employee_project_history_data["to_date"] = NULL;

    // If the project is newly assigned, create the history entry
    if ($assigned_project->wasRecentlyCreated) {
        EmployeeProjectHistory::create($employee_project_history_data);
    }
}
    /**
     *
     * @OA\Post(
     *      path="/v1.0/tasks",
     *      operationId="createTask",
     *      tags={"task"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store task listing",
     *      description="This method is to store task listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *     @OA\Property(property="name", type="string", format="string", example="Task X"),
     *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Task X's objectives and scope."),
     *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *     @OA\Property(property="due_date", type="string", format="date", example="2023-06-30"),
     *     @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
     *     @OA\Property(property="status", type="string", format="string", example="in_progress"),
     *     @OA\Property(property="project_id", type="integer", format="integer", example="1"),
     *     @OA\Property(property="parent_task_id", type="integer", format="integer", example="2"),
     *  *     @OA\Property(property="task_category_id", type="integer", format="integer", example="2"),
     *  *  *     @OA\Property(property="assigned_to", type="integer", format="integer", example="2"),
     *
     *  *     @OA\Property(property="assignees", type="string", format="array", example={1,2,3}),
     *
     *      @OA\Property(property="cover", type="string", format="string", example="in_progress"),
     *      @OA\Property(property="labels", type="string", format="array", example={1,2,3}),
     *      @OA\Property(property="assets", type="string", format="array", example={1,2,3}),
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

    public function createTask(TaskCreateRequest $request)
    {
        DB::beginTransaction();
        try {


            $this->isModuleEnabled("task_management");



            if (!$request->user()->hasPermissionTo('task_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();


            $request_data["business_id"] = auth()->user()->business_id;
            $request_data["is_active"] = true;
            $request_data["created_by"] = $request->user()->id;
            $request_data["assigned_by"] = auth()->user()->id;


            $request_data["unique_identifier"] = $this->generateUniqueId(Project::class, $request_data["project_id"], Task::class);

            $project = Project::where([
                "id" => $request_data["project_id"]
            ])
                ->first();

            if (empty($project)) {
                throw new Exception("Project not found for the given task.");
            }


            $project_start_date = Carbon::parse($project->start_date);
            $project_end_date = !empty($project->end_date) ? Carbon::parse($project->end_date) : null;

            $task_start_date = Carbon::parse($request_data["start_date"]);
            $task_end_date = !empty($request_data["end_date"]) ? Carbon::parse($request_data["end_date"]) : null;

            // Validate task start date
            if ($task_start_date->lessThan($project_start_date)) {
                throw new Exception("Task start date cannot be earlier than project start date.",409);
            }

            if ($project_end_date && $task_start_date->greaterThan($project_end_date)) {
                throw new Exception("Task start date cannot be later than project end date.",409);
            }

            // Validate task end date, if provided
            if ($task_end_date) {
                if ($task_end_date->lessThan($project_start_date)) {
                    throw new Exception("Task end date cannot be earlier than project start date.",409);
                }

                if ($project_end_date && $task_end_date->greaterThan($project_end_date)) {
                    throw new Exception("Task end date cannot be later than project end date.",409);
                }

                if ($task_end_date->lessThan($task_start_date)) {
                    throw new Exception("Task end date cannot be earlier than task start date.",409);
                }
            }






            $task =  Task::create($request_data);

            $task->order_no = Task::where(
                collect($request_data)->only(
                    "business_id",
                )->toArray()
            )->count();

            $task->save();

            $task->assignees()->sync($request_data['assignees']);
            $task->labels()->sync($request_data['labels']);



            $this->createUserProjectAndHistory($task->assigned_to, $task->project_id, $task->start_date, $project);

            // Handle additional assignees
            foreach ($request_data['assignees'] as $assignee_id) {
                $this->createUserProjectAndHistory($assignee_id, $task->project_id, $task->start_date, $project);
            }




            // Create the task entry
            Comment::create([
                'description' => ("Added this card to " . $task->task_category->name),
                // 'attachments' => $attachments,
                'type' => 'history',

                'task_id' => $task->id, // Assuming you have a $taskId variable
                'created_by' => auth()->user()->id, // Assuming you have a $userId variable
            ]);


            $notification_description = "A task is pending for your action.";
            $notification_link = "http://example.com/tasks/1"; // Example link
            Notification::create([
                "entity_id" => $task->id, // Assuming $task is your task object
                "entity_ids" => [$task->id],
                "entity_name" => "task",
                'notification_title' => "Task Pending Notification",
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => $task->created_by, // Assuming you have a variable for sender ID
                "receiver_id" => $task->assigned_to, // Assuming $manager_id is the manager's ID
                "business_id" => auth()->user()->business_id, // Assuming $business_id is the business ID
                "is_system_generated" => 1,
                "status" => "unread",
            ]);


            DB::commit();
            return response($task, 201);
        } catch (Exception $e) {

            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/tasks",
     *      operationId="updateTask",
     *      tags={"task"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update task listing ",
     *      description="This method is to update task listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *     @OA\Property(property="name", type="string", format="string", example="Task X"),
     *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Task X's objectives and scope."),
     *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *     @OA\Property(property="due_date", type="string", format="date", example="2023-06-30"),
     *     @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
     *     @OA\Property(property="status", type="string", format="string", example="in_progress"),
     *     @OA\Property(property="project_id", type="integer", format="integer", example="1"),
     *     @OA\Property(property="parent_task_id", type="integer", format="integer", example="2"),
     * *  *     @OA\Property(property="task_category_id", type="integer", format="integer", example="2"),
     *
     *  *  *  *     @OA\Property(property="assigned_to", type="integer", format="integer", example="2"),
     *
     *  *     @OA\Property(property="assignees", type="string", format="array", example={1,2,3}),
     *
     * *      @OA\Property(property="cover", type="string", format="string", example="in_progress"),
     *      @OA\Property(property="labels", type="string", format="array", example={1,2,3}),
     *      @OA\Property(property="assets", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="order_no", type="integer", format="integer", example="2"),
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

    public function updateTask(TaskUpdateRequest $request)
    {

        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");

            if (!$request->user()->hasPermissionTo('task_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $request_data = $request->validated();


            $task_query_params = [
                "id" => $request_data["id"],
                "business_id" => $business_id
            ];
            // $task_prev = Task::where($task_query_params)
            //     ->first();
            // if (!$task_prev) {
            //     return response()->json([
            //         "message" => "no task listing found"
            //     ], 404);
            // }

            $project = Project::where([
                "id" => $request_data["project_id"]
            ])
                ->first();

            if (empty($project)) {
                throw new Exception("Project not found for the given task.");
            }


            $project_start_date = Carbon::parse($project->start_date);
            $project_end_date = !empty($project->end_date) ? Carbon::parse($project->end_date) : null;

            $task_start_date = Carbon::parse($request_data["start_date"]);
            $task_end_date = !empty($request_data["end_date"]) ? Carbon::parse($request_data["end_date"]) : null;

            // Validate task start date
            if ($task_start_date->lessThan($project_start_date)) {
                throw new Exception("Task start date cannot be earlier than project start date.",409);
            }

            if ($project_end_date && $task_start_date->greaterThan($project_end_date)) {
                throw new Exception("Task start date cannot be later than project end date.",409);
            }

            // Validate task end date, if provided
            if ($task_end_date) {
                if ($task_end_date->lessThan($project_start_date)) {
                    throw new Exception("Task end date cannot be earlier than project start date.",409);
                }

                if ($project_end_date && $task_end_date->greaterThan($project_end_date)) {
                    throw new Exception("Task end date cannot be later than project end date.",409);
                }

                if ($task_end_date->lessThan($task_start_date)) {
                    throw new Exception("Task end date cannot be earlier than task start date.",409);
                }
            }

            $task  =  tap(Task::where($task_query_params))->update(
                collect($request_data)->only([
                    'name',
                    'description',
                    'assets',
                    'cover',
                    'start_date',
                    'due_date',
                    'end_date',
                    'status',
                    'project_id',
                    'parent_task_id',
                    "task_category_id",

                    "order_no",
                    // 'assigned_by',
                    'assigned_to',

                    // "is_active",
                    // "business_id",
                    // "created_by"

                ])->toArray()
            )
                // ->with("somthing")

                ->first();
            if (!$task) {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }



            $task->labels()->sync($request_data['labels']);
            $task->assignees()->sync($request_data['assignees']);




            $this->createUserProjectAndHistory($task->assigned_to, $task->project_id, $task->start_date, $project);

            // Handle additional assignees
            foreach ($request_data['assignees'] as $assignee_id) {
                $this->createUserProjectAndHistory($assignee_id, $task->project_id, $task->start_date, $project);
            }

            $notification_description = "A task has been updated.";
            $notification_link = "http://example.com/tasks/{$task->id}"; // Dynamic link based on task ID

            Notification::create([
                "entity_id" => $task->id,
                "entity_ids" => [$task->id],
                "entity_name" => "task",
                'notification_title' => "Task Update Notification",
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => auth()->user()->id, // Assuming you have a variable for the updater's ID
                "receiver_id" => $task->assigned_to,
                "business_id" => auth()->user()->business_id,
                "is_system_generated" => 1,
                "status" => "unread",
            ]);


            DB::commit();
            return response($task, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/tasks/position",
     *      operationId="updateTaskPosition",
     *      tags={"task"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update task listing ",
     *      description="This method is to update task listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *     @OA\Property(property="project_id", type="integer", format="integer", example="1"),
     *     @OA\Property(property="parent_task_id", type="integer", format="integer", example="2"),
     * *  *     @OA\Property(property="task_category_id", type="integer", format="integer", example="2"),

     *     @OA\Property(property="order_no", type="integer", format="integer", example="2"),
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

    public function updateTaskPosition(TaskPositionUpdateRequest $request)
    {

        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");

            if (!$request->user()->hasPermissionTo('task_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $request_data = $request->validated();




            $task_query_params = [
                "id" => $request_data["id"],
                "business_id" => $business_id
            ];



            $task_prev = Task::where($task_query_params)
                ->first();


            if (!$task_prev) {
                return response()->json([
                    "message" => "no task listing found"
                ], 404);
            }

            $task  =  tap(Task::where($task_query_params))->update(
                collect($request_data)->only([
                    'project_id',
                    'parent_task_id',
                    "task_category_id",
                    "order_no",

                ])->toArray()
            )
                // ->with("somthing")

                ->first();

            if (!$task) {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }


            if ($task_prev->project_id !== $task->project_id) {
                // Create the task entry
                Comment::create([
                    'description' => ("Transferred this card from " . $task_prev->name),
                    // 'attachments' => $attachments,
                    'type' => 'history',

                    'task_id' => $task->id, // Assuming you have a $taskId variable
                    'project_id' => $task->project_id, // Assuming you have a $taskId variable
                    'created_by' => auth()->user()->id, // Assuming you have a $userId variable
                ]);


                Comment::create([
                    'description' => ("Transferred this card to " . $task->name),
                    // 'attachments' => $attachments,
                    'type' => 'history',

                    'task_id' => $task_prev->id, // Assuming you have a $taskId variable

                    'project_id' => $task_prev->project_id, // Assuming you have a $taskId variable

                    'created_by' => auth()->user()->id, // Assuming you have a $userId variable
                ]);
            } else if ($task->task_category_id !== $task->task_category_id) {


                Comment::create([

                    'description' => ("moved this card from " . $task_prev->name . " to " . $task->name),
                    // 'attachments' => $attachments,
                    'type' => 'history',

                    'task_id' => $task->id, // Assuming you have a $taskId variable

                    'project_id' => $task->project_id, // Assuming you have a $taskId variable

                    'created_by' => auth()->user()->id, // Assuming you have a $userId variable
                ]);
            }



            $order_no_overlapped = Task::where([
                'project_id' => $task->project_id,
                'parent_task_id' => $task->parent_task_id,
                'task_category_id' => $task->task_category_id,
                'order_no' => $task->order_no,
            ])
                ->whereNotIn('id', [$task->id])
                ->exists();

            if ($order_no_overlapped) {
                Task::where([
                    'project_id' => $task->project_id,
                    'parent_task_id' => $task->parent_task_id,
                    'task_category_id' => $task->task_category_id,
                ])
                    ->where('order_no', '>=', $task->order_no)
                    ->whereNotIn('id', [$task->id])
                    ->increment('order_no');
            }


            DB::commit();
            return response($task, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/tasks",
     *      operationId="getTasks",
     *      tags={"task"},
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
     *
     *    @OA\Parameter(
     *         name="project_id",
     *         in="query",
     *         description="project_id",
     *         required=true,
     *  example="1"
     *      ),
     *      *    @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="status",
     *         required=true,
     *  example="pending"
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

     *      summary="This method is to get task listings  ",
     *      description="This method is to get task listings ",
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

    public function getTasks(Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('task_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $tasksQuery = Task::with(
                [
                    "assigned_by" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "assignees" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "assigned_to" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "labels" => function ($query) {
                        $query->select(
                            'labels.id',
                            'labels.name',
                            'labels.color',
                            "labels.unique_identifier",
                            'labels.project_id',
                        );
                    },

                ]
            )

                ->where(
                    [
                        "business_id" => $business_id
                    ]
                )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("location", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->project_id), function ($query) use ($request) {
                    return $query->where('project_id', $request->project_id);
                })
                ->when(!empty($request->task_category_id), function ($query) use ($request) {
                    return $query->where('task_category_id', $request->task_category_id);
                })

                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $tasks =  $this->retrieveData($tasksQuery, "id", "tasks");




            return response()->json($tasks, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/tasks/{id}",
     *      operationId="getTaskById",
     *      tags={"task"},
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
     *      summary="This method is to get task listing by id",
     *      description="This method is to get task listing by id",
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


    public function getTaskById($id, Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('task_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;

            $task =  Task::with(
                [
                    "assigned_by" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "assignees" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "assigned_to" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "labels" => function ($query) {
                        $query->select(
                            'labels.id',
                            'labels.name',
                            'labels.color',
                            "labels.unique_identifier",
                            'labels.project_id',
                        );
                    },

                ]


            )
                ->where([
                    "id" => $id,
                    "business_id" => $business_id
                ])
                ->select(
                    'tasks.*'
                )
                ->first();
            if (!$task) {

                return response()->json([
                    "message" => "no task listing found"
                ], 404);
            }

            return response()->json($task, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v2.0/tasks/{id}",
     *      operationId="getTaskByIdV2",
     *      tags={"task"},
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
     *      summary="This method is to get task listing by id",
     *      description="This method is to get task listing by id",
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


    public function getTaskByIdV2($id, Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('task_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;

            $task =  Task::with(
                [
                    "project.labels",
                    "assigned_by" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "assignees" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "assigned_to" => function ($query) {
                        $query->select(
                            'users.id',
                            "users.title",
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "labels" => function ($query) {
                        $query->select(
                            'labels.id',
                            'labels.name',
                            'labels.color',
                            "labels.unique_identifier",
                            'labels.project_id',
                        );
                    },

                ]


            )
                ->where([
                    "id" => $id,
                    "business_id" => $business_id
                ])
                ->select(
                    'tasks.*'
                )
                ->first();
            if (!$task) {

                return response()->json([
                    "message" => "no task listing found"
                ], 404);
            }

            return response()->json($task, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/tasks/{ids}",
     *      operationId="deleteTasksByIds",
     *      tags={"task"},
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
     *      summary="This method is to delete task listing by id",
     *      description="This method is to delete task listing by id",
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

    public function deleteTasksByIds(Request $request, $ids)
    {

        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('task_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Task::where([
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

            Task::destroy($existingIds);




            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
