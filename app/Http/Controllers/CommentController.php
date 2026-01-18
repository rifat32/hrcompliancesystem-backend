<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentCreateRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    use ErrorUtil, BusinessUtil, ModuleUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/comments",
     *      operationId="createComment",
     *      tags={"comment"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store comment listing",
     *      description="This method is to store comment listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(


     *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Comment X's objectives and scope."),
     *     @OA\Property(property="attachments", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="status", type="string", format="string", example="open", enum={"open", "closed"}),
     *     @OA\Property(property="priority", type="string", format="string", example="low", enum={"low", "medium", "high"}),
     *     @OA\Property(property="visibility", type="string", format="string", example="public", enum={"public", "private"}),
     *     @OA\Property(property="tags", type="string", format="string", example="tag1,tag2,tag3"),
     *     @OA\Property(property="resolution", type="string", format="string", example="Resolution details"),
     *     @OA\Property(property="feedback", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="hidden_note", type="string", format="string", example="Hidden note details"),
     *
     *     @OA\Property(property="related_task_id", type="integer", format="int64", example=123),
     *     @OA\Property(property="project_id", type="integer", format="int64", example=456),
     *     @OA\Property(property="task_id", type="integer", format="int64", example=456),
     *     @OA\Property(property="parent_comment_id", type="integer", format="int64", example=456),
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

    public function createComment(CommentCreateRequest $request)
    {
        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");



            if (!$request->user()->hasPermissionTo('comment_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();


            $request_data["business_id"] = auth()->user()->business_id;
            $request_data["is_active"] = true;
            $request_data["created_by"] = $request->user()->id;

            $mentions_data = collect($request_data["mentioned_ids"])->map(function ($mentioned_id) {
                return [
                    'user_id' => $mentioned_id,
                ];
            });



            // $comment_text = $request_data["description"];

            // $pattern = '/@\[.*?\]\((.*?)\)/';
            // // $pattern = '/@(\w+)/';
            // // Parse comment for mentions
            // preg_match_all($pattern, $comment_text, $mentions);

            // $mentioned_users = array_map('trim', $mentions[1]);;

            // $mentioned_users = User::where('business_id', auth()->user()->business_id)
            //     ->whereIn('user_name', $mentioned_users)
            //     ->get();



            $comment =  Comment::create($request_data);



            // // Store mentions in user_note_mentions table using createMany
            // $mentions_data = $mentioned_users->map(function ($mentioned_user) {
            //     return [
            //         'user_id' => $mentioned_user->id,
            //     ];
            // });



            $comment->mentions()->createMany($mentions_data);




            // Prepare notification data for each mentioned user
            $notification_data = User::whereIn("id",$mentions_data->pluck("user_id")->toArray())->get(["id"])->map(function ($mentioned_user) use ($comment) {
                $notification_description = "You have been mentioned in a comment.";
                $notification_link = "http://example.com/comments/{$comment->id}"; // Dynamic link based on comment ID
                return [
                    "entity_id" => $comment->id,
                    "entity_ids" => json_encode([$comment->task_id,$comment->id]),
                    "entity_name" => "comment",
                    'notification_title' => "Comment Mention Notification",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id, // Assuming you have a variable for the mentioner's ID
                    "receiver_id" => $mentioned_user->id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            })->toArray();

            Notification::insert($notification_data);


            DB::commit();

            return response($comment, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/comments",
     *      operationId="updateComment",
     *      tags={"comment"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update comment listing ",
     *      description="This method is to update comment listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Comment X's objectives and scope."),
     *     @OA\Property(property="attachments", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="status", type="string", format="string", example="open", enum={"open", "closed"}),
     *     @OA\Property(property="priority", type="string", format="string", example="low", enum={"low", "medium", "high"}),
     *     @OA\Property(property="visibility", type="string", format="string", example="public", enum={"public", "private"}),
     *     @OA\Property(property="tags", type="string", format="string", example="tag1,tag2,tag3"),
     *     @OA\Property(property="resolution", type="string", format="string", example="Resolution details"),
     *     @OA\Property(property="feedback", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="hidden_note", type="string", format="string", example="Hidden note details"),
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

    public function updateComment(CommentUpdateRequest $request)
    {

        DB::beginTransaction();
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('comment_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $request_data = $request->validated();


            $mentions_data = collect($request_data["mentioned_ids"])->map(function ($mentioned_id) {
                return [
                    'user_id' => $mentioned_id,
                ];
            });

            $comment_query_params = [
                "id" => $request_data["id"],
                // "business_id" => $business_id
            ];
            // $comment_prev = Comment::where($comment_query_params)
            //     ->first();
            // if (!$comment_prev) {
            //     return response()->json([
            //         "message" => "no comment listing found"
            //     ], 404);
            // }

            $comment  =  tap(Comment::where($comment_query_params))->update(
                collect($request_data)->only([
                    'description',
                    'attachments',
                    'status',
                    'priority',
                    'visibility',
                    'tags',
                    'resolution',
                    'feedback',
                    // 'hidden_note',
                    // 'related_task_id',
                    // 'task_id',

                ])->toArray()
            )
                // ->with("somthing")

                ->first();
            if (!$comment) {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }
            if ($comment->created_by == auth()->user()->created_by) {
                $comment->hidden_note = $request_data["hidden_note"];
                $comment->save();
            }

            // $comment_text = $comment->description;
            // $pattern = '/@\[.*?\]\((.*?)\)/';
            // // $pattern = '/@(\w+)/';
            // // Parse comment for mentions
            // preg_match_all($pattern, $comment_text, $mentions);



            // $mentioned_users = array_map('trim', $mentions[1]);;


            // $mentioned_users = User::where('business_id', auth()->user()->business_id)
            //     ->whereIn('user_name', $mentioned_users)
            //     ->get();

            // Store mentions in user_note_mentions table using createMany





            // Fetch current mentions
            $current_mentions = $comment->mentions()->pluck('user_id')->toArray();

            // // Store mentions in user_note_mentions table and sync with new mentions
            // $mentions_data = $mentioned_users->map(function ($mentioned_user) {
            //     return [
            //         'user_id' => $mentioned_user->id,
            //     ];
            // });
            $comment->mentions()->delete();
            $comment->mentions()->createMany($mentions_data);

            // Determine old and new mentions
            $new_mentions = array_diff($mentions_data->pluck("user_id")->toArray(), $current_mentions);
            $old_mentions = array_intersect($mentions_data->pluck("user_id")->toArray(), $current_mentions);

            // Prepare notification data for newly added mentions
            $new_notification_data = User::whereIn('id', $new_mentions)->get(["id"])->map(function ($mentioned_user) use ($comment) {
                $notification_description = "You have been mentioned. The comment has been updated.";
                $notification_link = "http://example.com/comments/{$comment->id}"; // Dynamic link based on comment ID
                return [
                    "entity_id" => $comment->id,
                    "entity_ids" => json_encode([$comment->task_id, $comment->id]),
                    "entity_name" => "comment",
                    'notification_title' => "New Mention in Updated Comment",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id,
                    "receiver_id" => $mentioned_user->id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            })->toArray();

            // Prepare notification data for old mentions
            $old_notification_data = User::whereIn('id', $old_mentions)->get(["id"])->map(function ($mentioned_user) use ($comment) {
                $notification_description = "You have been mentioned. The comment has been updated.";
                $notification_link = "http://example.com/comments/{$comment->id}"; // Dynamic link based on comment ID
                return [
                    "entity_id" => $comment->id,
                    "entity_ids" => json_encode([$comment->task_id, $comment->id]),
                    "entity_name" => "comment",
                    'notification_title' => "Re-mention in Updated Comment",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id,
                    "receiver_id" => $mentioned_user->id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            })->toArray();

            // Insert notifications into the database
            Notification::insert(array_merge($new_notification_data, $old_notification_data));




            DB::commit();
            return response($comment, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/comments",
     *      operationId="getComments",
     *      tags={"comment"},
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
     *    @OA\Parameter(
     *         name="task_id",
     *         in="query",
     *         description="task_id",
     *         required=true,
     *  example="1"
     *      ),
     *      *    @OA\Parameter(
     *         name="project_id",
     *         in="query",
     *         description="project_id",
     *         required=true,
     *  example="1"
     *      ),
     *
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

     *      summary="This method is to get comment listings  ",
     *      description="This method is to get comment listings ",
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

    public function getComments(Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('comment_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $commentsQuery = Comment::with([
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },


                "mentioned_users" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "recursiveChildren"




            ])
            ->whereNull("parent_comment_id")
                ->whereHas(
                    "creator",
                    function ($query) {
                        $query->where("users.business_id", auth()->user()->business_id);
                    }
                )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("location", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->task_id), function ($query) use ($request) {
                    return $query->where('task_id', $request->task_id);
                })
                ->when(!empty($request->project_id), function ($query) use ($request) {
                    return $query->where('project_id', $request->project_id);
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

 $comments =  $this->retrieveData($commentsQuery, "id", "comments");





            return response()->json($comments, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/comments/{id}",
     *      operationId="getCommentById",
     *      tags={"comment"},
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
     *      summary="This method is to get comment listing by id",
     *      description="This method is to get comment listing by id",
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


    public function getCommentById($id, Request $request)
    {
        try {

            $this->isModuleEnabled("task_management");


            if (!$request->user()->hasPermissionTo('comment_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $comment =  Comment::with(
                [
                    "creator" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },

                    "mentioned_users" => function ($query) {
                        $query->select(
                            'users.id',
                            'users.title',
                            'users.first_Name',
                            'users.middle_Name',
                            'users.last_Name'
                        );
                    },
                    "recursiveChildren"

                     ]

                )
                ->where([
                    "id" => $id,
                    "business_id" => $business_id
                ])
                ->select(
                    'comments.*'
                )
                ->first();
            if (!$comment) {

                return response()->json([
                    "message" => "no comment listing found"
                ], 404);
            }

            return response()->json($comment, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/comments/{ids}",
     *      operationId="deleteCommentsByIds",
     *      tags={"comment"},
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
     *      summary="This method is to delete comment listing by id",
     *      description="This method is to delete comment listing by id",
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

    public function deleteCommentsByIds(Request $request, $ids)
    {

        try {

            $this->isModuleEnabled("task_management");

            if (!$request->user()->hasPermissionTo('comment_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Comment::
                where("created_by", auth()->user()->id)
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

            Comment::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
