<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Resolves the cash/bank portions of a cash+bank payment so the stored
 * amounts are what the shop actually kept. Cash balances are computed from
 * orders.cash_amount, so change handed back must never be recorded as cash in.
 */
class SplitPayment
{
    /**
     * Full split payment: cash + bank must cover $due. Any overpayment is
     * change, which always comes out of the cash portion.
     *
     * @return array{0: float, 1: float} [cash, bank]
     * @throws ValidationException
     */
    public static function full(float $cash, float $bank, float $due): array
    {
        [$cash, $bank] = [max(0, $cash), max(0, $bank)];
        self::guardBank($bank, $due);

        if ($cash + $bank < $due - 0.01) {
            throw ValidationException::withMessages([
                'payment' => 'Split payment (Rs. ' . number_format($cash + $bank) . ') is less than the total (Rs. '
                    . number_format($due) . '). Use Partial payment to put the rest on khata.',
            ]);
        }

        return [round($due - $bank, 2), $bank];
    }

    /**
     * Partial payment made in cash + bank: whatever is handed over is capped
     * at $due; the excess (change) comes out of the cash portion.
     *
     * @return array{0: float, 1: float, 2: float} [cash, bank, amount paid]
     * @throws ValidationException
     */
    public static function partial(float $cash, float $bank, float $due): array
    {
        [$cash, $bank] = [max(0, $cash), max(0, $bank)];
        self::guardBank($bank, $due);

        $paid = min($cash + $bank, $due);

        return [round($paid - $bank, 2), $bank, $paid];
    }

    private static function guardBank(float $bank, float $due): void
    {
        if ($bank > $due + 0.01) {
            throw ValidationException::withMessages([
                'payment' => 'Bank portion (Rs. ' . number_format($bank) . ') is more than the total (Rs. '
                    . number_format($due) . '). A bank transfer cannot give change.',
            ]);
        }
    }
}
