<?php

namespace App\Http\Controllers;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Traits\WithNotification;
use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Database\Eloquent\Collection;
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
            'masterAppRulesRu' => $settings->get('master_app_rules_ru')?->value ?? '',
            'masterAppRulesTk' => $settings->get('master_app_rules_tk')?->value ?? '',
            'clientAppRulesRu' => $settings->get('client_app_rules_ru')?->value ?? '',
            'clientAppRulesTk' => $settings->get('client_app_rules_tk')?->value ?? '',
            'masterAppRulesUpdatedAt' => $this->latestUpdate($settings, ['master_app_rules_ru', 'master_app_rules_tk']),
            'clientAppRulesUpdatedAt' => $this->latestUpdate($settings, ['client_app_rules_ru', 'client_app_rules_tk']),
            'masterCallOutFeeNoteRu' => $settings->get('master_call_out_fee_note_ru')?->value ?? '',
            'masterCallOutFeeNoteTk' => $settings->get('master_call_out_fee_note_tk')?->value ?? '',
            'masterCallOutFeeNoteUpdatedAt' => $settings->get('master_call_out_fee_note_ru')?->updated_at?->toIso8601String(),
            'orderUrgencyFee' => $settings->get('order_urgency_fee')?->value ?? '20',
            'orderCancelFee' => $settings->get('order_cancel_fee')?->value ?? '0',
            'orderCancelFeeNoteRu' => $settings->get('order_cancel_fee_note_ru')?->value ?? '',
            'orderCancelFeeNoteTk' => $settings->get('order_cancel_fee_note_tk')?->value ?? '',
            'orderUrgencyFeeNoteRu' => $settings->get('order_urgency_fee_note_ru')?->value ?? '',
            'orderUrgencyFeeNoteTk' => $settings->get('order_urgency_fee_note_tk')?->value ?? '',
            'masterAppDownloadUrl' => $settings->get('master_app_download_url')?->value ?? '',
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.settings')]);

        return redirect()->route('settings.index');
    }

    /**
     * Самое свежее `updated_at` среди языковых вариантов одной настройки.
     *
     * @param  Collection<string, Setting>  $settings
     * @param  list<string>  $keys
     */
    private function latestUpdate(Collection $settings, array $keys): ?string
    {
        return $settings->toBase()->only($keys)->max('updated_at')?->toIso8601String();
    }
}
