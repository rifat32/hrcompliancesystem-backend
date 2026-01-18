



    /**
    *
    * @OA\Get(
    * path="/v1.0/{{ $names['api_name'] }}",
    * operationId="get{{ $names['plural_model_name'] }}",
    * tags={"{{ $names['table_name'] }}"},
    * security={
    * {"bearerAuth": {}}
    * },

    @foreach ($fields->toArray() as $field)
    @if ($field['is_foreign_key'])
    * @OA\Parameter(
        * name="{{ $field['name'] }}s",
        * in="query",
        * description="{{ $field['name'] }}",
        * required=false,
        * example=""
        * ),
        @elseif ($field['type'] == 'string')
            @if ($field['request_validation_type'] == 'date')
                * @OA\Parameter(
                * name="start_{{ $field['name'] }}",
                * in="query",
                * description="start_{{ $field['name'] }}",
                * required=false,
                * example=""
                * ),
                * @OA\Parameter(
                * name="end_{{ $field['name'] }}",
                * in="query",
                * description="end_{{ $field['name'] }}",
                * required=false,
                * example=""
                * ),
            @else
                * @OA\Parameter(
                * name="{{ $field['name'] }}",
                * in="query",
                * description="{{ $field['name'] }}",
                * required=false,
                * example=""
                * ),
            @endif
        @endif
    @endforeach
    * @OA\Parameter(
    * name="per_page",
    * in="query",
    * description="per_page",
    * required=false,
    * example=""
    * ),

    * @OA\Parameter(
    * name="is_active",
    * in="query",
    * description="is_active",
    * required=false,
    * example=""
    * ),
    * @OA\Parameter(
    * name="start_date",
    * in="query",
    * description="start_date",
    * required=false,
    * example=""
    * ),
    * * @OA\Parameter(
    * name="end_date",
    * in="query",
    * description="end_date",
    * required=false,
    * example=""
    * ),
    * * @OA\Parameter(
    * name="search_key",
    * in="query",
    * description="search_key",
    * required=false,
    * example=""
    * ),
    * * @OA\Parameter(
    * name="order_by",
    * in="query",
    * description="order_by",
    * required=false,
    * example="ASC"
    * ),
    * * @OA\Parameter(
    * name="id",
    * in="query",
    * description="id",
    * required=false,
    * example=""
    * ),




    * summary="This method is to get {{ $names['plural_comment_name'] }} ",
    * description="This method is to get {{ $names['plural_comment_name'] }} ",
    *

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

    public function get{{ $names['plural_model_name'] }}(Request $request)
    {
    try {

    if (!auth()->user()->hasPermissionTo('{{ $names['singular_table_name'] }}_view')) {
    return response()->json([
    "message" => "You can not perform this action"
    ], 401);
    }


    $query = {{ $names['singular_model_name']. "::filter". $names['singular_model_name'] . "()" }};

    ${{ $names['table_name'] }} = $this->retrieveData($query, "id", "{{$names['table_name']}}");




    return response()->json(${{ $names['table_name'] }}, 200);
    } catch (Exception $e) {

    return $this->sendError($e);
    }
    }
