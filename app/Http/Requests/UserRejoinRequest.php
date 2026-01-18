<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;

use App\Models\Role;
use App\Models\WorkShift;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDesignation;
use App\Rules\ValidateEmploymentStatus;
use App\Rules\ValidateUser;
use App\Rules\ValidateWorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UserRejoinRequest extends FormRequest
{
    use BasicUtil;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $all_manager_department_ids = $this->get_all_departments_of_manager();

        return [

            'id' => [
                "required",
                "numeric",
                new ValidateUser($all_manager_department_ids,false,true),
            ],


            'role' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {
                    $role  = Role::where(["name" => $value])->first();


                    if (!$role){
                             // $fail($attribute . " is invalid.")
                             $fail("Role does not exists.");
                             return;

                    }

                    if(!empty(auth()->user()->business_id)) {
                        if (empty($role->business_id)){
                            // $fail($attribute . " is invalid.")
                          $fail("You don't have this role");
                          return;

                      }
                        if ($role->business_id != auth()->user()->business_id){
                              // $fail($attribute . " is invalid.")
                            $fail("You don't have this role");
                            return;

                        }
                    } else {
                        if (!empty($role->business_id)){
                            // $fail($attribute . " is invalid.")
                          $fail("You don't have this role");
                          return;

                      }
                    }


                },
            ],



            'work_shift_id' => [
                "nullable",
                'numeric',
                function ($attribute, $value, $fail) {


                    if(!empty($value)){

                        $exists = WorkShift::where('id', $value)
                        ->where([
                            "work_shifts.business_id" => auth()->user()->business_id
                        ])
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                    }

                },
            ],

            'departments' => 'required|array|size:1',
            'departments.*' =>  [
                'numeric',
                new ValidateDepartment($all_manager_department_ids)
            ],



        'work_location_ids' => [
            "present",
            'array',
        ],

        "work_location_ids.*" =>[
            "numeric",
        new ValidateWorkLocation()],





            'designation_id' => [
                "required",
                'numeric',
                new ValidateDesignation()
            ],
            'employment_status_id' => [
                "required",
                'numeric',
                new ValidateEmploymentStatus()
            ],

            'joining_date' => [
                "required",
                'date',
                function ($attribute, $value, $fail) {

                   $joining_date = Carbon::parse($value);
                   $start_date = Carbon::parse(auth()->user()->business->start_date);

                   if ($joining_date->lessThan($start_date)) {
                       $fail("The $attribute must not be before the start date of the business.");
                   }

                },
            ],

            'salary_per_annum' => "required|numeric",
            'weekly_contractual_hours' => 'required|numeric',
            "minimum_working_days_per_week" => 'required|numeric|max:7',
            "overtime_rate" => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [


            // 'sponsorship_details.status.in' => 'Invalid value for status. Valid values are: pending,approved,denied,visa_granted.',
            'sponsorship_details.current_certificate_status.in' => 'Invalid value for status. Valid values are: unassigned,assigned,visa_applied,visa_rejected,visa_grantes,withdrawal.',

            // ... other custom messages
        ];
    }
}
