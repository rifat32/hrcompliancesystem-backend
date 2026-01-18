<?php

namespace App\Http\Requests;

use App\Models\RecruitmentProcess;
use Illuminate\Foundation\Http\FormRequest;

class RecruitmentProcessPositionMultipleUpdateRequest extends FormRequest
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
            'recruitment_processes' => 'present|array',
            'recruitment_processes.*.id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }
                    $recruitment_process_query_params = [
                        "id" => $value,
                    ];

        $recruitment_process = RecruitmentProcess::where($recruitment_process_query_params)
        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
            $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
                $query->forSuperAdmin('recruitment_processes');
            }, function ($query) use ($created_by) {
                $query->forNonSuperAdmin('recruitment_processes', $created_by);
            });
        })
        ->when(!empty(auth()->user()->business_id), function ($query)  {
            $query->forBusiness('recruitment_processes');
        })

        ->first();

                    if (!$recruitment_process) {
                        $fail("No recruitment process found for $attribute.");
                        return;
                    }

                    // Additional role-based permission checks can be added here if needed.
                },
            ],
            'recruitment_processes.*.employee_order_no' => 'required|numeric',
            'recruitment_processes.*.candidate_order_no' => 'required|numeric',
            'recruitment_processes.*.termination_order_no' => 'required|numeric',

        ];
    }
}
