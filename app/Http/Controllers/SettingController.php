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
        $settings = $this->repository->all()->pluck('value', 'key');

        return Inertia::render('Settings/Index', [
            'masterAppRules' => $settings->get('master_app_rules', ''),
            'clientAppRules' => $settings->get('client_app_rules', ''),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.updated', ['resource' => __('resources.settings')]);

        return redirect()->route('settings.index');
    }
}
