<?php

namespace App\Http\Controllers\Api;

use App\Models\Party;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VoucherController extends ApiController
{
    /**
     * List a party's vouchers (bills), newest first, with line items.
     */
    public function index(Request $request, Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $vouchers = $party->vouchers()
            ->with('items')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Voucher $v) => $this->present($v, $request));

        return $this->ok($vouchers);
    }

    /**
     * Create a bill for a party. Type is derived from the party:
     * customer -> sale (বিক্রি), supplier -> purchase (ক্রয়).
     */
    public function store(Request $request, Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $data = $request->validate([
            'voucher_date' => ['nullable', 'date'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string'],       // base64 data URL
            'signature' => ['nullable', 'string'],   // base64 data URL
        ]);

        $type = $party->type === 'customer' ? 'sale' : 'purchase';

        $total = 0.0;
        foreach ($data['items'] as $it) {
            $total += round((float) $it['quantity'] * (float) $it['unit_price'], 2);
        }
        $total = round($total, 2);
        $paid = min(max((float) ($data['paid_amount'] ?? 0), 0), $total);
        $due = round($total - $paid, 2);

        $voucher = DB::transaction(function () use ($party, $request, $data, $type, $total, $paid, $due) {
            $voucher = $party->vouchers()->create([
                'business_id' => $party->business_id,
                'user_id' => $request->user()->id,
                'type' => $type,
                'voucher_date' => $data['voucher_date'] ?? now()->toDateString(),
                'total_amount' => $total,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $it) {
                $voucher->items()->create([
                    'product_id' => $it['product_id'] ?? null,
                    'name' => $it['name'],
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'],
                    'line_total' => round((float) $it['quantity'] * (float) $it['unit_price'], 2),
                ]);
            }

            $updates = [];
            if (! empty($data['image'])) {
                $updates['image_path'] = $this->storeBase64($data['image'], $voucher->id, 'image');
            }
            if (! empty($data['signature'])) {
                $updates['signature_path'] = $this->storeBase64($data['signature'], $voucher->id, 'signature');
            }
            if ($updates) {
                $voucher->update($updates);
            }

            return $voucher;
        });

        return $this->ok($this->present($voucher->load('items'), $request), 'Voucher saved.', 201);
    }

    public function show(Request $request, Voucher $voucher): JsonResponse
    {
        $this->ensureOwnsVoucher($voucher);

        return $this->ok($this->present($voucher->load('items'), $request));
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        $this->ensureOwnsVoucher($voucher);

        foreach (['image_path', 'signature_path'] as $col) {
            if ($voucher->$col) {
                Storage::disk('public')->delete($voucher->$col);
            }
        }
        $voucher->delete();

        return $this->ok(null, 'Voucher deleted.');
    }

    /** Store a base64 data URL to the public disk, return its relative path. */
    private function storeBase64(string $dataUrl, int $voucherId, string $name): ?string
    {
        $ext = 'png';
        $payload = $dataUrl;
        if (preg_match('#^data:image/(\w+);base64,#', $dataUrl, $m)) {
            $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            $payload = substr($dataUrl, strpos($dataUrl, ',') + 1);
        }
        $binary = base64_decode($payload, true);
        if ($binary === false) {
            return null;
        }
        $path = "vouchers/{$voucherId}/{$name}.{$ext}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /** Voucher as array + absolute image/signature URLs (matching the request host). */
    private function present(Voucher $voucher, Request $request): array
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $arr = $voucher->toArray();
        $arr['image_url'] = $voucher->image_path ? "{$base}/storage/{$voucher->image_path}" : null;
        $arr['signature_url'] = $voucher->signature_path ? "{$base}/storage/{$voucher->signature_path}" : null;

        return $arr;
    }
}
