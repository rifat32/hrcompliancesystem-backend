<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class CommentUpdateRequest extends FormRequest
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
                    $exists = Comment::where('id', $value)
                    ->where('comments.created_by', '=', auth()->user()->id)
                    ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'description' => 'required|string',
            'attachments' => 'nullable|array',
            'status' => 'string|in:open,closed',
            'priority' => 'string|in:low,medium,high',
            'visibility' => 'string|in:public,private',
            'tags' => 'nullable|string',
            'resolution' => 'nullable|string',
            'feedback' => 'nullable|array',
            'hidden_note' => 'nullable|string',
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
