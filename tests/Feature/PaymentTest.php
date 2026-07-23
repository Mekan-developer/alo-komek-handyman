<?php

namespace Tests\Feature;

use App\Models\Master;
use App\Models\MasterPayout;
use App\Models\User;
use App\PaymentModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_payments_index_requires_authentication(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_payments_index(): void
    {
        $this->actingAsAdmin();
        Master::factory()->create(['balance' => 500]);
        MasterPayout::factory()->create();

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Index')
                ->has('mastersWithBalance.data')
                ->has('payouts.data')
                ->has('stats')
                ->where('canPayout', true)
            );
    }

    public function test_index_only_lists_masters_with_positive_balance(): void
    {
        $this->actingAsAdmin();
        Master::factory()->create(['balance' => 300]);
        Master::factory()->create(['balance' => 0]);

        $this->get(route('payments.index'))
            ->assertInertia(fn ($page) => $page->where('mastersWithBalance.data', fn ($masters) => count($masters) === 1));
    }

    // ── Payout ────────────────────────────────────────────────────────────────

    public function test_admin_can_pay_out_master_balance(): void
    {
        $admin = $this->actingAsAdmin();
        $master = Master::factory()->salaryPercentage()->create(['balance' => 425.50]);

        $this->post(route('payments.payout', $master), ['note' => 'Наличными'])
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('notification', fn ($notification) => $notification['type'] === 'success');

        $this->assertEqualsWithDelta(0.0, (float) $master->fresh()->balance, 0.01);
        $this->assertDatabaseHas('master_payouts', [
            'master_id' => $master->id,
            'master_name' => $master->name,
            'amount' => 425.50,
            'payment_model' => PaymentModel::SalaryPercentage->value,
            'paid_by' => $admin->id,
            'note' => 'Наличными',
        ]);
    }

    public function test_admin_can_partially_pay_out_master_balance(): void
    {
        $admin = $this->actingAsAdmin();
        $master = Master::factory()->create(['balance' => 500]);

        $this->post(route('payments.payout', $master), ['amount' => 200, 'note' => 'Часть'])
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('notification', fn ($notification) => $notification['type'] === 'success');

        $this->assertEqualsWithDelta(300.0, (float) $master->fresh()->balance, 0.01);
        $this->assertDatabaseHas('master_payouts', [
            'master_id' => $master->id,
            'amount' => 200,
            'paid_by' => $admin->id,
            'note' => 'Часть',
        ]);
    }

    public function test_omitting_amount_pays_out_the_full_balance(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['balance' => 420.50]);

        $this->post(route('payments.payout', $master))
            ->assertRedirect(route('payments.index'));

        $this->assertEqualsWithDelta(0.0, (float) $master->fresh()->balance, 0.01);
        $this->assertDatabaseHas('master_payouts', [
            'master_id' => $master->id,
            'amount' => 420.50,
        ]);
    }

    public function test_partial_payout_cannot_exceed_balance(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['balance' => 100]);

        $this->post(route('payments.payout', $master), ['amount' => 250])
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('notification', fn ($notification) => $notification['type'] === 'error');

        $this->assertDatabaseCount('master_payouts', 0);
        $this->assertEqualsWithDelta(100.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_payout_rejects_non_positive_amount(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['balance' => 300]);

        $this->post(route('payments.payout', $master), ['amount' => 0])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('master_payouts', 0);
        $this->assertEqualsWithDelta(300.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_payout_with_zero_balance_fails_and_records_nothing(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['balance' => 0]);

        $this->post(route('payments.payout', $master))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('notification', fn ($notification) => $notification['type'] === 'error');

        $this->assertDatabaseCount('master_payouts', 0);
        $this->assertEqualsWithDelta(0.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_manager_cannot_view_payments_index(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->get(route('payments.index'))->assertForbidden();
    }

    public function test_manager_cannot_pay_out(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $master = Master::factory()->create(['balance' => 200]);

        $this->post(route('payments.payout', $master))->assertForbidden();

        $this->assertDatabaseCount('master_payouts', 0);
        $this->assertEqualsWithDelta(200.0, (float) $master->fresh()->balance, 0.01);
    }

    public function test_operator_cannot_pay_out(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        $master = Master::factory()->create(['balance' => 200]);

        $this->post(route('payments.payout', $master))->assertForbidden();

        $this->assertDatabaseCount('master_payouts', 0);
        $this->assertEqualsWithDelta(200.0, (float) $master->fresh()->balance, 0.01);
    }

    // ── Master reset-balance now records a payout ──────────────────────────────

    public function test_master_reset_balance_records_a_payout(): void
    {
        $admin = $this->actingAsAdmin();
        $master = Master::factory()->create(['balance' => 180]);

        $this->post(route('masters.reset-balance', $master))->assertRedirect(route('masters.index'));

        $this->assertEqualsWithDelta(0.0, (float) $master->fresh()->balance, 0.01);
        $this->assertDatabaseHas('master_payouts', [
            'master_id' => $master->id,
            'amount' => 180,
            'paid_by' => $admin->id,
        ]);
    }
}
