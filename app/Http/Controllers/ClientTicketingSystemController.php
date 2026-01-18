<?php

namespace App\Http\Controllers;

use App\Services\EncryptionService;
use Illuminate\Http\Request;

class ClientTicketingSystemController extends Controller
{

    protected EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * @OA\Get(
     *      path="/get-ticket-token",
     *      operationId="getTicketToken",
     *      tags={"ticketing_client"},
     *      security={{"bearerAuth": {}}},
     *      summary="Get encrypted JWT ticket token",
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=500, description="Server error"),
     * )
     */

    public function getTicketToken(Request $request)
    {
        $user = auth()->user();
        $business = $user->business;

        $claims = [
            'ticketing_system_user_id' => $user->id,
            'ticketing_system_name' => $user->full_name,
            'ticketing_system_email' => $user->email,
            'ticketing_system_business_id' => $business->id ?? null,
            'ticketing_system_business_name' => $business->name ?? null,
            'ticketing_system_business_identifier_prefix' => $business->identifier_prefix ?? null,
            'ticketing_system_business_web_page' => $business->web_page ?? null,
            'ticketing_system_business_phone' => $business->phone ?? null,
            'ticketing_system_business_email' => $business->email ?? null,
            'ticketing_system_business_address_line_1' => $business->address_line_1 ?? null,
            'ticketing_system_business_address_line_2' => $business->address_line_2 ?? null,
            'ticketing_system_business_city' => $business->city ?? null,
            'ticketing_system_business_country' => $business->country ?? null,
            'ticketing_system_business_postcode' => $business->postcode ?? null,
            'ticketing_system_business_currency' => $business->currency ?? null,
            'ticketing_system_business_logo' => env("APP_URL") . ($business->logo ?? ''),
            'ticketing_system_app_id' => env('APP_ID'),
            'ticketing_system_app_url' => env('APP_URL'),
            'ticketing_system_front_end_url' => env('FRONT_END_URL'),
            'ip' => request()->ip(),
        ];

        $payload = $this->encryptionService->generateEncryptedToken($claims);

        return response()->json($payload,200);
    }



}
