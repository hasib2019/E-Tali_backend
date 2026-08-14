<?php

namespace App\Http\Controllers\Api;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends ApiController
{
    /** Register (or refresh) the current device's Expo push token. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $device = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return $this->ok($device, 'Device registered.');
    }

    /** Unregister this device's token (called on logout). */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        DeviceToken::where('token', $request->string('token'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->ok(null, 'Device removed.');
    }
}
