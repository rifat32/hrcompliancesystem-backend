<?php

namespace App\Http\Requests;

use App\Models\RecruitmentProcess;
use Illuminate\Foundation\Http\FormRequest;

class RecruitmentProcessPositionUpdateRequest extends FormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $recruitment_process_query_params = [
                        "id" => $this->id,
                    ];
                    $recruitment_process = RecruitmentProcess::where($recruitment_process_query_params)
                        ->first();

                    if (!$recruitment_process) {
                            // $fail($attribute . " is invalid.");
                            $fail("no recruitment process  found");
                            return 0;
                    }

                    // if (empty(auth()->user()->business_id)) {

                    //     if(auth()->user()->hasRole('superadmin')) {
                    //         if(($recruitment_process->business_id != NULL )) {
                    //             // $fail($attribute . " is invalid.");
                    //             $fail("You do not have permission to update this recruitment process  due to role restrictions.");

                    //       }

                    //     } else {
                    //         if(($recruitment_process->business_id != NULL || $recruitment_process->is_default != 0 || $recruitment_process->created_by != auth()->user()->id)) {
                    //             // $fail($attribute . " is invalid.");
                    //             $fail("You do not have permission to update this recruitment process  due to role restrictions.");

                    //       }
                    //     }

                    // } else {
                    //     if(($recruitment_process->business_id != auth()->user()->business_id || $recruitment_process->is_default != 0)) {
                    //            // $fail($attribute . " is invalid.");
                    //         $fail("You do not have permission to update this recruitment process  due to role restrictions.");
                    //     }
                    // }


                },
            ],

            "employee_order_no" => "required|numeric",
            "candidate_order_no" => "required|numeric",
            "termination_order_no" => "required|numeric",

        ];
    }
}
