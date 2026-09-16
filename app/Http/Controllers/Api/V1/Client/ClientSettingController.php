<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Enums\OrderTimeSlot;
use App\Http\Controllers\Controller;
use App\Repositories\SettingRepository;
use Illuminate\Http\JsonResponse;

class ClientSettingController extends Controller
{
    public function __construct(private readonly SettingRepository $repository) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => [
                'content' => $this->repository->get('client_app_rules') ?? '',
                'master_call_out_fee_note' => $this->localizedNote('master_call_out_fee_note'),
                'order_urgency_fee' => (float) ($this->repository->get('order_urgency_fee') ?? '20'),
                'order_cancel_fee' => (float) ($this->repository->get('order_cancel_fee') ?? '0'),
                'order_cancel_fee_note' => $this->localizedNote('order_cancel_fee_note'),
                'time_slots' => OrderTimeSlot::values(),
            ],
        ]);
    }

    /**
     * Билингвальная заметка по префиксу ключа (X-Locale), с откатом на русский.
     */
    private function localizedNote(string $keyPrefix): string
    {
        $locale = app()->getLocale();
        $note = $this->repository->get("{$keyPrefix}_{$locale}");

        return $note ?: ($this->repository->get("{$keyPrefix}_ru") ?? '');
    }
}
