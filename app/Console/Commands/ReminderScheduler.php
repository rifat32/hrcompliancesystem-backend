<?php

namespace App\Console\Commands;

use App\Http\Utils\BasicUtil;
use App\Mail\DocumentExpiryReminder;
use App\Models\Business;
use App\Models\Department;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class ReminderScheduler extends Command
{
    use BasicUtil;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send reminder';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */


    public function sendNotification($reminder, $data, $business)
    {
        $logFile = storage_path('logs/document_reminder.log');
        $logHandle = fopen($logFile, 'a');
        fwrite($logHandle, "Sending notification" . now() . PHP_EOL);

        $user = User::where([
            "id" => $data->user_id
        ])
            ->first();




        $expiry_date_column = $reminder->expiry_date_column;

        $now = now();
        $days_difference = $now->diffInDays($data->$expiry_date_column);

        if ($reminder->send_time == "after_expiry") {

            $notification_description =   (explode('_', $reminder->entity_name)[0]) . " expired " . (abs($days_difference)) . " days ago. Please renew it now.";
            $notification_link = ($reminder->entity_name) . "/" . ($data->id);
        } else {
            $notification_description =    (explode('_', $reminder->entity_name)[0]) .  "will expire in " . (abs($days_difference)) . " days. Renew now.";
            $notification_link = ($reminder->entity_name) . "/" . ($data->id);
        }



         $notification =   Notification::create([
                "entity_id" => $data->user_id,
                "entity_ids" => [$data->user_id],
                "entity_name" => $reminder->entity_name,
                'notification_title' => $reminder->title,
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => 1,
                "receiver_id" => optional($user->departments()->first())->manager_id ?? $user->business->owner_id,
                "business_id" => $business->id,
                "is_system_generated" => 1,
                "status" => "unread",
            ]);

            fwrite($logHandle, "notification id:" . $notification->id . PHP_EOL);
            // Send Email
            // $manager = User::find($manager_id);
            // if ($manager && $manager->email) {
            //     Mail::to($manager->email)->send(new DocumentExpiryReminder($reminder, $notification_description, $notification_link));
            // }




        Mail::to([
            "rifatbilalphilips@gmail.com",
            // "asjadtariq@gmail.com"
        ])->send(new DocumentExpiryReminder($reminder, $notification_description, $notification_link));

        fclose($logHandle);
    }


    public function handle()
    {
        $logFile = storage_path('logs/document_reminder.log');
        $logHandle = fopen($logFile, 'a');
        fwrite($logHandle, "Reminder process started at " . now() . PHP_EOL);



        // Retrieve distinct business IDs for reminders
        $businesses =  Business::whereHas("reminders")
            ->get();


        // Iterate over each business
        foreach ($businesses as $business) {
            fwrite($logHandle, "Processing business ID: {$business->id}" . PHP_EOL);



            // Retrieve reminders for the current business
            $reminders = Reminder::where([
                "business_id" => $business->id
            ])
                ->get();

                fwrite($logHandle, "Fetched reminders: " . $reminders->count() . " reminders for business ID: {$business->id}" . PHP_EOL);


            // Iterate over each reminder
            foreach ($reminders as $reminder) {






                // Adjust reminder duration if necessary
                if ($reminder->duration_unit == "weeks") {
                    $reminder->duration =  $reminder->duration * 7;
                } else if ($reminder->duration_unit == "months") {
                    $reminder->duration =  $reminder->duration * 30;
                }

                // Get current timestamp
                $now = Carbon::now();
                $model_name = $reminder->model_name;

                $user_relationship = $reminder->user_relationship;
                $expiry_date_column = $reminder->expiry_date_column;


                $all_reminder_data = $this->resolveClassName($model_name)::where([
                    "business_id" => $business->id
                ])
                    ->where("is_current", 1)
                    ->whereNotNull($expiry_date_column)
                    ->whereHas($user_relationship, function ($query) {
                        $query
                            ->where("users.is_active", 1)
                            ->whereDate("users.joining_date", "<=", today())
                            ->whereDoesntHave("lastTermination", function ($query) {
                                $query->where('terminations.date_of_termination', "<", today())
                                    ->whereRaw('terminations.date_of_termination > users.joining_date');
                            });
                    })

                    ->when(($reminder->send_time == "before_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {
                        $query->where(
                            ($expiry_date_column),
                            "<=",
                            $now->copy()->addDays($reminder->duration)
                        );
                    })
                    ->when(($reminder->send_time == "after_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {

                        $query->where(
                            ($expiry_date_column),
                            "<=",
                            $now->copy()->subDays($reminder->duration)
                        );
                    })
                    ->get();





                // Iterate over all reminder data
                foreach ($all_reminder_data as $data) {

                    // Check if reminder should be sent after expiry
                    if ($reminder->send_time == "after_expiry") {

                        // Calculate the reminder date based on the duration set
                        $reminder_date =   $now->copy()->subDays($reminder->duration);


                        // Check if the reminder date matches the expiry date
                        if ($reminder_date->eq($data->$expiry_date_column)) {

                            // send notification or email based on setting
                            $this->sendNotification($reminder, $data, $business);
                        } else if ($reminder_date->gt($data->$expiry_date_column)) {

                            // Check if the reminder should keep sending until updated and if a frequency is set
                            if (!empty($reminder->frequency_after_first_reminder)) {

                                // Calculate the difference in days between reminder date and expiry date
                                $days_difference = $reminder_date->diffInDays($data->$expiry_date_column);

                                // Calculate the modulo once
                                $is_frequency_met = ($days_difference % $reminder->frequency_after_first_reminder) == 0;

                                if ($reminder->keep_sending_until_update) {
                                    // Check if the difference in days is a multiple of the set frequency
                                    if ($is_frequency_met) {
                                        // send notification or email based on setting
                                        $this->sendNotification($reminder, $data, $business);
                                    }
                                } else {

                                    if ($is_frequency_met && (($days_difference / $reminder->frequency_after_first_reminder) <= $reminder->reminder_limit)) {
                                        // send notification or email based on setting
                                        $this->sendNotification($reminder, $data, $business);
                                    }
                                }
                            }
                        }
                    } else if ($reminder->send_time == "before_expiry") {

                        // Calculate the reminder date based on the duration set
                        // $reminder_date =   $now->copy()->addDays($reminder->duration);
                        $reminder_date =   Carbon::parse($data->$expiry_date_column)->subDays($reminder->duration);



                        // Check if the reminder date matches the expiry date
                        if ($reminder_date->eq($now)) {
                            // send notification or email based on setting

                            $this->sendNotification($reminder, $data, $business);
                        } else if ($reminder_date->lt($now)) {

                            // Check if the reminder should keep sending until updated and if a frequency is set
                            if (!empty($reminder->frequency_after_first_reminder)) {

                                // Calculate the difference in days between reminder date and expiry date
                                $days_difference = $reminder_date->diffInDays($now);

                                // Calculate the modulo once
                                $is_frequency_met = ($days_difference % $reminder->frequency_after_first_reminder) == 0;

                                if ($reminder->keep_sending_until_update) {
                                    // Check if the difference in days is a multiple of the set frequency
                                    if ($is_frequency_met) {
                                        // send notification or email based on setting
                                        $this->sendNotification($reminder, $data, $business);
                                    }
                                } else {

                                    if ($is_frequency_met && (($days_difference / $reminder->frequency_after_first_reminder) <= $reminder->reminder_limit)) {
                                        // send notification or email based on setting
                                        $this->sendNotification($reminder, $data, $business);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Log that the reminder process has finished

        // Close the log file
        fclose($logHandle);
        return 0;
    }
}
