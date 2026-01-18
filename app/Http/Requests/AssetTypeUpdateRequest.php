<?php




namespace App\Http\Requests;

use App\Models\AssetType;
use App\Rules\ValidateAssetTypeName;
use Illuminate\Foundation\Http\FormRequest;

class AssetTypeUpdateRequest extends FormRequest
{
/**
* Determine if the user is authorized to make this request.
*
* @return  bool
*/
public function authorize()
{
return true;
}

/**
* Get the validation rules that apply to the request.
*
* @return  array
*/
public function rules()
{

$rules = [

'id' => [
  'required',
  'numeric',
  function ($attribute, $value, $fail) {

      $asset_type_query_params = [
          "id" => $this->id,
      ];
      $asset_type = AssetType::where($asset_type_query_params)
          ->first();
      if (!$asset_type) {
          // $fail($attribute . " is invalid.");
          $fail("no asset type found");
          return 0;
      }
      if (empty(auth()->user()->business_id)) {

          if (auth()->user()->hasRole('superadmin')) {
              if (($asset_type->business_id != NULL )) {
                  // $fail($attribute . " is invalid.");
                  $fail("You do not have permission to update this asset type due to role restrictions.");
              }
          } else {
              if (($asset_type->business_id != NULL || $asset_type->is_default != 0 || $asset_type->created_by != auth()->user()->id)) {
                  // $fail($attribute . " is invalid.");
                  $fail("You do not have permission to update this asset type due to role restrictions.");
              }
          }
      } else {
          if (($asset_type->business_id != auth()->user()->business_id || $asset_type->is_default != 0)) {
              // $fail($attribute . " is invalid.");
              $fail("You do not have permission to update this asset type due to role restrictions.");
          }
      }
  },
],



    'name' => [
    'required',
    'string',



        new ValidateAssetTypeName(NULL)




],







];



return $rules;
}
}



