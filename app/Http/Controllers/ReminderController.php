<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReminderCreateRequest;
use App\Http\Requests\ReminderUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Reminder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    use ErrorUtil, BusinessUtil;



    /**
     *
     * @OA\Post(
     *      path="/v1.0/reminders",
     *      operationId="createReminder",
     *      tags={"reminders"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store reminder",
     *      description="This method is to store reminder",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *
     *
 *     @OA\Property(property="title", type="string", format="string", example="Your Title"),
 *     @OA\Property(property="entity_name", type="string", format="string", example="Your Entity Name"),
 *     @OA\Property(property="duration", type="integer", format="int", example=10),
 *     @OA\Property(property="duration_unit", type="string", format="string", example="days", enum={"days", "weeks", "months"}),
 *     @OA\Property(property="send_time", type="string", format="string", example="before_expiry", enum={"before_expiry", "after_expiry"}),
 *     @OA\Property(property="frequency_after_first_reminder", type="integer", format="int", example=2),
 *  * *     @OA\Property(property="reminder_limit", type="integer", format="int", example=2),
 *     @OA\Property(property="keep_sending_until_update", type="boolean", format="boolean", example=true)
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

    public function createReminder(ReminderCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('reminder_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $reminder_options = config("setup-config.reminder_options");

                $reminder_option = collect($reminder_options)->first(function ($item) use ($request_data) {
                    return $item['entity_name'] === $request_data["entity_name"];
                });

                if(empty($reminder_option)) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["entity_name"=>["Invalid Entity Name."]]
                 ];
                    throw new Exception(json_encode($error),422);
                }


                $request_data["model_name"] = $reminder_option["model_name"];
                $request_data["issue_date_column"] = $reminder_option["issue_date_column"];
                $request_data["expiry_date_column"] = $reminder_option["expiry_date_column"];
                $request_data["user_eligible_field"] = $reminder_option["user_eligible_field"];
                $request_data["user_relationship"] = $reminder_option["user_relationship"];



                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;

                $reminder =  Reminder::create($request_data);



                return response($reminder, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/reminders",
     *      operationId="updateReminder",
     *      tags={"reminders"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update reminder ",
     *      description="This method is to update reminder",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 *     @OA\Property(property="title", type="string", format="string", example="Your Title"),
 *     @OA\Property(property="entity_name", type="string", format="string", example="Your Entity Name"),
 *     @OA\Property(property="duration", type="integer", format="int", example=10),
 *     @OA\Property(property="duration_unit", type="string", format="string", example="days", enum={"days", "weeks", "months"}),
 *     @OA\Property(property="send_time", type="string", format="string", example="before_expiry", enum={"before_expiry", "after_expiry"}),
 *     @OA\Property(property="frequency_after_first_reminder", type="integer", format="int", example=2),
 * *     @OA\Property(property="reminder_limit", type="integer", format="int", example=2),
 *
 *     @OA\Property(property="keep_sending_until_update", type="boolean", format="boolean", example=true)

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

    public function updateReminder(ReminderUpdateRequest $request)
    {

        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('reminder_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();

                $reminder_options = config("setup-config.reminder_options");

                $reminder_option = collect($reminder_options)->first(function ($item) use ($request_data) {
                    return $item['entity_name'] === $request_data["entity_name"];
                });

                if(empty($reminder_option)) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["entity_name"=>["Invalid Entity Name."]]
                 ];
                    throw new Exception(json_encode($error),422);
                }


                $request_data["model_name"] = $reminder_option["model_name"];
                $request_data["issue_date_column"] = $reminder_option["issue_date_column"];
                $request_data["expiry_date_column"] = $reminder_option["expiry_date_column"];
                $request_data["user_eligible_field"] = $reminder_option["user_eligible_field"];
                $request_data["user_relationship"] = $reminder_option["user_relationship"];



                $reminder_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                // $reminder_prev = Reminder::where($reminder_query_params)
                //     ->first();
                // if (!$reminder_prev) {
                //     return response()->json([
                //         "message" => "no reminder found"
                //     ], 404);
                // }

                $reminder  =  tap(Reminder::where($reminder_query_params))->update(
                    collect($request_data)->only([
                        'title',
                        'model_name',
                        "issue_date_column",
                        'expiry_date_column',
                        "user_eligible_field",
                        "user_relationship",
                        'duration',
                        'duration_unit',
                        'send_time',
                        'frequency_after_first_reminder',
                        'reminder_limit',
                        'keep_sending_until_update',
                        'entity_name',
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$reminder) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($reminder, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
 /**
     *
     * @OA\Get(
     *      path="/v1.0/reminders-entity-names",
     *      operationId="getReminderEntityNames",
     *      tags={"reminders"},
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

     public function getReminderEntityNames(Request $request)
     {
         try {

             if (!$request->user()->hasPermissionTo('reminder_create')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $reminder_options = config("setup-config.reminder_options");

             $reminder_entity_names = collect($reminder_options)->pluck("entity_name")->toArray();


             return response()->json($reminder_entity_names, 200);
         } catch (Exception $e) {

             return $this->sendError($e);
         }
     }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/reminders",
     *      operationId="getReminders",
     *      tags={"reminders"},
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

    public function getReminders(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('reminder_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $remindersQuery = Reminder::where(
                [
                    "reminders.business_id" => $business_id
                ]
            )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("reminders.name", "like", "%" . $term . "%")
                        //     ->orWhere("reminders.description", "like", "%" . $term . "%");
                    });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('reminders.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('reminders.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $reminders =  $this->retrieveData($remindersQuery, "id", "reminders");



            return response()->json($reminders, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/reminders/{id}",
     *      operationId="getReminderById",
     *      tags={"reminders"},
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
     *      summary="This method is to get reminder by id",
     *      description="This method is to get reminder by id",
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


    public function getReminderById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('reminder_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $reminder =  Reminder::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$reminder) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($reminder, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/reminders/{ids}",
     *      operationId="deleteRemindersByIds",
     *      tags={"reminders"},
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
     *      summary="This method is to delete reminder by id",
     *      description="This method is to delete reminder by id",
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

    public function deleteRemindersByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('reminder_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Reminder::where([
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
            Reminder::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
