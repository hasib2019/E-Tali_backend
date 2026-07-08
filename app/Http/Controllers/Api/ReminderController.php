<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends ApiController
{
    public function index(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        return $this->ok(
            $business->reminders()->orderBy('is_done')->orderBy('due_date')->get()
        );
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $reminder = $business->reminders()->create($data);

        return $this->ok($reminder, 'Reminder added.', 201);
    }

    public function update(Request $request, Reminder $reminder): JsonResponse
    {
        $this->ensureOwnsChild($reminder);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['sometimes', 'date'],
            'is_done' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $reminder->update($data);

        return $this->ok($reminder->fresh(), 'Reminder updated.');
    }

    public function destroy(Reminder $reminder): JsonResponse
    {
        $this->ensureOwnsChild($reminder);

        $reminder->delete();

        return $this->ok(null, 'Reminder removed.');
    }
}
