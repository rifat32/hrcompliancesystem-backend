<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;

class HolidayUpdateStatusRequest extends FormRequest
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



        return [
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Holiday::where('id', $value)
                    ->where('business_id', auth()->user()->business_id)
                    ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                },
            ],




            'status' => 'required|string|in:pending_approval,in_progress,approved,rejected',

        ];
    }

    public function messages()
    {
        return [

            'status.in' => 'Invalid value for status. Valid values are: pending_approval,in_progress,approved,rejected.',
            // ... other custom messages
        ];
    }
}
