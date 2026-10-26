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

    public function test_checkout_creates_account_then_places_order_without_payment(): void
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
        $this->assertEquals(5, $product->fresh()->stock);
    }

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->get('/apparel/orders')->assertOk()->assertSee('Order #'.$order->id);
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
}
