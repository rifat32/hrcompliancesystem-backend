<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRotaCreateRequest;
use App\Http\Requests\EmployeeRotaUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\Department;
use App\Models\EmployeeRota;
use App\Models\EmployeeRotaDetail;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;


class EmployeeRotaController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil, ModuleUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/employee-rotas",
     *      operationId="createEmployeeRota",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store employee rota",
     *      description="This method is to store employee rota",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *  *     @OA\Property(property="description", type="string", format="string", example="description"),

     *

     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),

     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "1",
     *             "start_at": "",
     *             "end_at": "",
      *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "2",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "3",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "4",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "5",
     *             "start_at": "",
     *             "end_at": "",
      *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "6",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         }
     *     }),

     *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
     *     @OA\Property(property="end_date", type="string", format="date", example="2023-11-16"),
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

    public function createEmployeeRota(EmployeeRotaCreateRequest $request)
    {

        try {


            $this->isModuleEnabled("rota");


            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_rota_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();


                $all_manager_department_ids = $this->get_all_departments_of_manager();

                $departments = [];

                if(!empty($request_data['departments'])) {
                    $departments = Department::whereIn('id', $request_data['departments'])
                    ->whereIn(
                        "id",
                        $all_manager_department_ids
                    )
                    ->whereDoesntHave("employee_rota")
                    ->where('business_id', auth()->user()->business_id)
                    ->pluck("id");

                    $invalid_department_ids = array_diff($request_data['departments'], $departments->toArray());


                    if (!empty($invalid_department_ids)) {
                        throw new Exception('Invalid department IDs found.',403);
                    }

                }



                $users = [];

                if(!empty($request_data['users'])) {
                    $users = User::where([
                        'users.business_id' => auth()->user()->business_id,
                        "is_active" => 1
                    ])
                    ->whereHas('departments', function($query) use($all_manager_department_ids) {
                        $query->whereIn('departments.id', $all_manager_department_ids);
                    })
                    ->whereDoesntHave("employee_rota")
                    ->whereIn('users.id', $request_data['users'])
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->pluck("id");

                    $invalid_user_ids = array_diff($request_data['users'], $users->toArray());


                    if (!empty($invalid_user_ids)) {
                        throw new Exception('Invalid user IDs found.',403);
                    }
                }








                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_default"] = false;




                $data_to_process = collect();

                $common_data = collect([
                    'name' => $request_data['name'],
                    'description' => $request_data['description'],
                    'is_default' => $request_data['is_default'],
                    'is_active' => $request_data['is_active'],
                    'business_id' => $request_data['business_id'],
                    'created_by' => $request_data['created_by'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (!empty($departments)) {
                    $data_to_process = $data_to_process->concat(collect($departments)->map(function ($department_id) use ($common_data) {
                        return $common_data->merge(['department_id' => $department_id, 'user_id' => NULL])->all();
                    }));
                }

                if (!empty($users)) {
                    $data_to_process = $data_to_process->concat(collect($users)->map(function ($user_id) use ($common_data) {
                        return $common_data->merge(['department_id' => NULL,'user_id' => $user_id])->all();
                    }));
                }

                if ($data_to_process->isNotEmpty()) {
                    $chunkSize = 1000; // Example chunk size
                    $data_to_process->chunk($chunkSize)->each(function ($chunk) {
                        EmployeeRota::insert($chunk->toArray());
                    });
                    // $employee_rota->details()->createMany($request_data['details']);
                }


                // Query to retrieve data based on department and user IDs
$employeeRotaIds = EmployeeRota::whereIn('department_id', $departments)
->orWhereIn('user_id', $users)
->pluck("id");


$processedDetails = collect($request_data['details'])->crossJoin($employeeRotaIds)->map(function ($item) {
    $detail = $item[0];
    $employeeRotaId = $item[1];

    return [
        'employee_rota_id' => $employeeRotaId,
        'day' => $detail['day'],
        'start_at' => $detail['start_at'],
        'end_at' => $detail['end_at'],
        'break_type' => $detail['break_type'],
        'break_hours' => $detail['break_hours'],
        "created_at" => now(),
        "updated_at" => now()
    ];
});



if ($processedDetails->isNotEmpty()) {
    $chunkSize = 1000; // Example chunk size
    $processedDetails->chunk($chunkSize)->each(function ($chunk) {
        EmployeeRotaDetail::insert($chunk->toArray());
    });
    // $employee_rota->details()->createMany($request_data['details']);
}



                // if(!empty($request_data['departments'])){
                //     foreach($request_data['departments'] as $department_id) {
                //         $employee_rota =  EmployeeRota::create($request_data);
                //         $employee_rota->details()->createMany($request_data['details']);
                //         $employee_rota->departments()->sync([$department_id]);
                //     }
                // }


                // if(!empty($request_data['users'])){
                //     foreach($request_data['users'] as $user_id) {
                //         $employee_rota =  EmployeeRota::create($request_data);
                //         $employee_rota->details()->createMany($request_data['details']);
                //         $employee_rota->users()->sync([$user_id]);
                //     }
                // }


















                return response(["ok" => true], 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/employee-rotas",
     *      operationId="updateEmployeeRota",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update employee rota ",
     *      description="This method is to update employee_rota",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),

     *     @OA\Property(property="description", type="string", format="string", example="description"),

     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3,4}),

     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "start_at": "",
     *             "end_at": "",
       *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "1",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "2",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "3",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "4",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "5",
     *             "start_at": "",
     *             "end_at": "",
       *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "6",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         }
     *     }),

     *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
     *     @OA\Property(property="end_date", type="string", format="date", example=""),
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

    public function updateEmployeeRota(EmployeeRotaUpdateRequest $request)
    {

        try {

            $this->isModuleEnabled("rota");

            return DB::transaction(function () use ($request) {

                if (!$request->user()->hasPermissionTo('employee_rota_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }


                $request_data = $request->validated();





                $employee_rota_query_params = [
                    "id" => $request_data["id"],
                ];



                $employee_rota  =  tap(EmployeeRota::where($employee_rota_query_params))->update(
                    collect($request_data)->only([
        'name',
        'type',
        "description",
        'start_date',
        'end_date',


                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$employee_rota) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }





                $employee_rota->details()->delete();
                $employee_rota->details()->createMany($request_data['details']);




                return response($employee_rota, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


  /**
     *
     * @OA\Put(
     *      path="/v1.0/employee-rotas/toggle-active",
     *      operationId="toggleActiveEmployeeRota",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle employee rota activity",
     *      description="This method is to toggle employee rota activity",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
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


     public function toggleActiveEmployeeRota(GetIdRequest $request)
     {

         try {

             $this->isModuleEnabled("rota");

             if (!$request->user()->hasPermissionTo('user_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();


             $all_manager_department_ids = $this->get_all_departments_of_manager();

            $employee_rota = EmployeeRota::where([
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ])
            ->where(function($query) use ($all_manager_department_ids) {
                $query->whereIn("employee_rotas.department_id", $all_manager_department_ids)
                ->orWhereHas("user.departments", function($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                });
            })
            ->whereNotIn("employee_rotas.user_id", [auth()->user()->id])
                ->first();
            if (!$employee_rota) {
                return response()->json([
                    "message" => "no rota found"
                ], 404);
            }
            $is_active = !$employee_rota->is_active;




             $employee_rota->update([
                 'is_active' => $is_active
             ]);


             return response()->json(['message' => 'Rota status updated successfully'], 200);
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/employee-rotas",
     *      operationId="getEmployeeRotas",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),

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
     *
     *
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     * @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),

     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),

     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get employee rotas  ",
     *      description="This method is to get employee rotas ",
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

    public function getEmployeeRotas(Request $request)
    {
        try {

            $this->isModuleEnabled("rota");

            if (!$request->user()->hasPermissionTo('employee_rota_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $employee_rotasQuery = EmployeeRota::with("details","department","user")
            ->where([
                "employee_rotas.business_id" => auth()->user()->business_id
            ])



            ->where(function($query) use ($all_manager_department_ids) {
                $query->whereIn("employee_rotas.department_id", $all_manager_department_ids)
                ->orWhereHas("user.departments", function($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                });
            })
            ->where(function($query)  {
                $query->whereNotIn("employee_rotas.user_id", [auth()->user()->id])
                ->orWhereNull("employee_rotas.user_id");
            })





                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_rotas.name", "like", "%" . $term . "%")
                            ->orWhere("employee_rotas.description", "like", "%" . $term . "%");
                    });
                })



                ->when(isset($request->name), function ($query) use ($request) {
                    $term = $request->name;
                    return $query->where("employee_rotas.name", "like", "%" . $term . "%");
                })
                ->when(isset($request->description), function ($query) use ($request) {
                    $term = $request->description;
                    return $query->where("employee_rotas.description", "like", "%" . $term . "%");
                })






                ->when(request()->filled("is_default"), function ($query) use ($request) {
                    return $query->where('employee_rotas.is_default', request()->boolean("is_default"));
                })


              
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_rotas.created_at', ">=", $request->start_date);
                })

                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_rotas.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                $employee_rotas =  $this->retrieveData($employee_rotasQuery, "id", "employee_rotas");
          


                if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                    // if (strtoupper($request->response_type) == 'PDF') {
                    //     $pdf = PDF::loadView('pdf.employee_rotas', ["employee_rotas" => $employee_rotas]);
                    //     return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                    // } elseif (strtoupper($request->response_type) === 'CSV') {

                    //     return Excel::download(new EmployeeRotasExport($employee_rotas), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                    // }
                } else {
                    return response()->json($employee_rotas, 200);
                }


            return response()->json($employee_rotas, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/employee-rotas/{id}",
     *      operationId="getEmployeeRotaById",
     *      tags={"administrator.employee_rota"},
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
     *      summary="This method is to get employee rota by id",
     *      description="This method is to get employee rota by id",
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


    public function getEmployeeRotaById($id, Request $request)
    {
        try {

            $this->isModuleEnabled("rota");


            if (!$request->user()->hasPermissionTo('employee_rota_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $employee_rota =  EmployeeRota::with("details","department","user")
            ->where([
                "id" => $id,
                "employee_rotas.business_id" => auth()->user()->business_id
            ])

            ->where(function($query) use ($all_manager_department_ids) {

                $query->whereIn("employee_rotas.department_id", $all_manager_department_ids)
                ->orWhereHas("user.departments", function($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->orWhereIn("employee_rotas.user_id", [auth()->user()->id]);

            })




                ->first();
            if (empty($employee_rota)) {

                return response()->json([
                    "message" => "no employee rota found"
                ], 404);
            }


            return response()->json($employee_rota, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }

    }


  /**
     *
     * @OA\Get(
     *      path="/v1.0/employee-rotas/get-by-user-id/{user_id}",
     *      operationId="getEmployeeRotaByUserId",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get employee rota by user id",
     *      description="This method is to get employee rota by user id",
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


     public function getEmployeeRotaByUserId($user_id, Request $request)
     {
         try {

             $this->isModuleEnabled("rota");

             if (!$request->user()->hasPermissionTo('employee_rota_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $business_id =  auth()->user()->business_id;

             $all_manager_department_ids = $this->get_all_departments_of_manager();


             $employee_rota =   EmployeeRota::with("details")
            ->where(function($query) use($business_id,$user_id) {
                $query->where([
                    "business_id" => $business_id
                ])->whereHas('users', function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                });
            })
            ->where(function($query) use ($all_manager_department_ids) {

                $query->whereIn("employee_rotas.department_id", $all_manager_department_ids)
                ->orWhereHas("user.departments", function($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->orWhereIn("employee_rotas.user_id", [auth()->user()->id]);

            })



            ->first();



             if (!$employee_rota) {

                 return response()->json([
                     "message" => "no employee rota found for the user"
                 ], 404);
             }

             return response()->json($employee_rota, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/employee-rotas/{ids}",
     *      operationId="deleteEmployeeRotasByIds",
     *      tags={"administrator.employee_rota"},
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
     *      summary="This method is to delete employee rota by id",
     *      description="This method is to delete employee rota by id",
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

    public function deleteEmployeeRotasByIds(Request $request, $ids)
    {

        try {

            $this->isModuleEnabled("rota");

            if (!$request->user()->hasPermissionTo('employee_rota_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = $this->get_all_departments_of_manager();




            $idsArray = explode(',', $ids);
            $existingIds = EmployeeRota::where([
                "business_id" => auth()->user()->business_id,
            ])
            ->where(function($query) use ($all_manager_department_ids) {
                $query
                ->where( function($query) use($all_manager_department_ids) {
                    $query->whereIn("employee_rotas.department_id", $all_manager_department_ids)
                    ->orWhereNull("employee_rotas.department_id");
                })
                ->orWhere( function($query) use($all_manager_department_ids) {
                    $query ->whereHas("user.departments", function($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);

                    })
                    ->orWhereNull("employee_rotas.user_id");
                });


            })
            ->where( function($query) {
                $query->whereNotIn("employee_rotas.user_id", [auth()->user()->id])
                ->orWhereNull("employee_rotas.user_id");
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



            EmployeeRota::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
