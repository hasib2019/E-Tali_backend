<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\CashbookEntry;
use App\Models\Party;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    /**
     * Standard success envelope: { success, message?, data }.
     */
    protected function ok($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        $payload['data'] = $data;

        return response()->json($payload, $status);
    }

    /**
     * Abort with 403 unless the current user owns the business.
     */
    protected function ensureOwnsBusiness(Business $business): void
    {
        abort_unless($business->user_id === request()->user()->id, 403, 'This business does not belong to you.');
    }

    protected function ensureOwnsParty(Party $party): void
    {
        abort_unless($party->business->user_id === request()->user()->id, 403, 'This party does not belong to you.');
    }

    protected function ensureOwnsTransaction(Transaction $transaction): void
    {
        abort_unless($transaction->business->user_id === request()->user()->id, 403, 'This transaction does not belong to you.');
    }

    protected function ensureOwnsCashbookEntry(CashbookEntry $entry): void
    {
        abort_unless($entry->business->user_id === request()->user()->id, 403, 'This entry does not belong to you.');
    }

    protected function ensureOwnsProduct(Product $product): void
    {
        abort_unless($product->business->user_id === request()->user()->id, 403, 'This product does not belong to you.');
    }

    protected function ensureOwnsVoucher(Voucher $voucher): void
    {
        abort_unless($voucher->business->user_id === request()->user()->id, 403, 'This voucher does not belong to you.');
    }
}
