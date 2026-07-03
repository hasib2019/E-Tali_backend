<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackupService
{
    /** Current backup schema version. */
    public const VERSION = 1;

    /**
     * Serialize a user's entire ledger (all businesses and their nested data)
     * into a portable, self-contained array ready to be JSON-encoded.
     *
     * Note: attached files (voucher images/signatures, transaction attachments)
     * are referenced by their stored path but the binary files are not included.
     */
    public function export(User $user): array
    {
        $user->load([
            'businesses.products',
            'businesses.cashbookEntries',
            'businesses.parties.transactions',
            'businesses.parties.vouchers.items',
        ]);

        $businesses = $user->businesses->map(fn (Business $b) => [
            'name' => $b->name,
            'type' => $b->type,
            'phone' => $b->phone,
            'address' => $b->address,
            'currency' => $b->currency,
            'products' => $b->products->map(fn ($p) => [
                'id' => $p->id, // original id, used to relink voucher items on restore
                'name' => $p->name,
                'unit' => $p->unit,
                'sale_price' => $p->sale_price,
                'purchase_price' => $p->purchase_price,
            ])->values(),
            'parties' => $b->parties->map(fn ($party) => [
                'name' => $party->name,
                'phone' => $party->phone,
                'type' => $party->type,
                'address' => $party->address,
                'opening_balance' => $party->opening_balance,
                'transactions' => $party->transactions->map(fn ($t) => [
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'note' => $t->note,
                    'txn_date' => $t->txn_date?->toDateString(),
                ])->values(),
                'vouchers' => $party->vouchers->map(fn ($v) => [
                    'type' => $v->type,
                    'voucher_date' => $v->voucher_date?->toDateString(),
                    'total_amount' => $v->total_amount,
                    'paid_amount' => $v->paid_amount,
                    'due_amount' => $v->due_amount,
                    'note' => $v->note,
                    'items' => $v->items->map(fn ($it) => [
                        'product_id' => $it->product_id,  // original product id
                        'name' => $it->name,
                        'quantity' => $it->quantity,
                        'unit_price' => $it->unit_price,
                        'line_total' => $it->line_total,
                    ])->values(),
                ])->values(),
            ])->values(),
            'cashbook_entries' => $b->cashbookEntries->map(fn ($c) => [
                'type' => $c->type,
                'amount' => $c->amount,
                'category' => $c->category,
                'note' => $c->note,
                'entry_date' => $c->entry_date?->toDateString(),
            ])->values(),
        ])->values();

        return [
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'businesses' => $businesses,
        ];
    }

    /**
     * Restore a backup into the authenticated user's account.
     *
     * Everything is (re)created under $user->id — ids in the payload are never
     * trusted. `replace` (default) wipes the user's existing businesses first;
     * `merge` adds alongside them. Returns a count summary.
     */
    public function import(User $user, array $data, string $mode = 'replace'): array
    {
        $version = $data['version'] ?? null;
        if ($version === null || (int) $version > self::VERSION) {
            throw new RuntimeException('Unsupported backup version.');
        }

        $counts = ['businesses' => 0, 'products' => 0, 'parties' => 0, 'transactions' => 0, 'vouchers' => 0, 'voucher_items' => 0, 'cashbook_entries' => 0];

        DB::transaction(function () use ($user, $data, $mode, &$counts) {
            if ($mode === 'replace') {
                // Cascades to parties/products/cashbook/transactions/vouchers/items.
                $user->businesses()->delete();
            }

            foreach ($data['businesses'] ?? [] as $b) {
                $business = $user->businesses()->create([
                    'name' => $b['name'] ?? 'Untitled',
                    'type' => $b['type'] ?? null,
                    'phone' => $b['phone'] ?? null,
                    'address' => $b['address'] ?? null,
                    'currency' => $b['currency'] ?? 'BDT',
                ]);
                $counts['businesses']++;

                // Products first — build old id -> new id map for voucher items.
                $productMap = [];
                foreach ($b['products'] ?? [] as $p) {
                    $product = $business->products()->create([
                        'name' => $p['name'] ?? 'Item',
                        'unit' => $p['unit'] ?? null,
                        'sale_price' => $p['sale_price'] ?? 0,
                        'purchase_price' => $p['purchase_price'] ?? 0,
                    ]);
                    if (isset($p['id'])) {
                        $productMap[$p['id']] = $product->id;
                    }
                    $counts['products']++;
                }

                foreach ($b['parties'] ?? [] as $partyData) {
                    $party = $business->parties()->create([
                        'name' => $partyData['name'] ?? 'Party',
                        'phone' => $partyData['phone'] ?? null,
                        'type' => $partyData['type'] ?? 'customer',
                        'address' => $partyData['address'] ?? null,
                        'opening_balance' => $partyData['opening_balance'] ?? 0,
                    ]);
                    $counts['parties']++;

                    foreach ($partyData['transactions'] ?? [] as $t) {
                        $party->transactions()->create([
                            'business_id' => $business->id,
                            'user_id' => $user->id,
                            'type' => $t['type'] ?? 'debit',
                            'amount' => $t['amount'] ?? 0,
                            'note' => $t['note'] ?? null,
                            'txn_date' => $t['txn_date'] ?? now()->toDateString(),
                        ]);
                        $counts['transactions']++;
                    }

                    foreach ($partyData['vouchers'] ?? [] as $v) {
                        $voucher = $party->vouchers()->create([
                            'business_id' => $business->id,
                            'user_id' => $user->id,
                            'type' => $v['type'] ?? 'sale',
                            'voucher_date' => $v['voucher_date'] ?? now()->toDateString(),
                            'total_amount' => $v['total_amount'] ?? 0,
                            'paid_amount' => $v['paid_amount'] ?? 0,
                            'due_amount' => $v['due_amount'] ?? 0,
                            'note' => $v['note'] ?? null,
                        ]);
                        $counts['vouchers']++;

                        foreach ($v['items'] ?? [] as $it) {
                            $voucher->items()->create([
                                'product_id' => isset($it['product_id']) ? ($productMap[$it['product_id']] ?? null) : null,
                                'name' => $it['name'] ?? 'Item',
                                'quantity' => $it['quantity'] ?? 0,
                                'unit_price' => $it['unit_price'] ?? 0,
                                'line_total' => $it['line_total'] ?? 0,
                            ]);
                            $counts['voucher_items']++;
                        }
                    }
                }

                foreach ($b['cashbook_entries'] ?? [] as $c) {
                    $business->cashbookEntries()->create([
                        'user_id' => $user->id,
                        'type' => $c['type'] ?? 'cash_in',
                        'amount' => $c['amount'] ?? 0,
                        'category' => $c['category'] ?? null,
                        'note' => $c['note'] ?? null,
                        'entry_date' => $c['entry_date'] ?? now()->toDateString(),
                    ]);
                    $counts['cashbook_entries']++;
                }
            }
        });

        return $counts;
    }
}
