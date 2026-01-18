<?php

namespace App\Rules;

use App\Models\Leave;
use App\Models\LeaveRecord;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class ValidateSettingLeaveType implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $user_id;
    protected $id;

    public function __construct($user_id,$id)
    {
        $this->user_id = $user_id;
        $this->id = $id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

$leave = Leave::where([
    "id" => $this->id,
    "leave_type_id" => $value
])
->first();

if($leave) {
return true;
}

        $user = User::where([
            "id" => $this->user_id
        ])
        ->first();


        $created_by  = NULL;
        if (auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }



        $setting_leave = SettingLeave::where('setting_leaves.business_id', auth()->user()->business_id)
            ->where('setting_leaves.is_default', 0)
            ->first();
        if (empty($setting_leave)) {
            return response()->json(
                ["message" => "No leave setting found."]
            );
        }
        if (!$setting_leave->start_month) {
            $setting_leave->start_month = 1;
        }

        // $paid_leave_available = in_array($user->employment_status_id, $setting_leave->paid_leave_employment_statuses()->pluck("employment_statuses.id")->toArray());



        $leave_type =   SettingLeaveType::
        where([
            "id"=> $value
        ])
        ->where(function ($query) use ( $user,$created_by) {

            $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                ->where('setting_leave_types.is_default', 0)
                ->where('setting_leave_types.is_active', 1)
                // ->when($paid_leave_available == 0, function ($query) {
                //     $query->where('setting_leave_types.type', "unpaid");
                // })
                ->where(function($query) use($user){
                   $query->whereHas("employment_statuses", function($query) use($user){
                    $query->whereIn("employment_statuses.id", [$user->employment_status->id]);
                   })
                   ->orWhereDoesntHave("employment_statuses");
                })
               ;

        })
            ->first();

            if(!$leave_type){
                return false;
            }

            $startOfMonth = Carbon::create(null, $setting_leave->start_month, 1, 0, 0, 0)->subYear();


            $total_recorded_hours = LeaveRecord::whereHas('leave', function ($query) use ($user, $leave_type) {
                $query->where([
                    "user_id" => $user->id,
                    "leave_type_id" => $leave_type->id

                ]);
            })
                ->where("leave_records.date", ">=", $startOfMonth)
                ->get()
                ->sum(function ($record) {
                    $total_seconds = Carbon::parse($record->end_time)->diffInSeconds(Carbon::parse($record->start_time));
                    return round($total_seconds / 3600, 2); // Converts seconds to hours and rounds to 2 decimals

                });







return true;



    }





    /**
     * Get the validation error message.
     *
     * @return string
     */



    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
