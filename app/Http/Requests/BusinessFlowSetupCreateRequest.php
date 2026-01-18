<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Rules\UniqueSettingLeaveTypeName;
use App\Rules\ValidateEmploymentStatus;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class BusinessFlowSetupCreateRequest extends FormRequest
{
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
        return [


            "leave_types" => "present|array",
            'leave_types.*.name' => [
                "required",
                'string',
                new UniqueSettingLeaveTypeName(NULL),
            ],

            'leave_types.*.type' => 'required|string|in:paid,unpaid',
            'leave_types.*.amount' => 'required|numeric',
            'leave_types.*.is_active' => 'required|boolean',
            'leave_types.*.is_earning_enabled' => 'required|boolean',

            'leave_types.*.carry_over_limit' => 'required|integer',
            'leave_types.*.leave_rollover_type' => 'required|string|in:none,partial,full',

            "leave_types.*.employment_statuses" => "present|array",
            'leave_types.*.employment_statuses.*' => [
                'numeric',
                new ValidateEmploymentStatus()
            ],





            'leave_setting.start_month' => 'required|integer|min:1|max:12',
            'leave_setting.approval_level' => 'required|string|in:single,multiple', // Adjust the valid values as needed
            'leave_setting.allow_bypass' => 'required|boolean',
            'leave_setting.special_roles' => 'present|array',

            'leave_setting.special_roles.*' => [
                'numeric',
                function ($attribute, $value, $fail) {
                    $role = Role::where("id", $value)
                        ->first();


                    if (!$role) {
                        // $fail($attribute . " is invalid.");
                        $fail("Role does not exists.");
                    }
                    if (empty(auth()->user()->business_id)) {
                        if (!(empty($role->business_id) || $role->is_default == 1)) {
                            // $fail($attribute . " is invalid.");
                            $fail("User belongs to another business.");
                        }
                    } else {
                        if ($role->business_id != auth()->user()->business_id) {
                            // $fail($attribute . " is invalid.");
                            $fail("User belongs to another business.");
                        }
                    }
                },
            ],
            'leave_setting.paid_leave_employment_statuses' => 'present|array',

            'leave_setting.paid_leave_employment_statuses.*' => [
                'numeric',
                new ValidateEmploymentStatus()

            ],
            'leave_setting.unpaid_leave_employment_statuses' => 'present|array',
            'leave_setting.unpaid_leave_employment_statuses.*' => [
                'numeric',
                new ValidateEmploymentStatus()
            ],




            'attendance_setting.single_day_work_shift' => 'required|string|in:same_day,split_time',
            'attendance_setting.multi_day_work_shift' => 'required|string|in:same_day,split_time',
            'attendance_setting.punch_in_time_tolerance' => 'nullable|numeric|min:0',
            'attendance_setting.work_availability_definition' => 'nullable|numeric|min:0',
            'attendance_setting.punch_in_out_alert' => 'nullable|boolean',
            'attendance_setting.punch_in_out_interval' => 'nullable|numeric|min:0',
            'attendance_setting.alert_area' => 'nullable|array',
            'attendance_setting.alert_area.*' => 'string',
            'attendance_setting.service_name' => 'nullable|string',
            'attendance_setting.api_key'  => 'nullable|string',

            'attendance_setting.auto_approval' => 'nullable|boolean',
            'attendance_setting.is_geolocation_enabled' => 'nullable|boolean',

            'attendance_setting.special_roles' => 'present|array',
            'attendance_setting.special_roles.*' => [
                'numeric',
                function ($attribute, $value, $fail) {
                    $role = Role::where("id", $value)
                        ->first();


                    if (!$role) {
                        // $fail($attribute . " is invalid.");
                        $fail("Role does not exists.");
                    }
                    if (empty(auth()->user()->business_id)) {
                        if (!(empty($role->business_id) || $role->is_default == 1)) {
                            // $fail($attribute . " is invalid.");
                            $fail("Role belongs to another business.");
                        }
                    } else {
                        if ($role->business_id != auth()->user()->business_id) {
                            // $fail($attribute . " is invalid.");
                            $fail("Role belongs to another business.");
                        }
                    }
                },
            ],


            'payrun_setting.payrun_period' => 'required|in:monthly,weekly',
            'payrun_setting.consider_type' => 'required|in:hour,daily_log,none',
            'payrun_setting.consider_overtime' => 'required|boolean',

            'payment_date_setting.payment_type' => 'required|in:weekly,monthly,custom',

            'payment_date_setting.custom_date' => 'nullable|date|required_if:payment_date_setting.payment_type,custom',

            'payment_date_setting.day_of_week' => 'nullable|integer|min:0|max:6|required_if:payment_date_setting.payment_type,weekly',

            'payment_date_setting.day_of_month' => 'nullable|integer|min:1|max:31|required_if:payment_date_setting.payment_type,monthly',

            'payment_date_setting.custom_frequency_interval' => 'nullable|integer|min:1|required_if:payment_date_setting.payment_type,custom',

            'payment_date_setting.custom_frequency_unit' => 'nullable|in:days,weeks,months|required_if:payment_date_setting.payment_type,custom',

            'payment_date_setting.role_specific_settings' => 'nullable|array',


        ];
    }


}
