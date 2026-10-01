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
        Setting::create(['key' => 'master_app_rules_ru', 'value' => 'some rules']);
        Setting::create(['key' => 'master_app_rules_tk', 'value' => 'käbir düzgünler']);
        Setting::create(['key' => 'client_app_rules_ru', 'value' => 'other rules']);
        Setting::create(['key' => 'client_app_rules_tk', 'value' => 'başga düzgünler']);

        $response = $this->actingAs($this->administrator())->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->where('masterAppRulesRu', 'some rules')
            ->where('masterAppRulesTk', 'käbir düzgünler')
            ->where('clientAppRulesRu', 'other rules')
            ->where('clientAppRulesTk', 'başga düzgünler')
            ->has('masterAppRulesUpdatedAt')
            ->has('clientAppRulesUpdatedAt')
        );
    }

    public function test_rules_updated_at_reflects_latest_locale_variant(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => 'ru'])->forceFill(['updated_at' => '2026-01-01 10:00:00'])->save();
        Setting::create(['key' => 'master_app_rules_tk', 'value' => 'tk'])->forceFill(['updated_at' => '2026-03-01 10:00:00'])->save();

        $this->actingAs($this->administrator())
            ->get(route('settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('masterAppRulesUpdatedAt', fn (string $iso) => str_starts_with($iso, '2026-03-01'))
                ->where('clientAppRulesUpdatedAt', null)
            );
    }

    public function test_settings_page_reports_null_timestamps_when_nothing_saved_yet(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('settings.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->where('masterAppRulesRu', '')
            ->where('masterAppRulesTk', '')
            ->where('clientAppRulesRu', '')
            ->where('clientAppRulesTk', '')
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
        Setting::create(['key' => 'master_app_rules_ru', 'value' => '']);
        Setting::create(['key' => 'client_app_rules_ru', 'value' => '']);

        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_app_rules_ru' => 'Правила мастера',
                'master_app_rules_tk' => 'Ussa düzgünleri',
                'client_app_rules_ru' => 'Правила клиента',
                'client_app_rules_tk' => 'Müşderi düzgünleri',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules_ru', 'value' => 'Правила мастера']);
        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules_tk', 'value' => 'Ussa düzgünleri']);
        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules_ru', 'value' => 'Правила клиента']);
        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules_tk', 'value' => 'Müşderi düzgünleri']);
    }

    public function test_settings_can_be_updated_to_empty(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => 'old value']);
        Setting::create(['key' => 'client_app_rules_tk', 'value' => 'old value']);

        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_app_rules_ru' => null,
                'client_app_rules_tk' => null,
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules_ru', 'value' => null]);
        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules_tk', 'value' => null]);
    }

    public function test_legacy_unlocalized_rules_key_is_ignored(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), ['master_app_rules' => 'legacy'])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseMissing('settings', ['key' => 'master_app_rules']);
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
        Setting::create(['key' => 'client_app_rules_ru', 'value' => 'client rules']);
        Setting::create(['key' => 'master_app_rules_tk', 'value' => 'master rules']);

        $this->actingAs($this->administrator())
            ->put(route('settings.update'), ['master_call_out_fee_note_ru' => 'Оплатите выезд'])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'client_app_rules_ru', 'value' => 'client rules']);
        $this->assertDatabaseHas('settings', ['key' => 'master_app_rules_tk', 'value' => 'master rules']);
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

    public function test_administrator_can_save_order_cancel_fee_notes(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'order_cancel_fee' => '15',
                'order_cancel_fee_note_ru' => 'При отмене после начала работ оплатите 15 TMT',
                'order_cancel_fee_note_tk' => 'Işe başlanandan soň ýatyrmak üçin 15 TMT töläň',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'order_cancel_fee_note_ru',
            'value' => 'При отмене после начала работ оплатите 15 TMT',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'order_cancel_fee_note_tk',
            'value' => 'Işe başlanandan soň ýatyrmak üçin 15 TMT töläň',
        ]);
    }

    public function test_administrator_can_save_order_urgency_fee_notes(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'order_urgency_fee' => '20',
                'order_urgency_fee_note_ru' => 'Срочный вызов +20 TMT',
                'order_urgency_fee_note_tk' => 'Gyssagly çagyryş +20 TMT',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'order_urgency_fee_note_ru',
            'value' => 'Срочный вызов +20 TMT',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'order_urgency_fee_note_tk',
            'value' => 'Gyssagly çagyryş +20 TMT',
        ]);
    }

    public function test_settings_page_exposes_order_cancel_fee_notes(): void
    {
        Setting::create(['key' => 'order_cancel_fee_note_ru', 'value' => 'Отмена RU']);
        Setting::create(['key' => 'order_cancel_fee_note_tk', 'value' => 'Yatyrmak TK']);
        Setting::create(['key' => 'order_urgency_fee_note_ru', 'value' => 'Срочность RU']);
        Setting::create(['key' => 'order_urgency_fee_note_tk', 'value' => 'Gyssaglylyk TK']);

        $this->actingAs($this->administrator())
            ->get(route('settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('orderCancelFeeNoteRu', 'Отмена RU')
                ->where('orderCancelFeeNoteTk', 'Yatyrmak TK')
                ->where('orderUrgencyFeeNoteRu', 'Срочность RU')
                ->where('orderUrgencyFeeNoteTk', 'Gyssaglylyk TK')
            );
    }

    public function test_order_cancel_fee_note_is_capped_at_500_characters(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), ['order_cancel_fee_note_ru' => str_repeat('a', 501)])
            ->assertSessionHasErrors('order_cancel_fee_note_ru');
    }

    public function test_order_urgency_fee_note_is_capped_at_500_characters(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), ['order_urgency_fee_note_ru' => str_repeat('a', 501)])
            ->assertSessionHasErrors('order_urgency_fee_note_ru');
    }

    public function test_operator_cannot_update_settings(): void
    {
        $this->actingAs($this->operator())
            ->put(route('settings.update'), [
                'master_app_rules_ru' => 'text',
                'client_app_rules_ru' => 'text',
            ])
            ->assertForbidden();
    }

    public function test_administrator_can_save_master_app_download_url(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_app_download_url' => 'https://cdn.example.com/apps/master.apk',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'master_app_download_url',
            'value' => 'https://cdn.example.com/apps/master.apk',
        ]);
    }

    public function test_master_app_download_url_must_be_valid(): void
    {
        $this->actingAs($this->administrator())
            ->put(route('settings.update'), [
                'master_app_download_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('master_app_download_url');
    }

    public function test_settings_page_exposes_master_app_download_url(): void
    {
        Setting::create(['key' => 'master_app_download_url', 'value' => 'https://cdn.example.com/master.apk']);

        $this->actingAs($this->administrator())
            ->get(route('settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('masterAppDownloadUrl', 'https://cdn.example.com/master.apk'));
    }

    public function test_master_app_download_url_is_shared_with_authenticated_staff(): void
    {
        Setting::create(['key' => 'master_app_download_url', 'value' => 'https://cdn.example.com/master.apk']);

        $this->actingAs($this->administrator())
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('masterAppDownloadUrl', 'https://cdn.example.com/master.apk'));

        $this->actingAs($this->operator())
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('masterAppDownloadUrl', 'https://cdn.example.com/master.apk'));
    }

    public function test_empty_master_app_download_url_is_shared_as_null(): void
    {
        Setting::create(['key' => 'master_app_download_url', 'value' => '']);

        $this->actingAs($this->administrator())
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page->where('masterAppDownloadUrl', null));
    }
}
