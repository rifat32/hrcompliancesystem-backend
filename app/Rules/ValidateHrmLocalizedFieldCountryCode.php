<?php
   namespace App\Rules;

        use App\Models\HrmLocalizedField;
        use Illuminate\Contracts\Validation\Rule;

        class ValidateHrmLocalizedFieldCountryCode implements Rule
        {
            /**
             * Create a new rule instance.
             *
             * @return  void
             */

             protected $id;
            protected $errMessage;

            public function __construct($id)
            {
                $this->id = $id;
                $this->errMessage = "";

            }


            /**
             * Determine if the validation rule passes.
             *
             * @param    string  $attribute
             * @param    mixed  $value
             * @return  bool
             */
            public function passes($attribute, $value)
            {


                $data = HrmLocalizedField::where("hrm_localized_fields.name",$value)
                ->when(!empty($this->id),function($query) {
                    $query->whereNotIn("id",[$this->id]);
                })
                ->where('hrm_localized_fields.reseller_id', auth()->user()->id)
                ->first();

                if(!empty($data)){

                    if ($data->is_active) {
                        $this->errMessage = "A hrm localized field with the same name already exists.";
                    } else {
                        $this->errMessage = "A hrm localized field with the same name exists but is deactivated. Please activate it to use.";
                    }


                    return 0;

                }
             return 1;
            }

            /**
             * Get the validation error message.
             *
             * @return  string
             */
            public function message()
            {
                return $this->errMessage;
            }

        }
