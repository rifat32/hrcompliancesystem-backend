/**
*
* @OA\Put(
* path="/v1.0/{{ $names['api_name'] }}",
* operationId="update{{ $names['singular_model_name'] }}",
* tags={"{{ $names['table_name'] }}"},
* security={
* {"bearerAuth": {}}
* },
* summary="This method is to update {{ $names['plural_comment_name'] }} ",
* description="This method is to update {{ $names['plural_comment_name'] }} ",
*
* @OA\RequestBody(
* required=true,
* @OA\JsonContent(
* @OA\Property(property="id", type="number", format="number", example="1"),
@foreach ($fields->toArray() as $field)
    * @OA\Property(property="{{ $field['name'] }}", type="string", format="string", example="{{ $field['name'] }}"),
@endforeach
*
* ),
* ),
* @OA\Response(
* response=200,
* description="Successful operation",
* @OA\JsonContent(),
* ),
* @OA\Response(
* response=401,
* description="Unauthenticated",
* @OA\JsonContent(),
* ),
* @OA\Response(
* response=422,
* description="Unprocesseble Content",
* @OA\JsonContent(),
* ),
* @OA\Response(
* response=403,
* description="Forbidden",
* @OA\JsonContent()
* ),
* * @OA\Response(
* response=400,
* description="Bad Request",
* *@OA\JsonContent()
* ),
* @OA\Response(
* response=404,
* description="not found",
* *@OA\JsonContent()
* )
* )
* )
*/

public function update{{ $names['singular_model_name'] }}({{ $names['singular_model_name'] }}UpdateRequest $request)
{
DB::beginTransaction();
try {


if (!auth()->user()->hasPermissionTo('{{ $names['singular_table_name'] }}_update')) {
return response()->json([
"message" => "You can not perform this action"
], 401);
}
$request_data = $request->validated();



${{ $names['singular_table_name'] }}_query_params = [
"id" => $request_data["id"],
];

${{ $names['singular_table_name'] }} =
{{ $names['singular_model_name'] }}::where(${{ $names['singular_table_name'] }}_query_params)->first();

if (${{ $names['singular_table_name'] }}) {
${{ $names['singular_table_name'] }}->fill($request_data);
${{ $names['singular_table_name'] }}->save();
} else {
return response()->json([
"message" => "something went wrong."
], 500);
}



DB::commit();
return response(${{ $names['singular_table_name'] }}, 201);

} catch (Exception $e) {
DB::rollBack();
return $this->sendError($e);
}
}
