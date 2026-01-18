<?php

namespace App\Rules;


use App\Models\Attendance;

use Illuminate\Contracts\Validation\Rule;

class UniqueAttendanceDate implements Rule
{

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $id;
    protected $user_id;
    protected $failureMessage;

    public function __construct($id, $user_id)
    {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->failureMessage = '';
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

     $attendance = Attendance::when(!empty($this->id), function($query) {
            $query->whereNotIn('id', [$this->id]);
        })
            ->where('attendances.user_id', $this->user_id)
            ->where('attendances.in_date', $value)
            ->where('attendances.business_id', auth()->user()->business_id)
            ->first();

            if($attendance) {
                $this->failureMessage = 'Attendance already exists on this date.' . $attendance->id;
                return 0;
            }


           return 1;

    }

    public function message()
    {
        return $this->failureMessage;
    }
}
