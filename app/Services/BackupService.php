<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Support\CategoryRegistry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackupService
{
    /**
     * Current backup schema version.
     * v2 adds: business category/meta; party monthly_fee/batch_id/roll;
     * cash_categories, budgets, savings_goals, reminders, batches,
     * fee_payments and attendances. v1 backups still restore fine (new
     * fields default), so the reader stays backward compatible.
     */
    public const VERSION = 2;

    /**
     * Serialize a user's entire ledger (all businesses and their nested data)
     * into a portable, self-contained array ready to be JSON-encoded.
     *
     * Note: attached files (voucher images/signatures, cashbook attachments)
     * are referenced by their stored path but the binary files are not included.
     */
    public function export(User $user): array
    {
        $user->load([
            'businesses.products',
            'businesses.cashbookEntries',
            'businesses.parties.transactions',
            'businesses.parties.vouchers.items',
            'businesses.cashCategories',
            'businesses.budgets',
            'businesses.savingsGoals',
            'businesses.reminders',
            'businesses.batches',
            'businesses.feePayments',
            'businesses.attendances',
            'businesses.meals',
            'businesses.messEntries',
        ]);

        $businesses = $user->businesses->map(fn (Business $b) => [
            'name' => $b->name,
            'type' => $b->type,
            'category' => $b->category,
            'phone' => $b->phone,
            'address' => $b->address,
            'currency' => $b->currency,
            'meta' => $b->meta,
            'products' => $b->products->map(fn ($p) => [
                'id' => $p->id, // original id, used to relink voucher items on restore
                'name' => $p->name,
                'unit' => $p->unit,
                'sale_price' => $p->sale_price,
                'purchase_price' => $p->purchase_price,
                'stock' => $p->stock,
                'category' => $p->category,
            ])->values(),
            'batches' => $b->batches->map(fn ($bt) => [
                'id' => $bt->id, // original id, used to relink parties on restore
                'name' => $bt->name,
                'schedule' => $bt->schedule,
            ])->values(),
            'parties' => $b->parties->map(fn ($party) => [
                'id' => $party->id, // original id, used to relink fees/attendance on restore
                'name' => $party->name,
                'phone' => $party->phone,
                'type' => $party->type,
                'address' => $party->address,
                'opening_balance' => $party->opening_balance,
                'monthly_fee' => $party->monthly_fee,
                'batch_id' => $party->batch_id, // original batch id
                'roll' => $party->roll,
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
                'id' => $c->id, // original id, used to relink fee payments on restore
                'type' => $c->type,
                'amount' => $c->amount,
                'category' => $c->category,
                'note' => $c->note,
                'entry_date' => $c->entry_date?->toDateString(),
            ])->values(),
            'cash_categories' => $b->cashCategories->map(fn ($cc) => [
                'type' => $cc->type,
                'name' => $cc->name,
                'icon' => $cc->icon,
                'sort' => $cc->sort,
            ])->values(),
            'budgets' => $b->budgets->map(fn ($bd) => [
                'name' => $bd->name,
                'amount' => $bd->amount,
            ])->values(),
            'savings_goals' => $b->savingsGoals->map(fn ($sg) => [
                'name' => $sg->name,
                'target_amount' => $sg->target_amount,
                'saved_amount' => $sg->saved_amount,
                'target_date' => $sg->target_date?->toDateString(),
                'is_done' => $sg->is_done,
            ])->values(),
            'reminders' => $b->reminders->map(fn ($r) => [
                'title' => $r->title,
                'amount' => $r->amount,
                'due_date' => $r->due_date?->toDateString(),
                'is_done' => $r->is_done,
                'note' => $r->note,
            ])->values(),
            'fee_payments' => $b->feePayments->map(fn ($fp) => [
                'party_id' => $fp->party_id,                 // original party id
                'cashbook_entry_id' => $fp->cashbook_entry_id, // original cashbook id
                'period' => $fp->period,
                'amount' => $fp->amount,
                'paid_at' => $fp->paid_at?->toDateString(),
            ])->values(),
            'attendances' => $b->attendances->map(fn ($a) => [
                'party_id' => $a->party_id,                  // original party id
                'date' => $a->date?->toDateString(),
                'status' => $a->status,
            ])->values(),
            'meals' => $b->meals->map(fn ($m) => [
                'party_id' => $m->party_id,                  // original party id
                'period' => $m->period,
                'count' => $m->count,
            ])->values(),
            'mess_entries' => $b->messEntries->map(fn ($e) => [
                'party_id' => $e->party_id,                  // original party id (nullable)
                'period' => $e->period,
                'kind' => $e->kind,
                'amount' => $e->amount,
                'entry_date' => $e->entry_date?->toDateString(),
                'note' => $e->note,
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
     * trusted; they are only used to relink nested records (voucher items →
     * products, parties → batches, fees/attendance → parties/cashbook).
     * `replace` (default) wipes the user's existing businesses first (DB
     * cascade clears all children); `merge` adds alongside them.
     */
    public function import(User $user, array $data, string $mode = 'replace'): array
    {
        $version = $data['version'] ?? null;
        if ($version === null || (int) $version > self::VERSION) {
            throw new RuntimeException('Unsupported backup version.');
        }

        $counts = [
            'businesses' => 0, 'products' => 0, 'parties' => 0, 'transactions' => 0,
            'vouchers' => 0, 'voucher_items' => 0, 'cashbook_entries' => 0,
            'cash_categories' => 0, 'budgets' => 0, 'savings_goals' => 0,
            'reminders' => 0, 'batches' => 0, 'fee_payments' => 0, 'attendances' => 0,
            'meals' => 0, 'mess_entries' => 0,
        ];

        DB::transaction(function () use ($user, $data, $mode, &$counts) {
            if ($mode === 'replace') {
                // DB-level ON DELETE CASCADE clears every child table.
                $user->businesses()->delete();
            }

            foreach ($data['businesses'] ?? [] as $b) {
                $business = $user->businesses()->create([
                    'name' => $b['name'] ?? 'Untitled',
                    'type' => $b['type'] ?? null,
                    'category' => CategoryRegistry::normalize($b['category'] ?? null),
                    'phone' => $b['phone'] ?? null,
                    'address' => $b['address'] ?? null,
                    'currency' => $b['currency'] ?? 'BDT',
                    'meta' => $b['meta'] ?? null,
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
                        'stock' => $p['stock'] ?? 0,
                        'category' => $p['category'] ?? null,
                    ]);
                    if (isset($p['id'])) {
                        $productMap[$p['id']] = $product->id;
                    }
                    $counts['products']++;
                }

                // Batches before parties — parties reference a batch.
                $batchMap = [];
                foreach ($b['batches'] ?? [] as $bt) {
                    $batch = $business->batches()->create([
                        'name' => $bt['name'] ?? 'Batch',
                        'schedule' => $bt['schedule'] ?? null,
                    ]);
                    if (isset($bt['id'])) {
                        $batchMap[$bt['id']] = $batch->id;
                    }
                    $counts['batches']++;
                }

                // Parties — map old id -> new id for fees/attendance relink.
                $partyMap = [];
                foreach ($b['parties'] ?? [] as $partyData) {
                    $party = $business->parties()->create([
                        'name' => $partyData['name'] ?? 'Party',
                        'phone' => $partyData['phone'] ?? null,
                        'type' => $partyData['type'] ?? 'customer',
                        'address' => $partyData['address'] ?? null,
                        'opening_balance' => $partyData['opening_balance'] ?? 0,
                        'monthly_fee' => $partyData['monthly_fee'] ?? null,
                        'batch_id' => isset($partyData['batch_id']) ? ($batchMap[$partyData['batch_id']] ?? null) : null,
                        'roll' => $partyData['roll'] ?? null,
                    ]);
                    if (isset($partyData['id'])) {
                        $partyMap[$partyData['id']] = $party->id;
                    }
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

                // Cashbook — map old id -> new id for fee payment relink.
                $cashbookMap = [];
                foreach ($b['cashbook_entries'] ?? [] as $c) {
                    $entry = $business->cashbookEntries()->create([
                        'user_id' => $user->id,
                        'type' => $c['type'] ?? 'cash_in',
                        'amount' => $c['amount'] ?? 0,
                        'category' => $c['category'] ?? null,
                        'note' => $c['note'] ?? null,
                        'entry_date' => $c['entry_date'] ?? now()->toDateString(),
                    ]);
                    if (isset($c['id'])) {
                        $cashbookMap[$c['id']] = $entry->id;
                    }
                    $counts['cashbook_entries']++;
                }

                // Personal-finance buckets (no cross-references).
                foreach ($b['cash_categories'] ?? [] as $cc) {
                    $business->cashCategories()->create([
                        'type' => $cc['type'] ?? 'out',
                        'name' => $cc['name'] ?? 'Other',
                        'icon' => $cc['icon'] ?? null,
                        'sort' => $cc['sort'] ?? 0,
                    ]);
                    $counts['cash_categories']++;
                }

                foreach ($b['budgets'] ?? [] as $bd) {
                    if (empty($bd['name'])) {
                        continue;
                    }
                    $business->budgets()->create([
                        'name' => $bd['name'],
                        'amount' => $bd['amount'] ?? null,
                    ]);
                    $counts['budgets']++;
                }

                foreach ($b['savings_goals'] ?? [] as $sg) {
                    $business->savingsGoals()->create([
                        'name' => $sg['name'] ?? 'Goal',
                        'target_amount' => $sg['target_amount'] ?? 0,
                        'saved_amount' => $sg['saved_amount'] ?? 0,
                        'target_date' => $sg['target_date'] ?? null,
                        'is_done' => $sg['is_done'] ?? false,
                    ]);
                    $counts['savings_goals']++;
                }

                foreach ($b['reminders'] ?? [] as $r) {
                    if (empty($r['due_date'])) {
                        continue;
                    }
                    $business->reminders()->create([
                        'title' => $r['title'] ?? 'Reminder',
                        'amount' => $r['amount'] ?? null,
                        'due_date' => $r['due_date'],
                        'is_done' => $r['is_done'] ?? false,
                        'note' => $r['note'] ?? null,
                    ]);
                    $counts['reminders']++;
                }

                // Teacher fee payments — relink party + cashbook entry.
                foreach ($b['fee_payments'] ?? [] as $fp) {
                    $partyId = isset($fp['party_id']) ? ($partyMap[$fp['party_id']] ?? null) : null;
                    if ($partyId === null || empty($fp['period'])) {
                        continue; // orphan without a party can't be restored
                    }
                    $business->feePayments()->create([
                        'party_id' => $partyId,
                        'cashbook_entry_id' => isset($fp['cashbook_entry_id'])
                            ? ($cashbookMap[$fp['cashbook_entry_id']] ?? null)
                            : null,
                        'period' => $fp['period'],
                        'amount' => $fp['amount'] ?? 0,
                        'paid_at' => $fp['paid_at'] ?? now()->toDateString(),
                    ]);
                    $counts['fee_payments']++;
                }

                // Attendance — relink party.
                foreach ($b['attendances'] ?? [] as $a) {
                    $partyId = isset($a['party_id']) ? ($partyMap[$a['party_id']] ?? null) : null;
                    if ($partyId === null || empty($a['date'])) {
                        continue;
                    }
                    $business->attendances()->create([
                        'party_id' => $partyId,
                        'date' => $a['date'],
                        'status' => $a['status'] ?? 'present',
                    ]);
                    $counts['attendances']++;
                }

                // Mess meals — relink member.
                foreach ($b['meals'] ?? [] as $ml) {
                    $partyId = isset($ml['party_id']) ? ($partyMap[$ml['party_id']] ?? null) : null;
                    if ($partyId === null || empty($ml['period'])) {
                        continue;
                    }
                    $business->meals()->create([
                        'party_id' => $partyId,
                        'period' => $ml['period'],
                        'count' => $ml['count'] ?? 0,
                    ]);
                    $counts['meals']++;
                }

                // Mess deposits / bazar — relink member (null = fund).
                foreach ($b['mess_entries'] ?? [] as $me) {
                    if (empty($me['period']) || empty($me['kind'])) {
                        continue;
                    }
                    $business->messEntries()->create([
                        'party_id' => isset($me['party_id']) ? ($partyMap[$me['party_id']] ?? null) : null,
                        'period' => $me['period'],
                        'kind' => $me['kind'],
                        'amount' => $me['amount'] ?? 0,
                        'entry_date' => $me['entry_date'] ?? now()->toDateString(),
                        'note' => $me['note'] ?? null,
                    ]);
                    $counts['mess_entries']++;
                }
            }
        });

        return $counts;
    }
}
