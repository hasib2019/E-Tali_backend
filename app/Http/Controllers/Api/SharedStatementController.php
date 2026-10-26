<?php

namespace App\Http\Controllers\Api;

use App\Models\SharedStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Turns a statement the app already computed into a public, read-only link
 * the owner can send to their customer (`/s/{token}`).
 */
class SharedStatementController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:190'],
            'business_phone' => ['nullable', 'string', 'max:40'],
            'party_name' => ['required', 'string', 'max:190'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
            'lang' => ['nullable', 'in:bn,en'],
            'entries' => ['present', 'array', 'max:2000'],
            'entries.*.date' => ['required', 'date_format:Y-m-d'],
            'entries.*.title' => ['required', 'string', 'max:300'],
            'entries.*.gave' => ['required', 'numeric'],
            'entries.*.got' => ['required', 'numeric'],
            'entries.*.balance' => ['required', 'numeric'],
        ]);

        $statement = SharedStatement::create([
            'user_id' => $request->user()->id,
            'token' => Str::random(32),
            'business_name' => $data['business_name'],
            'party_name' => $data['party_name'],
            'payload' => [
                'business_phone' => $data['business_phone'] ?? null,
                'opening_balance' => (float) $data['opening_balance'],
                'closing_balance' => (float) $data['closing_balance'],
                'lang' => $data['lang'] ?? 'bn',
                'entries' => $data['entries'],
                'generated_at' => now()->toIso8601String(),
            ],
            'expires_at' => now()->addDays(SharedStatement::LIFETIME_DAYS),
        ]);

        return $this->ok([
            'url' => route('statement.show', $statement->token),
            'expires_at' => $statement->expires_at->toIso8601String(),
        ], 'Link created', 201);
    }
}
