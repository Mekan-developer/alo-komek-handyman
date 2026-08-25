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
            ->assertJsonStructure(['data' => ['content', 'master_call_out_fee_note']])
            ->assertJsonPath('data.content', '')
            ->assertJsonPath('data.master_call_out_fee_note', '');
    }

    public function test_returns_call_out_fee_note_in_russian_by_default(): void
    {
        Setting::create(['key' => 'master_call_out_fee_note_ru', 'value' => 'Оплатите выезд мастера']);
        Setting::create(['key' => 'master_call_out_fee_note_tk', 'value' => 'Ussanyň ýol tölegi']);

        $this->getJson('/api/v1/client/settings')
            ->assertOk()
            ->assertJsonPath('data.master_call_out_fee_note', 'Оплатите выезд мастера');
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
        Setting::create(['key' => 'client_app_rules', 'value' => '<p>Условия</p>']);

        $response = $this->getJson('/api/v1/client/settings');

        $response->assertOk()
            ->assertJsonPath('data.content', '<p>Условия</p>');
    }

    public function test_does_not_return_master_rules(): void
    {
        Setting::create(['key' => 'master_app_rules', 'value' => 'master content']);
        Setting::create(['key' => 'client_app_rules', 'value' => 'client content']);

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
