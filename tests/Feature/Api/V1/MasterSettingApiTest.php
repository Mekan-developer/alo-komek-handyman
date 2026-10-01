<?php

namespace Tests\Feature\Api\V1;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSettingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_content_when_no_rules_saved(): void
    {
        $response = $this->getJson('/api/v1/master/settings');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['content']])
            ->assertJsonPath('data.content', '');
    }

    public function test_returns_saved_master_rules(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => '<h1>Правила</h1>']);

        $response = $this->getJson('/api/v1/master/settings');

        $response->assertOk()
            ->assertJsonPath('data.content', '<h1>Правила</h1>');
    }

    public function test_returns_turkmen_rules_for_tk_locale(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => '<p>Правила</p>']);
        Setting::create(['key' => 'master_app_rules_tk', 'value' => '<p>Düzgünler</p>']);

        $this->getJson('/api/v1/master/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.content', '<p>Düzgünler</p>');

        $this->getJson('/api/v1/master/settings', ['X-Locale' => 'ru'])
            ->assertOk()
            ->assertJsonPath('data.content', '<p>Правила</p>');
    }

    public function test_falls_back_to_russian_when_turkmen_rules_are_empty(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => '<p>Правила</p>']);
        Setting::create(['key' => 'master_app_rules_tk', 'value' => '']);

        $this->getJson('/api/v1/master/settings', ['X-Locale' => 'tk'])
            ->assertOk()
            ->assertJsonPath('data.content', '<p>Правила</p>');
    }

    public function test_does_not_return_client_rules(): void
    {
        Setting::create(['key' => 'master_app_rules_ru', 'value' => 'master content']);
        Setting::create(['key' => 'client_app_rules_ru', 'value' => 'client content']);

        $response = $this->getJson('/api/v1/master/settings');

        $response->assertOk()
            ->assertJsonPath('data.content', 'master content');
    }

    public function test_endpoint_is_public_no_auth_required(): void
    {
        $response = $this->getJson('/api/v1/master/settings');

        $response->assertOk();
    }
}
