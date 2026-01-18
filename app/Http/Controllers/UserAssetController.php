<?php

namespace App\Http\Controllers;

use App\Exports\UserAssetsExport;
use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserAssetAddExistingRequest;
use App\Http\Requests\UserAssetCreateRequest;
use App\Http\Requests\UserAssetReturnRequest;
use App\Http\Requests\UserAssetUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\User;
use App\Models\UserAsset;
use App\Models\UserAssetHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class UserAssetController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;






      /**
       *
       * @OA\Post(
       *      path="/v1.0/user-assets",
       *      operationId="createUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to store user document",
       *      description="This method is to store user document",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
*     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="name", type="string", format="string", example="Your Name"),
 *     @OA\Property(property="code", type="string", format="string", example="Your Code"),
 *     @OA\Property(property="is_working", type="boolean", format="boolean", example="1"),
 *  *     @OA\Property(property="status", type="string", format="string", example="status"),
 *
 *     @OA\Property(property="serial_number", type="string", format="string", example="Your Serial Number"),
 *     @OA\Property(property="type", type="string", format="string", example="Your Type"),
 *     @OA\Property(property="image", type="string", format="string", example="Your Image URL"),
 *     @OA\Property(property="date", type="string", format="string", example="Your Date"),
 *     @OA\Property(property="note", type="string", format="string", example="Your Note"),
 *
   *
   *
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

      public function createUserAsset(UserAssetCreateRequest $request)
      {
        DB::beginTransaction();
          try {


                  if (!$request->user()->hasPermissionTo('employee_asset_create')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }

                  $request_data = $request->validated();
                  $this->touchUserUpdatedAt([$request_data["user_id"]]);

                  if(!empty($request_data["image"])) {
                    $request_data["image"]= $this->storeUploadedFiles([$request_data["image"]],"","assets")[0];
                    $this->makeFilePermanent($request_data["image"],"");
                  }



                  $request_data["created_by"] = $request->user()->id;
                  $request_data["business_id"] = auth()->user()->business_id;


                  if(empty($request_data["user_id"])) {
                    $request_data["status"] = "available";
                  } else {
                    $request_data["status"] = "assigned";
                  }


                  $user_asset =  UserAsset::create($request_data);



                  $user_asset_history  =  UserAssetHistory::create([
                    'user_id' => $user_asset->user_id,
                    "user_asset_id" => $user_asset->id,

        'name' => $user_asset->name,
        'code' => $user_asset->code,
        'serial_number' => $user_asset->serial_number,
        'type' => $user_asset->type,
        "is_working" => $user_asset->is_working,
        "status" => $user_asset->status,
        'image' => $user_asset->image,
        'date' => $user_asset->date,
        'note' => $user_asset->note,
        "business_id" => $user_asset->business_id,
                    "from_date" => now(),
                    "to_date" => NULL,
                    'created_by' => $request_data["created_by"]

                  ]
                  );


                  if($user_asset->status == "returned") {
                    $user_asset->user_id = NULL;
                    $user_asset->status = "";
                    $user_asset->save();
                  }



                //   $this->moveUploadedFiles($request_data["image"],"assets");


    DB::commit();
                  return response($user_asset, 201);




          } catch (Exception $e) {


            try {
                if(!empty($request_data["image"])) {

                    $this->moveUploadedFilesBack([$request_data["image"]],"","assets");
                       }
            } catch (Exception $innerException) {

            }




    DB::rollBack();


              return $this->sendError($e);
          }
      }

       /**
       *
       * @OA\Put(
       *      path="/v1.0/user-assets/add-existing",
       *      operationId="addExistingUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to add existing  user asset ",
       *      description="This method is to add existing  user asset",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="1"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1)
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

       public function addExistingUserAsset(UserAssetAddExistingRequest $request)
       {

           try {

               return DB::transaction(function () use ($request) {
                   if (!$request->user()->hasPermissionTo('employee_asset_update')) {
                       return response()->json([
                           "message" => "You can not perform this action"
                       ], 401);
                   }

                   $request_data = $request->validated();
                   $this->touchUserUpdatedAt([$request_data["user_id"]]);

                   $request_data["status"] = "assigned";


                   $user_asset_query_params = [
                       "id" => $request_data["id"],
                   ];
                   $user_asset_prev = UserAsset::where($user_asset_query_params)
                       ->first();
                   if (!$user_asset_prev) {

                       return response()->json([
                           "message" => "no user document found"
                       ], 404);
                   }


                   $user_asset  =  tap(UserAsset::where($user_asset_query_params))->update(
                       collect($request_data)->only([
                            'user_id',
"status"

                       ])->toArray()
                   )
                       // ->with("somthing")

                       ->first();
                   if (!$user_asset) {
                       return response()->json([
                           "message" => "something went wrong."
                       ], 500);
                   }

                   if($user_asset_prev->user_id != $user_asset->user_id) {
                    UserAssetHistory::where([
                        'user_id' => $user_asset_prev->user_id,
                        "user_asset_id" => $user_asset_prev->id,
                        "to_date" => NULL
                    ])
                    ->update([
                        "to_date" => now(),
                    ]);


                        $user_asset_history  =  UserAssetHistory::create([
                            'user_id' => $user_asset->user_id,
                            "user_asset_id" => $user_asset->id,

                            'name' => $user_asset->name,
                            'code' => $user_asset->code,
                            'serial_number' => $user_asset->serial_number,
                            'type' => $user_asset->type,
                            "is_working" => $user_asset->is_working,
                            "status" => $user_asset->status,
                            'image' => $user_asset->image,
                            'date' => $user_asset->date,
                            'note' => $user_asset->note,
                            "business_id" => $user_asset->business_id,

                            "from_date" => now(),
                            "to_date" => NULL,
                            'created_by' => $user_asset->created_by

                          ]
                          );


                   }

                   return response($user_asset, 201);
               });
           } catch (Exception $e) {

               return $this->sendError($e);
           }
       }


      /**
       *
       * @OA\Put(
       *      path="/v1.0/user-assets",
       *      operationId="updateUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to update  user document ",
       *      description="This method is to update user document",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="name", type="string", format="string", example="Your Name"),
 *     @OA\Property(property="code", type="string", format="string", example="Your Code"),
 *
 *  *     @OA\Property(property="is_working", type="boolean", format="boolean", example="1"),
 *  *  *     @OA\Property(property="status", type="string", format="string", example="status"),
 *
 *     @OA\Property(property="serial_number", type="string", format="string", example="Your Serial Number"),
 *     @OA\Property(property="type", type="string", format="string", example="Your Type"),
 *     @OA\Property(property="image", type="string", format="string", example="Your Image URL"),
 *     @OA\Property(property="date", type="string", format="string", example="Your Date"),
 *     @OA\Property(property="note", type="string", format="string", example="Your Note"),
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

      public function updateUserAsset(UserAssetUpdateRequest $request)
      {

          try {

              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_asset_update')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }

                  $request_data = $request->validated();
                  $this->touchUserUpdatedAt([$request_data["user_id"]]);


                  if(!empty($request_data["image"])) {
                    $request_data["image"]= $this->storeUploadedFiles([$request_data["image"]],"","assets")[0];
                    $this->makeFilePermanent($request_data["image"],"");
                  }

                  if($request_data["status"] == "returned") {
                    $request_data["user_id"] = NULL;
                  }




                  $user_asset_query_params = [
                      "id" => $request_data["id"],
                  ];
                  $user_asset_prev = UserAsset::where($user_asset_query_params)
                      ->first();
                  if (!$user_asset_prev) {

                      return response()->json([
                          "message" => "no user asset found"
                      ], 404);
                  }

                  $user_asset  =  tap(UserAsset::where($user_asset_query_params))->update(
                      collect($request_data)->only([
                           'user_id',
                          'name',
                          'code',
                          'serial_number',
                          'is_working',
                          "status",
                          'type',
                          'image',
                          'date',
                          'note',
                          // 'created_by',

                      ])->toArray()
                  )
                      // ->with("somthing")

                      ->first();
                  if (!$user_asset) {
                      return response()->json([
                          "message" => "something went wrong."
                      ], 500);
                  }
                  if($user_asset_prev->user_id != $user_asset->user_id) {

                    $user_asset->status = "assigned";
                    $user_asset->save();


                    UserAssetHistory::where([
                        'user_id' => $user_asset_prev->user_id,
                        "user_asset_id" => $user_asset_prev->id,
                        "to_date" => NULL
                    ])
                    ->update([
                        "to_date" => now(),
                        "status" => "returned",
                    ]);

                   }


                   $user_asset_history  =  UserAssetHistory::create([
                    'user_id' => $user_asset->user_id,
                    "user_asset_id" => $user_asset->id,

                    'name' => $user_asset->name,
                    'code' => $user_asset->code,
                    'serial_number' => $user_asset->serial_number,
                    'type' => $user_asset->type,
                    "is_working" => $user_asset->is_working,
                    "status" => $user_asset->status,
                    'image' => $user_asset->image,
                    'date' => $user_asset->date,
                    'note' => $user_asset->note,
                    "business_id" => $user_asset->business_id,

                    "from_date" => now(),
                    "to_date" => NULL,
                    'created_by' => $user_asset->created_by

                  ]);
                //    $this->moveUploadedFiles($request_data["image"],"assets");
                  return response($user_asset, 201);
              });
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }
   /**
       *
       * @OA\Put(
       *      path="/v1.0/user-assets/return",
       *      operationId="returnUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to update  user document ",
       *      description="This method is to update user document",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1),

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

       public function returnUserAsset(UserAssetReturnRequest $request)
       {

           try {

               return DB::transaction(function () use ($request) {
                   if (!$request->user()->hasPermissionTo('employee_asset_update')) {
                       return response()->json([
                           "message" => "You can not perform this action"
                       ], 401);
                   }

                   $request_data = $request->validated();
          $this->touchUserUpdatedAt([$request_data["user_id"]]);




                   $user_asset_query_params = [
                       "id" => $request_data["id"],
                       "user_id" => $request_data["user_id"],
                       "business_id" => auth()->user()->business_id
                   ];

                   $user_asset  =  UserAsset::where($user_asset_query_params)

                       // ->with("somthing")
                       ->first();

                   if (empty($user_asset)) {
                       return response()->json([
                           "message" => "something went wrong."
                       ], 500);
                   }
                   $user_asset->user_id = NULL;
                   $user_asset->status = "available";
                   $user_asset->save();

                     UserAssetHistory::where([
                         'user_id' => $request_data["user_id"],
                         "user_asset_id" => $request_data["id"],
                         "to_date" => NULL
                     ])
                     ->update([
                         "to_date" => now(),
                         "status" => "returned",
                     ]);






                   return response($user_asset, 201);
               });
           } catch (Exception $e) {
               return $this->sendError($e);
           }
       }


      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-assets",
       *      operationId="get",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *              @OA\Parameter(
       *         name="user_id",
       *         in="query",
       *         description="user_id",
       *         required=true,
       *  example="1"
       *      ),
       *        @OA\Parameter(
       *         name="not_in_user_id",
       *         in="query",
       *         description="not_in_user_id",
       *         required=true,
       *  example="1"
       *      ),
       *   *   *   *              @OA\Parameter(
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
       *
       *    @OA\Parameter(
       *         name="type",
       *         in="query",
       *         description="type",
       *         required=true,
       *  example="1"
       *      ),
       *   *    @OA\Parameter(
       *         name="name",
       *         in="query",
       *         description="name",
       *         required=true,
       *  example="name"
       *      ),
       *
       *        *   *    @OA\Parameter(
       *         name="asset_code",
       *         in="query",
       *         description="asset_code",
       *         required=true,
       *  example="asset_code"
       *      ),
       *       *        *   *    @OA\Parameter(
       *         name="serial_no",
       *         in="query",
       *         description="serial_no",
       *         required=true,
       *  example="serial_no"
       *      ),
       *        *       *        *   *    @OA\Parameter(
       *         name="is_working",
       *         in="query",
       *         description="is_working",
       *         required=true,
       *  example="is_working"
       *      ),
       *
       *      @OA\Parameter(
       *         name="date",
       *         in="query",
       *         description="date",
       *         required=true,
       *  example="date"
       *      ),
       *

       *
       *
       *    @OA\Parameter(
       *         name="status",
       *         in="query",
       *         description="status",
       *         required=true,
       *         example="status"
       *      ),
       *
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

       *      summary="This method is to get user assets  ",
       *      description="This method is to get user assets ",
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

      public function getUserAssets(Request $request)
      {
          try {


              if (!$request->user()->hasPermissionTo('employee_asset_view')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }
              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $user_assetsQuery = UserAsset::with([
                  "creator" => function ($query) {
                      $query->select('users.id', "users.title", 'users.first_Name','users.middle_Name',
                      'users.last_Name');
                  },
                  "user" => function ($query) {
                    $query->select('users.id', "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },


              ])
              ->where([
                "business_id" => auth()->user()->business_id
              ])


              ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->orWhere('user_assets.user_id', NULL)
                 ->orWhereHas("user", function($query)  {
                    $query->where("users.id",auth()->user()->id);
                 })
                 ;

              })
              ->when(!empty($request->search_key), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                          $term = $request->search_key;
                          $query->where("user_assets.name", "like", "%" . $term . "%");
                          $query->orWhere("user_assets.code", "like", "%" . $term . "%");
                          $query->orWhere("user_assets.serial_number", "like", "%" . $term . "%");

                          //     ->orWhere("user_assets.description", "like", "%" . $term . "%");
                      });
                  })

                  ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->name;
                        $query->where("user_assets.name", "like", "%" . $term . "%");

                    });
                })

                ->when(!empty($request->asset_code), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->asset_code;
                        $query->where("user_assets.code", "like", "%" . $term . "%");

                    });
                })
                ->when(!empty($request->serial_number), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->serial_number;
                        $query->where("user_assets.serial_number", "like", "%" . $term . "%");

                    });
                })

                ->when(isset($request->is_working), function ($query) use ($request) {
                    return $query->where('user_assets.is_working', intval($request->is_working));
                })


                ->when(!empty($request->date), function ($query) use ($request) {
                    return $query->where('user_assets.date', $request->date);
                })

                  ->when(request()->filled("user_id"), function ($query) {
                    $idsArray = explode(',', request()->input("user_id"));
                      return $query->whereIn('user_assets.user_id', $idsArray);
                  })

                  ->when(!empty($request->not_in_user_id), function ($query) use ($request) {
                    return $query->where(function($query) use($request) {
                        $query->whereNotIn('user_assets.user_id', [$request->not_in_user_id])
                        ->orWhereNull('user_assets.user_id');
                    });


                })


                  ->when(!empty($request->type), function ($query) use ($request) {
                    return $query->where('user_assets.type', $request->type);
                })

                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('user_assets.status', $request->status);
                })


                  ->when(!empty($request->start_date), function ($query) use ($request) {
                      return $query->where('user_assets.date', ">=", $request->start_date);
                  })
                  ->when(!empty($request->end_date), function ($query) use ($request) {
                      return $query->where('user_assets.date', "<=", ($request->end_date . ' 23:59:59'));
                  });

                   $user_assets =  $this->retrieveData($user_assetsQuery, "id", "user_assets");


                  if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                    if (strtoupper($request->response_type) == 'PDF') {

                        if(empty($user_assets->count())) {
                            $pdf = PDF::loadView('pdf.no_data', []);
                        }else {
                            $pdf = PDF::loadView('pdf.user_assets', ["user_assets" => $user_assets]);
                        }

                        return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                    } elseif (strtoupper($request->response_type) === 'CSV') {

                        return Excel::download(new UserAssetsExport($user_assets), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                    }
                } else {
                    return response()->json($user_assets, 200);
                }


              return response()->json($user_assets, 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }

      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-assets/{id}",
       *      operationId="getUserAssetById",
       *      tags={"user_assets"},
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
       *      summary="This method is to get user document by id",
       *      description="This method is to get user document by id",
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


      public function getUserAssetById($id, Request $request)
      {
          try {

              if (!$request->user()->hasPermissionTo('employee_asset_view')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $user_asset =  UserAsset::where([
                  "id" => $id,
                  "business_id" => auth()->user()->business_id
              ])
              ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->orWhere('user_assets.user_id', NULL)
                 ;

              })
                  ->first();
              if (!$user_asset) {

                  return response()->json([
                      "message" => "no data found"
                  ], 404);
              }



                // if(!empty($user_asset->user->departments[0])){
                //     if(!in_array($user_asset->user->departments[0]->id,$all_manager_department_ids)){
                //         return response()->json([
                //             "message" => "The use assigned is not in your department"
                //         ], 409);
                //     }
                // } else {
                //     return response()->json([
                //         "message" => "The use assigned don't have a department"
                //     ], 409);
                // }






              return response()->json($user_asset, 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }



      /**
       *
       *     @OA\Delete(
       *      path="/v1.0/user-assets/{ids}",
       *      operationId="deleteUserAssetsByIds",
       *      tags={"user_assets"},
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
       *      summary="This method is to delete user document by id",
       *      description="This method is to delete user document by id",
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

      public function deleteUserAssetsByIds(Request $request, $ids)
      {

          try {

              if (!$request->user()->hasPermissionTo('employee_asset_delete')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $idsArray = explode(',', $ids);


            $user_ids = User::whereHas("assets",function($query) use($idsArray) {
                $query->whereIn('user_assets.id', $idsArray);
              })
              ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);



              $userAssets = UserAsset::whereIn('id', $idsArray)
              ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->orWhere('user_assets.user_id', NULL);
              })
             ->where([
                "business_id" => auth()->user()->business_id
              ])
                  ->select('id')
                  ->get();

            $canDeleteAssetIds = $userAssets->filter(function ($asset) {
                    return $asset->can_delete;
                })->pluck('id')->toArray();
              $nonExistingIds = array_diff($idsArray, $canDeleteAssetIds);

              if (!empty($nonExistingIds)) {

                  return response()->json([
                      "message" => "Some or all of the specified data do not exist."
                  ], 404);
              }

            UserAsset::destroy($canDeleteAssetIds);


            UserAssetHistory::where([
                "to_date" => NULL
            ])
            ->whereIn("user_asset_id",$canDeleteAssetIds)
            ->update([
                "to_date" => now(),
            ]);





              return response()->json(["message" => "data deleted sussfully","deleted_ids" => $canDeleteAssetIds], 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }
}
