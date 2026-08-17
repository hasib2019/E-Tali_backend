<?php

namespace App\Http\Controllers\Api;

use App\Models\Donation;
use App\Models\DonationSetting;
use App\Models\DonationTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonationController extends ApiController
{
    /** Active tiers + the bKash number/intro copy for the Support Us screen. */
    public function tiers(): JsonResponse
    {
        $settings = DonationSetting::current();

        return $this->ok([
            'bkash_number' => $settings->bkash_number,
            'intro_title' => $settings->intro_title,
            'intro_message' => $settings->intro_message,
            'tiers' => DonationTier::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'key', 'label', 'amount', 'icon']),
        ]);
    }

    /** Record that the user sent (or intends to send) a bKash donation for a tier. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'donation_tier_id' => ['required', 'integer', 'exists:donation_tiers,id'],
            'trx_id' => ['nullable', 'string', 'max:40'],
        ]);

        $tier = DonationTier::findOrFail($data['donation_tier_id']);
        $settings = DonationSetting::current();

        abort_if(blank($settings->bkash_number), 422, 'Donations are not configured yet. Please try again later.');

        $donation = Donation::create([
            'user_id' => $request->user()->id,
            'donation_tier_id' => $tier->id,
            'tier_label' => $tier->label,
            'amount' => $tier->amount,
            'bkash_number' => $settings->bkash_number,
            'trx_id' => $data['trx_id'] ?? null,
            'status' => 'pending',
        ]);

        return $this->ok(['id' => $donation->id], 'Thank you for your support!', 201);
    }
}
