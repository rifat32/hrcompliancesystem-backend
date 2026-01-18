<?php

namespace App\Http\Controllers;

use App\Http\Components\DepartmentComponent;
use App\Http\Requests\NotificationStatusUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;


use App\Models\Notification;
use App\Services\FirebaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;
    protected $departmentComponent;

    protected $firebase;

    public function __construct(DepartmentComponent $departmentComponent, FirebaseService $firebase)
    {

        $this->departmentComponent = $departmentComponent;
        $this->firebase = $firebase;
    }

    /**
     *
     * @OA\Post(
     *      path="/send-push",
     *      operationId="sendPush",
     *      tags={"notifications"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store notification",
     *      description="This method is to store notification",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="user_id", type="string", format="string", example="r"),
     *  * @OA\Property(property="title", type="string", format="string", example="f"),
     *  * @OA\Property(property="body", type="string", format="string", example="g"),
     *  * @OA\Property(property="data", type="string", format="string", example="h")
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

    public function sendPush(Request $request)
    {

        // Convert JSON string to associative array if needed
        $data = $request->input('data');

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // Ensure $data is an associative array
        if (!is_array($data) || array_keys($data) === range(0, count($data) - 1)) {
            return response()->json([
                'success' => false,
                'message' => 'The "data" field must be a JSON object (key-value map).',
            ], 400);
        }

        $responses = $this->firebase->sendNotificationToUser(
            $request->user_id,
            $request->title,
            $request->body,
            $data
        );

        return response()->json([
            'success' => true,
            'responses' => $responses,
        ]);
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/notifications",
     *      operationId="getNotifications",
     *      tags={"notification_management"},
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
     * * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="status"
     * ),
     *
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get notification",
     *      description="This method is to get notification",
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

    public function getNotifications(Request $request)
    {
        try {


$notificationsQuery = Notification::with("sender", "business")->where(
                [
                    "receiver_id" => $request->user()->id
                ]
            )

                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('notifications.status', $request->status);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('notifications.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('notifications.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });
                $data["notifications"] =  $this->retrieveData($notificationsQuery, "id", "notifications");




            $data["total_unread_messages"] = Notification::where('receiver_id', $request->user()->id)->where([
                "status" => "unread"
            ])->count();
            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/notifications/{business_id}/{perPage}",
     *      operationId="getNotificationsByBusinessId",
     *      tags={"notification_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),

     *      summary="This method is to get notification by business id",
     *      description="This method is to get notification by business id",
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

    public function getNotificationsByBusinessId($business_id, $perPage, Request $request)
    {
        try {


            $this->businessOwnerCheck($business_id, FALSE);

            $notificationsQuery = Notification::where([
                "receiver_id" => $request->user()->id,
                "business_id" => $business_id
            ]);

            $notifications = $notificationsQuery->orderByDesc("id")->paginate($perPage);


            $total_data = count($notifications->items());
            for ($i = 0; $i < $total_data; $i++) {

                $notifications->items()[$i]["template_string"] = json_decode($notifications->items()[$i]->template->template);




                if (!empty($notifications->items()[$i]->customer_id)) {
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[customer_name]",


                        ($notifications->items()[$i]->customer->title . " " . $notifications->items()[$i]->customer->first_Name . " " . $notifications->items()[$i]->customer->last_Name),

                        $notifications->items()[$i]["template_string"]
                    );
                }

                if (!empty($notifications->items()[$i]->business_id)) {
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[business_owner_name]",

                        ($notifications->items()[$i]->business->owner->title . " " . $notifications->items()[$i]->business->owner->first_Name . " " . $notifications->items()[$i]->business->owner->last_Name),

                        $notifications->items()[$i]["template_string"]
                    );

                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[business_name]",

                        ($notifications->items()[$i]->business->name),

                        $notifications->items()[$i]["template_string"]
                    );
                }

                if (in_array($notifications->items()[$i]->template->type, ["booking_created_by_client", "booking_accepted_by_client"])) {

                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[Date]",
                        ($notifications->items()[$i]->booking->job_start_date),

                        $notifications->items()[$i]["template_string"]
                    );
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[Time]",
                        ($notifications->items()[$i]->booking->job_start_time),

                        $notifications->items()[$i]["template_string"]
                    );
                }



                $notifications->items()[$i]["link"] = json_decode($notifications->items()[$i]->template->link);



                $notifications->items()[$i]["link"] =  str_replace(
                    "[customer_id]",
                    $notifications->items()[$i]->customer_id,
                    $notifications->items()[$i]["link"]
                );




                $notifications->items()[$i]["link"] =  str_replace(
                    "[business_id]",
                    $notifications->items()[$i]->business_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[bid_id]",
                    $notifications->items()[$i]->bid_id,
                    $notifications->items()[$i]["link"]
                );
            }

            $data = json_decode(json_encode($notifications), true);

            $data["total_unread_messages"] = Notification::where('receiver_id', $request->user()->id)->where([
                "status" => "unread"
            ])->count();
            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }






    /**
     *
     * @OA\Put(
     *      path="/v1.0/notifications/change-status",
     *      operationId="updateNotificationStatus",
     *      tags={"notification_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update notification status",
     *      description="This method is to update notification status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"notification_ids"},
     *    @OA\Property(property="notification_ids", type="string", format="array", example={1,2,3,4,5,6}),

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

    public function updateNotificationStatus(NotificationStatusUpdateRequest $request)
    {
        try {

            return    DB::transaction(function () use (&$request) {

                $updatableData = $request->validated();


                Notification::whereIn('id', $updatableData["notification_ids"])
                    ->where('receiver_id', $request->user()->id)
                    ->update([
                        "status" => "read"
                    ]);



                return response(["ok" => true], 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Delete(
     *      path="/v1.0/notifications/{id}",
     *      operationId="deleteNotificationById",
     *      tags={"notification_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete notification by id",
     *      description="This method is to delete notification by id",
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

    public function deleteNotificationById($id, Request $request)
    {

        try {


            $notification = Notification::where([
                "id" => $id,
                'receiver_id' => $request->user()->id
            ])->first();

            if (!$notification) {

                return response(["message" => "Notification not found"], 404);
            }

            $notification->delete();
            return response(["message" => "Notification deleted"], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
