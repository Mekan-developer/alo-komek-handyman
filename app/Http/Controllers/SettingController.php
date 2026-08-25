<?php

namespace App\Http\Controllers;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Traits\WithNotification;
use App\Repositories\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    use WithNotification;

    public function __construct(private readonly SettingRepository $repository) {}

    public function index(): Response
    {
        $settings = $this->repository->all()->keyBy('key');

        return Inertia::render('Settings/Index', [
            'masterAppRules' => $settings->get('master_app_rules')?->value ?? '',
            'clientAppRules' => $settings->get('client_app_rules')?->value ?? '',
            'masterAppRulesUpdatedAt' => $settings->get('master_app_rules')?->updated_at?->toIso8601String(),
            'clientAppRulesUpdatedAt' => $settings->get('client_app_rules')?->updated_at?->toIso8601String(),
            'masterCallOutFeeNoteRu' => $settings->get('master_call_out_fee_note_ru')?->value ?? '',
            'masterCallOutFeeNoteTk' => $settings->get('master_call_out_fee_note_tk')?->value ?? '',
            'masterCallOutFeeNoteUpdatedAt' => $settings->get('master_call_out_fee_note_ru')?->updated_at?->toIso8601String(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.settings')]);

        return redirect()->route('settings.index');
    }
}
