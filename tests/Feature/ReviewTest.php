<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\OrderReview;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    /** A completed order with a review attached to the given master. */
    private function reviewFor(Master $master, int $rating, ?string $comment = null, ?Client $client = null): OrderReview
    {
        $client = $client ?? Client::factory()->create();
        $order = Order::factory()->forMaster($master)->completed()->create(['client_id' => $client->id]);

        return OrderReview::factory()->create([
            'order_id' => $order->id,
            'master_id' => $master->id,
            'client_id' => $client->id,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_reviews_index_requires_authentication(): void
    {
        $this->get(route('reviews.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_view_reviews(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        $this->get(route('reviews.index'))->assertForbidden();
    }

    public function test_manager_can_view_reviews(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Manager]));

        $this->get(route('reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Reviews/Index'));
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_lists_reviews_with_master_client_and_order(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['name' => 'Мурад']);
        $client = Client::factory()->create(['name' => 'Клиент Тест']);
        $review = $this->reviewFor($master, 5, 'Отличная работа', $client);
        $categoryName = $review->order->category->name;

        $this->get(route('reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reviews/Index')
                ->has('reviews.data', 1)
                ->where('reviews.data.0.rating', 5)
                ->where('reviews.data.0.comment', 'Отличная работа')
                ->where('reviews.data.0.master.name', 'Мурад')
                ->where('reviews.data.0.client.name', 'Клиент Тест')
                ->where('reviews.data.0.order.id', $review->order_id)
                ->where('reviews.data.0.order.category.name', $categoryName)
            );
    }

    public function test_index_returns_rating_statistics(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $this->reviewFor($master, 5, 'Супер');
        $this->reviewFor($master, 3);
        $this->reviewFor($master, 1, 'Плохо');

        $this->get(route('reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 3)
                ->where('stats.average', 3)
                ->where('stats.with_comment', 2)
                ->where('stats.negative', 1)
                ->where('stats.distribution.5', 1)
                ->where('stats.distribution.4', 0)
                ->where('stats.distribution.3', 1)
                ->where('stats.distribution.1', 1)
            );
    }

    public function test_index_can_be_filtered_by_master(): void
    {
        $this->actingAsAdmin();
        $target = Master::factory()->create();
        $other = Master::factory()->create();
        $this->reviewFor($target, 5);
        $this->reviewFor($other, 2);

        $this->get(route('reviews.index', ['master_id' => $target->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.master.id', $target->id)
                ->where('stats.total', 1)
            );
    }

    public function test_index_can_be_filtered_by_rating(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $this->reviewFor($master, 5);
        $this->reviewFor($master, 2);

        $this->get(route('reviews.index', ['rating' => 2]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.rating', 2)
                ->where('stats.total', 1)
                // The histogram deliberately ignores the rating filter.
                ->where('stats.distribution.5', 1)
                ->where('stats.distribution.2', 1)
            );
    }

    public function test_index_can_be_filtered_to_reviews_with_a_comment(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $this->reviewFor($master, 4, 'Есть замечания');
        $this->reviewFor($master, 4, null);

        $this->get(route('reviews.index', ['only_with_comment' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.comment', 'Есть замечания')
            );
    }

    public function test_index_search_matches_comment_client_and_master(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['name' => 'Аман']);
        $this->reviewFor($master, 5, 'Приехал вовремя');
        $this->reviewFor(Master::factory()->create(['name' => 'Батыр']), 3, 'Опоздал');

        $this->get(route('reviews.index', ['search' => 'вовремя']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('reviews.data', 1));

        $this->get(route('reviews.index', ['search' => 'Батыр']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('reviews.data', 1)
                ->where('reviews.data.0.master.name', 'Батыр')
            );
    }

    public function test_index_only_lists_masters_that_have_reviews(): void
    {
        $this->actingAsAdmin();
        $reviewed = Master::factory()->create(['name' => 'С отзывом']);
        Master::factory()->create(['name' => 'Без отзывов']);
        $this->reviewFor($reviewed, 4);

        $this->get(route('reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('masters', 1)
                ->where('masters.0.name', 'С отзывом')
                ->where('masters.0.reviews_count', 1)
                ->where('masters.0.reviews_avg_rating', 4)
            );
    }

    public function test_index_rejects_an_out_of_range_rating_filter(): void
    {
        $this->actingAsAdmin();

        $this->get(route('reviews.index', ['rating' => 9]))
            ->assertSessionHasErrors('rating');
    }

    public function test_index_rejects_a_date_range_that_ends_before_it_starts(): void
    {
        $this->actingAsAdmin();

        $this->get(route('reviews.index', ['date_from' => '2026-05-10', 'date_to' => '2026-05-01']))
            ->assertSessionHasErrors('date_to');
    }

    // ── Per-master panel ──────────────────────────────────────────────────────

    public function test_master_reviews_endpoint_returns_reviews_and_stats(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create(['name' => 'Мерген']);
        $this->reviewFor($master, 5, 'Быстро');
        $this->reviewFor($master, 4);
        $this->reviewFor(Master::factory()->create(), 1, 'Чужой отзыв');

        $response = $this->getJson(route('masters.reviews', $master->id))->assertOk();

        $response->assertJsonPath('master.name', 'Мерген')
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.average', 4.5)
            ->assertJsonCount(2, 'reviews')
            ->assertJsonPath('reviews.0.master.id', $master->id);
    }

    public function test_master_reviews_endpoint_returns_empty_stats_for_a_master_without_reviews(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();

        $this->getJson(route('masters.reviews', $master->id))
            ->assertOk()
            ->assertJsonPath('stats.total', 0)
            ->assertJsonPath('stats.average', null)
            ->assertJsonCount(0, 'reviews');
    }

    public function test_master_reviews_endpoint_404s_for_an_unknown_master(): void
    {
        $this->actingAsAdmin();

        $this->getJson(route('masters.reviews', 999999))->assertNotFound();
    }

    // ── Order detail ──────────────────────────────────────────────────────────

    public function test_order_page_exposes_the_client_review(): void
    {
        $this->actingAsAdmin();
        $master = Master::factory()->create();
        $client = Client::factory()->create(['name' => 'Довольный клиент']);
        $review = $this->reviewFor($master, 5, 'Всё понравилось', $client);

        $this->get(route('orders.show', $review->order_id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.review.rating', 5)
                ->where('order.review.comment', 'Всё понравилось')
                ->where('order.review.client_name', 'Довольный клиент')
            );
    }

    public function test_order_page_review_is_null_when_the_client_has_not_left_one(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->completed()->create();

        $this->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('order.review', null));
    }
}
