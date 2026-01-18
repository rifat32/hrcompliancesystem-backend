<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\TaskCategoryCreateRequest;
use App\Http\Requests\TaskCategoryPositionUpdateRequest;
use App\Http\Requests\TaskCategoryUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\DisabledTaskCategory;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskCategoryOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskCategoryController extends Controller
{
    use ErrorUtil, BusinessUtil, ModuleUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/task-categories",
     *      operationId="createTaskCategory",
     *      tags={"task_categories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store task category",
     *      description="This method is to store task category",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 * * @OA\Property(property="color", type="string", format="string", example="tttttt"),
 * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
 *  *  * @OA\Property(property="project_id", type="string", format="string", example="erg ear ga&nbsp;"),
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

    public function createTaskCategory(TaskCategoryCreateRequest $request)
    {
        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");

                if (!$request->user()->hasPermissionTo('task_category_create')) {
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

                $task_category =  TaskCategory::create($request_data);

              $task_category->order_no = TaskCategory::where(function($query) {
                $query->where("business_id" , auth()->user()->business_id)
                ->orWhereNull("business_id");
            })->count();

                $task_category->save();



                DB::commit();
                return response($task_category, 201);


        } catch (Exception $e) {
          DB::rollBack();
            return $this->sendError($e);
        }
    }
  /**
     *
     * @OA\Put(
     *      path="/v1.0/task-categories",
     *      operationId="updateTaskCategory",
     *      tags={"task_categories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update task category ",
     *      description="This method is to update task category",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 *  * * @OA\Property(property="color", type="string", format="string", example="tttttt"),
 * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
 *  * @OA\Property(property="order_no", type="string", format="string", example="erg ear ga&nbsp;"),
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

     public function updateTaskCategory(TaskCategoryUpdateRequest $request)
     {

         DB::beginTransaction();
         try {

             $this->isModuleEnabled("task_management");

                 if (!$request->user()->hasPermissionTo('task_category_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }
                 // $business_id =  auth()->user()->business_id;
                 $request_data = $request->validated();



                 $task_category_query_params = [
                     "id" => $request_data["id"],
                     // "business_id" => $business_id
                 ];



                 $task_category  =  tap(TaskCategory::where($task_category_query_params))->update(
                     collect($request_data)->only([
                         'name',
                         "color",
                         'description',
                         'order_no'
                          // "is_default",
                         // "is_active",
                         // "business_id",

                     ])->toArray()
                 )
                     // ->with("somthing")

                     ->first();
                 if (!$task_category) {
                     return response()->json([
                         "message" => "something went wrong."
                     ], 500);
                 }

                 DB::commit();
                 return response($task_category, 201);

         } catch (Exception $e) {
             DB::rollBack();
             return $this->sendError($e);
         }
     }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/task-categories/position",
     *      operationId="updateTaskCategoryPosition",
     *      tags={"task_categories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update task category ",
     *      description="This method is to update task category",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="project_id", type="string", format="string", example="tttttt"),

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

    public function updateTaskCategoryPosition(TaskCategoryPositionUpdateRequest $request)
    {

        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");

                if (!$request->user()->hasPermissionTo('task_category_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                // $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();
                $request_data["business_id"] = auth()->user()->business_id?auth()->user()->business_id:NULL;
                $request_data["project_id"] = !empty($request_data["project_id"])?$request_data["project_id"]:NULL;


                $no_order_task_categories = TaskCategory::

                where(function($query) {
                    $query->where("business_id" , auth()->user()->business_id)
                    ->orWhereNull("business_id");
                })
                ->whereDoesntHave("order", function($query) use($request_data){
                    $query->when(!empty(auth()->user()->business_id), function($query) use($request_data) {
                        $query->where("task_category_orders.business_id",auth()->user()->business_id)
                        ->where("task_category_orders.project_id",$request_data["project_id"]);
                    }, function($query) {
                        $query->whereNull("task_category_orders.business_id");
                    });

    //    add business project conditions
                })

                ->get();

                foreach ($no_order_task_categories as $no_order_task_category ) {
                    TaskCategoryOrder::create([
                        'task_category_id' => $no_order_task_category->id,
                        'order_no' => $no_order_task_category->order_no,
                        'project_id' => $request_data["project_id"],
                        "business_id" => $request_data["business_id"]
                    ]);
                }



                $task_category_order_query_params = [
                    "task_category_id" => $request_data["id"],
                    'project_id' => $request_data["project_id"],
                    "business_id" => $request_data["business_id"]
                ];


                $task_category_order = TaskCategoryOrder::updateOrCreate(
                    // The condition to check if a record exists
                    $task_category_order_query_params,
                    // The data to update or create
                    collect($request_data)->only(['order_no'])->toArray()
                );




                if (!$task_category_order) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                $order_no_overlapped = TaskCategoryOrder::where([
                    'business_id' => $task_category_order->business_id,
                    'project_id' => $task_category_order->project_id,
                    'order_no' => $task_category_order->order_no,
                ])
                ->whereNotIn('id', [$task_category_order->id])
                ->exists();

                if ($order_no_overlapped) {
                    TaskCategoryOrder::where([
                        'business_id' => $task_category_order->business_id,
                        'project_id' => $task_category_order->project_id,
                        'order_no' => $task_category_order->order_no,
                    ])
                    ->where('order_no', '>=', $task_category_order->order_no)
                    ->whereNotIn('id', [$task_category_order->id])
                    ->increment('order_no');
                }


                DB::commit();

                return response(["ok" => true], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

  /**
     *
     * @OA\Put(
     *      path="/v1.0/task-categories/toggle-active",
     *      operationId="toggleActiveTaskCategory",
     *      tags={"task_categories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle task category",
     *      description="This method is to toggle task category",
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

     public function toggleActiveTaskCategory(GetIdRequest $request)
     {

        DB::beginTransaction();
         try {

             $this->isModuleEnabled("task_management");
             if (!$request->user()->hasPermissionTo('task_category_activate')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $this->toggleActivation(
                TaskCategory::class,
                DisabledTaskCategory::class,
                'task_category_id',
                $request_data["id"],
                auth()->user()
            );
          DB::commit();
             return response()->json(['message' => 'Task Category status updated successfully'], 200);
         } catch (Exception $e) {
             DB::rollBack();
             return $this->sendError($e);
         }
     }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/task-categories",
     *      operationId="getTaskCategories",
     *      tags={"task_categories"},
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
     * name="task_id",
     * in="query",
     * description="task_id",
     * required=true,
     * example="1"
     * ),
     *     *   *    *      * *  @OA\Parameter(
     * name="project_id",
     * in="query",
     * description="project_id",
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

     *      summary="This method is to get task categories  ",
     *      description="This method is to get task categories ",
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

    public function getTaskCategories(Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");
            if (!$request->user()->hasPermissionTo('task_category_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if(auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }

            $task_categoriesQuery = TaskCategory::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('task_categories');
                }, function ($query) use ($request, $created_by) {
                    $query->forNonSuperAdmin('task_categories', $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query)  {
                $query->forBusiness('task_categories');
            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("task_categories.name", "like", "%" . $term . "%")
                            ->orWhere("task_categories.description", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('task_categories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('task_categories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })


                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where('task_categories.name', $request->name );
                })
                ->when(!empty($request->description), function ($query) use ($request) {
                    return $query->where('task_categories.description', $request->description );
                });
                 $task_categories =  $this->retrieveData($task_categoriesQuery, "id", "task_categories");




            return response()->json($task_categories, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v2.0/task-categories",
     *      operationId="getTaskCategoriesV2",
     *      tags={"task_categories"},
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
     * name="task_id",
     * in="query",
     * description="task_id",
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

     *      summary="This method is to get task categories  ",
     *      description="This method is to get task categories ",
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

     public function getTaskCategoriesV2(Request $request)
     {
         try {

             $this->isModuleEnabled("task_management");
             if (!$request->user()->hasPermissionTo('task_category_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $created_by  = NULL;
             if(auth()->user()->business) {
                 $created_by = auth()->user()->business->created_by;
             }

             $task_categoriesQuery = TaskCategory::with([
                'tasks' => function ($query) {
                    $query->where('business_id', auth()->user()->business_id) // Ensure business_id condition
                          ->when(request()->project_id, function ($query) {
                              $query->where('project_id', request()->project_id); // Apply project_id condition only if it's present
                          });
                },
                'tasks.labels' => function ($query) {

                },
                'tasks.assigned_by' => function ($query) {
                    $query->select('users.id', "users.title", "users.first_Name", "users.middle_Name", "users.last_Name", "users.image");
                },
                'tasks.assigned_to' => function ($query) {
                    $query->select('users.id', "users.title", "users.first_Name", "users.middle_Name", "users.last_Name", "users.image");
                },
                'tasks.assignees' => function ($query) {
                    $query->select('users.id', "users.title", "users.first_Name", "users.middle_Name", "users.last_Name", "users.image",  'task_assignees.task_id', 'task_assignees.assignee_id');
                }

            ])

            ->when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('task_categories');
                }, function ($query) use ($request, $created_by) {
                    $query->forNonSuperAdmin('task_categories', $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query) {
                $query->forBusiness('task_categories');
            })

                 ->when(!empty($request->search_key), function ($query) use ($request) {
                 return $query->where(function ($query) use ($request) {
                     $term = $request->search_key;
                     $query->where("task_categories.name", "like", "%" . $term . "%")
                         ->orWhere("task_categories.description", "like", "%" . $term . "%");
                 });
             })

                 ->when(!empty($request->task_id), function ($query) use ($request) {
                     return $query->whereHas('tasks',function($query) use($request) {
                         $query->where('tasks.id',$request->task_id);
                     });
                 })
                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('task_categories.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('task_categories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 });
 $task_categories =  $this->retrieveData($task_categoriesQuery, "id", "task_categories");


             return response()->json($task_categories, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/task-categories/{id}",
     *      operationId="getTaskCategoryById",
     *      tags={"task_categories"},
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
     *      summary="This method is to get task category by id",
     *      description="This method is to get task category by id",
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


    public function getTaskCategoryById($id, Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");

            if (!$request->user()->hasPermissionTo('task_category_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $task_category =  TaskCategory::with("tasks")->where([
                "id" => $id,
            ])

                ->first();
            if (!$task_category) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($task_category->business_id != NULL )) {

                        return response()->json([
                            "message" => "You do not have permission to update this task category due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($task_category->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this task category due to role restrictions."
                        ], 403);
                    } else if ($task_category->is_default == 0 && $task_category->created_by != auth()->user()->id) {

                            return response()->json([
                                "message" => "You do not have permission to update this task category due to role restrictions."
                            ], 403);

                    }
                }
            } else {
                if ($task_category->business_id != NULL) {
                    if (($task_category->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this task category due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($task_category->is_default == 0) {
                        if ($task_category->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this task category due to role restrictions."
                            ], 403);
                        }
                    }
                }
            }

            return response()->json($task_category, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
/**
     *
     * @OA\Get(
     *      path="/v1.0/task-categories-by-project-id/{project_id}",
     *      operationId="getTaskCategoryByProjectId",
     *      tags={"task_categories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="project_id",
     *         in="path",
     *         description="project_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get task category by id",
     *      description="This method is to get task category by id",
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


     public function getTaskCategoryByProjectId($project_id, Request $request)
     {
         try {

             $this->isModuleEnabled("task_management");

             if (!$request->user()->hasPermissionTo('task_category_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

             $task_category =  TaskCategory::with("tasks")->where([
                 "project_id" => $project_id,
             ])
             // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
             //     return $query->where('task_categories.business_id', NULL)
             //                  ->where('task_categories.is_default', 1);
             // })
             // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
             //     return $query->where('task_categories.business_id', auth()->user()->business_id);
             // })
                 ->first();
             if (!$task_category) {

                 return response()->json([
                     "message" => "no data found"
                 ], 404);
             }

             if (empty(auth()->user()->business_id)) {

                 if (auth()->user()->hasRole('superadmin')) {
                     if (($task_category->business_id != NULL)) {

                         return response()->json([
                             "message" => "You do not have permission to update this task category due to role restrictions."
                         ], 403);
                     }
                 } else {
                     if ($task_category->business_id != NULL) {

                         return response()->json([
                             "message" => "You do not have permission to update this task category due to role restrictions."
                         ], 403);
                     } else if ($task_category->is_default == 0 && $task_category->created_by != auth()->user()->id) {

                             return response()->json([
                                 "message" => "You do not have permission to update this task category due to role restrictions."
                             ], 403);

                     }
                 }
             } else {
                 if ($task_category->business_id != NULL) {
                     if (($task_category->business_id != auth()->user()->business_id)) {

                         return response()->json([
                             "message" => "You do not have permission to update this task category due to role restrictions."
                         ], 403);
                     }
                 } else {
                     if ($task_category->is_default == 0) {
                         if ($task_category->created_by != auth()->user()->created_by) {

                             return response()->json([
                                 "message" => "You do not have permission to update this task category due to role restrictions."
                             ], 403);
                         }
                     }
                 }
             }

             return response()->json($task_category, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }
/**
     *
     * @OA\Get(
     *      path="/v2.0/task-categories-by-project-id/{project_id}",
     *      operationId="getTaskCategoryByProjectIdV2",
     *      tags={"task_categories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="project_id",
     *         in="path",
     *         description="project_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get task category by id",
     *      description="This method is to get task category by id",
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


     public function getTaskCategoryByProjectIdV2($project_id, Request $request)
     {
         try {

             $this->isModuleEnabled("task_management");

             if (!$request->user()->hasPermissionTo('task_category_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }

             $task_categories =  TaskCategory::where([
                 "project_id" => $project_id,
                 "business_id" => auth()->user()->business_id

             ])

                 ->get();

                $tasks = Task::whereIn("task_category_id",$task_categories->pluck("id")->toArray())->get();

                $responseData = [
                  "task_categories"  => $task_categories,
                  "tasks"  => $tasks
                ];



             return response()->json($responseData, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }
    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/task-categories/{ids}",
     *      operationId="deleteTaskCategoriesByIds",
     *      tags={"task_categories"},
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
     *      summary="This method is to delete task category by id",
     *      description="This method is to delete task category by id",
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

    public function deleteTaskCategoriesByIds(Request $request, $ids)
    {

        try {

            $this->isModuleEnabled("task_management");
            if (!$request->user()->hasPermissionTo('task_category_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = TaskCategory::whereIn('id', $idsArray)
            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                if ($request->user()->hasRole("superadmin")) {
                    return $query->where('task_categories.business_id', NULL)
                        ->where('task_categories.is_default', 1);
                } else {
                    return $query->where('task_categories.business_id', NULL)
                        ->where('task_categories.is_default', 0)
                        ->where('task_categories.created_by', $request->user()->id);
                }
            })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where('task_categories.business_id', auth()->user()->business_id)
                    ->where('task_categories.is_default', 0);
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

            // Check for conflicts in Tasks with Task Categories
            $task_exists = Task::whereIn("task_category_id", $existingIds)->exists();
            if ($task_exists) {
                $conflicts[] = "Tasks associated with the specified Task Categories";
            }

            // Add more checks for other related models or conditions as needed

            // Return combined error message if conflicts exist
            if (!empty($conflicts)) {
                $conflictList = implode(', ', $conflicts);
                return response()->json([
                    "message" => "Cannot delete this data as there are records associated with it in the following areas: $conflictList. Please update these records before attempting to delete.",
                ], 409);
            }

            // Proceed with the deletion process if no conflicts are found.


            TaskCategory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
