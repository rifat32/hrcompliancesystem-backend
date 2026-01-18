<?php

namespace App\Http\Requests;

use App\Models\UserLetter;
use Illuminate\Foundation\Http\FormRequest;

class UserLetterUpdateViewRequest extends FormRequest
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
                    $exists = UserLetter::where('id', $value)
                        ->where('user_letters.user_id', '=', auth()->user()->id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'letter_viewed' => [
                'required',
                'boolean',
            ],

        ];
    }
}
