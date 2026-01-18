<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;

use Carbon\Carbon;
use Closure;


class ResponseMiddleware
{


    public function handle($request, Closure $next)
    {

        // Define your API project's base URL



        $response = $next($request);

        if ($response->headers->get('content-type') === 'application/json') {
            $content = $response->getContent();
            $convertedContent = $this->convertDatesInJson($content);
            $response->setContent($convertedContent);

            $status_code = $response->getStatusCode();
$is_error = $status_code >= 300;
$activityLog = [
    "api_url" => request()->fullUrl(),
    "fields" => json_encode(request()->all()),
    "token" => request()->bearerToken() ?? "",
    "user" => auth()->user() ? json_encode(auth()->user()) : "",
    "user_id" => auth()->user()->id ?? "",
    "status_code" => $status_code,
    "ip_address" => request()->ip(),
    "request_method" => request()->method(),
    "message" => $response->getContent(),
    "device" => $request->header('User-Agent'),
    "is_error" => $is_error ? 1 : 0
];
$activity = ActivityLog::create($activityLog);

if ($status_code >= 500 && $status_code < 600) {
    $errorMessage = "Error ID: {$activity->id} - Status: {$activity->status_code} - We encountered an issue while processing your request and apologize for any inconvenience this may have caused. Please contact customer support and provide the Error ID: {$activity->id} for assistance.";
    $response->setContent(json_encode(['message' => $errorMessage]));
} elseif ($status_code >= 300 && $status_code < 500) {
    $responseData = json_decode($response->getContent(), true);
    if (isset($responseData['message'])) {
        $responseData['message'] = "Error ID: {$activity->id} - Status: {$activity->status_code} - {$responseData['message']}";
        $response->setContent(json_encode($responseData));
    }
}


        }

        return $response;
    }

    private function convertDatesInJson($json)
    {
        $data = json_decode($json, true);


        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            array_walk_recursive($data, function (&$value, $key) {
                // Check if the value resembles a date but not in the format G-0001
                if (is_string($value) && (Carbon::hasFormat($value, 'Y-m-d') || Carbon::hasFormat($value, 'Y-m-d\TH:i:s.u\Z') || Carbon::hasFormat($value, 'Y-m-d\TH:i:s')  ||  Carbon::hasFormat($value, 'Y-m-d H:i:s'))) {
                    // Parse the date and format it as 'd-m-Y'

                    $date = Carbon::parse($value);

                    // If the date is in the far past, it's likely invalid
                    if ($date->year <= 0) {
                        $value = "";
                    } else {
                        // Format the date as 'd-m-Y' if no time is present, otherwise 'd-m-Y H:i:s'
                        if ($date->hour == 0 && $date->minute == 0 && $date->second == 0) {
                            $value = $date->format('d-m-Y');
                        } else {
                            $value = $date->format('d-m-Y H:i:s');
                        }
                    }
                }
            });

            return json_encode($data);
        }

        return $json;
    }
}
