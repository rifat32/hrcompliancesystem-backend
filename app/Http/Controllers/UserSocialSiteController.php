<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserSocialSiteCreateRequest;
use App\Http\Requests\UserSocialSiteUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\SocialSite;
use App\Models\User;
use App\Models\UserSocialSite;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSocialSiteController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;




      /**
       *
       * @OA\Post(
       *      path="/v1.0/user-social-sites",
       *      operationId="createUserSocialSite",
       *      tags={"user_social_sites"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to store user social site",
       *      description="This method is to store user social site",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  * @OA\Property(property="social_site_id", type="number", format="number", example=1),
     * @OA\Property(property="user_id", type="number", format="number", example=1),
     * @OA\Property(property="profile_link", type="string", format="string", example="https://example.com/profile")
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

      public function createUserSocialSite(UserSocialSiteCreateRequest $request)
      {

          try {


              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_social_site_create')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }

                  $request_data = $request->validated();

                  if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }






                  $request_data["created_by"] = $request->user()->id;


UserSocialSite::where([
    'social_site_id'=> $request_data["social_site_id"],
    'user_id' => $request_data["user_id"] ,
])->delete();

                  $user_social_site =  UserSocialSite::create($request_data);



                  return response($user_social_site, 201);
              });
          } catch (Exception $e) {
              return $this->sendError($e);
          }
      }

      /**
       *
       * @OA\Put(
       *      path="/v1.0/user-social-sites",
       *      operationId="updateUserSocialSite",
       *      tags={"user_social_sites"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to update  user social site ",
       *      description="This method is to update user social site",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
   * @OA\Property(property="social_site_id", type="number", format="number", example=1),
     * @OA\Property(property="user_id", type="number", format="number", example=1),
     * @OA\Property(property="profile_link", type="string", format="string", example="https://example.com/profile")
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

      public function updateUserSocialSite(UserSocialSiteUpdateRequest $request)
      {

          try {

              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_social_site_update')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }

                  $request_data = $request->validated();

            if(!empty($request_data["user_id"])){
                $this->touchUserUpdatedAt([$request_data["user_id"]]);
            }



                  $user_social_site_query_params = [
                      "id" => $request_data["id"]
                  ];
                  // $user_social_site_prev = UserSocialSite::where($user_social_site_query_params)
                  //     ->first();
                  // if (!$user_social_site_prev) {
                  //     return response()->json([
                  //         "message" => "no user social site found"
                  //     ], 404);
                  // }

                  if (empty($request["profile_link"])) {
                    UserSocialSite::where($user_social_site_query_params)->delete();
                    return response(["ok" => true], 201);
                  } else {
                    $user_social_site  =  tap(UserSocialSite::where($user_social_site_query_params))->update(
                        collect($request_data)->only([
                          'social_site_id',
                          'user_id',
                          'profile_link',
                          // "created_by"

                        ])->toArray()
                    )
                        // ->with("somthing")

                        ->first();
                    if (empty($user_social_site)) {
                        return response()->json([
                            "message" => "something went wrong."
                        ], 500);
                    }

                  }


                  return response($user_social_site, 201);
              });
          } catch (Exception $e) {
              return $this->sendError($e);
          }
      }


      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-social-sites",
       *      operationId="getUserSocialSites",
       *      tags={"user_social_sites"},
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

       *      summary="This method is to get user social sites  ",
       *      description="This method is to get user social sites ",
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

      public function getUserSocialSites(Request $request)
      {
          try {


              if (!$request->user()->hasPermissionTo('employee_social_site_view')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $business_id =  auth()->user()->business_id;
              $all_manager_department_ids = $this->get_all_departments_of_manager();

              $social_sitesQuery = SocialSite::where('is_active', 1)
              ->with(['user_social_site' => function ($query) use ($request, $all_manager_department_ids) {
                  $query->when(!empty($request->user_id), function ($query) use ($request, $all_manager_department_ids) {
                    return $query->where('user_social_sites.user_id', $request->user_id)
                    ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids)
                        ->orWhere("users.id",auth()->user()->id);
                     });

                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_social_sites.user_id', $request->user()->id);
                })
                ;
              }])

              ->when(!empty($request->search_key), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                          $term = $request->search_key;
                          $query->where("social_sites.name", "like", "%" . $term . "%");
                          //     ->orWhere("user_social_sites.description", "like", "%" . $term . "%");
                      });
                  })



                  ->when(!empty($request->start_date), function ($query) use ($request) {
                      return $query->where('user_social_sites.created_at', ">=", $request->start_date);
                  })
                  ->when(!empty($request->end_date), function ($query) use ($request) {
                      return $query->where('user_social_sites.created_at', "<=", ($request->end_date . ' 23:59:59'));
                  });

                   $social_sites =  $this->retrieveData($social_sitesQuery, "id", "social_sites");




              return response()->json($social_sites, 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }

      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-social-sites/{id}",
       *      operationId="getUserSocialSiteById",
       *      tags={"user_social_sites"},
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
       *      summary="This method is to get user social site by id",
       *      description="This method is to get user social site by id",
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


      public function getUserSocialSiteById($id, Request $request)
      {
          try {

              if (!$request->user()->hasPermissionTo('employee_social_site_view')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $user_social_site =  UserSocialSite::where([
                  "id" => $id,
              ])
              ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
              ->whereHas("user", function($q) use($request) {
                $q->where("users.business_id", auth()->user()->business_id)
                ->orWhere("users.created_by", $request->user()->id);
            })
                  ->first();
              if (!$user_social_site) {

                  return response()->json([
                      "message" => "no data found"
                  ], 404);
              }

              return response()->json($user_social_site, 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }



      /**
       *
       *     @OA\Delete(
       *      path="/v1.0/user-social-sites/{ids}",
       *      operationId="deleteUserSocialSitesByIds",
       *      tags={"user_social_sites"},
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
       *      summary="This method is to delete user social site by id",
       *      description="This method is to delete user social site by id",
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

      public function deleteUserSocialSitesByIds(Request $request, $ids)
      {

          try {

              if (!$request->user()->hasPermissionTo('employee_social_site_delete')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }

              $all_manager_department_ids = $this->get_all_departments_of_manager();
              $idsArray = explode(',', $ids);

              $user_ids = User::whereHas("social_links",function($query) use($idsArray) {
                $query->whereIn('user_social_sites.id', $idsArray);
              })
              ->pluck("id");
            $this->touchUserUpdatedAt($user_ids);

              $existingIds = UserSocialSite::whereIn("id",$idsArray)
              ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
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
              UserSocialSite::destroy($existingIds);


              return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
          } catch (Exception $e) {

              return $this->sendError($e);
          }
      }
}
