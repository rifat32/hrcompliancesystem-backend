<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckDiscountRequest;
use App\Http\Requests\ServicePlanCreateRequest;
use App\Http\Requests\ServicePlanUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\Module;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicePlanController extends Controller
{
    use ErrorUtil, BusinessUtil, DiscountUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/service-plans",
     *      operationId="createServicePlan",
     *      tags={"service_plans"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store service plan",
     *      description="This method is to store service plan",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="set_up_amount", type="number", format="number", example="10"),
     * @OA\Property(property="duration_months", type="number", format="number", example="12"),
     *  * @OA\Property(property="number_of_employees_allowed", type="number", format="number", example="12"),
     *
     *  * @OA\Property(property="price", type="number", format="number", example="50"),
     * @OA\Property(property="business_tier_id", type="number", format="number", example="1"),
     *
     * * @OA\Property(property="discount_codes", type="string", format="string", example={
     *{"code" : "ddedddd",
     * "discount_amount" : 50,
     *},
     *{"code" : "ddedddd",
     * "discount_amount" : 50,
     *}
     * }),
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

    public function createServicePlan(ServicePlanCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('business_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["is_active"] = 1;
                $request_data["created_by"] = $request->user()->id;


                $service_plan =  ServicePlan::create($request_data);

                $service_plan->discount_codes()->createMany($request_data['discount_codes']);



                // Step 2: Determine active and disabled module IDs
                $active_module_ids = $request_data['active_module_ids'];
                $all_module_ids = Module::where('is_enabled', 1)->pluck('id')->toArray();


                // Step 3: Prepare ServicePlanModule data for bulk insertion
                $service_plan_modules = [];
                foreach ($all_module_ids as $module_id) {
                    $service_plan_modules[] = [
                        'is_enabled' => in_array($module_id, $active_module_ids) ? 1 : 0,
                        'service_plan_id' => $service_plan->id,
                        'module_id' => $module_id,
                        'created_by' => auth()->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Bulk insert ServicePlanModule records
                ServicePlanModule::insert($service_plan_modules);



                return response($service_plan, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/service-plans",
     *      operationId="updateServicePlan",
     *      tags={"service_plans"},
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
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="set_up_amount", type="number", format="number", example="10"),
     *  * @OA\Property(property="number_of_employees_allowed", type="number", format="number", example="12"),
     * @OA\Property(property="duration_months", type="number", format="number", example="30"),
     *  *  * @OA\Property(property="price", type="number", format="number", example="50"),
     * @OA\Property(property="business_tier_id", type="number", format="number", example="1"),
     *  * * @OA\Property(property="discount_codes", type="string", format="string", example={
     *{
     *   "id" :1,
     * "code" : "ddedddd",
     * "discount_amount" : 50,
     *},
     *{
     *   "id" :1,
     *  "code" : "ddedddd",
     * "discount_amount" : 50,
     *}
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

    public function updateServicePlan(ServicePlanUpdateRequest $request)
    {
        DB::beginTransaction();

        try {


            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You cannot perform this action."
                ], 401);
            }

            $request_data = $request->validated();
            $service_plan = ServicePlan::find($request_data['id']);

            if (!$service_plan) {
                return response()->json(["message" => "Service plan not found."], 404);
            }

            // Update main service plan details
            $service_plan->fill($request_data);
            $service_plan->save();

            // Handle discount codes
            foreach ($request_data['discount_codes'] as $discountCode) {
                if (!empty($discountCode['id'])) {
                    $service_plan->discount_codes()->updateOrCreate(
                        ['id' => $discountCode['id']],
                        $discountCode
                    );
                } else {
                    $service_plan->discount_codes()->create($discountCode);
                }
            }
            DB::commit();
            return response()->json($service_plan, 200);
            // Fetch all enabled modules
            $all_modules = Module::where('is_enabled', 1)->get();
            $active_module_ids = $request_data['active_module_ids'];

            $service_plan_modules = [];
            $business_modules = [];
            $deactivated_module_names = [];

            foreach ($all_modules as $module) {
                $is_enabled = in_array($module->id, $active_module_ids) ? 1 : 0;

                if (!$is_enabled) {
                    $deactivated_module_names[] = $module->name;
                }

                $service_plan_modules[] = [
                    'is_enabled' => $is_enabled,
                    'service_plan_id' => $service_plan->id,
                    'module_id' => $module->id,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Get all businesses using this service plan
            $service_businesses = Business::where('service_plan_id', $service_plan->id)->get();
            $service_business_ids = $service_businesses->pluck("id")->toArray();

            // Validate deactivation rules
            $this->validateModuleDeactivationConstraints($deactivated_module_names, $service_businesses, $service_plan);

            // Update ServicePlanModules
            ServicePlanModule::where('service_plan_id', $service_plan->id)->delete();
            ServicePlanModule::insert($service_plan_modules);

            // Prepare BusinessModules
            foreach ($service_business_ids as $business_id) {
                foreach ($all_modules as $module) {
                    $is_enabled = in_array($module->id, $active_module_ids) ? 1 : 0;

                    $business_modules[] = [
                        'is_enabled' => $is_enabled,
                        'business_id' => $business_id,
                        'module_id' => $module->id,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Update BusinessModules
            BusinessModule::whereIn('business_id', $service_business_ids)->delete();
            BusinessModule::insert($business_modules);

            // DB::commit();
            // return response()->json($service_plan, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     * Prevent deactivation of critical modules if certain conditions are not met.
     */
    public function validateModuleDeactivationConstraints($deactivated_module_names,  $service_businesses, $service_plan)
    {
        $service_business_ids = $service_businesses->pluck("id")->toArray();

        $businesses_without_employee_limits = $service_businesses->filter(function ($business) {
            return empty($business->number_of_employees_allowed);
        });

        if ($businesses_without_employee_limits->isNotEmpty()) {
            $number_of_employees_allowed = $service_plan->getRawOriginal('number_of_employees_allowed');
            foreach ($businesses_without_employee_limits as $business) {

                $user_count = Business::withCount([
                    'users' => function ($query) {
                        $query->where("users.is_active", 1)
                            ->whereColumn('users.id', '!=', 'businesses.owner_id')
                            ->whereDoesntHave("lastTermination", function ($query) {
                                $query->where('terminations.date_of_termination', "<", today())
                                    ->whereRaw('terminations.date_of_termination > users.joining_date');
                            });
                    }
                ])
                    ->where('id', $business->id)
                    ->having('users_count', '>', $number_of_employees_allowed)
                    ->exists();

                if ($user_count) {
                    throw new Exception("Business ID {$business->id} exceeds allowed number of employees.", 409);
                }
            }
        }

        if (in_array('department', $deactivated_module_names)) {
            $multi_department_businesses = Business::withCount('departments')
                ->whereIn('id', $service_business_ids)
                ->having('departments_count', '>', 1)
                ->pluck('id')
                ->toArray();

            if (!empty($multi_department_businesses)) {
                throw new Exception('Cannot deactivate "department" module. One or more businesses have more than 1 department.', 409);
            }
        }


        if (in_array('project', $deactivated_module_names)) {
            $multi_project_businesses = Business::withCount('projects')
                ->whereIn('id', $service_business_ids)
                ->having('projects_count', '>', 1)
                ->pluck('id')
                ->toArray();

            if (!empty($multi_project_businesses)) {
                throw new Exception('Cannot deactivate "project" module. One or more businesses have more than 1 project.', 409);
            }
        }

        if (in_array('letter_template', $deactivated_module_names)) {

            $multi_letter_businesses = Business::whereIn('id', $service_business_ids)
                ->whereHas('user_letters')
                ->pluck('id')
                ->toArray();

            if (!empty($multi_letter_businesses)) {
                throw new Exception('Cannot deactivate "letter_template" module. One or more businesses have letter templates.', 409);
            }
        }

        if (in_array('flexible_shifts', $deactivated_module_names)) {

            $multi_work_shifts_businesses = Business::whereIn('id', $service_business_ids)
                ->whereHas('work_shifts', function($query) {
                    $query->where("work_shifts.type","flexible");
                })
                ->pluck('id')
                ->toArray();

            if (!empty($multi_work_shifts_businesses)) {
                throw new Exception('Cannot deactivate "flexible shifts" module. One or more businesses have flexible shifts.', 409);
            }

            $multi_work_shift_histories_businesses = Business::whereIn('id', $service_business_ids)
            ->whereHas('work_shift_histories', function($query) {
                $query->where("work_shift_histories.type","flexible");
            })
                ->pluck('id')
                ->toArray();

            if (!empty($multi_work_shift_histories_businesses)) {
                throw new Exception('Cannot deactivate "flexible shifts" module. One or more businesses have flexible shifts.', 409);
            }
        }

        if (in_array('task_management', $deactivated_module_names)) {
            $multi_tasks_businesses = Business::whereIn('id', $service_business_ids)
                ->whereHas('tasks')
                ->pluck('id')
                ->toArray();

            if (!empty($multi_tasks_businesses)) {
                throw new Exception('Cannot deactivate "tasks" module. One or more businesses have tasks.', 409);
            }
        }

      if (in_array('employee_location_attendance', $deactivated_module_names)) {
    $multi_tasks_businesses = Business::whereIn('id', $service_business_ids)
        ->whereHas('work_locations', function($query) {
            $query->where("work_locations.is_geo_location_enabled", 1);
        })
        ->pluck('id')
        ->toArray();

    if (!empty($multi_tasks_businesses)) {
        throw new Exception('Cannot deactivate "employee_location_attendance" module. One or more businesses have work sites with location enabled.', 409);
    }
}

        if (in_array('role', $deactivated_module_names)) {
            $multi_role_businesses = Business::withCount([
                'users as users_count' => function ($query) {
                    $query->whereColumn('users.id', '!=', 'businesses.owner_id');
                },
                'users as employee_users_count' => function ($query) {
                    $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                          ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                          ->where('roles.name', 'like', 'business_employee#%')
                          ->whereColumn('users.id', '!=', 'businesses.owner_id');
                }
            ])
            ->whereIn('id', $service_business_ids)
            ->havingRaw('users_count != employee_users_count') // Only include businesses with a mismatch
            ->pluck('id')
            ->toArray();

            if (!empty($multi_role_businesses)) {
                throw new Exception('Cannot deactivate "role" module. One or more businesses have users with multiple roles.', 409);
            }
        }

    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/service-plans",
     *      operationId="getServicePlans",
     *      tags={"service_plans"},
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

    public function getServicePlans(Request $request)
    {
        try {


            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $service_plansQuery = ServicePlan::with("business_tier")
                ->where('service_plans.created_by', auth()->user()->id)
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("service_plans.name", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('service_plans.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('service_plans.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                 $service_plans =  $this->retrieveData($service_plansQuery, "id", "service_plans");

            return response()->json($service_plans, 200);
        } catch (Exception $e) {


            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/service-plans",
     *      operationId="getServicePlanClient",
     *      tags={"service_plans"},
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

    public function getServicePlanClient(Request $request)
    {
        try {




            $service_plansQuery = ServicePlan::with("business_tier")
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;

                    });
                })
                ->when(!empty($request->reseller_id), function ($query) use ($request) {
                    return $query->where('service_plans.created_by', $request->reseller_id);
                }, function ($query) use ($request) {
                    return $query->where('service_plans.created_by', 1);
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('service_plans.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('service_plans.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                $service_plans =  $this->retrieveData($service_plansQuery, "id", "service_plans");



            return response()->json($service_plans, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/service-plans/{id}",
     *      operationId="getServicePlanById",
     *      tags={"service_plans"},
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


    public function getServicePlanById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $service_plan =  ServicePlan::with("discount_codes")->where([
                "id" => $id,
            ])
                ->first();
            if (!$service_plan) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            $modules = Module::where('modules.is_enabled', 1)
                ->orderBy("modules.name", "ASC")

                ->select("id", "name")
                ->get()

                ->map(function ($item) use ($service_plan) {
                    $item->is_enabled = 0;

                    $servicePlanModule =    ServicePlanModule::where([
                        "service_plan_id" => $service_plan->id,
                        "module_id" => $item->id
                    ])
                        ->first();

                    if (!empty($servicePlanModule)) {
                        if ($servicePlanModule->is_enabled) {
                            $item->is_enabled = 1;
                        }
                    }

                    return $item;
                });

            $service_plan->modules = $modules;

            return response()->json($service_plan, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/service-plans/{ids}",
     *      operationId="deleteServicePlansByIds",
     *      tags={"service_plans"},
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

    public function deleteServicePlansByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = ServicePlan::whereIn('id', $idsArray)
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

            // Check for conflicts in Businesses with Service Plans
            $conflictingBusinessesExists = Business::whereIn("service_plan_id", $existingIds)->exists();
            if ($conflictingBusinessesExists) {
                $conflicts[] = "Businesses associated with the Service Plans";
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


            ServicePlan::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/check-discount",
     *      operationId="checkDiscountClient",
     *      tags={"service_plans"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check discount",
     *      description="This method is to check discount",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * * @OA\Property(property="service_plan_discount_code", type="string", format="string", example="tttttt"),

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

    public function checkDiscountClient(CheckDiscountRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {


                $request_data = $request->validated();

                $response_data['service_plan_discount_amount'] = $this->getDiscountAmount($request_data);


                return response($response_data, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
