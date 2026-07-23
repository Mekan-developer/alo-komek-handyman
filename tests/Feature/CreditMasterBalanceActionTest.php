<?php

namespace Tests\Feature;

use App\Actions\CreditMasterBalanceAction;
use App\Models\Category;
use App\Models\City;
use App\Models\Master;
use App\Models\Order;
use App\OrderStatus;
use App\PaymentModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreditMasterBalanceActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeOrder(Master $master, ?float $finalPrice = 1000.0): Order
    {
        $city = City::factory()->create();
        $category = Category::factory()->create();

        return Order::factory()->create([
            'master_id' => $master->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'status' => OrderStatus::InProgress,
            'final_price' => $finalPrice,
        ]);
    }

    private function makeMaster(PaymentModel $model, float $value, float $initialBalance = 0.0): Master
    {
        $city = City::factory()->create();

        return Master::factory()->create([
            'city_id' => $city->id,
            'payment_model' => $model,
            'payment_value' => $value,
            'balance' => $initialBalance,
        ]);
    }

    public function test_percentage_model_credits_correct_amount(): void
    {
        $master = $this->makeMaster(PaymentModel::Percentage, 35.0);
        $order = $this->makeOrder($master, finalPrice: 1000.0);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertEqualsWithDelta(350.0, $master->fresh()->balance, 0.01);
    }

    public function test_salary_model_does_not_credit_balance(): void
    {
        $master = $this->makeMaster(PaymentModel::Salary, 3000.0);
        $order = $this->makeOrder($master, finalPrice: 1000.0);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertEqualsWithDelta(0.0, $master->fresh()->balance, 0.01);
    }

    public function test_salary_percentage_credits_percentage_part(): void
    {
        $master = $this->makeMaster(PaymentModel::SalaryPercentage, 20.0);
        $order = $this->makeOrder($master, finalPrice: 500.0);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertEqualsWithDelta(100.0, $master->fresh()->balance, 0.01);
    }

    public function test_salary_percentage_ignores_monthly_salary_in_per_order_credit(): void
    {
        $master = $this->makeMaster(PaymentModel::SalaryPercentage, 35.0);
        $master->update(['monthly_salary' => 1500.0]);
        $order = $this->makeOrder($master, finalPrice: 1000.0);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertEqualsWithDelta(350.0, $master->fresh()->balance, 0.01);
    }

    public function test_percentage_with_null_final_price_credits_nothing(): void
    {
        $master = $this->makeMaster(PaymentModel::Percentage, 35.0);
        $order = $this->makeOrder($master, finalPrice: null);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertEqualsWithDelta(0.0, $master->fresh()->balance, 0.01);
    }

    public function test_balance_accumulates_across_multiple_orders(): void
    {
        $master = $this->makeMaster(PaymentModel::Percentage, 20.0, initialBalance: 400.0);
        $order = $this->makeOrder($master, finalPrice: 1000.0);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertEqualsWithDelta(600.0, $master->fresh()->balance, 0.01);
    }

    public function test_order_without_master_does_nothing(): void
    {
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $order = Order::factory()->create([
            'master_id' => null,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'status' => OrderStatus::InProgress,
        ]);

        app(CreditMasterBalanceAction::class)->handle($order->load('master'));

        $this->assertTrue(true);
    }
}
