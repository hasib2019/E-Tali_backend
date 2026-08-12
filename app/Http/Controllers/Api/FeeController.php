<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\FeePayment;
use App\Models\Party;
use App\Support\CategoryRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends ApiController
{
    /**
     * Monthly fee collection sheet: every student with their monthly fee,
     * what they've paid for the month, and what's due. ?month=YYYY-MM,
     * optional ?batch_id.
     */
    public function collection(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $month = $request->string('month')->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $students = $business->parties()
            ->where('type', 'customer')
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->integer('batch_id')))
            ->orderBy('name')
            ->get();

        $paidByParty = $business->feePayments()
            ->where('period', $month)
            ->get()
            ->keyBy('party_id');

        $expected = 0.0;
        $collected = 0.0;

        $rows = $students->map(function (Party $s) use ($paidByParty, &$expected, &$collected) {
            $fee = (float) $s->monthly_fee;
            $payment = $paidByParty->get($s->id);
            $paid = $payment ? (float) $payment->amount : 0.0;

            $expected += $fee;
            $collected += $paid;

            $due = round(max($fee - $paid, 0), 2);
            $status = $paid <= 0 ? 'due' : ($paid >= $fee ? 'paid' : 'partial');

            return [
                'party_id' => $s->id,
                'name' => $s->name,
                'roll' => $s->roll,
                'batch_id' => $s->batch_id,
                'monthly_fee' => round($fee, 2),
                'paid' => round($paid, 2),
                'due' => $due,
                'status' => $status,
                'payment_id' => $payment?->id,
            ];
        })->values();

        return $this->ok([
            'month' => $month,
            'expected' => round($expected, 2),
            'collected' => round($collected, 2),
            'due' => round(max($expected - $collected, 0), 2),
            'students' => $students->count(),
            'paid_count' => $rows->where('status', 'paid')->count(),
            'rows' => $rows,
        ]);
    }

    /**
     * Collect a fee installment for a member/month. `amount` is what is being
     * paid now — it ADDS to any earlier partial payment for the same month
     * (so paying 5000 then 3000 gives a total of 8000, not 3000). Keeps a single
     * matching cash-in entry in sync with the running total.
     */
    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $student = Party::findOrFail($data['party_id']);
        abort_unless($student->business_id === $business->id, 403, 'This student does not belong to you.');

        $label = CategoryRegistry::collectionLabel($business->category);

        $payment = DB::transaction(function () use ($business, $student, $data, $request, $label) {
            $existing = $business->feePayments()
                ->where('party_id', $student->id)
                ->where('period', $data['period'])
                ->first();

            // Accumulate onto the earlier partial payment for this month.
            $total = round(($existing ? (float) $existing->amount : 0) + (float) $data['amount'], 2);
            $note = "{$label} {$data['period']} · {$student->name}";

            if ($existing && $existing->cashbook_entry_id) {
                $existing->cashbookEntry?->update(['amount' => $total, 'note' => $note]);
                $entryId = $existing->cashbook_entry_id;
            } else {
                $entry = $business->cashbookEntries()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'cash_in',
                    'amount' => $total,
                    'category' => $label,
                    'note' => $note,
                    'entry_date' => now()->toDateString(),
                ]);
                $entryId = $entry->id;
            }

            return $business->feePayments()->updateOrCreate(
                ['party_id' => $student->id, 'period' => $data['period']],
                ['amount' => $total, 'paid_at' => now()->toDateString(), 'cashbook_entry_id' => $entryId],
            );
        });

        return $this->ok($payment, 'Fee collected.', 201);
    }

    public function destroy(FeePayment $feePayment): JsonResponse
    {
        $this->ensureOwnsChild($feePayment);

        DB::transaction(function () use ($feePayment) {
            $feePayment->cashbookEntry?->delete();
            $feePayment->delete();
        });

        return $this->ok(null, 'Fee record removed.');
    }
}
