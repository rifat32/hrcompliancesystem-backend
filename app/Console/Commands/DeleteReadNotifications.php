<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use Carbon\Carbon;

class DeleteReadNotifications extends Command
{
    protected $signature = 'notifications:delete-read';
    protected $description = 'Delete read notifications older than 30 days for businesses with the setting enabled';

    public function handle()
    {
        $businesses = Business::where('delete_read_notifications_after_30_days', 1)->get();

        foreach ($businesses as $business) {
            $business->notifications()
                ->where('notifications.status','read')
                ->whereDate('notifications.updated_at', '<=', Carbon::now()->subDays(30))
                ->delete();
        }

        $this->info('Old read notifications deleted successfully.');
    }
}
