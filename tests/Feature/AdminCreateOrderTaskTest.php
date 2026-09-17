<?php

namespace Tests\Feature;

use App\Events\OrderTaskCreated;
use App\Models\Master;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminCreateOrderTaskTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_create_a_task_on_an_in_progress_order(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => 'Замена смесителя',
            'description' => 'Старый подтёк',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('order_tasks', [
            'order_id' => $order->id,
            'title' => 'Замена смесителя',
            'description' => 'Старый подтёк',
            'price' => null,
        ]);
    }

    public function test_admin_can_create_a_task_with_price(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => 'Прочистка',
            'price' => 150.5,
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('order_tasks', [
            'order_id' => $order->id,
            'title' => 'Прочистка',
            'price' => '150.50',
        ]);
        $this->assertEquals('150.50', $order->fresh()->final_price);
    }

    public function test_admin_can_create_a_task_while_order_is_assigned(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->assigned()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => 'Плановая работа',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('order_tasks', [
            'order_id' => $order->id,
            'title' => 'Плановая работа',
        ]);
    }

    public function test_admin_cannot_create_a_task_without_a_master(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => 'Без мастера',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseMissing('order_tasks', ['order_id' => $order->id]);
    }

    public function test_admin_cannot_create_a_task_on_a_completed_order(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->completed()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => 'Поздно',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseMissing('order_tasks', [
            'order_id' => $order->id,
            'title' => 'Поздно',
        ]);
    }

    public function test_title_is_required(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => '',
        ])->assertSessionHasErrors('title');
    }

    public function test_creating_a_task_from_admin_broadcasts_order_task_created(): void
    {
        Event::fake([OrderTaskCreated::class]);
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->inProgress()->create();

        $this->post(route('orders.tasks.store', $order), [
            'title' => 'Замена крана',
        ])->assertRedirect();

        Event::assertDispatched(OrderTaskCreated::class, fn (OrderTaskCreated $event) => $event->task->order_id === $order->id
            && $event->task->title === 'Замена крана');
    }
}
