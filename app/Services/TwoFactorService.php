<?php

namespace App\Services;

use App\Models\User;

class TwoFactorService
{
    /**
     * Number of digits in the TOTP code.
     */
    protected int $codeLength = 6;

    /**
     * Time step in seconds (standard: 30).
     */
    protected int $timeStep = 30;

    /**
     * Hash algorithm used for HMAC (standard: SHA1).
     */
    protected string $algorithm = 'sha1';

    /**
     * Generate a new TOTP secret (Base32-encoded, 32 chars = 160 bits).
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(20); // 160 bits
        return $this->base32Encode($bytes);
    }

    /**
     * Build the otpauth:// URL that authenticator apps scan.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $label = rawurlencode(config('app.name', 'Ngemba Mining') . ':' . ($user->email ?: $user->username));
        $issuer = rawurlencode(config('app.name', 'Ngemba Mining'));

        return "otpauth://totp/{$label}"
            . "?secret={$secret}"
            . "&issuer={$issuer}"
            . "&algorithm=SHA1"
            . "&digits={$this->codeLength}"
            . "&period={$this->timeStep}";
    }

    /**
     * Verify a TOTP code. Allows ±1 time step of clock drift.
     */
    public function verifyTotp(string $secret, string $code): bool
    {
        $code = trim($code);
        if (strlen($code) !== $this->codeLength) {
            return false;
        }

        $currentTime = time();

        // Check current, previous, and next time step (±30s)
        for ($i = -1; $i <= 1; $i++) {
            $timeSlice = intdiv($currentTime, $this->timeStep) + $i;
            $expected = $this->generateTotp($secret, $timeSlice);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a random 6-digit SMS code.
     */
    public function generateSmsCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify an SMS code against the session-stored one.
     * Code expires after 5 minutes.
     */
    public function verifySmsCode(string $inputCode): bool
    {
        $stored = session('2fa:sms_code');
        $expires = session('2fa:sms_code_expires');

        if (!$stored || !$expires) {
            return false;
        }

        if (now()->timestamp > $expires) {
            session()->forget(['2fa:sms_code', '2fa:sms_code_expires']);
            return false;
        }

        if (!hash_equals((string) $stored, (string) $inputCode)) {
            return false;
        }

        // Consume the code so it can't be reused
        session()->forget(['2fa:sms_code', '2fa:sms_code_expires']);

        return true;
    }

    /**
     * Generate one-time recovery codes for a user.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5))); // 10-char hex
        }
        return $codes;
    }

    /**
     * Verify and consume a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $input): bool
    {
        if (empty($user->two_factor_recovery_codes)) {
            return false;
        }

        try {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
        } catch (\Throwable $e) {
            return false;
        }

        $input = strtoupper(trim($input));

        $index = array_search($input, $codes, true);
        if ($index === false) {
            return false;
        }

        // Consume it
        unset($codes[$index]);
        $user->two_factor_recovery_codes = encrypt(json_encode(array_values($codes)));
        $user->save();

        return true;
    }

    /* ============================================================
     * INTERNAL HELPERS — no external dependencies
     * ============================================================ */

    /**
     * Generate a TOTP code for a given time slice.
     */
    protected function generateTotp(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hmac = hash_hmac($this->algorithm, $time, $secretKey, true);

        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $binary = (
            ((ord($hmac[$offset])     & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8)  |
            (ord($hmac[$offset + 3])  & 0xFF)
        );

        $otp = $binary % (10 ** $this->codeLength);

        return str_pad((string) $otp, $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 encode (RFC 4648).
     */
    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    /**
     * Base32 decode (RFC 4648).
     */
    protected function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $secret = rtrim($secret, '=');

        $binary = '';
        foreach (str_split($secret) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) {
                continue; // skip invalid chars
            }
            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }
            $decoded .= chr(bindec($chunk));
        }

        return $decoded;
    }
}