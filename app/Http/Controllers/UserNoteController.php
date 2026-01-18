<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserNoteCreateRequest;
use App\Http\Requests\UserNoteUpdateByBusinessOwnerRequest;
use App\Http\Requests\UserNoteUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNote;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserNoteController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;






    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-notes",
     *      operationId="createUserNote",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user note",
     *      description="This method is to store user note",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
 * @OA\Property(property="title", type="string", format="string", example="Your title"),
 * @OA\Property(property="description", type="string", format="string", example="Your Major")
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

    public function createUserNote(UserNoteCreateRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_note_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }


                $comment_text = $request_data["description"];

                $pattern = '/@\[.*?\]\((.*?)\)/';
                // $pattern = '/@(\w+)/';
                // Parse comment for mentions
                preg_match_all($pattern, $comment_text, $mentions);


                $mentioned_users = array_map('trim', $mentions[1]);;

                $mentioned_users = User::where('business_id', auth()->user()->business_id)
                    ->whereIn('user_name', $mentioned_users)
                    ->get();

                $request_data["created_by"] = $request->user()->id;




                $user_note =  UserNote::create($request_data);



// Store mentions in user_note_mentions table using createMany
$mentions_data = $mentioned_users->map(function ($mentioned_user) {
    return [
        'user_id' => $mentioned_user->id,
    ];
});

$user_note->mentions()->createMany($mentions_data);

