<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    /**
     * Strong enough for school accounts without special-character hassle:
     * 8+ characters, mixed case, and a digit.
     */
    public static function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)
            ->mixedCase()
            ->letters()
            ->numbers();
    }

    /**
     * @return list<array{id:string,label:string,ok:bool}>
     */
    public static function checklist(string $password): array
    {
        return [
            [
                'id' => 'length',
                'label' => 'At least '.self::MIN_LENGTH.' characters',
                'ok' => mb_strlen($password) >= self::MIN_LENGTH,
            ],
            [
                'id' => 'upper',
                'label' => 'A capital letter (A–Z)',
                'ok' => (bool) preg_match('/[A-Z]/', $password),
            ],
            [
                'id' => 'lower',
                'label' => 'A small letter (a–z)',
                'ok' => (bool) preg_match('/[a-z]/', $password),
            ],
            [
                'id' => 'digit',
                'label' => 'A number (0–9)',
                'ok' => (bool) preg_match('/\d/', $password),
            ],
        ];
    }

    public static function isStrong(string $password): bool
    {
        foreach (self::checklist($password) as $item) {
            if (! $item['ok']) {
                return false;
            }
        }

        return true;
    }

    public static function generate(int $length = 10): string
    {
        $length = max(self::MIN_LENGTH, $length);
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $all = $upper.$lower.$digits;

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
