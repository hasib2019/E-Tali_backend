<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\Business;
use App\Models\CashbookEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookController extends ApiController
{
    use HandlesMedia;

    /**
     * List cashbook entries of a business.
     * Filters: ?type=cash_in|cash_out & ?from=YYYY-MM-DD & ?to=YYYY-MM-DD
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $entries = $business->cashbookEntries()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CashbookEntry $e) => $this->present($e));

        return $this->ok($entries);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
            'entry_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $entry = $business->cashbookEntries()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'category' => $data['category'] ?? null,
            'note' => $data['note'] ?? null,
            'user_id' => $request->user()->id,
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
        ]);

        $this->attachMedia($entry, $data);

        return $this->ok($this->present($entry, withMedia: true), 'Cashbook entry saved.', 201);
    }

    public function show(CashbookEntry $cashbookEntry): JsonResponse
    {
        $this->ensureOwnsCashbookEntry($cashbookEntry);

        return $this->ok($this->present($cashbookEntry, withMedia: true));
    }

    public function update(Request $request, CashbookEntry $cashbookEntry): JsonResponse
    {
        $this->ensureOwnsCashbookEntry($cashbookEntry);

        $data = $request->validate([
            'type' => ['sometimes', 'in:cash_in,cash_out'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
            'entry_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $cashbookEntry->update(
            array_intersect_key($data, array_flip(['type', 'amount', 'category', 'note', 'entry_date'])),
        );

        $this->attachMedia($cashbookEntry, $data);

        return $this->ok($this->present($cashbookEntry->fresh(), withMedia: true), 'Cashbook entry updated.');
    }

    public function destroy(CashbookEntry $cashbookEntry): JsonResponse
    {
        $this->ensureOwnsCashbookEntry($cashbookEntry);

        $this->deleteMedia($cashbookEntry->image_path, $cashbookEntry->signature_path);
        $cashbookEntry->delete();

        return $this->ok(null, 'Entry deleted.');
    }

    /** Persist any supplied base64 image/signature onto the entry. */
    private function attachMedia(CashbookEntry $entry, array $data): void
    {
        $updates = [];
        if (! empty($data['image'])) {
            $updates['image_path'] = $this->storeBase64($data['image'], 'cashbook', $entry->id, 'image');
        }
        if (! empty($data['signature'])) {
            $updates['signature_path'] = $this->storeBase64($data['signature'], 'cashbook', $entry->id, 'signature');
        }
        if ($updates) {
            $entry->update($updates);
        }
    }

    /** Entry as array; embeds media as data URIs when requested. */
    private function present(CashbookEntry $entry, bool $withMedia = false): array
    {
        $arr = $entry->toArray();
        $arr['has_image'] = (bool) $entry->image_path;
        $arr['has_signature'] = (bool) $entry->signature_path;
        if ($withMedia) {
            $arr['image_url'] = $this->mediaDataUri($entry->image_path);
            $arr['signature_url'] = $this->mediaDataUri($entry->signature_path);
        }

        return $arr;
    }
}
