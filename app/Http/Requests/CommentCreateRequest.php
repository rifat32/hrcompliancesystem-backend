<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentCreateRequest extends FormRequest
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
                    "parent_comment_id"=>["nullable", "numeric", "exists:comments,id"],
                    'description' => 'required|string',
                    'attachments' => 'nullable|array',
                    'status' => 'string|in:open,closed',
                    'priority' => 'string|in:low,medium,high',
                    'visibility' => 'string|in:public,private',
                    'tags' => 'nullable|string',
                    'resolution' => 'nullable|string',
                    'feedback' => 'nullable|array',
                    'hidden_note' => 'nullable|string',
                    'related_task_id' => 'nullable|exists:tasks,id',
                    'task_id' => 'required|exists:tasks,id',
                    'project_id' => 'required|exists:projects,id',
                    'mentioned_ids' => 'present|array',
                    'mentioned_ids.*' => "numeric"

                ];
            }

            public function messages()
            {
                return [
                    'description.required' => 'The description field is required.',
                    'status.in' => 'Invalid status provided. Valid values are: open, closed.',
                    'priority.in' => 'Invalid priority provided. Valid values are: low, medium, high.',
                    'visibility.in' => 'Invalid visibility provided. Valid values are: public, private.',
                ];
            }



}
