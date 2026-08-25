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
            ->where('masterAppRules', 'some rules')
            ->where('clientAppRules', 'other rules')
            ->has('masterAppRulesUpdatedAt')
            ->has('clientAppRulesUpdatedAt')
        );
    }

    public function test_settings_page_reports_null_timestamps_when_nothing_saved_yet(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('settings.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->where('masterAppRules', '')
            ->where('clientAppRules', '')
            ->where('masterAppRulesUpdatedAt', null)
            ->where('clientAppRulesUpdatedAt', null)
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

    // ── Текст о плате за выезд мастера ────────────────────────────────────────

    public function test_administrator_can_save_bilingual_call_out_fee_note(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_call_out_fee_note_ru' => 'Оплатите выезд мастера',
                'master_call_out_fee_note_tk' => 'Ussanyň ýol tölegini töläň',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'master_call_out_fee_note_ru', 'value' => 'Оплатите выезд мастера']);
        $this->assertDatabaseHas('settings', ['key' => 'master_call_out_fee_note_tk', 'value' => 'Ussanyň ýol tölegini töläň']);
    }

    public function test_saving_call_out_fee_note_keeps_app_rules_untouched(): void
    {
        Setting::create(['key' => 'client_app_rules', 'value' => 'client rules']);
        Setting::create(['key' => 'master_app_rules', 'value' => 'master rules']);

        $this->actingAs($this->administrator())
            ->put(route('settings.update'), ['master_call_out_fee_note_ru' => 'Оплатите выезд'])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules', 'value' => 'client rules']);
        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules', 'value' => 'master rules']);
    }

    public function test_settings_page_exposes_call_out_fee_notes(): void
    {
        Setting::create(['key' => 'master_call_out_fee_note_ru', 'value' => 'Текст RU']);
        Setting::create(['key' => 'master_call_out_fee_note_tk', 'value' => 'Tekst TK']);

        $this->actingAs($this->administrator())
            ->get(route('settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('masterCallOutFeeNoteRu', 'Текст RU')
                ->where('masterCallOutFeeNoteTk', 'Tekst TK')
                ->has('masterCallOutFeeNoteUpdatedAt')
            );
    }

    public function test_call_out_fee_notes_default_to_empty_strings(): void
    {
        $this->actingAs($this->administrator())
            ->get(route('settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('masterCallOutFeeNoteRu', '')
                ->where('masterCallOutFeeNoteTk', '')
                ->where('masterCallOutFeeNoteUpdatedAt', null)
            );
    }

    public function test_call_out_fee_note_is_capped_at_500_characters(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), ['master_call_out_fee_note_ru' => str_repeat('a', 501)])
            ->assertSessionHasErrors('master_call_out_fee_note_ru');
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
