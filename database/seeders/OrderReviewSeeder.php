<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderReview;
use App\Models\OrderTask;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data for the Reviews section — not part of `db:seed`.
 *
 * Run it by hand: `php artisan db:seed --class=OrderReviewSeeder`
 */
class OrderReviewSeeder extends Seeder
{
    /** Ashgabat centre — the service covers this city only. */
    private const CENTER_LAT = 37.9500;

    private const CENTER_LNG = 58.3833;

    /** @var array<int, string> */
    private const POSITIVE_COMMENTS = [
        'Приехал раньше времени, всё сделал аккуратно. Рекомендую!',
        'Отличный мастер, объяснил в чём была проблема и как её избежать.',
        'Работой доволен, убрал за собой мусор. Цена как договаривались.',
        'Быстро, чисто, вежливо. Буду обращаться ещё.',
        'Спасибо большое! Починил то, что до этого двое не смогли.',
        'Вежливый, пунктуальный, всё показал и проверил при мне.',
        'Işini örän gowy etdi, sag bolsun aýdýaryn.',
        'Wagtynda geldi, hemme zady arassa etdi.',
        'Мастер знает своё дело, вопросов нет.',
        'Всё супер, работает как новое.',
    ];

    /** @var array<int, string> */
    private const NEUTRAL_COMMENTS = [
        'В целом нормально, но опоздал на полчаса.',
        'Работу сделал, хотя пришлось напоминать про мелочи.',
        'Нормально, но после работы пришлось самим прибирать.',
        'Сделал, но объяснял неохотно. По результату претензий нет.',
        'Bolýar, ýöne biraz gijä galdy.',
        'Средне. Цена чуть выше, чем ожидали.',
    ];

    /** @var array<int, string> */
    private const NEGATIVE_COMMENTS = [
        'Опоздал на два часа и не предупредил.',
        'Через неделю проблема вернулась, пришлось вызывать снова.',
        'Работу сделал наспех, остался беспорядок.',
        'Обещал одну цену, по факту назвал другую.',
        'Işi gowy edilmedi, ýene bozuldy.',
        'Очень долго, и результат так себе.',
    ];

    public function run(): void
    {
        $categories = Category::query()->whereNotNull('parent_id')->get();

        if ($categories->isEmpty()) {
            $this->command?->warn('No leaf categories — run CategorySeeder first.');

            return;
        }

        $masters = Master::query()->where('is_active', true)->get();

        if ($masters->isEmpty()) {
            $this->command?->warn('No active masters — run MasterSeeder first.');

            return;
        }

        $clients = $this->clients();

        // Completed orders nobody has reviewed yet get their review first.
        $unreviewed = Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereNotNull('master_id')
            ->whereNotNull('client_id')
            ->whereDoesntHave('review')
            ->get();

        $created = 0;

        foreach ($unreviewed as $order) {
            $this->review($order, $order->master_id, $order->client_id, $this->pickRating('good'));
            $created++;
        }

        // Every master but the last two gets a review history, so the admin can
        // also see how a master with no feedback at all renders.
        foreach ($masters->take(max(1, $masters->count() - 2)) as $index => $master) {
            $profile = match (true) {
                $index % 7 === 0 => 'poor',
                $index % 3 === 0 => 'mixed',
                default => 'good',
            };

            foreach (range(1, rand(2, 7)) as $ignored) {
                $client = $clients->random();
                $date = now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                $order = Order::factory()
                    ->around(self::CENTER_LAT, self::CENTER_LNG)
                    ->forMaster($master)
                    ->completed()
                    ->create([
                        'category_id' => $categories->random()->id,
                        'client_id' => $client->id,
                        'client_name' => $client->name,
                        'client_phone' => $client->phone,
                        'completed_at' => $date,
                    ]);

                $order->forceFill(['created_at' => $date->copy()->subHours(rand(2, 30))])->saveQuietly();

                // Priced tasks make the linked order look like a real finished job;
                // OrderTaskObserver recalculates `final_price` from them.
                OrderTask::factory()
                    ->count(rand(1, 3))
                    ->for($order)
                    ->state(fn () => ['price' => rand(50, 600)])
                    ->create();

                $this->review($order, $master->id, $client->id, $this->pickRating($profile), $date);
                $created++;
            }
        }

        $this->command?->info("Seeded {$created} order reviews.");
    }

    /**
     * Demo clients — top the table up so reviews are not all from the same person.
     *
     * Clients that stopped after the OTP step have no name yet and would break
     * the `orders.client_name` NOT NULL constraint, so they are skipped.
     */
    private function clients(): Collection
    {
        $clients = Client::query()
            ->where('is_blocked', false)
            ->whereNotNull('name')
            ->get();

        if ($clients->count() >= 8) {
            return $clients;
        }

        return $clients->merge(Client::factory()->count(8 - $clients->count())->create());
    }

    /**
     * Rating drawn from the master's demo "quality profile" — a service where
     * every master scores 5 would make the new section look broken.
     */
    private function pickRating(string $profile): int
    {
        $weights = match ($profile) {
            'poor' => [1, 1, 2, 2, 2, 3, 3, 4],
            'mixed' => [2, 3, 3, 4, 4, 4, 5, 5],
            default => [3, 4, 4, 5, 5, 5, 5, 5],
        };

        return $weights[array_rand($weights)];
    }

    private function review(Order $order, int $masterId, int $clientId, int $rating, ?Carbon $date = null): void
    {
        $date = $date ?? now()->subDays(rand(0, 60));

        $review = OrderReview::create([
            'order_id' => $order->id,
            'master_id' => $masterId,
            'client_id' => $clientId,
            'rating' => $rating,
            'comment' => $this->comment($rating),
        ]);

        // `created_at` is not fillable, but the feed is ordered by it.
        $review->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();
    }

    /** Roughly a third of real reviews carry no text — keep that gap visible. */
    private function comment(int $rating): ?string
    {
        if (rand(1, 100) <= 30) {
            return null;
        }

        $pool = match (true) {
            $rating <= 2 => self::NEGATIVE_COMMENTS,
            $rating === 3 => self::NEUTRAL_COMMENTS,
            default => self::POSITIVE_COMMENTS,
        };

        return $pool[array_rand($pool)];
    }
}
