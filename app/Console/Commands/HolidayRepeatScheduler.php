<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HolidayRepeatScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holiday:renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command holiday renew';

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
    public function handle()
    {

        Holiday::where("repeats_annually", 1)
        ->whereYear("start_date", now()->subYear()->year)
        ->get()
        ->each(function($item) {

            $holiday_data = $item->toArray();
            $holiday_data["start_date"] = \Carbon\Carbon::parse($item->start_date)->addYear();
            $holiday_data["end_date"] = \Carbon\Carbon::parse($item->end_date)->addYear();
            unset($holiday_data["id"]); // Avoid duplicate primary key issues
            
           $holiday =    Holiday::create($holiday_data);

           $department_ids = $item->departments->pluck("id")->toArray();
           $holiday->departments()->sync($department_ids);

           $user_ids = $item->employees->pluck("id")->toArray();
           $holiday->employees()->sync($user_ids);


        });
        Log::info("holiday job is running");
        return 0;





    }
}
