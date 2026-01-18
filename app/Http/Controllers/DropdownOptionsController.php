<?php

namespace App\Http\Controllers;

use App\Http\Components\AuthorizationComponent;
use App\Http\Components\DepartmentComponent;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Exception;
use Illuminate\Http\Request;

class DropdownOptionsController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;



    protected $authorizationComponent;
    protected $departmentComponent;


    public function __construct(AuthorizationComponent $authorizationComponent,   DepartmentComponent $departmentComponent, )
    {
        $this->authorizationComponent = $authorizationComponent;
        $this->departmentComponent = $departmentComponent;

    }

 private function get_work_locations($created_by,$fields=[]) {
   $work_locations = WorkLocation::when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
    $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
        $query->forSuperAdmin('work_locations');
    }, function ($query) use ( $created_by) {
        $query->forNonSuperAdmin('work_locations', $created_by);
    });
})
->when(!empty(auth()->user()->business_id), function ($query)  {
    $query->forBusiness('work_locations',  true);
})
->when(request()->filled("user_id"), function ($query) {

    $query->whereHas("users" , function($query) {
       $query->whereIn("users.id",[request()->input("user_id")]) ;
    });
})
->when(!empty($fields), function($query) use($fields) {
    $query->select($fields);
 })
        ->get();

        return $work_locations;
 }

 private function get_designations($created_by,$fields=[]) {
    $designations = Designation::when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
        $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
            $query->forSuperAdmin('designations');
        }, function ($query) use ( $created_by) {
            $query->forNonSuperAdmin('designations', $created_by);
        });
    })
    ->when(!empty(auth()->user()->business_id), function ($query)  {
        $query->forBusiness('designations', true);
    })
    ->when(request()->filled("user_id"), function ($query)  {

        $query->whereHas("users" , function($query) {
           $query->whereIn("users.id",[request()->input("user_id")]) ;
        });
    })
 ->when(!empty($fields), function($query) use($fields) {
    $query->select($fields);
 })
         ->get();

         return $designations;
  }

  private function get_employment_statuses($created_by,$fields=[]) {
    $employment_statuses = EmploymentStatus::when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
        $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
            $query->forSuperAdmin('employment_statuses');
        }, function ($query) use ( $created_by) {
            $query->forNonSuperAdmin('employment_statuses', $created_by);
        });
    })
    ->when(!empty(auth()->user()->business_id), function ($query)  {
        $query->forBusiness('employment_statuses', true);
    })
    ->when(request()->filled("user_id"), function ($query)  {

        $query->whereHas("users" , function($query) {
           $query->whereIn("users.id",[request()->input("user_id")]) ;
        });
    })
 ->when(!empty($fields), function($query) use($fields) {
    $query->select($fields);
 })
         ->get();

         return $employment_statuses;
  }


  private function get_work_shifts($all_manager_department_ids,$fields=[]) {
          $work_shifts = WorkShift::where(function($query) use($all_manager_department_ids) {
                    $query
                    ->where([
                        "work_shifts.business_id" => auth()->user()->business_id,
                        "work_shifts.is_active" => 1,
                    ])
                    ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });

                })
                // ->orWhere(function($query)  {
                //     $query->where([
                //         "is_active" => 1,
                //         "business_id" => NULL,
                //         "is_default" => 1
                //     ]);

                // })
                ->when(!empty($fields), function($query) use($fields) {
                    $query->select($fields);
                 })
            ->get();



         return $work_shifts;
  }

  private function get_projects($fields=[]) {
    $projects = Project::
    where(
        [
            "business_id" => auth()->user()->business_id
        ]
    )


 ->when(!empty($fields), function($query) use($fields) {
    $query->select($fields);
 })
         ->get();

         return $projects;
  }

  private function get_roles($fields=[]) {

    $roles = Role::where("id",">",$this->getMainRoleId())
    ->where('business_id', auth()->user()->business_id)
    ->when(!empty($fields), function($query) use($fields) {
        $query->select($fields);
     })

                  ->get();

         return $roles;


  }


  private function get_departments($all_manager_department_ids,$fields=[]) {


    $departments = Department::where(
        [
            "business_id" => auth()->user()->business_id
        ]
    )
    ->when(request()->filled("hide_children_for_id"), function($query) {
        $query->whereNotIn("id",[request()->input("hide_children_for_id")]);

       $department = Department::where("id",request()->input("hide_children_for_id"))->first();
       if(!empty($department)) {
    $allDescendantIds = $department->getAllDescendantIds();
    $query->whereNotIn("id",$allDescendantIds);
       }

    })
    ->whereIn("id",$all_manager_department_ids)
        ->where('departments.is_active', 1)
        ->when(!empty($fields), function($query) use($fields) {
           $query->select($fields);
        })
        ->get()
        ->map(function($record,$index) {
            if ($index === 0) {
                $record->is_current = true;
            }
            return $record;

        });

         return $departments;
  }
  private function users($all_manager_department_ids,$fields=[]) {


    $users = User::whereHas('departments',function($query) use($all_manager_department_ids) {
         $query->whereIn("departments.id", $all_manager_department_ids);

    })

     ->where("users.is_active",1)
     ->whereDate("users.joining_date", "<=", today())
        ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })
    ->when(!empty($fields), function($query) use($fields) {
        $query->select($fields);
     })
     ->get();

         return $users;
  }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/dropdown-options/employee-form",
     *      operationId="getEmployeeFormDropdownData",
     *      tags={"dropdowns"},
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

     *      summary="This method is to get reminders  ",
     *      description="This method is to get reminders ",
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

     public function getEmployeeFormDropdownData(Request $request)
     {
         try {

             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $user =  auth()->user();

             $business = $user->business;

             $business_created_by = $business->created_by;

             $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();

             $data["work_locations"] = $this->get_work_locations($business_created_by);
             $data["designations"] = $this->get_designations($business_created_by);
             $data["employment_statuses"] = $this->get_employment_statuses($business_created_by);

             $data["work_shifts"] = $this->get_work_shifts($all_manager_department_ids);
             $data["roles"] = $this->get_roles();
             $data["departments"] = $this->get_departments($all_manager_department_ids);



             return response()->json($data, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

   /**
     *
     * @OA\Get(
     *      path="/v2.0/dropdown-options/employee-form",
     *      operationId="getEmployeeFormDropdownDataV2",
     *      tags={"dropdowns"},
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

     *      summary="This method is to get reminders  ",
     *      description="This method is to get reminders ",
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

     public function getEmployeeFormDropdownDataV2(Request $request)
     {
         try {

             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $user =  auth()->user();
             $business = $user->business;
             $business_id =  $business->id;
             $business_created_by = $business->created_by;

             $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();


             $data["work_locations"] = $this->get_work_locations($business_created_by,[
                "id","name","business_id","is_active","is_default",
             ]);

             $data["designations"] = $this->get_designations($business_created_by,[
                 "designations.id","designations.name","designations.business_id","designations.is_active","designations.is_default",
             ]);

             $data["employment_statuses"] = $this->get_employment_statuses($business_created_by,[
                "employment_statuses.id",
                "employment_statuses.name",
                "employment_statuses.business_id",
                "employment_statuses.is_active",
                "employment_statuses.is_default",
                "employment_statuses.color",
             ]);

             $data["work_shifts"] = $this->get_work_shifts($all_manager_department_ids,[
                "id", "name", "business_id", "is_active", "is_default",
             ]);
             $data["roles"] = $this->get_roles([
                "roles.id","roles.name","roles.business_id","roles.is_active","roles.is_default","roles.is_default_for_business",
             ]);


             $data["departments"] = $this->get_departments($all_manager_department_ids,
             [
                "departments.id", "departments.name", 'departments.is_active', "departments.is_current", "departments.manager_id", "departments.parent_id", "departments.work_location_id"
             ]
            );



             return response()->json($data, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/dropdown-options/employee-filter",
     *      operationId="getEmployeeFilterDropdownData",
     *      tags={"dropdowns"},
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

     *      summary="This method is to get reminders  ",
     *      description="This method is to get reminders ",
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

     public function getEmployeeFilterDropdownData(Request $request)
     {
         try {

             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $user =  auth()->user();

             $business = $user->business;

             $business_created_by = $business->created_by;

             $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();

             $data["work_locations"] = $this->get_work_locations($business_created_by);
             $data["designations"] = $this->get_designations($business_created_by);
             $data["employment_statuses"] = $this->get_employment_statuses($business_created_by);

            //  $data["work_shifts"] = $this->get_work_shifts($all_manager_department_ids);
            //  $data["roles"] = $this->get_roles();
             $data["departments"] = $this->get_departments($all_manager_department_ids);

             $data["projects"] = $this->get_projects();


             return response()->json($data, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

      /**
     *
     * @OA\Get(
     *      path="/v1.0/dropdown-options/attendance-filter",
     *      operationId="getAttendanceFilterDropdownData",
     *      tags={"dropdowns"},
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

     *      summary="This method is to get reminders  ",
     *      description="This method is to get reminders ",
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

     public function getAttendanceFilterDropdownData(Request $request)
     {
         try {

             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $user =  auth()->user();

             $business = $user->business;

             $business_created_by = $business->created_by;

             $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();

             $data["work_locations"] = $this->get_work_locations($business_created_by);
             $data["designations"] = $this->get_designations($business_created_by);
            //  $data["employment_statuses"] = $this->get_employment_statuses($business_created_by);

              $data["work_shifts"] = $this->get_work_shifts($all_manager_department_ids);
            //  $data["roles"] = $this->get_roles();
             $data["departments"] = $this->get_departments($all_manager_department_ids);

             $data["users"] = $this->users($all_manager_department_ids,["users.id", "users.title", "users.first_Name", "users.last_Name", "users.middle_Name"]);

             $data["projects"] = $this->get_projects();


             return response()->json($data, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }


}
