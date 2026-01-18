<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
      public function store(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'nullable|string',
        ]);

        DeviceToken::updateOrCreate(
            ['device_token' => $request->device_token],
            [
                'user_id' => $request->user()->id,
                'device_type' => $request->device_type,
            ]
        );

        return response()->json(['message' => 'Device token registered/updated']);
    }
}
