<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateProfileRequestV2 extends FormRequest
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
            'title' => 'required|string|max:255',
            'first_Name' => 'required|string|max:255',
            'middle_Name' => 'nullable|string|max:255',
            'last_Name' => 'required|string|max:255',
            // 'email' => 'required|string|email|indisposable|max:255|unique:users',
            'email' => 'required|string|unique:users,email,' . auth()->user()->id . ',id',



        ];
    }
}
