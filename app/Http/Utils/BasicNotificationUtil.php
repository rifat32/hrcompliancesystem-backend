<?php

namespace App\Http\Utils;

use App\Models\ActivityLog;
use App\Models\Department;

use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait BasicNotificationUtil
{
    use BasicUtil;
    // this function do all the task and returns transaction id or -1
    public function send_notification($data, $user, $title, $type, $entity_name)
    {

        if ($data instanceof \Illuminate\Support\Collection) {
            // If it's a collection, check if it's empty
            if ($data->isNotEmpty()) {
                // If not empty, take the first element as the entity
                $entity_ids = $data->pluck('id')->toArray();

                $entity = $data->first();

                if ($entity_name == "attendance") {
                    $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
                } else {
                    $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
                }
            } else {
                // Handle the case where the collection is empty
                return; // or do something else, depending on your requirements
            }
        } else {
            // If it's not a collection, it's assumed to be a single entity
            $entity = $data;
            $entity_ids = [$entity->id];
            if ($entity_name == "attendance") {
                $notification_link = ($entity_name) . "/" . ($entity->id);
            }
            $notification_link = ($entity_name) . "/" . ($entity->id);
        }


        $departments = Department::whereHas("users", function ($query) use ($entity) {
            $query->where("users.id", $entity->user_id);
        })
            ->get();

        $notification_description = '';

        if ($type == "create") {
            $notification_description = (explode('_', $entity_name)[0]) . " taken for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "update") {
            $notification_description = (explode('_', $entity_name)[0]) . " updated for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "approved") {
            $notification_description = (explode('_', $entity_name)[0]) . " approved for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "rejected") {
            $notification_description = (explode('_', $entity_name)[0]) . " rejected for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "delete") {
            $notification_description = (explode('_', $entity_name)[0]) . " deleted for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "clocked_in") {
    $notification_description = "User " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name) . " clocked in.";
}
if ($type == "clocked_out") {
    $notification_description = "User " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name) . " clocked out.";
}


        // Perform bulk insertion of notifications
        Notification::create([
                "entity_id" => $entity->id,
                "entity_ids" => json_encode($entity_ids),
                "entity_name" => $entity_name,
                'notification_title' => $title,
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => auth()->user()->id,
                "receiver_id" => optional($user->departments()->first())->manager_id ?? $user->business->owner_id,
                "business_id" => auth()->user()->business_id,
                "is_system_generated" => 1,
                "status" => "unread",
                "created_at" => now(),
                "updated_at" => now(),
                "type" => $type
            ]);
    }




    public function send_notification_for_department($data, $user, $title, $type, $entity_name,  $for_department = 0, $department = NULL)
    {

        if ($data instanceof \Illuminate\Support\Collection) {
            // If it's a collection, check if it's empty
            if ($data->isNotEmpty()) {
                // If not empty, take the first element as the entity
                $entity_ids = $data->pluck('id')->toArray();

                $entity = $data->first();
                $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
            } else {
                // Handle the case where the collection is empty
                return; // or do something else, depending on your requirements
            }
        } else {
            // If it's not a collection, it's assumed to be a single entity
            $entity = $data;
            $entity_ids = [$entity->id];
            $notification_link = "/holiday/holiday-request/?enc_id=" . base64_encode($entity->id);
        }


        $notification_description = '';



        if (!empty($user)) {
            if ($type == "create") {
                $notification_description = (explode('_', $entity_name)[0]) . " taken for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            } else if ($type == "update") {
                $notification_description = (explode('_', $entity_name)[0]) . " updated for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            } else if ($type == "approve") {
                $notification_description = (explode('_', $entity_name)[0]) . " approved for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            } else if ($type == "reject") {
                $notification_description = (explode('_', $entity_name)[0]) . " rejected for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            } else  if ($type == "delete") {
                $notification_description = (explode('_', $entity_name)[0]) . " deleted for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            } else {
                $notification_description = (explode('_', $entity_name)[0]) . " status for the user " . ($user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
        }


        $receiver_id = !empty($user) ? $user->id : auth()->user()->business->owner_id;




        // Perform bulk insertion of notifications
        Notification::create([
            "entity_id" => $entity->id,
            "entity_ids" => json_encode($entity_ids),
            "entity_name" => $entity_name,
            'notification_title' => $title,
            'notification_description' => $notification_description,
            'notification_link' => $notification_link,
            "sender_id" => auth()->user()->id,
            "receiver_id" => $receiver_id,
            "business_id" => auth()->user()->business_id,
            "is_system_generated" => 1,
            "status" => "unread",
            "created_at" => now(),
            "updated_at" => now(),
            "type" => $type,
        ]);
    }





}
