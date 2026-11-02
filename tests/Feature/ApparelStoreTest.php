<?php

namespace Tests\Feature;

use App\Models\ApparelCategory;
use App\Models\ApparelProduct;
use Tests\TestCase;

class ApparelStoreTest extends TestCase
{
    public function test_guests_cannot_read_order_history_or_submit_orders(): void
    {
        $this->get('/apparel/orders')->assertRedirect(route('login'));
        $this->get('/apparel/orders/1')->assertRedirect(route('login'));
        $this->post('/apparel/checkout', $this->orderData())->assertRedirect(route('login'));
        $this->product();
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 1]);
        $this->get('/apparel/checkout')->assertOk()->assertSee('Contact Information')->assertSee('Order Summary')->assertSee('Confirm password');
    }

    public function test_checkout_creates_account_then_places_order_awaiting_online_payment(): void
    {
        $this->customer(); // Create the isolated authentication schema.
        $product = $this->product();
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 1]);
        $this->get('/apparel/checkout')->assertOk();
        $token = session('apparel_checkout_token');
        $this->postJson('/apparel/checkout/account', [
            'first_name' => 'New', 'last_name' => 'Customer', 'email' => 'NEW@example.com',
            'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!',
        ])->assertOk()->assertJsonPath('authenticated', true)->assertJsonPath('csrf_token', session()->token());
        $user = \App\Models\User::where('email', 'new@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('SecurePass123!', $user->password));
        $this->assertEquals($token, session('apparel_checkout_token'));
        $this->assertNotEmpty(session('apparel_cart'));
        $this->post('/apparel/checkout', [
            'checkout_token' => $token, 'first_name' => 'New', 'last_name' => 'Customer',
            'email' => 'new@example.com', 'phone' => '+1 555 123 4567', 'country' => 'United States',
            'street' => '123 Main Street', 'city' => 'Boston', 'region' => 'MA', 'postal_code' => '02108',
        ])->assertSessionHasNoErrors();
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertStringContainsString('Boston, MA 02108', $order->address);
        $this->assertEquals(4, $product->fresh()->stock);
        $this->get('/apparel/orders')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_existing_checkout_email_requires_correct_password(): void
    {
        $user = $this->customer();
        $data = ['first_name' => 'Existing', 'last_name' => 'Customer', 'email' => $user->email, 'password' => 'wrong'];
        $this->postJson('/apparel/checkout/account', $data)->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertGuest();
        $data['password'] = 'password';
        $this->postJson('/apparel/checkout/account', $data)->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(1, \App\Models\User::count());
    }

    public function test_shared_cart_drawer_renders_on_product_pages_and_updates_without_redirects(): void
    {
        $product = $this->product();
        foreach (['/apparel', '/apparel/category/home-and-living', '/apparel/product/made-for-more'] as $path) {
            $this->get($path)->assertOk()->assertSee('id="cart-drawer"', false)->assertSee('data-cart-open', false);
        }
        $this->postJson('/apparel/product/made-for-more/cart', ['quantity' => 1])
            ->assertOk()->assertJsonPath('count', 1)->assertJsonStructure(['html']);
        $this->getJson('/apparel/cart')->assertOk()->assertJsonPath('count', 1);
        $this->patchJson('/apparel/cart/'.$product->id.':0', ['quantity' => 3])
            ->assertOk()->assertJsonPath('count', 3);
        $this->patchJson('/apparel/cart/'.$product->id.':0', ['quantity' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->deleteJson('/apparel/cart/'.$product->id.':0')->assertOk()->assertJsonPath('count', 0);
        $this->postJson('/apparel/product/made-for-more/cart', ['quantity' => 1])->assertOk();
        $this->deleteJson('/apparel/cart')->assertOk()->assertJsonPath('count', 0);
        $this->assertEquals(5, $product->fresh()->stock);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.currency' => 'usd', 'services.stripe.secret' => 'sk_test', 'services.stripe.key' => 'pk_test']);
        foreach (glob(database_path('migrations/*apparel*.php')) as $path) {
            (require $path)->up();
        }
    }

    private function product(array $attributes = []): ApparelProduct
    {
        $category = ApparelCategory::firstOrCreate(['slug' => 'home-and-living'], ['name' => 'Home & Living', 'status' => true]);

        return ApparelProduct::create($attributes + ['apparel_category_id' => $category->id, 'name' => 'Made For More Mug', 'slug' => 'made-for-more', 'price' => 11.99, 'stock' => 5, 'status' => 'published']);
    }

    private function customer(bool $admin = false): \App\Models\User
    {
        $this->withoutMiddleware(\App\Http\Middleware\AuthGates::class);
        if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('password');
                $table->string('stripe_customer_id')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
            \Illuminate\Support\Facades\Schema::create('roles', function ($table) {
                $table->id();
                $table->string('title');
                $table->softDeletes();
                $table->timestamps();
            });
            \Illuminate\Support\Facades\Schema::create('role_user', function ($table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('role_id');
            });
        }
        $user = \App\Models\User::create(['name' => 'Customer', 'email' => uniqid().'@example.com', 'password' => 'password']);
        if ($admin) {
            \Illuminate\Support\Facades\DB::table('roles')->insertOrIgnore(['id' => 1, 'title' => 'Admin']);
            $user->roles()->attach(1);
        }

        return $user;
    }

    private function orderData(): array
    {
        return ['name' => 'Customer', 'email' => 'customer@example.com', 'phone' => '123456789', 'address' => '123 Main St, City, Country', 'checkout_token' => (string) \Illuminate\Support\Str::uuid()];
    }

    public function test_checkout_reduces_stock_once_and_preserves_order_history(): void
    {
        $product = $this->product();
        $this->actingAs($this->customer());
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 2]);
        $this->assertEquals(5, $product->fresh()->stock);
        $this->get('/apparel/checkout')->assertOk()->assertSee('Order total: $23.98');
        $data = $this->orderData();
        $this->withSession(['apparel_checkout_token' => $data['checkout_token']])->post('/apparel/checkout', $data)->assertSessionHasNoErrors();
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertEquals(3, $product->fresh()->stock);
        $this->assertEquals('23.98', $order->total);
        $this->assertEquals('pending', $order->payment_status);
        $this->post('/apparel/checkout', $data)->assertRedirect(route('apparel.orders.show', $order));
        $this->assertEquals(1, \App\Models\ApparelOrder::count());
        $this->assertEquals(3, $product->fresh()->stock);
        $product->update(['name' => 'Renamed', 'price' => 99]);
        $this->get('/apparel/orders/'.$order->id)->assertOk()->assertSee('Made For More Mug')->assertSee('23.98');
        $this->get('/apparel/orders')->assertOk()->assertSee('Order #'.$order->id)
            ->assertSee('Made For More Mug')->assertSee('Qty 2')->assertSee('23.98')
            ->assertSee('Order: Pending')->assertSee('Payment: Pending')->assertSee('View order details')
            ->assertDontSee('Renamed');
        $this->actingAs($this->customer())->get('/apparel/orders/'.$order->id)->assertNotFound();
        $this->get('/admin/apparel-orders')->assertForbidden();
        $this->actingAs($this->customer(true));
        $this->get('/admin/apparel-orders')->assertOk()->assertSee('customer@example.com');
        $this->get('/admin/apparel-orders/'.$order->id)->assertOk()->assertSee('Made For More Mug');
        $this->patch('/admin/apparel-orders/'.$order->id, ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(5, $product->fresh()->stock);
        $this->patch('/admin/apparel-orders/'.$order->id, ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(5, $product->fresh()->stock);
        $this->patch('/admin/apparel-orders/'.$order->id, ['status' => 'processing'])->assertSessionHasErrors('status');
    }

    public function test_checkout_rolls_back_all_stock_when_an_item_is_unavailable(): void
    {
        $product = $this->product();
        $other = $this->product(['slug' => 'other', 'stock' => 1]);
        $data = $this->orderData();
        $this->actingAs($this->customer())->withSession([
            'apparel_checkout_token' => $data['checkout_token'],
            'apparel_cart' => [
                $product->id.':0' => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2],
                $other->id.':0' => ['product_id' => $other->id, 'variant_id' => null, 'quantity' => 2],
            ],
        ])->post('/apparel/checkout', $data)->assertSessionHasErrors('cart');
        $this->assertEquals(5, $product->fresh()->stock);
        $this->assertEquals(0, \App\Models\ApparelOrder::count());
    }

    public function test_quantity_updates_and_variant_checkout_use_variant_stock(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create(['size' => 'L', 'price' => 14.50, 'stock' => 3, 'status' => true]);
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 1, 'variant_id' => $variant->id]);
        $key = $product->id.':'.$variant->id;
        $this->patch('/apparel/cart/'.$key, ['quantity' => 4])->assertSessionHasErrors('quantity');
        $this->patch('/apparel/cart/'.$key, ['quantity' => 2])->assertSessionHas('apparel_cart.'.$key.'.quantity', 2);
        $data = $this->orderData();
        $this->actingAs($this->customer())->withSession(['apparel_checkout_token' => $data['checkout_token']])->post('/apparel/checkout', $data);
        $this->assertEquals(1, $variant->fresh()->stock);
        $this->assertEquals(5, $product->fresh()->stock);
        $this->assertEquals('29.00', \App\Models\ApparelOrder::firstOrFail()->total);
    }

    public function test_published_products_render_and_drafts_are_hidden(): void
    {
        $this->product();
        $this->product(['slug' => 'draft-mug', 'name' => 'Secret draft', 'status' => 'draft']);
        $this->get('/apparel/category/home-and-living')->assertOk()->assertSee('Made For More Mug')->assertDontSee('Secret draft');
        $this->get('/apparel/product/made-for-more')->assertOk()->assertSee('Add to cart');
        $this->get('/apparel/product/draft-mug')->assertNotFound();
    }

    public function test_cart_enforces_stock_and_uses_variant_price(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create(['size' => '11oz', 'price' => 14.50, 'stock' => 2, 'status' => true]);
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 1])->assertSessionHasErrors('variant_id');
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 2, 'variant_id' => $variant->id])->assertSessionHasNoErrors()->assertSessionHas('apparel_cart');
        $this->get('/apparel/cart')->assertOk()->assertSee('$29.00');
        $this->post('/apparel/product/made-for-more/cart', ['quantity' => 1, 'variant_id' => $variant->id])->assertSessionHasErrors('quantity');
        $this->delete('/apparel/cart/'.$product->id.':'.$variant->id)->assertSessionHas('apparel_cart', []);
    }

    public function test_category_visibility_and_sorting(): void
    {
        $this->product();
        $this->product(['name' => 'Affordable mug', 'slug' => 'affordable', 'price' => 5]);
        $this->get('/apparel?sort=price-asc')->assertOk()->assertSeeInOrder(['Affordable mug', 'Made For More Mug']);
        ApparelCategory::where('slug', 'home-and-living')->update(['status' => false]);
        $this->get('/apparel/category/home-and-living')->assertNotFound();
        $this->get('/apparel/product/made-for-more')->assertNotFound();
    }

    public function test_web_payment_is_verified_and_can_be_retried_without_duplicate_stock_changes(): void
    {
        $product = $this->product();
        $buyer = $this->customer();
        $this->actingAs($buyer)->post('/apparel/product/made-for-more/cart', ['quantity' => 2]);
        $data = $this->orderData();
        $this->withSession(['apparel_checkout_token' => $data['checkout_token']])->post('/apparel/checkout', $data);
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertTrue($order->api_checkout);
        $this->assertSame('USD', $order->currency);
        $intent = ['id' => 'pi_web', 'client_secret' => 'pi_web_secret', 'status' => 'succeeded', 'amount' => 2398, 'amount_received' => 2398, 'currency' => 'usd', 'metadata' => ['purchase_type' => 'apparel', 'order_id' => (string) $order->id, 'user_id' => (string) $buyer->id]];
        $responses = [['id' => 'cus_web'], $intent, $intent, $intent, $intent];
        $mock = new \GuzzleHttp\Handler\MockHandler(array_map(fn ($value) => new \GuzzleHttp\Psr7\Response(200, [], json_encode($value)), $responses));
        $this->app->bind(\GuzzleHttp\Client::class, fn () => new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create($mock), 'base_uri' => 'https://api.stripe.com/v1/']));
        $this->get('/apparel/orders/'.$order->id)->assertOk()->assertSee('Pay now');
        $this->postJson('/apparel/orders/'.$order->id.'/payment-intent')->assertOk()->assertJsonPath('payment_intent_id', 'pi_web')->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson('/apparel/orders/'.$order->id.'/payment-intent')->assertOk()->assertJsonPath('payment_intent_id', 'pi_web');
        $this->postJson('/apparel/orders/'.$order->id.'/cancel')->assertUnprocessable();
        $this->postJson('/apparel/orders/'.$order->id.'/confirm-payment', ['payment_intent_id' => 'pi_wrong'])->assertUnprocessable();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->postJson('/apparel/orders/'.$order->id.'/confirm-payment', ['payment_intent_id' => 'pi_web'])->assertOk();
        $this->get('/apparel/orders/'.$order->id.'/payment-return?redirect_status=failed&payment_intent=pi_other')->assertRedirect(route('apparel.orders.show', $order));
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertEquals(3, $product->fresh()->stock);
        $this->get('/apparel/orders/'.$order->id)->assertOk()->assertSee('Payment received')->assertDontSee('id="order-payment"', false);
        $this->actingAs($this->customer())->postJson('/apparel/orders/'.$order->id.'/payment-intent')->assertNotFound();
        $this->postJson('/apparel/orders/'.$order->id.'/confirm-payment', ['payment_intent_id' => 'pi_web'])->assertNotFound();
    }

    public function test_web_order_can_be_cancelled_once_before_payment(): void
    {
        $product = $this->product();
        $this->actingAs($this->customer())->post('/apparel/product/made-for-more/cart', ['quantity' => 2]);
        $data = $this->orderData();
        $this->withSession(['apparel_checkout_token' => $data['checkout_token']])->post('/apparel/checkout', $data);
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertEquals(3, $product->fresh()->stock);
        $this->post('/apparel/orders/'.$order->id.'/cancel')->assertRedirect(route('apparel.orders.show', $order));
        $this->post('/apparel/orders/'.$order->id.'/cancel')->assertRedirect();
        $this->assertEquals(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->postJson('/apparel/orders/'.$order->id.'/payment-intent')->assertUnprocessable();
    }

    public function test_color_swatches_support_variant_images_and_catalog_search(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create(['color_name' => 'Pink', 'color_hex' => '#f4c7c7', 'size' => 'One size', 'stock' => 2, 'status' => true]);
        $variant->images()->create(['apparel_product_id' => $product->id, 'image_path' => 'uploads/pink.jpg', 'sort_order' => 0]);
        $this->get('/apparel/product/made-for-more')->assertOk()->assertSee('product-variants')->assertSee('uploads/pink.jpg')->assertSee('variant-selectors')->assertSee('#f4c7c7');
        $this->get('/apparel?search=Mug')->assertOk()->assertSee('color-swatch')->assertSee('?variant='.$variant->id, false);
        $this->get('/apparel?search=nonexistent')->assertOk()->assertDontSee('Made For More Mug');
        $variant->update(['color_hex' => 'invalid-css']);
        $this->assertSame('#f4c7c7', $variant->colorSwatch()['hex']);
    }

    public function test_hosted_checkout_opens_stripe_reuses_session_and_confirms_payment(): void
    {
        $product = $this->product();
        $buyer = $this->customer();
        $this->actingAs($buyer)->post('/apparel/product/made-for-more/cart', ['quantity' => 2]);
        $data = $this->orderData() + ['payment_flow' => 'hosted'];
        $session = ['id' => 'cs_test_hosted', 'status' => 'open', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_hosted'];
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], json_encode($session)),
            new \GuzzleHttp\Psr7\Response(200, [], json_encode($session)),
        ]);
        $history = [];
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $this->app->bind(\GuzzleHttp\Client::class, fn () => new \GuzzleHttp\Client(['handler' => $stack, 'base_uri' => 'https://api.stripe.com/v1/']));
        $this->withSession(['apparel_checkout_token' => $data['checkout_token']])->postJson('/apparel/checkout', $data)
            ->assertOk()->assertJsonPath('redirect', $session['url']);
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertSame('hosted', $order->payment_flow);
        $this->assertSame('cs_test_hosted', $order->checkout_session_id);
        $this->postJson('/apparel/checkout', $data)->assertOk()->assertJsonPath('redirect', $session['url']);
        $this->assertEquals(1, \App\Models\ApparelOrder::count());
        $this->assertEquals(3, $product->fresh()->stock);
        $this->assertSame('GET', $history[1]['request']->getMethod());
        parse_str((string) $history[0]['request']->getBody(), $sent);
        $this->assertSame('2398', (string) ($sent['line_items'][0]['price_data']['unit_amount'] * $sent['line_items'][0]['quantity']));
        $this->assertSame((string) $buyer->id, $sent['payment_intent_data']['metadata']['user_id']);
        $this->postJson('/apparel/orders/'.$order->id.'/payment-intent')->assertUnprocessable();
        $this->postJson('/apparel/orders/'.$order->id.'/cancel')->assertUnprocessable();
        $session += ['mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 2398, 'currency' => 'usd', 'metadata' => ['purchase_type' => 'apparel', 'order_id' => (string) $order->id, 'user_id' => (string) $buyer->id], 'payment_intent' => 'pi_hosted'];
        $session['status'] = 'complete';
        $intent = ['id' => 'pi_hosted', 'status' => 'succeeded', 'amount' => 2398, 'amount_received' => 2398, 'currency' => 'usd', 'metadata' => $session['metadata']];
        foreach ([$session, $intent, $session, $intent] as $value) $mock->append(new \GuzzleHttp\Psr7\Response(200, [], json_encode($value)));
        $url = '/apparel/orders/'.$order->id.'/checkout-return';
        $this->get($url.'?session_id=cs_wrong')->assertRedirect(route('apparel.orders.show', $order));
        $this->get($url)->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertEquals(3, $product->fresh()->stock);
        $this->get('/apparel/orders/'.$order->id)->assertOk()->assertSee('Your order is confirmed');
        $this->actingAs($this->customer())->get($url)->assertNotFound();
    }

    public function test_explicit_account_modes_do_not_create_accounts_during_sign_in(): void
    {
        $buyer = $this->customer();
        $data = ['first_name' => 'Buyer', 'last_name' => 'Test', 'email' => 'missing@example.com', 'password' => 'SecurePass123!', 'account_mode' => 'login'];
        $this->postJson('/apparel/checkout/account', $data)->assertUnprocessable();
        $this->assertEquals(1, \App\Models\User::count());
        $data['email'] = $buyer->email;
        $data['account_mode'] = 'register';
        $this->postJson('/apparel/checkout/account', $data)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_hosted_payment_failure_keeps_order_and_prevents_a_second_payment_flow(): void
    {
        $product = $this->product();
        $this->actingAs($this->customer())->post('/apparel/product/made-for-more/cart', ['quantity' => 1]);
        $data = $this->orderData() + ['payment_flow' => 'hosted'];
        $mock = new \GuzzleHttp\Handler\MockHandler([new \GuzzleHttp\Exception\ConnectException('Connection lost', new \GuzzleHttp\Psr7\Request('POST', 'https://api.stripe.com/v1/checkout/sessions'))]);
        $this->app->bind(\GuzzleHttp\Client::class, fn () => new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create($mock), 'base_uri' => 'https://api.stripe.com/v1/']));
        $this->withSession(['apparel_checkout_token' => $data['checkout_token']])->postJson('/apparel/checkout', $data)->assertStatus(502)->assertJsonStructure(['redirect']);
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('hosted', $order->payment_flow);
        $this->assertEquals(4, $product->fresh()->stock);
        $this->postJson('/apparel/orders/'.$order->id.'/payment-intent')->assertUnprocessable();
        $mock->append(new \GuzzleHttp\Psr7\Response(200, [], json_encode(['id' => 'cs_test_retry', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_retry'])));
        $this->postJson('/apparel/checkout', $data)->assertOk();
        $this->assertSame(1, \App\Models\ApparelOrder::count());
        $this->assertEquals(4, $product->fresh()->stock);
    }

    public function test_hosted_checkout_explains_stripe_minimum_amount_rejection(): void
    {
        $product = $this->product();
        $this->actingAs($this->customer())->post('/apparel/product/made-for-more/cart', ['quantity' => 1]);
        $data = $this->orderData() + ['payment_flow' => 'hosted'];
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(400, [], json_encode(['error' => ['code' => 'amount_too_small']])),
        ]);
        $this->app->bind(\GuzzleHttp\Client::class, fn () => new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create($mock), 'base_uri' => 'https://api.stripe.com/v1/']));
        $this->withSession(['apparel_checkout_token' => $data['checkout_token']])->postJson('/apparel/checkout', $data)
            ->assertUnprocessable()->assertJsonPath('message', 'This order total is below the minimum accepted by our payment provider. Your order is saved. Please contact the store for help completing this order.');
        $order = \App\Models\ApparelOrder::firstOrFail();
        $this->assertSame('pending', $order->payment_status);
        $this->assertEquals(4, $product->fresh()->stock);
        $this->get('/apparel/orders/'.$order->id)->assertOk()->assertSee('Order summary')->assertSee('Delivery details');
    }

    public function test_hosted_signed_webhook_verifies_amount_and_completes_once_without_browser_return(): void
    {
        $buyer = $this->customer();
        $order = \App\Models\ApparelOrder::create($this->orderData() + ['number' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $buyer->id, 'total' => 11.99, 'currency' => 'USD', 'status' => 'pending', 'payment_status' => 'pending', 'api_checkout' => true, 'payment_flow' => 'hosted', 'checkout_session_id' => 'cs_test_webhook']);
        config(['services.stripe.apparel_webhook_secret' => 'whsec_test']);
        $session = ['id' => 'cs_test_webhook', 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 1199, 'currency' => 'usd', 'metadata' => ['purchase_type' => 'apparel', 'order_id' => (string) $order->id, 'user_id' => (string) $buyer->id], 'payment_intent' => 'pi_hosted_hook'];
        $intent = ['id' => 'pi_hosted_hook', 'status' => 'succeeded', 'amount' => 1199, 'amount_received' => 1199, 'currency' => 'usd', 'metadata' => $session['metadata']];
        $mock = new \GuzzleHttp\Handler\MockHandler(array_map(fn () => new \GuzzleHttp\Psr7\Response(200, [], json_encode($intent)), range(1, 2)));
        $this->app->bind(\GuzzleHttp\Client::class, fn () => new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create($mock), 'base_uri' => 'https://api.stripe.com/v1/']));
        $send = function ($session, $signatureValid = true) {
            $body = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => $session]]);
            $time = time();
            $signature = 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$body, $signatureValid ? 'whsec_test' : 'wrong');
            return $this->call('POST', '/api/apparel/payments/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $body);
        };
        $send($session, false)->assertBadRequest();
        $invalid = $session;
        $invalid['amount_total'] = 1;
        $send($invalid)->assertUnprocessable();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $send($session)->assertOk();
        $send($session)->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('pi_hosted_hook', $order->fresh()->payment_intent_id);
    }
}
