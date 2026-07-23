<?php

namespace App\Repositories;

use App\Models\MasterPayout;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentRepository
{
    /** Payout ledger, latest first. */
    public function history(int $perPage = 15): LengthAwarePaginator
    {
        return MasterPayout::with(['master', 'paidBy'])
            ->latest()
            ->paginate($perPage);
    }

    /** Total amount paid out across all masters, all time. */
    public function totalPaid(): float
    {
        return (float) MasterPayout::sum('amount');
    }

    public function create(array $data): MasterPayout
    {
        return MasterPayout::create($data);
    }
}
