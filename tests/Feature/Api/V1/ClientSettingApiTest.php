<?php

namespace Tests\Feature\Api\V1;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSettingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_content_when_no_rules_saved(): void
    {
        $response = $this->getJson('/api/v1/client/settings');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['content', 'master_call_out_fee_note', 'order_urgency_fee', 'order_urgency_fee_note', 'order_cancel_fee', 'order_cancel_fee_note', 'time_slots']])
            ->assertJsonPath('data.content', '')
            ->assertJsonPath('data.master_call_out_fee_note', '')
            ->assertJsonPath('data.order_urgency_fee', 20)
            ->assertJsonPath('data.order_urgency_fee_note', '')
            ->assertJsonPath('data.order_cancel_fee', 0)
            ->assertJsonPath('data.order_cancel_fee_note', '');
    }

    public function test_returns_call_out_fee_note_in_russian_by_default(): void
    {
        Setting::create(['key' => 'master_call_out_fee_note_ru', 'value' => 'Оплатите выезд мастера']);
        Setting::create(['key' => 'master_call_out_fee_note_tk', 'value' => 'Ussanyň ýol tölegi']);

        $this->getJson('/api/v1/client/settings')
            ->assertOk()
            ->assertJsonPath('data.master_call_out_fee_note', 'Оплатите выезд мастера');
    }

    public function test_returns_order_cancel_fee_note_by_locale(): void
    {
        Setting::create(['key' => 'order_cancel_fee_note_ru', 'value' => 'При отмене после начала работ — 15 TMT']);
        Setting::create(['key' => 'order_cancel_fee_note_tk', 'value' => 'Işe başlanandan soň ýatyrmak — 15 TMT']);

        $this->getJson('/api/v1/client/settings')
            ->assertOk()
            ->assertJsonPath('data.order_cancel_fee_note', 'При отмене после начала работ — 15 TMT');

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.order_cancel_fee_note', 'Işe başlanandan soň ýatyrmak — 15 TMT');
    }

    public function test_returns_order_urgency_fee_note_by_locale(): void
    {
        Setting::create(['key' => 'order_urgency_fee_note_ru', 'value' => 'Срочный вызов +20 TMT']);
        Setting::create(['key' => 'order_urgency_fee_note_tk', 'value' => 'Gyssagly çagyryş +20 TMT']);

        $this->getJson('/api/v1/client/settings')
            ->assertOk()
            ->assertJsonPath('data.order_urgency_fee_note', 'Срочный вызов +20 TMT');

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.order_urgency_fee_note', 'Gyssagly çagyryş +20 TMT');
    }

    public function test_order_cancel_fee_note_falls_back_to_russian_when_turkmen_is_empty(): void
    {
        Setting::create(['key' => 'order_cancel_fee_note_ru', 'value' => 'Текст отмены RU']);

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.order_cancel_fee_note', 'Текст отмены RU');
    }

    public function test_returns_call_out_fee_note_in_turkmen_for_tk_locale(): void
    {
        Setting::create(['key' => 'master_call_out_fee_note_ru', 'value' => 'Оплатите выезд мастера']);
        Setting::create(['key' => 'master_call_out_fee_note_tk', 'value' => 'Ussanyň ýol tölegi']);

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.master_call_out_fee_note', 'Ussanyň ýol tölegi');
    }

    public function test_call_out_fee_note_falls_back_to_russian_when_turkmen_is_empty(): void
    {
        Setting::create(['key' => 'master_call_out_fee_note_ru', 'value' => 'Оплатите выезд мастера']);

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.master_call_out_fee_note', 'Оплатите выезд мастера');
    }

    public function test_returns_saved_client_rules(): void
    {
        Setting::create(['key' => 'client_app_rules_ru', 'value' => '<p>Условия</p>']);

        $response = $this->getJson('/api/v1/client/settings');

        $response->assertOk()
            ->assertJsonPath('data.content', '<p>Условия</p>');
    }

    public function test_returns_client_rules_by_locale(): void
    {
        Setting::create(['key' => 'client_app_rules_ru', 'value' => '<p>Условия</p>']);
        Setting::create(['key' => 'client_app_rules_tk', 'value' => '<p>Şertler</p>']);

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.content', '<p>Şertler</p>');
    }

    public function test_client_rules_fall_back_to_russian_when_turkmen_is_empty(): void
    {
        Setting::create(['key' => 'client_app_rules_ru', 'value' => '<p>Условия</p>']);

        $this->getJson('/api/v1/client/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.content', '<p>Условия</p>');
    }

    public function test_does_not_return_master_rules(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => 'master content']);
        Setting::create(['key' => 'client_app_rules_ru', 'value' => 'client content']);

        $response = $this->getJson('/api/v1/client/settings');

        $response->assertOk()
            ->assertJsonPath('data.content', 'client content');
    }

    public function test_endpoint_is_public_no_auth_required(): void
    {
        $response = $this->getJson('/api/v1/client/settings');

        $response->assertOk();
    }
}
