<?php

namespace App\Http\Controllers\Api\V1\Client;

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
                'master_call_out_fee_note' => $this->callOutFeeNote(),
            ],
        ]);
    }

    /**
     * Пояснение к плате за выезд на языке запроса (X-Locale), с откатом на русский —
     * та же схема, что у билингвальных названий категорий.
     */
    private function callOutFeeNote(): string
    {
        $locale = app()->getLocale();
        $note = $this->repository->get("master_call_out_fee_note_{$locale}");

        return $note ?: ($this->repository->get('master_call_out_fee_note_ru') ?? '');
    }
}
