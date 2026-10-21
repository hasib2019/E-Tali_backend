<?php

namespace App\Support;

/**
 * How many billable SMS parts a message needs — the same rule the gateway
 * bills by. Plain GSM-7 text fits 160 characters in one SMS (153 per part
 * once split); anything else (Bangla, emoji) is sent as UCS-2: 70 in one SMS,
 * 67 per part once split. The app mirrors this in `utils/smsSegments.ts`.
 */
class SmsSegments
{
    private const GSM_CHARS = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /** Extension-table characters cost two septets each. */
    private const GSM_EXT = '^{}\\[~]|€';

    public static function count(string $message): int
    {
        if ($message === '') {
            return 0;
        }

        $chars = mb_str_split($message);
        $gsm = true;
        $septets = 0;
        foreach ($chars as $ch) {
            if (mb_strpos(self::GSM_CHARS, $ch) !== false) {
                $septets++;
            } elseif (mb_strpos(self::GSM_EXT, $ch) !== false) {
                $septets += 2;
            } else {
                $gsm = false;
                break;
            }
        }

        if ($gsm) {
            return $septets <= 160 ? 1 : (int) ceil($septets / 153);
        }

        // UCS-2 counts UTF-16 code units, so an emoji is two.
        $units = intdiv(strlen(mb_convert_encoding($message, 'UTF-16BE', 'UTF-8')), 2);

        return $units <= 70 ? 1 : (int) ceil($units / 67);
    }
}
