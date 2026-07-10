<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends ApiController
{
    public function index(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        return $this->ok($business->notes()->latest('updated_at')->get());
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $note = $business->notes()->create($data);

        return $this->ok($note, 'Note saved.', 201);
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $this->ensureOwnsChild($note);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $note->update($data);

        return $this->ok($note->fresh(), 'Note updated.');
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->ensureOwnsChild($note);

        $note->delete();

        return $this->ok(null, 'Note deleted.');
    }
}
