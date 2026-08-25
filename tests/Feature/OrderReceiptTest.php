<?php

namespace Tests\Feature;

use App\Actions\IssueOrderReceiptAction;
use App\Actions\UpdateOrderStatusAction;
use App\Exceptions\OrderException;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderReceipt;
use App\Models\OrderTask;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderReceiptTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    /**
     * Заказ в работе с задачами по 30 и 70 — итого 100 до скидки.
     */
    private function orderInProgressWithTasks(float $discountPercent = 0): Order
    {
        $master = Master::factory()->create(['name' => 'Gurban Azadow']);

        $order = Order::factory()
            ->forMaster($master)
            ->inProgress()
            ->create([
                'client_name' => 'Emil',
                'discount_percent' => $discountPercent,
            ]);

        OrderTask::factory()->priced(30)->create(['order_id' => $order->id, 'title' => 'Замена смесителя']);
        OrderTask::factory()->priced(70)->create(['order_id' => $order->id, 'title' => 'Прочистка сифона']);

        return $order->fresh();
    }

    private function complete(Order $order): Order
    {
        return app(UpdateOrderStatusAction::class)->handle($order, OrderStatus::Completed);
    }

    // ── Выдача чека ───────────────────────────────────────────────────────────

    public function test_receipt_is_issued_when_order_is_completed(): void
    {
        $order = $this->orderInProgressWithTasks();

        $this->complete($order);

        $receipt = OrderReceipt::where('order_id', $order->id)->firstOrFail();

        $this->assertSame('100.00', $receipt->subtotal);
        $this->assertSame('100.00', $receipt->total);
        $this->assertCount(2, $receipt->items);
    }

    public function test_receipt_lists_every_priced_task_with_its_price(): void
    {
        $order = $this->orderInProgressWithTasks();

        $this->complete($order);

        $items = OrderReceipt::where('order_id', $order->id)->firstOrFail()->items;

        $this->assertEqualsCanonicalizing(
            ['Замена смесителя', 'Прочистка сифона'],
            $items->pluck('title')->all()
        );
        $this->assertEqualsCanonicalizing(['30.00', '70.00'], $items->pluck('price')->all());
    }

    public function test_receipt_snapshots_client_and_master(): void
    {
        $order = $this->orderInProgressWithTasks();

        $this->complete($order);

        $receipt = OrderReceipt::where('order_id', $order->id)->firstOrFail();

        $this->assertSame('Emil', $receipt->client_name);
        $this->assertSame('Gurban Azadow', $receipt->master_name);
        $this->assertSame($order->client_phone, $receipt->client_phone);
        $this->assertSame($order->master->phone, $receipt->master_phone);
    }

    public function test_receipt_total_reflects_the_discount_and_matches_final_price(): void
    {
        $order = $this->orderInProgressWithTasks(discountPercent: 10);

        $completed = $this->complete($order);

        $receipt = OrderReceipt::where('order_id', $order->id)->firstOrFail();

        $this->assertSame('100.00', $receipt->subtotal);
        $this->assertSame('10.00', $receipt->discount_amount);
        $this->assertSame('90.00', $receipt->total);
        $this->assertSame($completed->final_price, $receipt->total);
    }

    public function test_completing_an_order_with_an_unpriced_task_is_blocked(): void
    {
        $order = $this->orderInProgressWithTasks();
        OrderTask::factory()->create(['order_id' => $order->id, 'title' => 'Без цены', 'price' => null]);

        $this->expectException(OrderException::class);

        $this->complete($order->fresh());
    }

    public function test_completing_an_order_with_only_unpriced_tasks_is_blocked(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();
        OrderTask::factory()->create(['order_id' => $order->id, 'price' => null]);

        $this->expectException(OrderException::class);

        $this->complete($order);
    }

    public function test_cancelled_order_gets_no_receipt(): void
    {
        $order = $this->orderInProgressWithTasks();

        app(UpdateOrderStatusAction::class)->handle($order, OrderStatus::Cancelled, 'Клиент передумал');

        $this->assertDatabaseMissing('order_receipts', ['order_id' => $order->id]);
    }

    public function test_issuing_a_receipt_twice_keeps_the_original(): void
    {
        $order = $this->orderInProgressWithTasks();
        $this->complete($order);

        $first = OrderReceipt::where('order_id', $order->id)->firstOrFail();
        $second = app(IssueOrderReceiptAction::class)->handle($order->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OrderReceipt::where('order_id', $order->id)->count());
    }

    // ── Номер чека ────────────────────────────────────────────────────────────

    public function test_receipt_number_uses_month_day_and_a_daily_counter(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 24)->setTime(11, 31));

        $first = $this->complete($this->orderInProgressWithTasks());
        $second = $this->complete($this->orderInProgressWithTasks());

        $this->assertSame('824-0001', $first->receipt->number);
        $this->assertSame('824-0002', $second->receipt->number);
    }

    // ── Админка ───────────────────────────────────────────────────────────────

    public function test_order_page_exposes_the_receipt_to_the_admin(): void
    {
        $this->actingAsAdmin();
        $order = $this->orderInProgressWithTasks();
        $this->complete($order);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('receipt.total', 100)
                ->where('receipt.items', fn ($items) => count($items) === 2));
    }

    public function test_order_page_has_no_receipt_before_completion(): void
    {
        $this->actingAsAdmin();
        $order = $this->orderInProgressWithTasks();

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('receipt', null));
    }

    // ── Бэкфилл ───────────────────────────────────────────────────────────────

    public function test_backfill_command_issues_receipts_for_older_completed_orders(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->completed()->create();
        OrderTask::factory()->priced(50)->create(['order_id' => $order->id]);

        $this->artisan('receipts:backfill')->assertSuccessful();

        $this->assertDatabaseHas('order_receipts', ['order_id' => $order->id, 'total' => 50]);
    }
}
