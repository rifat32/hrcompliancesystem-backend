<?php

namespace App\Console\Commands;

use App\Http\Utils\BasicUtil;
use App\Models\Notification;
use App\Models\SettingPaymentDate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SalaryReminderScheduler extends Command
{
    use BasicUtil;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salary_reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send salary reminder';

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

    public function getWeeklyDateRange($dayOfWeek) {
        // Get today's date
        $today = Carbon::today();

        // Calculate the difference between today and the provided dayOfWeek
        $diff = $today->dayOfWeek - $dayOfWeek;

        // If the difference is negative, move back one week
        if ($diff < 0) {
            $diff += 7;
        }

        // Get the date of the last occurrence of the provided dayOfWeek
        $lastDay = $today->subDays($diff);

        // Get the date of the previous occurrence of the provided dayOfWeek
        $prevLastDay = $lastDay->copy()->subWeek();

        // Return the range of dates
        return [
            'last_day' => $lastDay->toDateString(),
            'previous_last_day' => $prevLastDay->toDateString()
        ];
    }

    public function getMonthlyDateRange($dayOfMonth) {
        // Get today's date
        $today = Carbon::today();

        // Get the last day of the current month
        $lastDayOfMonth = $today->endOfMonth();

        // If the provided dayOfMonth is greater than the last day of the current month,
        // move to the next month
        if ($dayOfMonth > $lastDayOfMonth->day) {
            $today->addMonth();
        }

        // Set the day of the month
        $today->day($dayOfMonth);

        // Get the date of the last occurrence of the provided dayOfMonth
        $lastDay = $today->copy();

        // Get the date of the previous occurrence of the provided dayOfMonth
        $prevLastDay = $lastDay->copy()->subMonth();

        // Return the range of dates
        return [
            'last_day' => $lastDay->toDateString(),
            'previous_last_day' => $prevLastDay->toDateString()
        ];
    }


    public function getHelplessEmployees($business_id,$start_date,$end_date) {


     $helplessEmployees =  User::
        where([
            "business_id" => $business_id
        ])
                ->where("users.is_active", 1)
                ->whereDate("users.joining_date", "<=", $end_date)
                ->whereDoesntHave("lastTermination", function ($query) use($start_date) {
                    $query->where('terminations.date_of_termination', "<", $start_date)
                        ->whereRaw('terminations.date_of_termination > users.joining_date');
                })



        ->whereDoesntHave("payrolls", function ($q) use ($start_date,$end_date) {
            $q->whereDate("payrolls.start_date", ">=", ($start_date))
                ->whereDate("payrolls.end_date","<=", ($end_date ));
        })

        ->get();

        return $helplessEmployees;
    }

    public function handle()
    {
        Log::info('Salary Reminder process started.');
     $settingPaymentDates =   SettingPaymentDate::get();

     foreach($settingPaymentDates as $settingPaymentDate){



        if(empty($settingPaymentDate->business)){
            continue;
        }
        Log::info('business',["business" => $settingPaymentDate->business] );

        $period = "";

        if($settingPaymentDate->payment_type == "weekly") {
     $dateRange =  $this->getWeeklyDateRange($settingPaymentDate->day_of_week);
     $period = "week";

        } else if($settingPaymentDate->payment_type == "monthly") {
     $dateRange =  $this->getMonthlyDateRange($settingPaymentDate->day_of_month);
     $period = "month";

        } else if($settingPaymentDate->payment_type == "custom") {

     $dateRange = [
        'last_day' => $settingPaymentDate->custom_date,
        'previous_last_day' => Carbon::parse($settingPaymentDate->custom_date)->subMonth()->format('Y-m-d'),
     ];
        }

  $helplessEmployees =  $this->getHelplessEmployees($settingPaymentDate->business_id,$dateRange["previous_last_day"],$dateRange["last_day"]);

  foreach($helplessEmployees as $employee){
    $this->sendNotification($employee,$settingPaymentDate->business_id,$period,$dateRange);
  }



     }
     Log::info('Reminder process finished.');
        return 0;
    }

    public function sendNotification($user, $business_id, $period,$dateRange)
    {


        $notification_description =   "Salary for " . ($user->title . " " .$user->first_Name . ' ' . $user->middle_Name  . " " . $user->last_Name) .  " for the current ". $period . " is pending. Please address this promptly. Thank you.";
        $notification_link = ("employee") . "/" . ($user->id);





            Notification::create([
                "entity_id" => $user->id,
                "entity_ids" => [$user->id],
                "entity_name" => "pending_salary",
                'notification_title' => "Salary Pending Notification",
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => 1,
                "receiver_id" => optional($user->departments()->first())->manager_id ?? $user->business->owner_id,
                "business_id" => $business_id,
                "is_system_generated" => 1,
                "status" => "unread",
                "start_date" => $dateRange["previous_last_day"],
                "end_date" => $dateRange["last_day"]
            ]);



    }





}
