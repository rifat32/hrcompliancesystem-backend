<?php

namespace App\Http\Requests;

use App\Models\JobType;
use App\Rules\ValidateJobTypeName;
use Illuminate\Foundation\Http\FormRequest;

class JobTypeUpdateRequest extends FormRequest
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



        $rules = [

            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $job_type_query_params = [
                        "id" => $this->id,
                    ];
                    $job_type = JobType::where($job_type_query_params)
                        ->first();
                    if (!$job_type) {
                            // $fail($attribute . " is invalid.");
                            $fail("no job type found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($job_type->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this job type due to role restrictions.");

                          }

                        } else {
                            if(($job_type->business_id != NULL || $job_type->is_default != 0 || $job_type->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this job type due to role restrictions.");

                          }
                        }

                    } else {
                        if(($job_type->business_id != auth()->user()->business_id || $job_type->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this job type due to role restrictions.");
                        }
                    }




                },
            ],


            'name' => 'required|string',
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                new ValidateJobTypeName($this->id),

            ],
        ];


return $rules;
    }
}
