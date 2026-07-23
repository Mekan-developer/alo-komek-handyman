<?php

namespace App\Actions;

use App\Exceptions\PaymentException;
use App\Models\Master;
use App\Models\MasterPayout;
use App\Models\User;
use App\Repositories\MasterRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;

class RecordMasterPayoutAction
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly MasterRepository $masters,
    ) {}

    /**
     * Pay out a master's balance: record a ledger entry, then reduce the balance.
     * Pass $amount for a partial payout; omit it (null) to pay the full balance.
     */
    public function handle(Master $master, ?User $paidBy = null, ?string $note = null, ?float $amount = null): MasterPayout
    {
        $balance = (float) $master->balance;

        if ($balance <= 0) {
            throw PaymentException::nothingToPay();
        }

        $amount = $amount ?? $balance;

        if ($amount <= 0) {
            throw PaymentException::nothingToPay();
        }

        if ($amount - $balance > 0.001) {
            throw PaymentException::exceedsBalance();
        }

        return DB::transaction(function () use ($master, $amount, $balance, $paidBy, $note): MasterPayout {
            $payout = $this->payments->create([
                'master_id' => $master->id,
                'master_name' => $master->name,
                'amount' => $amount,
                'payment_model' => $master->payment_model->value,
                'paid_by' => $paidBy?->id,
                'note' => $note,
            ]);

            // Full payout zeroes the balance exactly; a partial one decrements it.
            if ($amount >= $balance - 0.001) {
                $this->masters->resetBalance($master);
            } else {
                $this->masters->decrementBalance($master, $amount);
            }

            return $payout;
        });
    }
}
