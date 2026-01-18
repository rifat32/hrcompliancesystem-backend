<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CodeGeneratorController extends Controller
{
   public function getCodeGeneratorForm (Request $request) {
        $names=[];

        $validationRules = [
         'Basic Validation Rules' => [
           'required',
           'optional',
           'present',
           'filled',
           'nullable',
         ],
         'String Validation Rules' => [
           'string',
           'text',
           'email',
           'url',
           'date'
         ],
         'Numeric Validation Rules' => [
           'integer',
           'numeric',
         ],
       ];

       $is_active = 1;
       $is_default = 1;

         return view('code_generator.code-generator', compact("names","validationRules",  "is_active", "is_default"));
     }



   public   function generateCode(Request $request) {

        $names["table_name"] = $request->table_name;
        $names["singular_table_name"] = Str::singular($names["table_name"]);

        $names["singular_model_name"] = Str::studly($names["singular_table_name"]);
        $names["plural_model_name"] = Str::plural($names["singular_model_name"]);

        $names["api_name"] = str_replace('_', '-', $names["table_name"]);
        $names["controller_name"] = $names["singular_model_name"] . 'Controller';

        $names["singular_comment_name"] = Str::singular(str_replace('_', ' ', $names["table_name"]));
        $names["plural_comment_name"] = str_replace('_', ' ', $names["table_name"]);

        $is_active = $request->is_active;
        $is_default = $request->is_default;



        $fields = collect([]);




// Get inputs from the request
$field_names = $request->input('field_names', []);
$validation_types = $request->input('validation_types', []);
$string_validation_rules = $request->input('string_validation_rules', []);
$number_validation_rules = $request->input('number_validation_rules', []);
$is_unique_values = $request->input('is_unique_values', []);
$is_foreign_key_values = $request->input('is_foreign_key_values', []);
$basic_validation_rules = $request->input('basic_validation_rules', []);
$relationship_table_names = $request->input('relationship_table_names', []);

// Remove the first item from each array
array_shift($field_names);
array_shift($validation_types);
array_shift($string_validation_rules);
array_shift($number_validation_rules);
array_shift($is_unique_values);
array_shift($is_foreign_key_values);
array_shift($basic_validation_rules);
array_shift($relationship_table_names);

// Now $validation_types, $string_validation_rules, etc. contain the remaining items





        foreach($field_names as $index=>$value) {


            $field["name"] = $value;

            $field["type"] = $validation_types[$index];

            $field["basic_validation_rule"] = $basic_validation_rules[$index];

            $field["relationship_table_name"] = $relationship_table_names[$index];



            $field["is_unique"] = 0;
            $field["is_foreign_key"] = 0;


            if($field["type"] == "string") {
                $field["request_validation_type"] = $string_validation_rules[$index];

                $field["is_unique"] = $is_unique_values[$index];

                $field["db_validation_type"] = $field["request_validation_type"];

                if($field["request_validation_type"] == "text"){
                    $field["request_validation_type"] = "string";
                    $field["db_validation_type"] = "longText";
                }

                if($field["request_validation_type"] == "email" || $field["request_validation_type"] == "url" ){
                    $field["db_validation_type"] = "string";
                }

            }
            else if($field["type"] == "numeric") {
                $field["request_validation_type"] = $number_validation_rules[$index];
                $field["is_foreign_key"] = $is_foreign_key_values[$index];
                $field["db_validation_type"] =  $field["request_validation_type"];

                if($field["is_foreign_key"] == 1) {
                    $field["request_validation_type"] = "numeric";
                    $field["db_validation_type"] = "unsignedBigInteger";
                } else if($field["request_validation_type"] == "numeric") {
                    $field["db_validation_type"] = "decimal";
                }
            }
            else if ($field["type"] == "array"){
                $field["request_validation_type"] = $field["type"];
                $field["db_validation_type"] = "json";
            }
            else {
                $field["request_validation_type"] = $field["type"];
                $field["db_validation_type"] = $field["type"];
            }


            $fields->push($field);

        }







        $validationRules = [
            'Basic Validation Rules' => [
              'required',
              'optional',
              'present',
              'filled',
              'nullable',
            ],
            'String Validation Rules' => [
              'string',
              'email',
              'url',
              'date'
            ],
            'Numeric Validation Rules' => [
              'integer',
              'numeric',
            ],

          ];





        return view('code_generator.code-generator',compact("names","fields","validationRules", "is_active", "is_default"));


    }



}
