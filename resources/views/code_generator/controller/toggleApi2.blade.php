/**
*
* @OA\Put(
* path="/v1.0/{{ $names['api_name'] }}/toggle-active",
* operationId="toggleActive{{ $names['singular_model_name'] }}",
* tags={"{{ $names['table_name'] }}"},
* security={
* {"bearerAuth": {}}
* },
* summary="This method is to toggle {{ $names['plural_comment_name'] }}",
* description="This method is to toggle {{ $names['plural_comment_name'] }}",
*
* @OA\RequestBody(
* required=true,
* @OA\JsonContent(

* @OA\Property(property="id", type="string", format="number",example="1"),
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

public function toggleActive{{ $names['singular_model_name'] }}(GetIdRequest $request)
{

try {



if (!$request->user()->hasPermissionTo('{{ $names['singular_table_name'] }}_activate')) {
return response()->json([
"message" => "You can not perform this action"
], 401);
}
$request_data = $request->validated();

${{ $names['singular_table_name'] }} = {{ $names['singular_model_name'] }}::where([
"id" => $request_data["id"],
])
->first();
if (!${{ $names['singular_table_name'] }}) {

return response()->json([
"message" => "no data found"
], 404);
}

${{ $names['singular_table_name'] }}->update([
'is_active' => !${{ $names['singular_table_name'] }}->is_active
]);




return response()->json(['message' => '{{ $names['singular_comment_name'] }} status updated successfully'], 200);
} catch (Exception $e) {

return $this->sendError($e);
}
}