// Prepare notification data for each mentioned user
$notification_data = $mentioned_users->map(function ($mentioned_user) use ($user_note) {
    $notification_description = "You have been mentioned in a note.";
    $notification_link = "http://example.com/notes/{$user_note->id}"; // Dynamic link based on comment ID
    return [
        "entity_id" => $user_note->id,
        "entity_name" => "note",
        'notification_title' => "Note Mention Notification",
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






                return response($user_note, 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-notes",
     *      operationId="updateUserNote",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user note ",
     *      description="This method is to update user note",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
  * @OA\Property(property="title", type="string", format="string", example="Your title"),
 * @OA\Property(property="description", type="string", format="string", example="Your Major")
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

    public function updateUserNote(UserNoteUpdateRequest $request)
    {

        DB::beginTransaction();
        try {


                if (!$request->user()->hasPermissionTo('employee_note_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }

                $request_data["updated_by"] = $request->user()->id;

                $comment_text = $request_data["description"];

                $pattern = '/@\[.*?\]\((.*?)\)/';
                // $pattern = '/@(\w+)/';
                // Parse comment for mentions
                preg_match_all($pattern, $comment_text, $mentions);



                $mentioned_users = array_map('trim', $mentions[1]);;

                $mentioned_users = User::where('business_id', auth()->user()->business_id)
                    ->whereIn('user_name', $mentioned_users)
                    ->get();


                $user_note_query_params = [
                    "id" => $request_data["id"],
                ];


               // Retrieve the first matching UserNote object
$user_note = UserNote::where($user_note_query_params)->first();

if ($user_note) {
    // Fill the UserNote object with the updated data
    $user_note->fill(collect($request_data)->only([
        'user_id',
        'title',
        'description',
        'updated_by'
    ])->toArray());

    if(auth()->user()->hasRole("business_owner")){
  // Update the timestamps explicitly
  if (isset($request_data['created_at'])) {
    $user_note->created_at = Carbon::parse($request_data['created_at']);
}
if (isset($request_data['updated_at'])) {
    $user_note->updated_at = Carbon::parse($request_data['updated_at']);
}
    }


    // Save the updated UserNote object
    $user_note->save();



}

                if (!$user_note) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                $current_mentions = $user_note->mentions;
                $user_note->mentions()->delete();
// Store mentions in user_note_mentions table using createMany
$mentions_data = $mentioned_users->map(function ($mentioned_user) {
    return [
        'user_id' => $mentioned_user->id,
    ];
});


$user_note->mentions()->createMany($mentions_data);

  // Determine old and new mentions
$new_mentions = array_diff($mentions_data->toArray(), $current_mentions->toArray());

  $old_mentions = array_intersect($mentions_data->toArray(), $current_mentions->toArray());

  // Prepare notification data for newly added mentions
  $new_notification_data = $mentioned_users->whereIn('id', $new_mentions)->map(function ($mentioned_user) use ($user_note) {
      $notification_description = "You have been mentioned. The note has been updated.";
      $notification_link = "http://example.com/notes/{$user_note->id}"; // Dynamic link based on comment ID
      return [
          "entity_id" => $user_note->id,
          "entity_name" => "note",
          'notification_title' => "New Mention in Updated Note",
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
 $mentioned_users->whereIn('id', $old_mentions)->map(function ($mentioned_user) use ($user_note) {
      $notification_description = "You have been mentioned. The note has been updated.";
      $notification_link = "http://example.com/notes/{$user_note->id}"; // Dynamic link based on comment ID
      return [
          "entity_id" => $user_note->id,
          "entity_name" => "note",
          'notification_title' => "Re-mention in Updated Note",
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

                DB::commit();
                return response($user_note, 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }

    }

      /**
     *
     * @OA\Put(
     *      path="/v1.0/user-notes/by-business-owner",
     *      operationId="updateUserNoteByBusinessOwner",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user note ",
     *      description="This method is to update user note",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
  * @OA\Property(property="title", type="string", format="string", example="Your title"),
 * @OA\Property(property="description", type="string", format="string", example="Your Major"),
 *  * @OA\Property(property="created_at", type="string", format="string", example="your date"),
 *  * @OA\Property(property="updated_at", type="string", format="string", example="your date")
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

     public function updateUserNoteByBusinessOwner(UserNoteUpdateByBusinessOwnerRequest $request)
     {

         try {

             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('business_owner')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();

                 if(!empty($request_data["user_id"])){
                    $this->touchUserUpdatedAt([$request_data["user_id"]]);
                }

                 $request_data["updated_by"] = $request->user()->id;


                 $comment_text = $request_data["description"];

                 $pattern = '/@\[.*?\]\((.*?)\)/';
            // $pattern = '/@(\w+)/';
            // Parse comment for mentions
            preg_match_all($pattern, $comment_text, $mentions);



            $mentioned_users = array_map('trim', $mentions[1]);;

            $mentioned_users = User::where('business_id', auth()->user()->business_id)
                ->whereIn('user_name', $mentioned_users)
                ->get();
                 $user_note_query_params = [
                     "id" => $request_data["id"],
                 ];
                 // $user_note_prev = UserNote::where($user_note_query_params)
                 //     ->first();
                 // if (!$user_note_prev) {
                 //     return response()->json([
                 //         "message" => "no user note found"
                 //     ], 404);
                 // }

                 UserNote::disableTimestamps();



                 // Update the record
                 UserNote::where($user_note_query_params)->update(
                     collect($request_data)->only([
                         'user_id',
                         'title',

                         'description',
                         'created_at', // If you need to update created_at manually
                         'updated_at', // If you need to update updated_at manually
                         'updated_by'
                     ])->toArray()
                 );



                 // Enable timestamp updates
                 UserNote::enableTimestamps();

                 // Retrieve the updated record
                 $user_note = UserNote::where($user_note_query_params)->first();

                 if (!$user_note) {
                     return response()->json([
                         "message" => "something went wrong."
                     ], 500);
                 }

                $current_mentions = $user_note->mentions();
                 $user_note->mentions()->delete();
                 // Store mentions in user_note_mentions table using createMany
                 $mentions_data = $mentioned_users->map(function ($mentioned_user) {
                     return [
                         'user_id' => $mentioned_user->id,
                     ];
                 });

                 $user_note->mentions()->createMany($mentions_data);

                   // Determine old and new mentions
  $new_mentions = array_diff($mentions_data->toArray(), $current_mentions->toArray());
  $old_mentions = array_intersect($mentions_data->toArray(), $current_mentions->toArray());

  // Prepare notification data for newly added mentions
  $new_notification_data = $mentioned_users->whereIn('id', $new_mentions)->map(function ($mentioned_user) use ($user_note) {
      $notification_description = "You have been mentioned. The note has been updated.";
      $notification_link = "http://example.com/notes/{$user_note->id}"; // Dynamic link based on comment ID
      return [
          "entity_id" => $user_note->id,
          "entity_name" => "note",
          'notification_title' => "New Mention in Updated Note",
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
  $old_notification_data = $mentioned_users->whereIn('id', $old_mentions)->map(function ($mentioned_user) use ($user_note) {
      $notification_description = "You have been mentioned. The note has been updated.";
      $notification_link = "http://example.com/notes/{$user_note->id}"; // Dynamic link based on comment ID
      return [
          "entity_id" => $user_note->id,
          "entity_name" => "note",
          'notification_title' => "Re-mention in Updated Note",
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
//   Notification::insert(array_merge($new_notification_data, $old_notification_data));

                 return response($user_note, 201);
             });
         } catch (Exception $e) {
             return $this->sendError($e);
         }
     }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-notes",
     *      operationId="getUserNotes",
     *      tags={"user_notes"},
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
     *    * *    *  *    * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get user notes  ",
     *      description="This method is to get user notes ",
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

    public function getUserNotes(Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_note_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $user_notesQuery = UserNote::with([
                "creator" => function ($query) {
                    $query->select('users.id',  "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },
                "updater" => function ($query) {
                    $query->select('users.id',   "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
           ->when(request()->boolean("show_my_data"),function ($query) {
                    $query->where('user_notes.user_id', auth()->user()->id);
                }, function($query) use($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })
            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("user_notes.name", "like", "%" . $term . "%");

                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_notes.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_notes.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_notes.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_notes.created_at', "<=", ($request->end_date . ' 23:59:59'));
                });

                 $user_notes =  $this->retrieveData($user_notesQuery, "id", "user_notes");




            return response()->json($user_notes, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-notes/{id}",
     *      operationId="getUserNoteById",
     *      tags={"user_notes"},
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
     *      summary="This method is to get user note by id",
     *      description="This method is to get user note by id",
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


    public function getUserNoteById($id, Request $request)
    {
        try {

            if (!$request->user()->hasPermissionTo('employee_note_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_note =  UserNote::
            with([
                "creator" => function ($query) {
                    $query->select('users.id',   "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },
                "updater" => function ($query) {
                    $query->select('users.id',   "users.title", 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            ->where([
                "id" => $id,
            ])
            ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 });
                //  ->orWhereHas("mentions", function($query) {
                //     $query->where("user_note_mentions.user_id",auth()->user()->id);
                //  });
            })
                ->first();
            if (!$user_note) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_note, 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-notes/{ids}",
     *      operationId="deleteUserNotesByIds",
     *      tags={"user_notes"},
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
     *      summary="This method is to delete user note by id",
     *      description="This method is to delete user note by id",
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

    public function deleteUserNotesByIds(Request $request, $ids)
    {

        try {

            if (!$request->user()->hasPermissionTo('employee_note_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $idsArray = explode(',', $ids);

            $user_ids = User::whereHas("notes",function($query) use($idsArray) {
                $query->whereIn('user_notes.id', $idsArray);
              })
              ->pluck("id");

            $this->touchUserUpdatedAt($user_ids);

            $existingIds = UserNote::
            whereIn('id', $idsArray)
            ->when( !auth()->user()->hasPermissionTo('business_owner'), function($query) {
                $query->where('user_notes.created_by', '=', auth()->user()->id);
            })
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
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
            UserNote::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e);
        }
    }
}
