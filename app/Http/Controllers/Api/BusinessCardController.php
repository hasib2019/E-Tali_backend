<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\BusinessCard;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Publishes the owner's business card as a public link (/card/{token}).
 * Upserted per user + business so the shared link never changes.
 */
class BusinessCardController extends ApiController
{
    use HandlesMedia;

    /** ~3 MB of image bytes once base64 is decoded. */
    private const MAX_DATA_URI = 4_200_000;

    public function store(Request $request): JsonResponse
    {
        $image = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! preg_match('#^data:image/(png|jpe?g|webp);base64,[A-Za-z0-9+/=\r\n]+$#', $value)) {
                $fail('The :attribute must be a PNG, JPEG or WebP image.');
            }
        };

        $data = $request->validate([
            'business_key' => ['required', 'string', 'max:64'],
            'template' => ['required', 'string', Rule::in(array_keys(BusinessCard::PALETTES))],
            'lang' => ['nullable', 'in:bn,en'],
            'name' => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:300'],
            'website' => ['nullable', 'string', 'max:190'],
            'image' => ['nullable', 'string', 'max:'.self::MAX_DATA_URI, $image],
            // The card as the app rendered it — the link preview and the page's hero.
            'card_image' => ['nullable', 'string', 'max:'.self::MAX_DATA_URI, $image],
        ]);

        $user = $request->user();
        $card = BusinessCard::firstOrNew(['user_id' => $user->id, 'business_key' => $data['business_key']]);
        if (! $card->exists) {
            $card->token = Str::random(32);
        }
        $card->template = $data['template'];
        $card->data = [
            'lang' => $data['lang'] ?? 'bn',
            'name' => $data['name'],
            'designation' => $data['designation'] ?? null,
            'business_name' => $data['business_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
        ];
        $card->save();

        // An absent image means "no image": the card is replaced as a whole.
        $old = [$card->image_path, $card->card_path];
        $card->image_path = ! empty($data['image']) ? $this->storeBase64($data['image'], 'business-cards', $card->id, 'photo-'.Str::random(8)) : null;
        $card->card_path = ! empty($data['card_image']) ? $this->storeBase64($data['card_image'], 'business-cards', $card->id, 'card-'.Str::random(8)) : null;
        $card->save();
        $this->deleteMedia(...array_values(array_diff(array_filter($old), [$card->image_path, $card->card_path])));

        return $this->ok(['url' => route('business-card.show', $card->token)], 'Card shared', $card->wasRecentlyCreated ? 201 : 200);
    }
}
