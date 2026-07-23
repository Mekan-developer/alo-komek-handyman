<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }

    private function operator(): User
    {
        return User::factory()->operator()->create();
    }

    private function validPayload(string $role = 'manager'): array
    {
        return [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => $role,
        ];
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_users_index(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_administrator_can_view_users_index(): void
    {
        $this->actingAs($this->administrator());

        $this->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Users/Index', false)->has('users'));
    }

    public function test_manager_cannot_view_users_index(): void
    {
        $this->actingAs($this->manager());

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_operator_cannot_view_users_index(): void
    {
        $this->actingAs($this->operator());

        $this->get(route('users.index'))->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_administrator_can_create_user_with_assignable_roles(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        foreach (UserRole::assignable() as $role) {
            $payload = $this->validPayload($role->value);
            $payload['email'] = "test_{$role->value}@example.com";

            $this->post(route('users.store'), $payload)
                ->assertRedirect(route('users.index'));

            $this->assertDatabaseHas('users', [
                'email' => $payload['email'],
                'role' => $role->value,
            ]);
        }
    }

    public function test_operator_role_cannot_be_assigned_on_create(): void
    {
        $this->actingAs($this->administrator());

        $payload = $this->validPayload('operator');

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_created_by_is_recorded_on_store(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $payload = $this->validPayload('manager');
        $this->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'created_by' => $admin->id,
        ]);
    }

    public function test_manager_cannot_create_user(): void
    {
        $this->actingAs($this->manager());

        $this->post(route('users.store'), $this->validPayload('manager'))
            ->assertForbidden();
    }

    public function test_operator_cannot_create_user(): void
    {
        $this->actingAs($this->operator());

        $this->post(route('users.store'), $this->validPayload())
            ->assertForbidden();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_administrator_can_update_any_user(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $target = $this->manager();

        $this->patch(route('users.update', $target), [
            'name' => 'Updated Name',
            'email' => $target->email,
            'role' => 'operator',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'operator']);
    }

    public function test_manager_cannot_update_user(): void
    {
        $this->actingAs($this->manager());

        $target = $this->operator();

        $this->patch(route('users.update', $target), [
            'name' => 'New Name',
            'email' => $target->email,
            'role' => 'manager',
        ])->assertForbidden();
    }

    public function test_operator_cannot_update_user(): void
    {
        $this->actingAs($this->operator());

        $target = $this->manager();

        $this->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'manager',
        ])->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_administrator_can_delete_other_user(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $target = $this->manager();

        $this->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_administrator_cannot_delete_self(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $this->delete(route('users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_administrator_can_delete_admin_they_created(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $created = User::factory()->administrator()->create(['created_by' => $admin->id]);

        $this->delete(route('users.destroy', $created))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $created->id]);
    }

    public function test_administrator_cannot_delete_admin_created_by_another(): void
    {
        $creator = $this->administrator();
        $otherAdmin = User::factory()->administrator()->create(['created_by' => $creator->id]);

        $this->actingAs($this->administrator());

        $this->delete(route('users.destroy', $otherAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_administrator_cannot_delete_admin_with_no_creator(): void
    {
        $this->actingAs($this->administrator());

        $rootAdmin = User::factory()->administrator()->create(['created_by' => null]);

        $this->delete(route('users.destroy', $rootAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $rootAdmin->id]);
    }

    public function test_manager_cannot_delete_user(): void
    {
        $this->actingAs($this->manager());

        $target = $this->operator();

        $this->delete(route('users.destroy', $target))
            ->assertForbidden();
    }

    public function test_operator_cannot_delete_user(): void
    {
        $this->actingAs($this->operator());

        $target = $this->manager();

        $this->delete(route('users.destroy', $target))
            ->assertForbidden();
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->administrator());

        $payload = $this->validPayload();
        unset($payload['name']);

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_valid_email(): void
    {
        $this->actingAs($this->administrator());

        $payload = $this->validPayload();
        $payload['email'] = 'not-an-email';

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('email');
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $this->actingAs($this->administrator());

        $existing = $this->manager();
        $payload = $this->validPayload();
        $payload['email'] = $existing->email;

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('email');
    }

    public function test_store_requires_password_min_8(): void
    {
        $this->actingAs($this->administrator());

        $payload = $this->validPayload();
        $payload['password'] = 'short';
        $payload['password_confirmation'] = 'short';

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('password');
    }

    public function test_store_requires_password_confirmation(): void
    {
        $this->actingAs($this->administrator());

        $payload = $this->validPayload();
        $payload['password_confirmation'] = 'different_password';

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('password');
    }

    public function test_store_rejects_invalid_role(): void
    {
        $this->actingAs($this->administrator());

        $payload = $this->validPayload();
        $payload['role'] = 'superadmin';

        $this->post(route('users.store'), $payload)
            ->assertSessionHasErrors('role');
    }

    public function test_update_allows_same_email_for_same_user(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $target = $this->manager();

        $this->patch(route('users.update', $target), [
            'name' => 'Updated',
            'email' => $target->email,
            'role' => 'manager',
        ])->assertRedirect(route('users.index'));
    }

    public function test_update_password_is_optional(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        $target = $this->manager();
        $oldPasswordHash = $target->password;

        $this->patch(route('users.update', $target), [
            'name' => 'New Name',
            'email' => $target->email,
            'role' => 'manager',
        ])->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertEquals($oldPasswordHash, $target->password);
    }
}
