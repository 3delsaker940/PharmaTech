<?php

namespace App\Concerns;

trait HasHashedFields
{
    /**
     * Normalize a Syrian phone number to the canonical 09XXXXXXXX format.
     * Accepts: 09XXXXXXXX, +9639XXXXXXXX, 009639XXXXXXXX (with optional spaces/dashes).
     * Returns null if the input doesn't look like a valid phone at all.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Strip spaces, dashes, parentheses
        $digitsOnly = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (preg_match('/^\+9639(\d{8})$/', $digitsOnly, $m)) {
            return '09' . $m[1];
        }
        if (preg_match('/^009639(\d{8})$/', $digitsOnly, $m)) {
            return '09' . $m[1];
        }
        if (preg_match('/^09(\d{8})$/', $digitsOnly, $m)) {
            return '09' . $m[1];
        }

        // Doesn't match any known format — return as-is (validation layer should catch this)
        return $digitsOnly;
    }

    /**
     * Normalize an email address (lowercase + trim) so the same address
     * always hashes to the same value regardless of case.
     */
    public static function normalizeEmail(?string $email): ?string
    {
        if (blank($email)) {
            return null;
        }

        return mb_strtolower(trim($email));
    }

    /**
     * Compute a deterministic HMAC hash for exact-match lookups.
     * Uses the app key as the HMAC secret so the hash cannot be
     * brute-forced from the database alone.
     */
    public static function hashForLookup(?string $normalizedValue): ?string
    {
        if (blank($normalizedValue)) {
            return null;
        }

        return hash_hmac('sha256', $normalizedValue, config('app.key'));
    }
}
