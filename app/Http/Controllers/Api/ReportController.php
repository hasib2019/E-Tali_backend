<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    /**
     * Dashboard summary: total receivable / payable, cash in hand, counts.
     */
    public function summary(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $parties = $business->parties()
            ->withSum(['transactions as debit_total' => fn ($q) => $q->where('type', 'debit')], 'amount')
            ->withSum(['transactions as credit_total' => fn ($q) => $q->where('type', 'credit')], 'amount')
            ->withSum(['vouchers as sale_due' => fn ($q) => $q->where('type', 'sale')], 'due_amount')
            ->withSum(['vouchers as purchase_due' => fn ($q) => $q->where('type', 'purchase')], 'due_amount')
            ->get();

        $receivable = 0.0;   // you will get (পাবেন)
        $payable = 0.0;      // you will give (দিবেন)

        foreach ($parties as $p) {
            $balance = (float) $p->opening_balance
                + (float) $p->debit_total - (float) $p->credit_total
                + (float) $p->sale_due - (float) $p->purchase_due;
            if ($balance > 0) {
                $receivable += $balance;
            } elseif ($balance < 0) {
                $payable += abs($balance);
            }
        }

        $cashIn = (float) $business->cashbookEntries()->where('type', 'cash_in')->sum('amount');
        $cashOut = (float) $business->cashbookEntries()->where('type', 'cash_out')->sum('amount');

        return $this->ok([
            'total_receivable' => round($receivable, 2),
            'total_payable' => round($payable, 2),
            'cash_in' => round($cashIn, 2),
            'cash_out' => round($cashOut, 2),
            'cash_in_hand' => round($cashIn - $cashOut, 2),
            'customer_count' => $parties->where('type', 'customer')->count(),
            'supplier_count' => $parties->where('type', 'supplier')->count(),
        ]);
    }

    /**
     * Monthly income/expense snapshot for the personal categories:
     * this month's income, expense, net, the set budget, and an
     * expense-by-category breakdown. ?month=YYYY-MM (defaults to now).
     */
    public function monthly(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $month = $request->string('month')->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $mon] = explode('-', $month);

        $entries = $business->cashbookEntries()
            ->whereYear('entry_date', (int) $year)
            ->whereMonth('entry_date', (int) $mon)
            ->get(['type', 'category', 'amount']);

        $income = round((float) $entries->where('type', 'cash_in')->sum(fn ($e) => (float) $e->amount), 2);
        $expense = round((float) $entries->where('type', 'cash_out')->sum(fn ($e) => (float) $e->amount), 2);

        $byCategory = $entries->where('type', 'cash_out')
            ->groupBy(fn ($e) => $e->category ?: 'Others')
            ->map(fn ($grp, $name) => [
                'name' => $name,
                'total' => round((float) $grp->sum(fn ($e) => (float) $e->amount), 2),
            ])
            ->sortByDesc('total')
            ->values();

        $budget = $business->budgets()->where('period', $month)->value('amount');

        return $this->ok([
            'month' => $month,
            'income' => $income,
            'expense' => $expense,
            'net' => round($income - $expense, 2),
            'budget' => $budget !== null ? (float) $budget : null,
            'by_category' => $byCategory,
        ]);
    }

    /**
     * Personal-finance snapshot (salaried / student). Everything lives on one
     * running Balance:
     *   balance = cash-in-hand + (repayments received − money lent)
     * Salary/income raises it; expenses, savings deposits, budget spends and
     * lending lower it; repayments received raise it. ?month=YYYY-MM.
     */
    public function personal(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $month = $request->string('month')->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $mon] = explode('-', $month);

        $cashIn = (float) $business->cashbookEntries()->where('type', 'cash_in')->sum('amount');
        $cashOut = (float) $business->cashbookEntries()->where('type', 'cash_out')->sum('amount');
        $cashInHand = $cashIn - $cashOut;

        // Savings pool = deposits (cash-out 'Savings') − withdrawals (cash-in 'Savings').
        $savingsOut = (float) $business->cashbookEntries()->where('type', 'cash_out')->where('category', 'Savings')->sum('amount');
        $savingsIn = (float) $business->cashbookEntries()->where('type', 'cash_in')->where('category', 'Savings')->sum('amount');

        // Person ledger effect on cash: repayment received (credit) up, lending (debit) down.
        $credit = (float) $business->transactions()->where('type', 'credit')->sum('amount');
        $debit = (float) $business->transactions()->where('type', 'debit')->sum('amount');
        $txnNet = $credit - $debit;

        $balance = round($cashInHand + $txnNet, 2);

        // This month (savings transfers excluded from "expense").
        $monthEntries = $business->cashbookEntries()
            ->whereYear('entry_date', (int) $year)
            ->whereMonth('entry_date', (int) $mon)
            ->get(['type', 'category', 'amount']);
        $monthIncome = round((float) $monthEntries->where('type', 'cash_in')->sum(fn ($e) => (float) $e->amount), 2);
        $expenseEntries = $monthEntries->where('type', 'cash_out')->filter(fn ($e) => ($e->category ?: '') !== 'Savings');
        $monthExpense = round((float) $expenseEntries->sum(fn ($e) => (float) $e->amount), 2);
        $byCategory = $expenseEntries
            ->groupBy(fn ($e) => $e->category ?: 'Others')
            ->map(fn ($grp, $name) => ['name' => $name, 'total' => round((float) $grp->sum(fn ($e) => (float) $e->amount), 2)])
            ->sortByDesc('total')
            ->values();

        $salaryAdded = $business->cashbookEntries()
            ->where('type', 'cash_in')
            ->whereIn('category', ['Salary', 'Allowance', 'Income'])
            ->whereYear('entry_date', (int) $year)
            ->whereMonth('entry_date', (int) $mon)
            ->exists();

        // Who owes you / whom you owe, from the simple person ledger.
        $parties = $business->parties()
            ->withSum(['transactions as debit_total' => fn ($q) => $q->where('type', 'debit')], 'amount')
            ->withSum(['transactions as credit_total' => fn ($q) => $q->where('type', 'credit')], 'amount')
            ->get();
        $lent = 0.0;
        $borrowed = 0.0;
        foreach ($parties as $p) {
            $bal = (float) $p->opening_balance + (float) $p->debit_total - (float) $p->credit_total;
            if ($bal > 0) {
                $lent += $bal;
            } elseif ($bal < 0) {
                $borrowed += abs($bal);
            }
        }

        return $this->ok([
            'month' => $month,
            'balance' => $balance,
            'cash_in_hand' => round($cashInHand, 2),
            'savings' => round($savingsOut - $savingsIn, 2),
            'salary' => (float) data_get($business->meta, 'monthly_salary', 0),
            'salary_added' => $salaryAdded,
            'month_income' => $monthIncome,
            'month_expense' => $monthExpense,
            'month_net' => round($monthIncome - $monthExpense, 2),
            'lent' => round($lent, 2),
            'borrowed' => round($borrowed, 2),
            'by_category' => $byCategory,
        ]);
    }

    /**
     * Cashbook report over an optional date range.
     */
    public function cashbook(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $base = $business->cashbookEntries()
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')));

        $cashIn = (float) (clone $base)->where('type', 'cash_in')->sum('amount');
        $cashOut = (float) (clone $base)->where('type', 'cash_out')->sum('amount');

        $entries = (clone $base)->orderByDesc('entry_date')->orderByDesc('id')->get();

        return $this->ok([
            'cash_in' => round($cashIn, 2),
            'cash_out' => round($cashOut, 2),
            'net' => round($cashIn - $cashOut, 2),
            'entries' => $entries,
        ]);
    }

    /**
     * Receivable / payable party list (dues report).
     * ?type=customer|supplier to filter.
     */
    public function parties(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $parties = $business->parties()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->withSum(['transactions as debit_total' => fn ($q) => $q->where('type', 'debit')], 'amount')
            ->withSum(['transactions as credit_total' => fn ($q) => $q->where('type', 'credit')], 'amount')
            ->withSum(['vouchers as sale_due' => fn ($q) => $q->where('type', 'sale')], 'due_amount')
            ->withSum(['vouchers as purchase_due' => fn ($q) => $q->where('type', 'purchase')], 'due_amount')
            ->get()
            ->map(function (Party $p) {
                $balance = round(
                    (float) $p->opening_balance
                    + (float) $p->debit_total - (float) $p->credit_total
                    + (float) $p->sale_due - (float) $p->purchase_due,
                    2,
                );

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'phone' => $p->phone,
                    'type' => $p->type,
                    'balance' => $balance,
                    'status' => $balance > 0 ? 'receivable' : ($balance < 0 ? 'payable' : 'settled'),
                ];
            })
            ->sortByDesc(fn ($p) => abs($p['balance']))
            ->values();

        return $this->ok($parties);
    }

    /**
     * Full ledger for a party — simple cash entries AND vouchers (bills)
     * merged into one chronological timeline with a running balance.
     */
    public function statement(Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $entries = collect();

        foreach ($party->transactions()->get() as $t) {
            $entries->push([
                'kind' => 'transaction',
                'id' => $t->id,
                'date' => $t->txn_date->toDateString(),
                'seq' => $t->created_at?->timestamp ?? 0,
                'type' => $t->type,                 // debit | credit
                'amount' => (float) $t->amount,
                'note' => $t->note,
                'effect' => $t->type === 'debit' ? (float) $t->amount : -(float) $t->amount,
            ]);
        }

        foreach ($party->vouchers()->with('items')->get() as $v) {
            $entries->push([
                'kind' => 'voucher',
                'id' => $v->id,
                'date' => $v->voucher_date->toDateString(),
                'seq' => $v->created_at?->timestamp ?? 0,
                'voucher_type' => $v->type,         // sale | purchase
                'total_amount' => (float) $v->total_amount,
                'paid_amount' => (float) $v->paid_amount,
                'due_amount' => (float) $v->due_amount,
                'note' => $v->note,
                'items' => $v->items->map(fn ($i) => [
                    'name' => $i->name,
                    'quantity' => (float) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                ])->values(),
                'effect' => $v->balanceEffect(),
            ]);
        }

        $running = (float) $party->opening_balance;

        $rows = $entries
            ->sortBy([['date', 'asc'], ['seq', 'asc'], ['id', 'asc']])
            ->values()
            ->map(function (array $e) use (&$running) {
                $running += $e['effect'];
                $e['running_balance'] = round($running, 2);
                unset($e['effect'], $e['seq']);

                return $e;
            });

        return $this->ok([
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'phone' => $party->phone,
                'type' => $party->type,
                'opening_balance' => (float) $party->opening_balance,
            ],
            'closing_balance' => round($running, 2),
            'entries' => $rows,
        ]);
    }
}
