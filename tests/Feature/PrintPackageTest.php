<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\UserController;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\PrintDelivery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrintPackageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('plans', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->decimal('price');
            $t->integer('duration_days')->nullable(); $t->text('description')->nullable(); $t->text('features')->nullable();
            $t->integer('duration'); $t->string('duration_type'); $t->boolean('status')->default(true); $t->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('plan_id');
            $t->dateTime('start_date'); $t->dateTime('end_date')->nullable();
            $t->string('status'); $t->boolean('payment_status'); $t->decimal('amount_paid');
            $t->string('transaction_id')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_10_27_000001_add_print_delivery_to_plans_and_subscriptions.php'))->up();
        (require database_path('migrations/2026_10_28_000001_add_package_formats_and_seven_day_trial.php'))->up();
    }

    private function plan(bool $print): Plan
    {
        return Plan::create(['name' => 'Monthly', 'price' => 0, $print ? 'both_price' : 'digital_price' => 0, 'duration' => 1, 'duration_type' => 'months', 'status' => 1, 'print_enabled' => $print]);
    }

    private function address(): array
    {
        return ['name' => 'Reader', 'phone' => '+919876543210', 'line1' => '12 Main Street', 'line2' => null, 'city' => 'Kochi', 'state' => 'Kerala', 'postal_code' => '682001', 'country' => 'India'];
    }

    private function request(array $data): Request
    {
        $request = Request::create('/api/user/subscribe/1', 'POST', $data);
        $user = new User;
        $user->id = 12;
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_print_subscription_requires_address(): void
    {
        $this->expectException(ValidationException::class);
        (new UserController)->subscribe($this->request([]), $this->plan(true));
    }

    public function test_free_print_purchase_saves_delivery_snapshot_and_admin_alert(): void
    {
        $plan = $this->plan(true);
        $response = (new UserController)->subscribe($this->request(['delivery_address' => $this->address()]), $plan);
        $this->assertSame(201, $response->getStatusCode());
        $subscription = Subscription::firstOrFail();
        $this->assertTrue($subscription->print_enabled);
        $this->assertSame($this->address(), $subscription->delivery_address);
        $this->assertNull($subscription->print_acknowledged_at);
        $plan->update(['print_enabled' => false]);
        $this->assertTrue($subscription->fresh()->print_enabled);
    }

    public function test_digital_purchase_needs_no_address(): void
    {
        (new UserController)->subscribe($this->request([]), $this->plan(false));
        $this->assertFalse(Subscription::firstOrFail()->print_enabled);
        $this->assertNull(Subscription::firstOrFail()->delivery_address);
    }

    public function test_stripe_metadata_preserves_address_after_plan_changes(): void
    {
        $plan = $this->plan(true);
        $metadata = PrintDelivery::metadata($this->request(['delivery_address' => $this->address()]), $plan);
        $stripeMetadata = [];
        foreach ($metadata as $key => $value) {
            $stripeMetadata[substr($key, 9, -1)] = $value;
        }
        $plan->update(['print_enabled' => false]);
        $expected = $this->address();
        $expected['line2'] = '';
        $this->assertSame($expected, PrintDelivery::fromMetadata($stripeMetadata));
        $this->assertSame([], PrintDelivery::fromMetadata([]));
    }

    public function test_plan_api_exposes_print_requirements(): void
    {
        $data = (new PlanResource($this->plan(true)))->toArray(Request::create('/api/plans'));
        $this->assertTrue($data['print_enabled']);
        $this->assertTrue($data['requires_delivery_address']);
    }

    public function test_web_print_purchase_collects_address_and_can_be_acknowledged(): void
    {
        $plan = $this->plan(true);
        $controller = new \App\Http\Controllers\UserDashboardController;
        $controller->subscribe($this->request(['delivery_address' => $this->address()]), $plan);
        $subscription = Subscription::firstOrFail();
        $this->assertTrue($subscription->print_enabled);
        $admin = new \App\Http\Controllers\Admin\SubscriptionController;
        $admin->acknowledgePrint($this->request([]), $subscription);
        $acknowledged = $subscription->fresh()->print_acknowledged_at;
        $this->assertNotNull($acknowledged);
        $admin->acknowledgePrint($this->request([]), $subscription->fresh());
        $this->assertTrue($acknowledged->equalTo($subscription->fresh()->print_acknowledged_at));
    }
}
