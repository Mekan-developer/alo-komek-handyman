<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderTask;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_blocked_client_cannot_use_api(): void
    {
        $client = Client::factory()->blocked()->create();
        Sanctum::actingAs($client);

        $this->getJson(route('api.v1.client.me'))
            ->assertForbidden()
            ->assertJsonPath('message', __('api.client.blocked'));
    }

    public function test_blocked_client_cannot_verify_otp(): void
    {
        $client = Client::factory()->blocked()->create();
        Cache::put("client_otp:{$client->phone}", '123456', now()->addMinutes(5));

        $this->postJson(route('api.v1.client.auth.verify-otp'), [
            'phone' => $client->phone,
            'code' => '123456',
        ])->assertForbidden();
    }

    public function test_otp_is_invalidated_after_five_failed_verify_attempts(): void
    {
        $master = Master::factory()->create();
        Cache::put("master_otp:{$master->phone}", '123456', now()->addMinutes(5));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.master.auth.verify-otp'), [
                'phone' => $master->phone,
                'code' => '000000',
            ])->assertUnprocessable();
        }

        $this->assertNull(Cache::get("master_otp:{$master->phone}"));
        $this->assertNull(Cache::get("master_otp:{$master->phone}:attempts"));
    }

    public function test_completed_order_rejects_new_task_photo_upload(): void
    {
        $master = Master::factory()->create();
        $order = Order::factory()->forMaster($master)->completed()->create();
        $task = OrderTask::factory()->create(['order_id' => $order->id]);
        Sanctum::actingAs($master);

        $this->postJson(route('api.v1.master.orders.tasks.photo', [
            'order' => $order->id,
            'task' => $task->id,
        ]), [
            'type' => 'before',
            'photo' => UploadedFile::fake()->image('done.jpg'),
        ])->assertUnprocessable();
    }
}
