<?php

namespace App\Http\Requests;

use App\Models\Bank;
use App\Rules\ValidateBankName;
use Illuminate\Foundation\Http\FormRequest;

class BankUpdateRequest extends FormRequest
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

                    $bank_query_params = [
                        "id" => $this->id,
                    ];
                    $bank = Bank::where($bank_query_params)
                        ->first();
                    if (!$bank) {
                            // $fail($attribute . " is invalid.");
                            $fail("no bank found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($bank->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this bank due to role restrictions.");

                          }

                        } else {
                            if(($bank->business_id != NULL || $bank->is_default != 0 || $bank->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this bank due to role restrictions.");

                          }
                        }

                    } else {
                        if(($bank->business_id != auth()->user()->business_id || $bank->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this bank due to role restrictions.");
                        }
                    }




                },
            ],



            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                new  ValidateBankName($this->id),
            ],
        ];


return $rules;
    }
}
