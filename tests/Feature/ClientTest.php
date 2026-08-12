<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Иван Иванов',
            'phone' => '+99361111222',
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_clients_index_requires_authentication(): void
    {
        $this->get(route('clients.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_clients_index(): void
    {
        $this->actingAsAdmin();
        Client::factory()->count(3)->create();

        $this->get(route('clients.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Clients/Index')->has('clients'));
    }

    // ── Search ────────────────────────────────────────────────────────────────

    public function test_clients_can_be_searched_by_name(): void
    {
        $this->actingAsAdmin();
        Client::factory()->create(['name' => 'Иван Иванов', 'phone' => '+99361111222']);
        Client::factory()->create(['name' => 'Пётр Петров', 'phone' => '+99362222333']);

        $this->get(route('clients.index', ['search' => 'Иван']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Index')
                ->has('clients.data', 1)
                ->where('clients.data.0.name', 'Иван Иванов')
                ->where('filters.search', 'Иван')
            );
    }

    public function test_clients_can_be_searched_by_phone(): void
    {
        $this->actingAsAdmin();
        Client::factory()->create(['name' => 'Иван Иванов', 'phone' => '+99361111222']);
        Client::factory()->create(['name' => 'Пётр Петров', 'phone' => '+99362222333']);

        $this->get(route('clients.index', ['search' => '2222333']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('clients.data', 1)
                ->where('clients.data.0.phone', '+99362222333')
            );
    }

    public function test_client_search_by_phone_ignores_formatting(): void
    {
        $this->actingAsAdmin();
        Client::factory()->create(['name' => 'Иван Иванов', 'phone' => '+99361111222']);
        Client::factory()->create(['name' => 'Пётр Петров', 'phone' => '+99362222333']);

        $this->get(route('clients.index', ['search' => '+993 62 22-23-33']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('clients.data', 1)
                ->where('clients.data.0.phone', '+99362222333')
            );
    }

    public function test_client_search_returns_empty_when_nothing_matches(): void
    {
        $this->actingAsAdmin();
        Client::factory()->count(3)->create();

        $this->get(route('clients.index', ['search' => 'несуществующий-клиент']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('clients.data', 0));
    }

    public function test_clients_index_without_search_returns_all(): void
    {
        $this->actingAsAdmin();
        Client::factory()->count(3)->create();

        $this->get(route('clients.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('clients.data', 3));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_client(): void
    {
        $this->actingAsAdmin();

        $this->post(route('clients.store'), $this->validPayload())
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'name' => 'Иван Иванов',
            'phone' => '+99361111222',
            'is_blocked' => false,
        ]);
    }

    public function test_store_requires_unique_phone(): void
    {
        $this->actingAsAdmin();
        Client::factory()->create(['phone' => '+99361111222']);

        $this->post(route('clients.store'), $this->validPayload())
            ->assertSessionHasErrors('phone');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->post(route('clients.store'), [])
            ->assertSessionHasErrors(['name', 'phone']);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_client(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create();

        $this->put(route('clients.update', $client->id), [
            'name' => 'Обновлённое имя',
            'phone' => $client->phone,
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Обновлённое имя',
        ]);
    }

    public function test_update_allows_same_phone_for_same_client(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create(['phone' => '+99361111222']);

        $this->put(route('clients.update', $client->id), [
            'name' => $client->name,
            'phone' => '+99361111222',
        ])->assertRedirect(route('clients.index'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_client(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create();

        $this->delete(route('clients.destroy', $client->id))
            ->assertRedirect(route('clients.index'));

        $this->assertModelMissing($client);
    }

    // ── Toggle Block ──────────────────────────────────────────────────────────

    public function test_admin_can_block_client(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create(['is_blocked' => false]);

        $this->post(route('clients.toggle-block', $client->id))
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'is_blocked' => true]);
    }

    public function test_admin_can_unblock_client(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->blocked()->create();

        $this->post(route('clients.toggle-block', $client->id))
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'is_blocked' => false]);
    }

    // ── Mobile API catalog ────────────────────────────────────────────────────

    /**
     * The service covers Ashgabat only, so the geography directories were removed
     * from the mobile API entirely.
     *
     * @dataProvider removedGeographyEndpoints
     */
    public function test_geography_catalog_endpoints_are_gone(string $endpoint): void
    {
        $this->getJson($endpoint)->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function removedGeographyEndpoints(): array
    {
        return [
            'oblasts' => ['/api/v1/client/oblasts'],
            'regions' => ['/api/v1/client/regions'],
            'cities' => ['/api/v1/client/cities'],
        ];
    }
}
