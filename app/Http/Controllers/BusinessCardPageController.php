<?php

namespace App\Http\Controllers;

use App\Models\BusinessCard;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Public business-card page behind a shared link, plus its "save contact" file. */
class BusinessCardPageController extends Controller
{
    public function show(string $token): View
    {
        $card = BusinessCard::where('token', $token)->firstOrFail();
        $card->increment('views');

        $disk = Storage::disk('public');

        return view('business-card', [
            'card' => $card,
            'd' => $card->data,
            'c' => $card->palette(),
            'photo' => $card->image_path && $disk->exists($card->image_path) ? $disk->url($card->image_path) : null,
            'render' => $card->card_path && $disk->exists($card->card_path) ? $disk->url($card->card_path) : null,
        ]);
    }

    public function vcard(string $token): Response
    {
        $card = BusinessCard::where('token', $token)->firstOrFail();
        $d = $card->data;
        $esc = fn (?string $v) => str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\;'], (string) $v);

        $lines = ['BEGIN:VCARD', 'VERSION:3.0', 'N:'.$esc($d['name']).';;;;', 'FN:'.$esc($d['name'])];
        if (! empty($d['business_name'])) {
            $lines[] = 'ORG:'.$esc($d['business_name']);
        }
        if (! empty($d['designation'])) {
            $lines[] = 'TITLE:'.$esc($d['designation']);
        }
        if (! empty($d['phone'])) {
            $lines[] = 'TEL;TYPE=CELL:'.$esc($d['phone']);
        }
        if (! empty($d['email'])) {
            $lines[] = 'EMAIL;TYPE=INTERNET:'.$esc($d['email']);
        }
        if (! empty($d['address'])) {
            $lines[] = 'ADR;TYPE=WORK:;;'.$esc($d['address']).';;;;';
        }
        if (! empty($d['website'])) {
            $lines[] = 'URL:'.$esc($d['website']);
        }
        $lines[] = 'END:VCARD';

        $file = preg_replace('/[^A-Za-z0-9_-]+/', '-', Str::ascii($d['name'])) ?: 'contact';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.trim($file, '-').'.vcf"',
        ]);
    }
}
