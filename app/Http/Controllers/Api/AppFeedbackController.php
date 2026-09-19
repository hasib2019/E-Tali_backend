<?php

namespace App\Http\Controllers\Api;

use App\Models\AppFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Users telling us what they want changed in the app. Read by the team in the
 * admin panel — users only ever write here.
 */
class AppFeedbackController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        $feedback = $request->user()->appFeedback()->create($data);

        return $this->ok(['id' => $feedback->id], 'Thanks! Your feedback has been sent.', 201);
    }
}
