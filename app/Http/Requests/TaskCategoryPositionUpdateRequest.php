<?php

namespace App\Http\Requests;

use App\Models\TaskCategory;
use Illuminate\Foundation\Http\FormRequest;

class TaskCategoryPositionUpdateRequest extends FormRequest
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

                    $task_category_query_params = [
                        "id" => $this->id,
                    ];
                    $task_category = TaskCategory::where($task_category_query_params)
                        ->first();
                    if (!$task_category) {
                            // $fail($attribute . " is invalid.");
                            $fail("no task category found");
                            return 0;

                    }

                    // if (empty(auth()->user()->business_id)) {

                    //     if(auth()->user()->hasRole('superadmin')) {
                    //         if(($task_category->business_id != NULL )) {
                    //             // $fail($attribute . " is invalid.");
                    //             $fail("You do not have permission to update this task category due to role restrictions.");
                    //       }
                    //     } else {
                    //         if(($task_category->business_id != NULL || $task_category->is_default != 0 || $task_category->created_by != auth()->user()->id)) {
                    //             // $fail($attribute . " is invalid.");
                    //             $fail("You do not have permission to update this task category due to role restrictions.");

                    //       }
                    //     }

                    // } else {
                    //     if(($task_category->business_id != auth()->user()->business_id || $task_category->is_default != 0)) {
                    //            // $fail($attribute . " is invalid.");
                    //         $fail("You do not have permission to update this task category due to role restrictions.");
                    //     }
                    // }



                },


            ],
            'project_id' => 'nullable|numeric|exists:projects,id',
            'order_no' => 'required|numeric'
        ];
    }
}
