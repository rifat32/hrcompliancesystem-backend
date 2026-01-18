<?php

namespace App\Services;

use App\Models\DeviceToken;
use Google_Client;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $client;
    protected $accessToken;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('firebase/tide-hr-firebase-adminsdk-fbsvc-8fa5c302c2.json'));
        $this->client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $this->client->setSubject(config('services.firebase.client_email')); // optional if you need to specify subject
        $this->client->refreshTokenWithAssertion();
        $this->accessToken = $this->client->getAccessToken()['access_token'];
    }

    /**
     * Send push notification to device token
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendNotificationToDevice(string $deviceToken, string $title, string $body, array $data = []): array
    {
        // Your Firebase project ID
        $projectId = config('services.firebase.project_id');

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                ],
                "data" => $data,
                "android" => [
                    "priority" => "HIGH"
                ],
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10"
                    ]
                ],
                "webpush" => [
                    "headers" => [
                        "Urgency" => "high"
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        return [
            "debug" => [$url, $this->accessToken],
            "response" => $response->json()
        ];
    }

    public function sendNotificationToUser($userId, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('device_token')->toArray();

        $responses = [];
        foreach ($tokens as $token) {
            $responses[] = $this->sendNotificationToDevice($token, $title, $body, $data);
        }

        return $responses;
    }
}
