<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    private function operator(): User
    {
        return User::factory()->operator()->create();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_administrator_can_view_settings_page(): void
    {
        Setting::create(['key' => 'master_app_rules', 'value' => 'some rules']);
        Setting::create(['key' => 'client_app_rules', 'value' => 'other rules']);

        $response = $this->actingAs($this->administrator())->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('masterAppRules')
            ->has('clientAppRules')
        );
    }

    public function test_guest_cannot_view_settings_page(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_view_settings_page(): void
    {
        $this->actingAs($this->operator())
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_administrator_can_update_settings(): void
    {
        Setting::create(['key' => 'master_app_rules', 'value' => '']);
        Setting::create(['key' => 'client_app_rules', 'value' => '']);

        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_app_rules' => 'Master rules text',
                'client_app_rules' => 'Client rules text',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules', 'value' => 'Master rules text']);
        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules', 'value' => 'Client rules text']);
    }

    public function test_settings_can_be_updated_to_empty(): void
    {
        Setting::create(['key' => 'master_app_rules', 'value' => 'old value']);
        Setting::create(['key' => 'client_app_rules', 'value' => 'old value']);

        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_app_rules' => null,
                'client_app_rules' => null,
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules', 'value' => null]);
        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules', 'value' => null]);
    }

    public function test_operator_cannot_update_settings(): void
    {
        $this->actingAs($this->operator())
            ->put(route('settings.update'), [
                'master_app_rules' => 'text',
                'client_app_rules' => 'text',
            ])
            ->assertForbidden();
    }
}
