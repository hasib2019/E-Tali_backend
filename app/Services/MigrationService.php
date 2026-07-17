<?php

namespace App\Services;

use App\Models\User;

/**
 * Produces a faithful, id-carrying snapshot of a user's entire server ledger for
 * the one-time server→device (SQLite) migration. Unlike BackupService (which
 * remaps ids on restore), this PRESERVES the original integer ids so the device
 * import is a 1:1 copy — no remapping, no chance of mis-linking rows.
 *
 * Money is emitted as the DB decimal value; the device converts to integer paisa
 * on import. Binary media (photos/signatures) is intentionally NOT included —
 * it migrates via a separate media-sync track.
 */
class MigrationService
{
    public const VERSION = 1;

    public function snapshot(User $user): array
    {
        $user->load([
            'businesses.batches',
            'businesses.parties.transactions',
            'businesses.parties.vouchers.items',
            'businesses.products',
            'businesses.cashbookEntries',
            'businesses.cashCategories',
            'businesses.budgets',
            'businesses.savingsGoals',
            'businesses.reminders',
            'businesses.notes',
            'businesses.feePayments',
            'businesses.attendances',
            'businesses.meals',
            'businesses.messEntries',
        ]);

        $t = [
            'businesses' => [], 'batches' => [], 'parties' => [], 'products' => [],
            'transactions' => [], 'vouchers' => [], 'voucher_items' => [], 'cashbook_entries' => [],
            'cash_categories' => [], 'budgets' => [], 'savings_goals' => [], 'reminders' => [],
            'notes' => [], 'fee_payments' => [], 'attendances' => [], 'meals' => [], 'mess_entries' => [],
        ];

        $dt = fn ($v) => $v ? (string) $v : null;              // timestamp → string
        $d = fn ($v) => $v?->toDateString();                    // date → Y-m-d

        foreach ($user->businesses as $b) {
            $t['businesses'][] = [
                'id' => $b->id, 'name' => $b->name, 'type' => $b->type, 'category' => $b->category,
                'phone' => $b->phone, 'address' => $b->address, 'currency' => $b->currency,
                'meta' => $b->meta, 'created_at' => $dt($b->created_at), 'updated_at' => $dt($b->updated_at),
            ];
            foreach ($b->batches as $x) {
                $t['batches'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'name' => $x->name,
                    'schedule' => $x->schedule, 'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->parties as $p) {
                $t['parties'][] = [
                    'id' => $p->id, 'business_id' => $p->business_id, 'name' => $p->name, 'phone' => $p->phone,
                    'type' => $p->type, 'address' => $p->address, 'opening_balance' => $p->opening_balance,
                    'monthly_fee' => $p->monthly_fee, 'batch_id' => $p->batch_id, 'roll' => $p->roll,
                    'created_at' => $dt($p->created_at), 'updated_at' => $dt($p->updated_at),
                ];
                foreach ($p->transactions as $x) {
                    $t['transactions'][] = [
                        'id' => $x->id, 'business_id' => $x->business_id, 'party_id' => $x->party_id,
                        'type' => $x->type, 'amount' => $x->amount, 'note' => $x->note,
                        'txn_date' => $d($x->txn_date), 'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                    ];
                }
                foreach ($p->vouchers as $v) {
                    $t['vouchers'][] = [
                        'id' => $v->id, 'business_id' => $v->business_id, 'party_id' => $v->party_id, 'type' => $v->type,
                        'voucher_date' => $d($v->voucher_date), 'total_amount' => $v->total_amount,
                        'paid_amount' => $v->paid_amount, 'due_amount' => $v->due_amount, 'note' => $v->note,
                        'created_at' => $dt($v->created_at), 'updated_at' => $dt($v->updated_at),
                    ];
                    foreach ($v->items as $i) {
                        $t['voucher_items'][] = [
                            'id' => $i->id, 'voucher_id' => $i->voucher_id, 'product_id' => $i->product_id,
                            'name' => $i->name, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price,
                            'line_total' => $i->line_total, 'created_at' => $dt($i->created_at), 'updated_at' => $dt($i->updated_at),
                        ];
                    }
                }
            }
            foreach ($b->products as $x) {
                $t['products'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'name' => $x->name, 'unit' => $x->unit,
                    'sale_price' => $x->sale_price, 'purchase_price' => $x->purchase_price, 'stock' => $x->stock,
                    'category' => $x->category, 'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->cashbookEntries as $x) {
                $t['cashbook_entries'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'type' => $x->type, 'amount' => $x->amount,
                    'category' => $x->category, 'note' => $x->note, 'entry_date' => $d($x->entry_date),
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->cashCategories as $x) {
                $t['cash_categories'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'type' => $x->type, 'name' => $x->name,
                    'icon' => $x->icon, 'sort' => $x->sort, 'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->budgets as $x) {
                // Legacy monthly budgets (pre make_budgets_named) have a NULL name; the
                // device schema requires budgets.name NOT NULL, so fall back to the period.
                $t['budgets'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id,
                    'name' => $x->name ?? $x->period ?? 'Budget', 'period' => $x->period,
                    'amount' => $x->amount, 'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->savingsGoals as $x) {
                $t['savings_goals'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'name' => $x->name,
                    'target_amount' => $x->target_amount, 'saved_amount' => $x->saved_amount,
                    'target_date' => $d($x->target_date), 'is_done' => (int) $x->is_done,
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->reminders as $x) {
                $t['reminders'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'title' => $x->title, 'amount' => $x->amount,
                    'due_date' => $d($x->due_date), 'is_done' => (int) $x->is_done, 'note' => $x->note,
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->notes as $x) {
                $t['notes'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'title' => $x->title, 'body' => $x->body,
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->feePayments as $x) {
                $t['fee_payments'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'party_id' => $x->party_id,
                    'cashbook_entry_id' => $x->cashbook_entry_id, 'period' => $x->period, 'amount' => $x->amount,
                    'paid_at' => $d($x->paid_at), 'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->attendances as $x) {
                $t['attendances'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'party_id' => $x->party_id,
                    'date' => $d($x->date), 'status' => $x->status,
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->meals as $x) {
                $t['meals'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'party_id' => $x->party_id,
                    'period' => $x->period, 'count' => $x->count,
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
            foreach ($b->messEntries as $x) {
                $t['mess_entries'][] = [
                    'id' => $x->id, 'business_id' => $x->business_id, 'party_id' => $x->party_id,
                    'period' => $x->period, 'kind' => $x->kind, 'amount' => $x->amount,
                    'entry_date' => $d($x->entry_date), 'note' => $x->note,
                    'created_at' => $dt($x->created_at), 'updated_at' => $dt($x->updated_at),
                ];
            }
        }

        $counts = [];
        foreach ($t as $name => $rows) {
            $counts[$name] = count($rows);
        }

        return [
            'version' => self::VERSION,
            'user_id' => $user->id,
            'exported_at' => now()->toIso8601String(),
            'counts' => $counts,
            'tables' => $t,
        ];
    }
}
