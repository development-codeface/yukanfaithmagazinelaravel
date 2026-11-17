<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('subscription_upgrades', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('plan_id');
            $t->string('status'); $t->unsignedBigInteger('amount_minor'); $t->string('currency');
            $t->string('payment_intent_id'); $t->dateTime('paid_at');
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('category_name');
            $t->softDeletes();
        });
        foreach (['plans', 'magazine_issues', 'articles'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->string('title')->nullable();
            });
        }
        foreach (['subscriptions', 'magazine_purchases', 'article_purchases', 'apparel_orders'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                foreach (['plan_id', 'magazine_issue_id', 'article_id'] as $column) $t->unsignedBigInteger($column)->nullable();
                foreach (['payment_status', 'transaction_id', 'payment_intent_id', 'number', 'currency'] as $column) $t->string($column)->nullable();
                $t->decimal('amount_paid', 10, 2)->nullable();
                $t->decimal('support_amount', 10, 2)->default(0);
                $t->decimal('total', 10, 2)->nullable();
                $t->dateTime('paid_at')->nullable();
                $t->dateTime('purchased_at')->nullable();
                $t->timestamps();
            });
        }
    }

    private function reader(): User
    {
        return (new User)->forceFill(['id' => 7, 'name' => 'Reader']);
    }

    public function test_payment_history_lists_upgrade_separately_from_original_subscription(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class)->actingAs($this->reader());
        $this->seedPayments();
        DB::table('subscription_upgrades')->insert(['user_id' => 7, 'plan_id' => 1, 'status' => 'paid',
            'amount_minor' => 1250, 'currency' => 'inr', 'payment_intent_id' => 'pi_upgrade', 'paid_at' => '2026-11-10 12:00:00']);
        DB::table('subscription_upgrades')->insert(['user_id' => 8, 'plan_id' => 1, 'status' => 'paid',
            'amount_minor' => 99900, 'currency' => 'inr', 'payment_intent_id' => 'pi_other_upgrade', 'paid_at' => '2026-11-11 12:00:00']);
        $this->getJson('/api/user/payment-history')->assertOk()->assertJsonPath('total', 5)
            ->assertJsonPath('data.0.type', 'subscription_upgrade')->assertJsonPath('data.0.amount', '12.50')
            ->assertJsonPath('data.0.support_amount', '0.00')->assertJsonPath('data.0.transaction_id', 'pi_upgrade');
    }

    private function seedPayments(): void
    {
        foreach (['subscriptions', 'magazine_purchases', 'article_purchases', 'apparel_orders'] as $index => $table) {
            DB::table($table)->insert([
                'user_id' => 7, 'amount_paid' => 25, 'total' => 30,
                'payment_status' => $table === 'apparel_orders' ? 'paid' : '1',
                'currency' => 'usd', 'number' => 'order-123',
                'created_at' => '2026-11-0'.($index + 1).' 12:00:00',
            ]);
            DB::table($table)->insert(['user_id' => 8, 'amount_paid' => 999, 'total' => 999, 'payment_status' => $table === 'apparel_orders' ? 'paid' : '1']);
        }
        DB::table('subscriptions')->insert(['user_id' => 7, 'payment_status' => '1', 'amount_paid' => 0]);
        DB::table('article_purchases')->insert(['user_id' => 7, 'payment_status' => '0', 'amount_paid' => 50]);
        DB::table('apparel_orders')->insert(['user_id' => 7, 'payment_status' => 'pending', 'total' => 50]);
    }

    public function test_history_combines_paid_records_and_paginates_without_other_users_or_free_trials(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class)->actingAs($this->reader());
        $this->seedPayments();
        $this->getJson('/api/user/payment-history?user_id=7&per_page=2')
            ->assertOk()->assertJsonPath('total', 4)->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'apparel')->assertJsonPath('data.0.currency', 'USD')
            ->assertJsonPath('data.0.amount', '30.00')->assertJsonPath('data.1.type', 'article');
        $this->getJson('/api/user/payment-history?per_page=2&page=2')
            ->assertOk()->assertJsonPath('data.0.type', 'magazine')->assertJsonPath('data.1.type', 'subscription');
    }

    public function test_other_user_id_is_forbidden_and_pagination_is_validated(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class)->actingAs($this->reader());
        $this->getJson('/api/user/payment-history?user_id=8')->assertForbidden();
        $this->getJson('/api/user/payment-history?per_page=101')->assertUnprocessable();
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/user/payment-history?user_id=7')->assertUnauthorized();
    }

    public function test_web_history_requires_login_and_displays_payments(): void
    {
        $this->get('/user/payment-history')->assertRedirect('/login');
        $this->seedPayments();
        $this->actingAs($this->reader())->get('/user/payment-history')
            ->assertOk()->assertSee('Payment History')->assertSee('USD 30.00')->assertDontSee('999.00');
    }
}
