<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ApparelCategory;
use App\Models\ApparelOrder;
use App\Models\ApparelProduct;
use App\Models\ApparelProductVariant;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApparelApiTest extends TestCase
{
    private User $buyer;

    private ApparelProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (glob(database_path('migrations/*apparel*.php')) as $path) {
            (require $path)->up();
        }
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->string('stripe_customer_id')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        (require database_path('migrations/2026_05_11_000000_create_api_tokens_table.php'))->up();
        $this->buyer = User::create(['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'Password123!']);
        ApiToken::create(['user_id' => $this->buyer->id, 'name' => 'test', 'token' => Hash::make('buyer-token')]);
        $category = ApparelCategory::firstOrCreate(['slug' => 'test'], ['name' => 'Test', 'status' => true]);
        $this->product = ApparelProduct::create(['apparel_category_id' => $category->id, 'name' => 'Test Mug', 'slug' => 'test-mug', 'price' => 12.34, 'stock' => 5, 'status' => 'published']);
        config(['services.stripe.secret' => 'sk_test', 'services.stripe.key' => 'pk_test', 'services.stripe.currency' => 'inr', 'services.stripe.apparel_webhook_secret' => 'whsec_test']);
    }

    private function auth(): static
    {
        return $this->withToken('buyer-token');
    }

    private function order(): ApparelOrder
    {
        $this->auth()->postJson('/api/apparel/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])->assertOk();
        $this->postJson('/api/apparel/orders', $this->shipping())->assertCreated();

        return ApparelOrder::firstOrFail();
    }

    private function shipping(): array
    {
        return ['checkout_token' => (string) Str::uuid(), 'name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '123456789', 'address' => '123 Test Street'];
    }

    private function intent(ApparelOrder $order): array
    {
        return ['id' => 'pi_test', 'client_secret' => 'pi_test_secret', 'status' => 'succeeded', 'amount' => 2468, 'amount_received' => 2468, 'currency' => 'inr', 'metadata' => ['purchase_type' => 'apparel', 'order_id' => (string) $order->id, 'user_id' => (string) $this->buyer->id]];
    }

    private function stripe(array $responses): MockHandler
    {
        $mock = new MockHandler(array_map(fn ($data) => new Response(200, [], json_encode($data)), $responses));
        $this->app->bind(Client::class, fn () => new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://api.stripe.com/v1/']));

        return $mock;
    }

    public function test_public_catalog_search_category_details_and_visibility(): void
    {
        $this->getJson('/api/apparel/products/search?search=Mug')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/apparel/categories/test/products')->assertOk()->assertJsonPath('data.0.id', $this->product->id);
        $this->getJson('/api/apparel/products/test-mug')->assertOk()->assertJsonPath('data.price', '12.34');
        $this->getJson('/api/apparel/products?per_page=0')->assertUnprocessable();
        $this->product->category->update(['status' => false]);
        $this->getJson('/api/apparel/products/test-mug')->assertNotFound();
        $this->getJson('/api/apparel/products')->assertJsonCount(0, 'data');
    }

    public function test_detail_variant_cart_flags_are_optional_and_belong_to_authenticated_user(): void
    {
        $first = $this->product->variants()->create(['stock' => 5, 'status' => true, 'sort_order' => 0]);
        $second = $this->product->variants()->create(['stock' => 5, 'status' => true, 'sort_order' => 1]);
        \App\Models\ApparelCart::create(['user_id' => $this->buyer->id, 'items' => [
            $this->product->id.':'.$first->id => ['product_id' => $this->product->id, 'variant_id' => $first->id, 'quantity' => 1],
        ]]);
        $url = '/api/apparel/products/test-mug';
        $this->getJson($url)->assertOk()->assertJsonPath('data.variants.0.is_cart', false)->assertJsonPath('data.variants.1.is_cart', false);
        $this->getJson($url.'?user_id='.$this->buyer->id)->assertUnauthorized();
        $this->withToken('invalid-token')->getJson($url)->assertUnauthorized();
        $this->auth()->getJson($url.'?user_id='.$this->buyer->id)->assertOk()
            ->assertJsonPath('data.variants.0.is_cart', true)->assertJsonPath('data.variants.1.is_cart', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson($url)->assertOk()->assertJsonPath('data.variants.0.is_cart', true);
        $this->getJson($url.'?user_id='.($this->buyer->id + 1))->assertForbidden();
        $this->getJson($url.'?user_id=abc')->assertUnprocessable();
        $this->deleteJson('/api/apparel/cart/items/'.$this->product->id.':'.$first->id)->assertOk();
        $this->getJson($url)->assertOk()->assertJsonPath('data.variants.0.is_cart', false);
        \App\Models\ApparelCart::where('user_id', $this->buyer->id)->delete();
        $this->getJson($url)->assertOk()->assertJsonPath('data.variants.0.is_cart', false);
        $this->assertSame(0, \App\Models\ApparelCart::count());
    }

    public function test_cart_requires_token_and_validates_stock_and_variant(): void
    {
        $this->getJson('/api/apparel/cart')->assertUnauthorized();
        $this->auth()->postJson('/api/apparel/cart/items', ['product_id' => $this->product->id, 'quantity' => 6])->assertUnprocessable();
        $this->postJson('/api/apparel/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])->assertOk()->assertJsonPath('data.total', 24.68);
        $this->patchJson('/api/apparel/cart/items/'.$this->product->id.':0', ['quantity' => 3])->assertOk()->assertJsonPath('data.count', 3);
        $this->assertEquals(5, $this->product->fresh()->stock);
        $this->deleteJson('/api/apparel/cart/items/'.$this->product->id.':0')->assertOk()->assertJsonPath('data.count', 0);
        ApparelProductVariant::create(['apparel_product_id' => $this->product->id, 'stock' => 3, 'status' => true]);
        $this->postJson('/api/apparel/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_checkout_retries_do_not_duplicate_orders_or_stock_and_orders_are_private(): void
    {
        $this->auth()->postJson('/api/apparel/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])->assertOk();
        $data = $this->shipping() + ['total' => 1, 'payment_status' => 'paid'];
        $this->postJson('/api/apparel/orders', $data)->assertCreated()->assertJsonPath('data.total', '24.68')->assertJsonPath('data.payment_status', 'pending');
        $this->postJson('/api/apparel/orders', $data)->assertCreated();
        $this->assertEquals(1, ApparelOrder::count());
        $this->assertEquals(3, $this->product->fresh()->stock);
        $this->getJson('/api/apparel/cart')->assertJsonPath('data.count', 0);
        $this->getJson('/api/apparel/orders')->assertOk()->assertJsonMissingPath('data.0.admin_notes');
        $order = ApparelOrder::first();
        $order->update(['user_id' => 999]);
        $this->getJson('/api/apparel/orders/'.$order->id)->assertNotFound();
        $this->postJson('/api/apparel/orders/'.$order->id.'/payment-intent')->assertNotFound();
    }

    public function test_checkout_rechecks_stock_and_rolls_back_failed_order(): void
    {
        $this->auth()->postJson('/api/apparel/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])->assertOk();
        $this->product->update(['stock' => 1]);
        $this->postJson('/api/apparel/orders', $this->shipping())->assertUnprocessable();
        $this->assertEquals(0, ApparelOrder::count());
        $this->getJson('/api/apparel/cart')->assertJsonPath('data.can_checkout', false)->assertJsonPath('data.count', 2);
    }

    public function test_cancellation_releases_stock_once_and_blocks_existing_payments(): void
    {
        $order = $this->order();
        $url = '/api/apparel/orders/'.$order->id.'/cancel';
        $order->update(['payment_intent_id' => 'pi_test']);
        $this->postJson($url)->assertUnprocessable();
        $order->update(['payment_intent_id' => null]);
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->postJson($url)->assertOk();
        $this->assertEquals(5, $this->product->fresh()->stock);
    }

    public function test_payment_sheet_reuses_intent_and_confirmation_is_idempotent(): void
    {
        $order = $this->order();
        $intent = $this->intent($order);
        $this->stripe([['id' => 'cus_test'], $intent, ['secret' => 'ek_test'], $intent, ['secret' => 'ek_retry'], $intent, $intent]);
        $url = '/api/apparel/orders/'.$order->id;
        $this->postJson($url.'/payment-intent')->assertOk()->assertJsonPath('paymentIntentId', 'pi_test')->assertJsonPath('ephemeralKey', 'ek_test')->assertJsonPath('customer', 'cus_test');
        $this->postJson($url.'/payment-intent')->assertOk()->assertJsonPath('paymentIntentId', 'pi_test');
        $this->postJson($url.'/confirm-payment', ['payment_intent_id' => 'pi_other'])->assertUnprocessable();
        $this->postJson($url.'/confirm-payment', ['payment_intent_id' => 'pi_test'])->assertOk()->assertJsonPath('data.payment_status', 'paid');
        $this->postJson($url.'/confirm-payment', ['payment_intent_id' => 'pi_test'])->assertOk();
        $this->assertEquals(3, $this->product->fresh()->stock);
        $this->assertEquals('processing', $order->fresh()->status);
    }

    public function test_payment_rejects_mismatched_amount_owner_currency_and_pending_status(): void
    {
        $order = $this->order();
        $order->update(['payment_intent_id' => 'pi_test']);
        foreach (['amount' => 1, 'amount_received' => 1, 'currency' => 'usd', 'status' => 'processing', 'metadata' => ['purchase_type' => 'apparel', 'order_id' => $order->id, 'user_id' => '999']] as $key => $value) {
            $intent = $this->intent($order);
            $intent[$key] = $value;
            $this->stripe([$intent]);
            $this->postJson('/api/apparel/orders/'.$order->id.'/confirm-payment', ['payment_intent_id' => 'pi_test'])->assertUnprocessable();
        }
        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    public function test_signed_webhook_completes_order_and_rejects_forgery(): void
    {
        $order = $this->order();
        $order->update(['payment_intent_id' => 'pi_test']);
        $event = ['type' => 'payment_intent.succeeded', 'data' => ['object' => $this->intent($order)]];
        $body = json_encode($event);
        $time = time();
        $signature = 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$body, 'whsec_test');
        $this->postJson('/api/apparel/payments/webhook', $event)->assertBadRequest();
        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', '/api/apparel/payments/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $body)->assertOk();
        }
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }
}
