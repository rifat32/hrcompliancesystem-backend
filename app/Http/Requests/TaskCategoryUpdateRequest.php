<?php

namespace App\Http\Requests;

use App\Models\TaskCategory;
use App\Rules\ValidateTaskCategoryName;
use Illuminate\Foundation\Http\FormRequest;

class TaskCategoryUpdateRequest extends FormRequest
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
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($task_category->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this task category due to role restrictions.");
                          }
                        } else {
                            if(($task_category->business_id != NULL || $task_category->is_default != 0 || $task_category->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this task category due to role restrictions.");

                          }
                        }

                    } else {
                        if(($task_category->business_id != auth()->user()->business_id || $task_category->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this task category due to role restrictions.");
                        }
                    }

                },

            ],



            'name' => [
                "required",
                'string',
               new ValidateTaskCategoryName($this->id)
            ],

            'description' => 'nullable|string',
            'order_no' => 'nullable|numeric',
            'color' => 'nullable|string',
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:task_categories,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     $rules['name'] .= '|unique:task_categories,name,'.$this->id.',id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        // }

return $rules;
    }







}
