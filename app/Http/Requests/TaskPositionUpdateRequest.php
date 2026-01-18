<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateTaskCategory;
use App\Rules\ValidateTaskId;
use App\Rules\ValidateProject;
use Illuminate\Foundation\Http\FormRequest;

class TaskPositionUpdateRequest extends FormRequest
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
                'required',
                'numeric',
                new ValidateTaskId()
            ],

            'project_id' => [
                'required',
                'numeric',
                new ValidateProject()
            ],

            'parent_task_id' => [
                'nullable',
                'numeric',
                new ValidateTaskId(),
            ],


            'task_category_id' => [
                'required',
                'numeric',
                new ValidateTaskCategory(),
            ],






            "order_no" => "nullable|numeric"


        ];
    }

    public function messages()
    {
        return [
            'due_date.after_or_equal' => 'Due date must be after or equal to the start date.',
            'end_date.after_or_equal' => 'End date must be after or equal to the due date.',
            'status.in' => 'Invalid value for status. Valid values are: pending, in_progress, done, in_review, approved, cancelled, rejected, draft.',
            'project_id.exists' => 'Invalid project selected.',
            'parent_task_id.exists' => 'Invalid parent task selected.',


        ];
    }
}
