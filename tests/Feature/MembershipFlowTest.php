<?php

namespace Tests\Feature;

use App\Models\{Article, MagazineIssue, MagazinePurchase, Plan, Subscription, User};
use App\Support\{PackageSelection, RegistrationTrial};
use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class MembershipFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-27 12:00:00'));
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password');
            $t->string('stripe_customer_id')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('plans', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->decimal('price', 10, 2)->default(0);
            $t->integer('duration'); $t->string('duration_type'); $t->integer('duration_days')->nullable();
            $t->text('description')->nullable(); $t->text('features')->nullable();
            $t->boolean('status')->default(true); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('plan_id')->nullable();
            $t->dateTime('start_date'); $t->dateTime('end_date')->nullable(); $t->string('status');
            $t->boolean('payment_status'); $t->decimal('amount_paid', 10, 2)->nullable();
            $t->string('transaction_id')->nullable(); $t->timestamps();
        });
        Schema::create('magazine_issues', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->text('description')->nullable(); $t->string('pdf_url')->nullable();
            $t->date('issue_date')->nullable(); $t->dateTime('published_at')->nullable(); $t->timestamps();
        });
        Schema::create('articles', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->text('summary')->nullable(); $t->text('content')->nullable();
            $t->string('status')->default('published'); $t->string('access_type')->default('paid');
            $t->unsignedBigInteger('issue_id')->nullable(); $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('author_id')->nullable(); $t->dateTime('published_at')->nullable();
            $t->decimal('single_article_price', 10, 2)->nullable(); $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) { $t->id(); $t->string('category_name'); });
        Schema::create('article_category', function (Blueprint $t) { $t->unsignedBigInteger('article_id'); $t->unsignedBigInteger('category_id'); });
        Schema::create('article_blocks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('article_id'); $t->string('block_type'); $t->integer('block_order'); $t->text('block_data')->nullable(); $t->timestamps();
        });
        Schema::create('article_gallery_images', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('article_id'); $t->string('image_path'); $t->timestamps(); });
        Schema::create('article_purchases', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('article_id'); $t->boolean('payment_status');
            $t->decimal('amount_paid', 10, 2)->nullable(); $t->string('transaction_id')->nullable(); $t->dateTime('purchased_at')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_10_22_000001_add_magazine_purchases.php'))->up();
        (require database_path('migrations/2026_10_27_000001_add_print_delivery_to_plans_and_subscriptions.php'))->up();
        (require database_path('migrations/2026_10_28_000001_add_package_formats_and_seven_day_trial.php'))->up();
        (require database_path('migrations/2026_10_28_000002_add_magazine_format_prices.php'))->up();
        (require database_path('migrations/2026_11_02_000001_add_plan_format_content.php'))->up();
        (require database_path('migrations/2026_11_09_000001_add_trial_billing_and_package_support.php'))->up();
        (require database_path('migrations/2026_11_11_000001_add_subscription_upgrades.php'))->up();
        (require database_path('migrations/2026_05_11_000000_create_api_tokens_table.php'))->up();
        config(['services.stripe.secret' => 'sk_test', 'services.stripe.key' => 'pk_test', 'services.stripe.subscription_webhook_secret' => 'whsec_test']);
    }

    public function test_plain_subscriptions_visit_does_not_restore_previous_magazine(): void
    {
        $user = $this->user(); $this->plan(); $magazine = $this->issue();
        $this->actingAs($user)->withSession(['selected_magazine_id' => $magazine->id, 'selected_magazine_type' => 'print'])
            ->get(route('user.subscriptions'))->assertOk()
            ->assertViewHas('selectedMagazine', null)->assertViewHas('selectedType', 'digital')
            ->assertDontSee('Your selected magazine');
        $this->get(route('user.subscriptions', ['magazine' => $magazine->id]))
            ->assertOk()->assertSee('Your selected magazine');
        $this->get(route('user.subscriptions'))->assertOk()->assertDontSee('Your selected magazine');
    }

    private function configuredTrial(): Plan
    {
        $annual = Plan::create(['name' => 'Basic Annual', 'digital_price' => 1200, 'price' => 1200, 'duration' => 1, 'duration_type' => 'years', 'status' => 1]);
        return Plan::create(['name' => 'Free Trial', 'is_trial' => true, 'digital_price' => 0, 'price' => 0, 'duration' => 7, 'duration_type' => 'days', 'status' => 1, 'trial_conversion_plan_id' => $annual->id]);
    }

    private function upgradeSubscription(string $type = 'digital'): Subscription
    {
        $user = $this->user(); $plan = $this->plan();
        return Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'delivery_type' => $type,
            'start_date' => now()->subDays(15), 'end_date' => now()->addDays(15), 'status' => 'active', 'payment_status' => 1,
            'amount_paid' => ($type === 'print' ? 150 : 100) + 25, 'support_amount' => 25, 'currency' => 'INR',
            'transaction_id' => 'pi_original', 'format_prices' => $plan->prices(), 'price_snapshot_source' => 'purchase',
            'print_enabled' => $type === 'print', 'delivery_address' => $type === 'print' ? $this->address() : null]);
    }

    public function test_upgrade_quote_uses_remaining_period_and_preserves_price_snapshot(): void
    {
        $subscription = $this->upgradeSubscription();
        $subscription->plan->update(['both_price' => 999]);
        $quote = app(\App\Services\SubscriptionUpgradeService::class)->quote($subscription);
        $this->assertSame(5000, $quote['amount']);
        $this->assertSame('50.00', $quote['upgrade_amount']);
        $this->assertSame('0.00', $quote['support_amount']);
        $this->assertSame($subscription->end_date->toIso8601String(), $quote['ends_at']);
        $this->assertSame('digital', $subscription->fresh()->delivery_type);
        $selected = PackageSelection::fromMetadata($subscription->plan, [
            'delivery_type' => 'digital', 'support_amount' => '25', 'format_prices' => json_encode(['digital' => '100.00', 'both' => '200.00']),
        ], 125);
        $this->assertSame('200.00', $selected->selected_format_prices['both']);
        $this->assertSame('100.00', $selected->selected_format_prices['digital']);
    }

    public function test_native_upgrade_only_applies_after_verified_payment_and_is_idempotent(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $subscription = $this->upgradeSubscription(); $user = $subscription->user;
        $service = app(\App\Services\SubscriptionUpgradeService::class);
        $status = 'requires_payment_method'; $amount = 5000;
        \Illuminate\Support\Facades\Http::fake(function ($request) use (&$status, &$amount, $service) {
            $upgrade = \App\Models\SubscriptionUpgrade::firstOrFail();
            return \Illuminate\Support\Facades\Http::response(['id' => 'pi_upgrade', 'client_secret' => 'pi_upgrade_secret',
                'status' => $status, 'amount_received' => $amount, 'currency' => 'inr', 'metadata' => $service->metadata($upgrade)]);
        });
        $this->actingAs($user)->postJson('/api/user/subscriptions/'.$subscription->id.'/upgrade/payment-intent', ['delivery_address' => $this->address(), 'amount' => 1])
            ->assertOk()->assertJsonPath('amount', 5000)->assertJsonPath('upgrade_id', 1);
        $this->assertSame('digital', $subscription->fresh()->delivery_type);
        $this->postJson('/api/user/subscription-upgrades/1/confirm')->assertUnprocessable();
        $status = 'succeeded'; $amount = 1;
        $this->postJson('/api/user/subscription-upgrades/1/confirm')->assertUnprocessable();
        $amount = 5000;
        $this->postJson('/api/user/subscription-upgrades/1/confirm')->assertOk()->assertJsonPath('subscription.delivery_type', 'both');
        $this->postJson('/api/user/subscription-upgrades/1/confirm')->assertOk();
        $updated = $subscription->fresh();
        $this->assertSame($subscription->end_date->toIso8601String(), $updated->end_date->toIso8601String());
        $this->assertSame('125.00', $updated->amount_paid);
        $this->assertSame('25.00', $updated->support_amount);
        $this->assertSame('pi_original', $updated->transaction_id);
        $this->assertTrue($updated->print_enabled);
        $this->assertSame(1, \App\Models\SubscriptionUpgrade::count());
        $this->assertSame(1, Subscription::count());
    }

    public function test_print_upgrade_keeps_delivery_address_and_unlocks_digital_after_payment(): void
    {
        $subscription = $this->upgradeSubscription('print');
        $this->assertFalse($subscription->user->hasActiveDigitalSubscription());
        $service = app(\App\Services\SubscriptionUpgradeService::class);
        $quote = $service->quote($subscription);
        $this->assertSame(2500, $quote['amount']);
        $upgrade = $service->prepare($subscription, $subscription->delivery_address, 'native');
        $upgrade->update(['payment_intent_id' => 'pi_print_upgrade']);
        \Illuminate\Support\Facades\Http::fake(fn () => \Illuminate\Support\Facades\Http::response([
            'id' => 'pi_print_upgrade', 'status' => 'succeeded', 'amount_received' => 2500, 'currency' => 'inr', 'metadata' => $service->metadata($upgrade),
        ]));
        $service->verify($upgrade);
        $this->assertTrue($subscription->user->hasActiveDigitalSubscription());
        $this->assertSame($subscription->delivery_address, $subscription->fresh()->delivery_address);
    }

    public function test_upgrade_web_checkout_retries_same_payment_and_respects_owner_and_channel(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $subscription = $this->upgradeSubscription(); $service = app(\App\Services\SubscriptionUpgradeService::class);
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'Password123!']);
        $this->actingAs($other)->getJson('/api/user/subscriptions/'.$subscription->id.'/upgrade')->assertForbidden();
        $this->actingAs($subscription->user)->get(route('user.upgrades.show', $subscription))->assertOk()->assertSee('50.00');
        \Illuminate\Support\Facades\Http::fake(fn () => \Illuminate\Support\Facades\Http::response([
            'id' => 'cs_upgrade', 'status' => 'open', 'url' => 'https://checkout.stripe.com/upgrade',
        ]));
        $this->post(route('user.upgrades.checkout', $subscription), ['delivery_address' => $this->address()])->assertRedirect('https://checkout.stripe.com/upgrade');
        $this->post(route('user.upgrades.checkout', $subscription), ['delivery_address' => $this->address()])->assertRedirect('https://checkout.stripe.com/upgrade');
        $this->assertSame(1, \App\Models\SubscriptionUpgrade::count());
        $this->postJson('/api/user/subscriptions/'.$subscription->id.'/upgrade/payment-intent', ['delivery_address' => $this->address()])->assertUnprocessable();
        $upgrade = \App\Models\SubscriptionUpgrade::firstOrFail();
        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory);
        \Illuminate\Support\Facades\Http::fake(function ($r) use ($upgrade, $service) {
            return \Illuminate\Support\Facades\Http::response(str_contains($r->url(), '/checkout/sessions/')
                ? ['mode' => 'payment', 'status' => 'complete', 'payment_status' => 'paid', 'payment_intent' => 'pi_hosted_upgrade', 'metadata' => $service->metadata($upgrade)]
                : ['id' => 'pi_hosted_upgrade', 'status' => 'succeeded', 'amount_received' => 5000, 'currency' => 'inr', 'metadata' => $service->metadata($upgrade)]);
        });
        $this->get(route('user.upgrades.success', [$upgrade, 'session_id' => 'cs_wrong']))->assertStatus(422);
        $this->get(route('user.upgrades.success', [$upgrade, 'session_id' => 'cs_upgrade']))->assertRedirect(route('user.subscriptions'));
        $this->assertSame('both', $subscription->fresh()->delivery_type);
    }

    public function test_upgrade_rejects_expired_combined_trial_and_missing_address(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $subscription = $this->upgradeSubscription(); $this->actingAs($subscription->user);
        $endpoint = '/api/user/subscriptions/'.$subscription->id.'/upgrade';
        $this->postJson($endpoint.'/payment-intent')->assertUnprocessable()->assertJsonValidationErrors('delivery_address');
        $subscription->update(['end_date' => now()->subSecond()]);
        $this->getJson($endpoint)->assertUnprocessable();
        $subscription->update(['end_date' => now()->addDays(15), 'delivery_type' => 'both']);
        $this->getJson($endpoint)->assertUnprocessable();
        $subscription->update(['delivery_type' => 'digital', 'plan_id' => $this->configuredTrial()->id]);
        $this->getJson($endpoint)->assertUnprocessable();
    }

    public function test_upgrade_scheduler_recovers_payment_and_packages_offer_upgrade(): void
    {
        $subscription = $this->upgradeSubscription(); $service = app(\App\Services\SubscriptionUpgradeService::class);
        $this->actingAs($subscription->user)->get(route('user.subscriptions'))->assertOk()->assertSee('Upgrade — INR 50.00');
        $upgrade = $service->prepare($subscription, $this->address(), 'native'); $upgrade->update(['payment_intent_id' => 'pi_recovered']);
        \Illuminate\Support\Facades\Http::fake(fn () => \Illuminate\Support\Facades\Http::response([
            'id' => 'pi_recovered', 'status' => 'succeeded', 'amount_received' => 5000, 'currency' => 'inr', 'metadata' => $service->metadata($upgrade),
        ]));
        $this->artisan('upgrades:sync')->assertExitCode(0);
        $this->artisan('upgrades:sync')->assertExitCode(0);
        $this->assertSame('both', $subscription->fresh()->delivery_type);
        $this->assertSame(1, \App\Models\SubscriptionUpgrade::where('status', 'paid')->count());
    }

    public function test_magazine_delivery_filters_search_magazine_format_review_and_purchase_date(): void
    {
        $user = $this->user(); $issue = $this->issue(); $other = $this->issue('2026-09-01');
        $base = ['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'amount_paid' => 50,
            'delivery_type' => 'both', 'transaction_id' => 'pi_delivery_target', 'created_at' => '2026-10-27 23:59:59'];
        $match = MagazinePurchase::create($base);
        MagazinePurchase::create(array_merge($base, ['magazine_issue_id' => $other->id, 'transaction_id' => 'pi_delivery_other']));
        MagazinePurchase::create(array_merge($base, ['delivery_type' => 'digital', 'transaction_id' => 'pi_delivery_digital']));
        MagazinePurchase::create(array_merge($base, ['delivery_type' => 'print', 'print_acknowledged_at' => now(), 'transaction_id' => 'pi_delivery_reviewed']));
        $controller = new \App\Http\Controllers\Admin\MagazinePurchaseController;
        $result = $controller->index(Request::create('/admin/magazine-purchases', 'GET', [
            'search' => $user->email, 'magazine_id' => $issue->id, 'delivery_type' => 'both',
            'review' => 'pending', 'date_from' => '2026-10-27', 'date_to' => '2026-10-27',
        ]))->getData()['purchases'];
        $this->assertSame([$match->id], $result->pluck('id')->all());
        $result = $controller->index(Request::create('/admin/magazine-purchases', 'GET', ['review' => 'reviewed']))->getData()['purchases'];
        $this->assertSame(1, $result->total());
        $result = $controller->index(Request::create('/admin/magazine-purchases', 'GET', ['review' => 'pending']))->getData()['purchases'];
        $this->assertSame(2, $result->total());
        $result = $controller->index(Request::create('/admin/magazine-purchases', 'GET', ['search' => 'pi_delivery_target']))->getData()['purchases'];
        $this->assertSame(1, $result->total());
        $result = $controller->index(Request::create('/admin/magazine-purchases', 'GET', ['search' => $user->name, 'search_by' => 'name']))->getData()['purchases'];
        $this->assertSame(4, $result->total());
        $result = $controller->index(Request::create('/admin/magazine-purchases', 'GET', ['search' => 'pi_delivery_target', 'search_by' => 'name']))->getData()['purchases'];
        $this->assertSame(0, $result->total());
    }

    public function test_magazine_delivery_pagination_preserves_filters_and_validates_input(): void
    {
        $user = $this->user(); $issue = $this->issue();
        for ($i = 0; $i < 17; $i++) MagazinePurchase::create([
            'user_id' => $user->id, 'magazine_issue_id' => $this->issue()->id, 'delivery_type' => 'print', 'amount_paid' => 20, 'transaction_id' => 'pi_page_'.$i,
        ]);
        $request = Request::create('/admin/magazine-purchases', 'GET', ['delivery_type' => 'print', 'per_page' => 15]);
        $this->app->instance('request', $request);
        $controller = new \App\Http\Controllers\Admin\MagazinePurchaseController;
        $result = $controller->index($request)->getData()['purchases'];
        $this->assertSame(17, $result->total());
        $this->assertCount(15, $result->items());
        $this->assertStringContainsString('delivery_type=print', $result->nextPageUrl());
        $this->assertStringContainsString('per_page=15', $result->nextPageUrl());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->index(Request::create('/admin/magazine-purchases', 'GET', ['date_from' => '2026-10-27', 'date_to' => '2026-10-01', 'per_page' => 1000]));
    }

    public function test_admin_subscription_filters_combine_search_package_support_format_and_review(): void
    {
        $user = $this->user(); $plan = $this->plan();
        $base = ['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'payment_status' => 1,
            'start_date' => now()->subDay(), 'end_date' => now()->addMonth(), 'amount_paid' => 125,
            'delivery_type' => 'both', 'print_enabled' => true, 'support_amount' => 25, 'transaction_id' => 'pi_filter_target'];
        $match = Subscription::create($base);
        Subscription::create(array_merge($base, ['support_amount' => 0, 'transaction_id' => 'pi_no_support']));
        Subscription::create(array_merge($base, ['print_acknowledged_at' => now(), 'transaction_id' => 'pi_reviewed']));
        $controller = new \App\Http\Controllers\Admin\SubscriptionController;
        $result = $controller->index(Request::create('/admin/subscriptions', 'GET', [
            'search' => $user->email, 'plan_id' => $plan->id, 'support' => 'paid', 'delivery_type' => 'both', 'review' => 'pending', 'status' => 'active',
        ]))->getData()['subscriptions'];
        $this->assertSame([$match->id], $result->pluck('id')->all());
        $result = $controller->index(Request::create('/admin/subscriptions', 'GET', ['search' => 'pi_reviewed']))->getData()['subscriptions'];
        $this->assertSame(1, $result->total());
        $this->assertSame('pi_reviewed', $result->first()->transaction_id);
        $result = $controller->index(Request::create('/admin/subscriptions', 'GET', ['payment_status' => '0']))->getData()['subscriptions'];
        $this->assertSame(0, $result->total());
    }

    public function test_admin_subscription_expiry_and_date_filters_use_actual_dates_and_paginate(): void
    {
        $user = $this->user(); $plan = $this->plan();
        $base = ['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'payment_status' => 1,
            'start_date' => now()->subMonth(), 'end_date' => now()->subDay(), 'amount_paid' => 100, 'delivery_type' => 'digital'];
        $expired = Subscription::create($base);
        Subscription::create(array_merge($base, ['start_date' => now()->addDay(), 'end_date' => now()->addMonth()]));
        $controller = new \App\Http\Controllers\Admin\SubscriptionController;
        $result = $controller->index(Request::create('/admin/subscriptions', 'GET', ['status' => 'expired']))->getData()['subscriptions'];
        $this->assertSame([$expired->id], $result->pluck('id')->all());
        $result = $controller->index(Request::create('/admin/subscriptions', 'GET', ['status' => 'scheduled']))->getData()['subscriptions'];
        $this->assertSame(1, $result->total());
        $result = $controller->index(Request::create('/admin/subscriptions', 'GET', [
            'date_field' => 'end_date', 'date_from' => now()->subDay()->toDateString(), 'date_to' => now()->subDay()->toDateString(), 'per_page' => 15,
        ]))->getData()['subscriptions'];
        $this->assertSame([$expired->id], $result->pluck('id')->all());
        $this->assertSame(15, $result->perPage());
    }

    public function test_admin_subscription_filters_reject_invalid_dates_and_fields(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new \App\Http\Controllers\Admin\SubscriptionController)->index(Request::create('/admin/subscriptions', 'GET', [
            'date_field' => 'transaction_id', 'date_from' => '2026-11-10', 'date_to' => '2026-11-01', 'per_page' => 10000,
        ]));
    }

    private function trialStripe(\App\Models\TrialEnrollment $enrollment): array
    {
        return ['id' => 'sub_trial_test', 'status' => 'trialing', 'default_payment_method' => 'pm_test',
            'trial_start' => now()->timestamp, 'trial_end' => now()->addDays(7)->timestamp,
            'cancel_at' => now()->addDays(7)->addYearNoOverflow()->timestamp, 'cancel_at_period_end' => false,
            'metadata' => ['trial_enrollment_id' => (string) $enrollment->id, 'user_id' => (string) $enrollment->user_id]];
    }

    public function test_guest_can_browse_packages_and_home_actions_without_signing_in(): void
    {
        $plan = $this->plan(); $this->configuredTrial();
        $this->get('/user/subscriptions')->assertOk()->assertSee($plan->name)->assertSee('Sign in to choose')->assertSee('7 days free')
            ->assertSee('href="'.route('user.subscriptions').'" class="btn btn-dark header-action"', false);
        $issue = $this->issue();
        $article = Article::create(['title' => 'Issue preview', 'status' => 'published', 'access_type' => 'paid', 'issue_id' => $issue->id]);
        $html = view('partials.home-magazine-actions', compact('article'))->render();
        $this->assertStringContainsString('Read More', $html);
        $this->assertStringContainsString('Subscribe', $html);
        $this->actingAs($this->user());
        $html = view('partials.home-magazine-actions', compact('article'))->render();
        $this->assertStringContainsString('Read Article', $html);
        $this->assertStringContainsString('Buy this magazine', $html);
        $this->assertStringContainsString('Subscribe', $html);
        RegistrationTrial::grant(auth()->user());
        $html = view('partials.home-magazine-actions', compact('article'))->render();
        $this->assertStringNotContainsString('Subscribe', $html);
        $this->assertStringNotContainsString('Buy this magazine', $html);
        $this->assertStringContainsString('href="'.route('user.magazine.reader', $issue).'"', $html);
        $this->assertSame(1, substr_count($html, '<a '));
        $this->get(route('user.magazine.reader', $issue))->assertOk()->assertViewIs('user.magazine-embed')
            ->assertSee('<iframe', false)->assertSee('src="'.$issue->pdf_url.'"', false);
    }

    public function test_selected_trial_collects_card_before_access_and_charges_for_only_one_year(): void
    {
        config(['services.stripe.subscription_webhook_secret' => null]);
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $trial = $this->configuredTrial();
        \Illuminate\Support\Facades\Http::fake(['https://api.stripe.com/v1/checkout/sessions' => \Illuminate\Support\Facades\Http::response(['id' => 'cs_trial_test', 'url' => 'https://checkout.stripe.com/test'])]);
        $this->actingAs($user)->postJson('/api/user/trial/'.$trial->id.'/checkout', ['accept_trial_billing' => true])
            ->assertOk()->assertJsonPath('requires_payment_setup', true);
        $this->assertSame(0, Subscription::count());
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['subscription_data']['trial_period_days'] === 7 && $r['payment_method_collection'] === 'always' && $r['line_items'][0]['price_data']['unit_amount'] === 120000);
        $enrollment = \App\Models\TrialEnrollment::firstOrFail();
        $stripe = $this->trialStripe($enrollment); $stripe['cancel_at'] = null;
        $session = ['id' => 'cs_trial_test', 'mode' => 'subscription', 'status' => 'complete', 'subscription' => $stripe['id'], 'metadata' => $stripe['metadata']];
        \Illuminate\Support\Facades\Http::fake(function ($request) use ($session, $stripe) {
            if (str_contains($request->url(), 'checkout/sessions/')) return \Illuminate\Support\Facades\Http::response($session);
            return \Illuminate\Support\Facades\Http::response(array_merge($stripe, ['cancel_at' => $request->method() === 'POST' ? $request['cancel_at'] : null]));
        });
        $this->postJson('/api/user/trial/confirm', ['session_id' => 'cs_trial_test'])->assertOk()->assertJsonPath('trial.status', 'trialing');
        $this->postJson('/api/user/trial/confirm', ['session_id' => 'cs_trial_test'])->assertOk();
        $this->assertSame(1, Subscription::count());
        $this->assertTrue($user->hasActiveDigitalSubscription());
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['cancel_at'] === now()->addDays(7)->addYearNoOverflow()->timestamp);
        $this->postJson('/api/user/trial/'.$trial->id.'/checkout', ['accept_trial_billing' => true])->assertUnprocessable();
    }

    public function test_trial_cancellation_preserves_trial_access_and_annual_invoice_is_idempotent(): void
    {
        $user = $this->user(); $trial = $this->configuredTrial();
        $enrollment = \App\Models\TrialEnrollment::create(['user_id' => $user->id, 'trial_plan_id' => $trial->id,
            'annual_plan_id' => $trial->trial_conversion_plan_id, 'annual_amount' => 1200, 'currency' => 'inr']);
        $stripe = $this->trialStripe($enrollment);
        $billing = app(\App\Services\TrialBillingService::class);
        $billing->sync($stripe);
        \Illuminate\Support\Facades\Http::fake(fn ($r) => \Illuminate\Support\Facades\Http::response(array_merge($stripe, ['cancel_at_period_end' => $r->method() === 'POST'])));
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class)->actingAs($user)
            ->postJson('/api/user/trial/cancel')->assertOk()->assertJsonPath('trial.cancel_requested', true);
        $this->assertTrue($user->hasActiveDigitalSubscription());
        $this->travel(8)->days();
        $this->assertFalse($user->hasActiveDigitalSubscription());
        // A separate paid invoice extends access only after verified payment.
        $stripe['status'] = 'active';
        $stripe['cancel_at_period_end'] = true;
        \Illuminate\Support\Facades\Http::fake(fn () => \Illuminate\Support\Facades\Http::response($stripe));
        $invoice = ['id' => 'in_annual_test', 'subscription' => $stripe['id'], 'status' => 'paid', 'currency' => 'inr', 'amount_paid' => 120000];
        $billing->paidInvoice($invoice); $billing->paidInvoice($invoice);
        $this->assertSame(2, Subscription::count());
        $this->assertSame('1200.00', Subscription::latest('id')->first()->amount_paid);
        $this->assertTrue($user->hasActiveDigitalSubscription());
    }

    public function test_support_is_optional_and_snapshotted_in_mobile_payment(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $plan = $this->plan(); $plan->update(['support_amount' => 25]);
        $request = Request::create('/api/user/subscribe/'.$plan->id, 'POST', ['include_support' => 1]);
        $request->setUserResolver(fn () => $user);
        $selected = PackageSelection::forRequest($request, $plan);
        $this->assertSame(25.0, $selected->selected_support_amount);
        $selected = PackageSelection::fromMetadata($plan, ['delivery_type' => 'digital', 'support_amount' => '25'], 125);
        $this->assertEquals(100, $selected->price);
        $plan->update(['support_amount' => 99]);
        $history = [];
        $this->stripe([['id' => 'pi_support', 'status' => 'succeeded', 'amount_received' => 12500,
            'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id, 'delivery_type' => 'digital', 'support_amount' => '25']]], $history);
        $this->actingAs($user)->postJson('/api/user/subscription/confirm-payment', ['payment_intent_id' => 'pi_support'])
            ->assertCreated()->assertJsonPath('subscription.support_amount', '25.00')->assertJsonPath('subscription.amount_paid', '125.00');
    }

    public function test_web_support_is_a_separate_checkout_line_and_is_not_added_without_opt_in(): void
    {
        $user = $this->user(); $plan = $this->plan(); $plan->update(['support_amount' => 25]); $history = [];
        $this->stripe([
            ['id' => 'cs_support', 'url' => 'https://checkout.stripe.com/support'],
            ['id' => 'cs_plain', 'url' => 'https://checkout.stripe.com/plain'],
        ], $history);
        $this->actingAs($user)->post(route('user.subscribe', $plan), ['include_support' => 1])->assertRedirect('https://checkout.stripe.com/support');
        $this->post(route('user.subscribe', $plan))->assertRedirect('https://checkout.stripe.com/plain');
        parse_str((string) $history[0]['request']->getBody(), $withSupport);
        parse_str((string) $history[1]['request']->getBody(), $withoutSupport);
        $this->assertSame('10000', $withSupport['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('2500', $withSupport['line_items'][1]['price_data']['unit_amount']);
        $this->assertSame('25', $withSupport['metadata']['support_amount']);
        $this->assertCount(1, $withoutSupport['line_items']);
        $this->assertSame('0', $withoutSupport['metadata']['support_amount']);
    }

    public function test_signed_invoice_webhook_grants_once_and_failed_payment_does_not_grant_annual_access(): void
    {
        $user = $this->user(); $trial = $this->configuredTrial();
        $enrollment = \App\Models\TrialEnrollment::create(['user_id' => $user->id, 'trial_plan_id' => $trial->id,
            'annual_plan_id' => $trial->trial_conversion_plan_id, 'annual_amount' => 1200, 'currency' => 'inr']);
        $stripe = $this->trialStripe($enrollment);
        app(\App\Services\TrialBillingService::class)->sync($stripe);
        $this->travel(8)->days();
        $stripe['status'] = 'past_due';
        config(['services.stripe.subscription_webhook_secret' => 'whsec_test']);
        $invoice = ['id' => 'in_webhook', 'subscription' => $stripe['id'], 'status' => 'paid', 'amount_paid' => 120000, 'currency' => 'inr'];
        \Illuminate\Support\Facades\Http::fake(function ($request) use (&$stripe, $invoice) {
            return \Illuminate\Support\Facades\Http::response(str_contains($request->url(), '/invoices/') ? $invoice : $stripe);
        });
        $send = function ($type, $object, $valid = true) {
            $body = json_encode(['type' => $type, 'data' => ['object' => $object]]);
            $stamp = time(); $signature = hash_hmac('sha256', $stamp.'.'.$body, $valid ? 'whsec_test' : 'invalid');
            return $this->call('POST', '/stripe/subscriptions/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't='.$stamp.',v1='.$signature], $body);
        };
        $send('invoice.paid', $invoice, false)->assertStatus(400);
        $send('invoice.payment_failed', ['subscription' => $stripe['id']])->assertOk();
        $this->assertFalse($user->hasActiveDigitalSubscription());
        $stripe['status'] = 'active';
        $send('invoice.paid', $invoice)->assertOk();
        $send('invoice.paid', $invoice)->assertOk();
        $this->assertSame(2, Subscription::count());
        $this->assertTrue($user->hasActiveDigitalSubscription());
        $stripe['status'] = 'canceled';
        $send('customer.subscription.deleted', $stripe)->assertOk();
        $this->assertFalse($user->hasActiveDigitalSubscription());
    }

    public function test_scheduler_recovers_checkout_without_webhook_and_grants_only_verified_paid_invoices(): void
    {
        config(['services.stripe.subscription_webhook_secret' => null]);
        $user = $this->user(); $trial = $this->configuredTrial();
        $this->actingAs($user)->get('/user/subscriptions')->assertOk()
            ->assertSee('Start trial and set up payment')->assertDontSee('Trial payment setup is temporarily unavailable.');
        $enrollment = \App\Models\TrialEnrollment::create(['user_id' => $user->id, 'trial_plan_id' => $trial->id,
            'annual_plan_id' => $trial->trial_conversion_plan_id, 'annual_amount' => 1200, 'currency' => 'inr',
            'checkout_session_id' => 'cs_recover', 'status' => 'pending']);
        $stripe = $this->trialStripe($enrollment);
        $stripe['cancel_at'] = null;
        $invoices = [];
        \Illuminate\Support\Facades\Http::fake(function ($request) use (&$stripe, &$invoices) {
            if (str_contains($request->url(), 'checkout/sessions/')) return \Illuminate\Support\Facades\Http::response([
                'id' => 'cs_recover', 'mode' => 'subscription', 'status' => 'complete',
                'subscription' => $stripe['id'], 'metadata' => $stripe['metadata']]);
            if (str_contains($request->url(), '/invoices')) return \Illuminate\Support\Facades\Http::response(['data' => $invoices, 'has_more' => false]);
            if ($request->method() === 'POST') $stripe['cancel_at'] = $request['cancel_at'];
            return \Illuminate\Support\Facades\Http::response($stripe);
        });
        $this->artisan('trials:sync')->assertSuccessful();
        $this->assertSame('trialing', $enrollment->fresh()->status);
        $this->assertSame(1, Subscription::count());
        $this->assertSame(now()->addDays(7)->addYearNoOverflow()->timestamp, $stripe['cancel_at']);
        $this->travel(8)->days();
        $stripe['status'] = 'past_due';
        $this->artisan('trials:sync')->assertSuccessful();
        $this->assertFalse($user->hasActiveDigitalSubscription());
        $stripe['status'] = 'active';
        $invoices = [['id' => 'in_recovered', 'subscription' => $stripe['id'], 'status' => 'paid', 'currency' => 'inr', 'amount_paid' => 120000]];
        $this->artisan('trials:sync')->assertSuccessful();
        $this->artisan('trials:sync')->assertSuccessful();
        $this->assertSame(2, Subscription::count());
        $this->assertTrue($user->hasActiveDigitalSubscription());
        $stripe['status'] = 'canceled';
        $this->artisan('trials:sync')->assertSuccessful();
        $this->assertFalse($user->hasActiveDigitalSubscription());
    }

    public function test_scheduler_continues_after_provider_failure_and_reports_failure(): void
    {
        $trial = $this->configuredTrial();
        foreach (['cs_failure', 'cs_open'] as $session) {
            $user = User::create(['name' => $session, 'email' => $session.'@example.com', 'password' => 'Password123!']);
            \App\Models\TrialEnrollment::create(['user_id' => $user->id, 'trial_plan_id' => $trial->id,
                'annual_plan_id' => $trial->trial_conversion_plan_id, 'annual_amount' => 1200, 'currency' => 'inr',
                'checkout_session_id' => $session, 'status' => 'pending']);
        }
        \Illuminate\Support\Facades\Http::fake(function ($request) {
            if (str_contains($request->url(), 'cs_failure')) return \Illuminate\Support\Facades\Http::response([], 503);
            $enrollment = \App\Models\TrialEnrollment::where('checkout_session_id', 'cs_open')->firstOrFail();
            return \Illuminate\Support\Facades\Http::response(['id' => 'cs_open', 'status' => 'open',
                'metadata' => ['trial_enrollment_id' => $enrollment->id, 'user_id' => $enrollment->user_id]]);
        });
        $this->artisan('trials:sync')->assertFailed();
        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => str_contains($request->url(), 'cs_open'));
        $this->assertSame(0, Subscription::count());
    }

    public function test_trial_confirmation_checks_ownership_and_does_not_grant_without_payment_method(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $trial = $this->configuredTrial();
        $enrollment = \App\Models\TrialEnrollment::create(['user_id' => $user->id, 'trial_plan_id' => $trial->id,
            'annual_plan_id' => $trial->trial_conversion_plan_id, 'annual_amount' => 1200, 'currency' => 'inr']);
        $stripe = $this->trialStripe($enrollment); $stripe['default_payment_method'] = null;
        $session = ['mode' => 'subscription', 'status' => 'complete', 'subscription' => $stripe['id'], 'metadata' => $stripe['metadata']];
        \Illuminate\Support\Facades\Http::fake(fn ($r) => \Illuminate\Support\Facades\Http::response(str_contains($r->url(), 'checkout/sessions/') ? $session : $stripe));
        $this->actingAs($user)->postJson('/api/user/trial/confirm', ['session_id' => 'cs_test'])->assertUnprocessable();
        $this->assertSame(0, Subscription::count());
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'Password123!']);
        $this->actingAs($other)->postJson('/api/user/trial/confirm', ['session_id' => 'cs_test'])->assertForbidden();
        $this->getJson('/api/user/trial')->assertOk()->assertJsonPath('trial', null);
    }

    public function test_author_migration_preserves_byline_and_admin_saves_plain_name(): void
    {
        $user = $this->user();
        $article = Article::create(['title' => 'Legacy', 'content' => '', 'author_id' => $user->id]);
        (require database_path('migrations/2026_10_28_000003_add_author_name_to_articles.php'))->up();
        $this->assertSame('Reader', $article->fresh()->author_name);
        $categoryId = DB::table('categories')->insertGetId(['category_name' => 'News']);
        $request = Request::create('/admin/articles', 'POST', [
            'title' => 'New byline', 'author_name' => 'Guest Writer', 'status' => 'draft', 'categories' => [$categoryId],
        ]);
        (new \App\Http\Controllers\Admin\ArticleController)->store($request);
        $saved = Article::where('title', 'New byline')->firstOrFail();
        $this->assertNull($saved->author_id);
        $this->assertSame('Guest Writer', $saved->author_name);
        $data = (new \App\Http\Resources\ArticleResource($saved))->toArray(Request::create('/api/articles'));
        $this->assertSame('Guest Writer', $data['author_name']);
        $this->assertSame(['id' => null, 'name' => 'Guest Writer'], $data['author']);
    }

    public function test_current_package_is_disabled_and_repeat_purchase_is_blocked_until_expiry(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user();
        $plan = Plan::create(['name' => 'Monthly reader', 'digital_price' => 100, 'price' => 100, 'duration' => 1, 'duration_type' => 'months', 'status' => 1]);
        $subscription = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'delivery_type' => 'digital', 'status' => 'active', 'payment_status' => 1, 'start_date' => now()->subDay(), 'end_date' => now()->addMonth(), 'amount_paid' => 100]);
        $this->actingAs($user)->get(route('user.subscriptions'))->assertOk()->assertSee('Current package')->assertSee('disabled aria-disabled="true"', false);
        $this->post(route('user.subscribe', $plan), ['delivery_type' => 'digital'])->assertSessionHasErrors('plan');
        $this->postJson('/api/user/subscribe/'.$plan->id, ['delivery_type' => 'digital'])->assertUnprocessable()->assertJsonValidationErrors('plan');
        $this->postJson('/api/user/subscription/payment-intent/'.$plan->id, ['delivery_type' => 'digital'])->assertUnprocessable()->assertJsonValidationErrors('plan');
        $this->assertSame(1, Subscription::count());
        $subscription->update(['end_date' => now()->subSecond()]);
        $this->assertFalse($user->hasCurrentPackage($plan->id, 'digital'));
        $this->postJson('/api/user/subscribe/'.$plan->id, ['delivery_type' => 'digital'])->assertStatus(402);
    }

    public function test_api_login_keeps_print_purchase_intent_even_with_digital_access(): void
    {
        $user = $this->user(); $magazine = $this->issue();
        MagazinePurchase::create(['user_id' => $user->id, 'magazine_issue_id' => $magazine->id, 'delivery_type' => 'digital', 'amount_paid' => 25, 'transaction_id' => 'cs_context']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Password123!', 'magazine_id' => $magazine->id, 'delivery_type' => 'print'])
            ->assertOk()->assertJsonPath('purchase_context.delivery_type', 'print')
            ->assertJsonPath('purchase_context.show_selected_magazine', true)
            ->assertJsonPath('purchase_context.next_action', 'subscriptions');
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Password123!'])
            ->assertOk()->assertJsonPath('purchase_context.show_selected_magazine', false)
            ->assertJsonPath('purchase_context.magazine', null);
    }

    public function test_login_issues_direct_lookup_token_and_logout_revokes_it(): void
    {
        $user = $this->user();
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Password123!'])->assertOk();
        $token = $response->json('token');
        $this->assertSame(60, strlen($token));
        $this->assertDatabaseHas('api_tokens', ['user_id' => $user->id, 'token' => hash('sha256', $token)]);
        $this->withToken($token)->getJson('/api/user/subscription/status')->assertOk();
        $this->postJson('/api/user/logout')->assertOk();
        $this->assertDatabaseMissing('api_tokens', ['token' => hash('sha256', $token)]);
        $this->getJson('/api/user/subscription/status')->assertUnauthorized();
    }

    public function test_plans_identify_current_package_using_token_and_optional_matching_user_id(): void
    {
        $user = $this->user();
        $plan = $this->plan();
        Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'delivery_type' => 'digital', 'status' => 'active', 'payment_status' => 1, 'start_date' => now()->subDay(), 'end_date' => now()->addMonth(), 'amount_paid' => 100]);
        $this->getJson('/api/plans/'.$plan->id)->assertOk()->assertJsonPath('data.is_current', false);
        $this->getJson('/api/plans?user_id='.$user->id)->assertUnauthorized();
        $token = 'plan-reader-token';
        \App\Models\ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $token)]);
        $this->withToken($token)->getJson('/api/plans/'.$plan->id.'?user_id='.$user->id)->assertOk()
            ->assertJsonPath('data.is_current', true)->assertJsonPath('data.formats.0.is_current', true)
            ->assertHeader('Cache-Control', 'no-store, private');
        $response = $this->getJson('/api/plans?user_id='.$user->id)->assertOk();
        $this->assertTrue(collect($response->json('data'))->firstWhere('id', $plan->id)['is_current']);
        $this->getJson('/api/plans?user_id='.($user->id + 1))->assertForbidden();
        $user->subscriptions()->update(['end_date' => now()->subSecond()]);
        $this->getJson('/api/plans/'.$plan->id)->assertOk()->assertJsonPath('data.is_current', false);
        $this->withToken('invalid')->getJson('/api/plans')->assertUnauthorized();
    }

    public function test_magazine_paymentsheet_preserves_price_and_address_and_confirmation_is_idempotent(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue(); $issue->update(['both_price' => 50]); $history = [];
        $metadata = ['purchase_type' => 'magazine', 'user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'delivery_type' => 'both', 'print_enabled' => '1'];
        foreach ($this->address() as $key => $value) $metadata['delivery_'.$key] = $value;
        $intent = ['id' => 'pi_magazine', 'status' => 'succeeded', 'amount' => 5000, 'amount_received' => 5000, 'metadata' => $metadata];
        $this->stripe([['id' => 'cus_test'], ['secret' => 'ek_test'], ['id' => 'pi_magazine', 'client_secret' => 'pi_magazine_secret_test', 'amount' => 5000, 'currency' => 'inr'], $intent, $intent], $history);
        $this->actingAs($user)->postJson('/api/user/magazines/'.$issue->id.'/checkout', ['delivery_type' => 'both', 'price' => 1, 'delivery_address' => $this->address()])
            ->assertOk()->assertJsonPath('client_secret', 'pi_magazine_secret_test')->assertJsonPath('paymentIntent', 'pi_magazine_secret_test')
            ->assertJsonPath('amount', 5000)->assertJsonPath('ephemeralKey', 'ek_test')->assertJsonPath('customer', 'cus_test');
        $body = (string) $history[2]['request']->getBody(); parse_str($body, $params);
        $this->assertSame('5000', $params['amount']);
        $this->assertSame('Kochi', $params['metadata']['delivery_city']);
        $this->assertSame(0, MagazinePurchase::count());
        $issue->update(['both_price' => 500]);
        $this->postJson('/api/user/magazines/confirm-payment', ['payment_intent_id' => 'pi_magazine'])
            ->assertCreated()->assertJsonPath('can_read', true)->assertJsonPath('delivery_type', 'both');
        $this->postJson('/api/user/magazines/confirm-payment', ['payment_intent_id' => 'pi_magazine'])->assertOk();
        $this->assertSame(1, MagazinePurchase::count());
        $this->assertSame('50.00', MagazinePurchase::first()->amount_paid);
        $this->assertSame('Kochi', MagazinePurchase::first()->delivery_address['city']);
    }

    public function test_magazine_payment_rejects_wrong_account_kind_and_incomplete_payment(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue(); $history = [];
        $metadata = ['purchase_type' => 'magazine', 'user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'delivery_type' => 'digital'];
        $this->stripe([
            ['id' => 'pi_wrong', 'status' => 'succeeded', 'metadata' => array_merge($metadata, ['user_id' => 999])],
            ['id' => 'pi_plan', 'status' => 'succeeded', 'metadata' => array_merge($metadata, ['purchase_type' => 'plan'])],
            ['id' => 'pi_pending', 'status' => 'processing', 'metadata' => $metadata],
        ], $history);
        foreach (['pi_wrong', 'pi_plan', 'pi_pending'] as $id) {
            $this->actingAs($user)->postJson('/api/user/magazines/confirm-payment', ['payment_intent_id' => $id])->assertUnprocessable();
        }
        $this->assertSame(0, MagazinePurchase::count());
    }

    public function test_print_only_free_magazine_does_not_unlock_digital_and_is_not_duplicated(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue(); $issue->update(['print_price' => 0]);
        $body = ['delivery_type' => 'print', 'delivery_address' => $this->address()];
        $this->actingAs($user)->postJson('/api/user/magazines/'.$issue->id.'/payment-intent', $body)
            ->assertCreated()->assertJsonPath('requires_payment', false)->assertJsonPath('can_read', false);
        $this->postJson('/api/user/magazines/'.$issue->id.'/checkout', $body)->assertOk()->assertJsonPath('already_purchased', true);
        $this->assertSame(1, MagazinePurchase::count());
    }

    private function user(): User
    {
        return User::create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'Password123!']);
    }

    private function plan(): Plan
    {
        return Plan::create(['name' => 'Monthly Reader', 'duration' => 1, 'duration_type' => 'months',
            'price' => 100, 'digital_price' => 100, 'print_price' => 150, 'both_price' => 200, 'print_enabled' => true, 'status' => 1]);
    }

    private function issue(string $date = '2026-10-27'): MagazineIssue
    {
        return MagazineIssue::create(['title' => 'Magazine '.$date, 'issue_date' => $date, 'pdf_url' => 'https://example.com/reader', 'price' => 25]);
    }

    private function address(): array
    {
        return ['name' => 'Reader', 'phone' => '1234567890', 'line1' => '12 Main Street', 'city' => 'Kochi', 'state' => 'Kerala', 'postal_code' => '682001', 'country' => 'India'];
    }

    private function stripe(array $responses, array &$history): void
    {
        $handler = HandlerStack::create(new MockHandler(array_map(fn ($data) => new Response(200, ['Content-Type' => 'application/json'], json_encode($data)), $responses)));
        $handler->push(Middleware::history($history));
        $this->app->bind(Client::class, fn () => new Client(['base_uri' => 'https://api.stripe.com/v1/', 'handler' => $handler]));
    }

    public function test_web_checkout_charges_selected_format_and_keeps_it_after_admin_edits(): void
    {
        $user = $this->user(); $plan = $this->plan(); $history = [];
        $metadata = ['user_id' => $user->id, 'plan_id' => $plan->id, 'delivery_type' => 'print', 'print_enabled' => '1'];
        foreach ($this->address() as $key => $value) $metadata['delivery_'.$key] = $value;
        $this->stripe([
            ['id' => 'cs_print', 'url' => 'https://checkout.stripe.com/test'],
            ['id' => 'cs_print', 'payment_status' => 'paid', 'payment_intent' => 'pi_print', 'amount_total' => 15000, 'metadata' => $metadata],
            ['id' => 'cs_print', 'payment_status' => 'paid', 'payment_intent' => 'pi_print', 'amount_total' => 15000, 'metadata' => $metadata],
        ], $history);
        $this->actingAs($user)->post(route('user.subscribe', $plan), ['delivery_type' => 'print', 'price' => 1, 'delivery_address' => $this->address()])->assertRedirect('https://checkout.stripe.com/test');
        parse_str((string) $history[0]['request']->getBody(), $sent);
        $this->assertEquals(15000, $sent['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('print', $sent['metadata']['delivery_type']);
        $plan->update(['print_price' => null, 'both_price' => 900]);
        $this->get(route('user.subscribe.success', ['session_id' => 'cs_print']))->assertRedirect(route('user.dashboard'));
        $this->get(route('user.subscribe.success', ['session_id' => 'cs_print']))->assertRedirect(route('user.dashboard'));
        $subscription = Subscription::firstOrFail();
        $this->assertSame(1, Subscription::count());
        $this->assertSame('print', $subscription->delivery_type);
        $this->assertSame('150.00', $subscription->amount_paid);
        $this->assertSame('Kochi', $subscription->delivery_address['city']);
        $this->assertFalse($user->canReadMagazine($this->issue()));
    }

    public function test_api_payment_intent_charges_both_price_and_confirmation_restores_format(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $user->update(['stripe_customer_id' => 'cus_test']); $plan = $this->plan(); $history = [];
        $metadata = ['user_id' => $user->id, 'plan_id' => $plan->id, 'delivery_type' => 'both', 'print_enabled' => '1'];
        foreach ($this->address() as $key => $value) $metadata['delivery_'.$key] = $value;
        $this->stripe([
            ['secret' => 'eph_test'], ['id' => 'pi_both', 'client_secret' => 'secret', 'amount' => 20000],
            ['id' => 'pi_both', 'status' => 'succeeded', 'amount_received' => 20000, 'metadata' => $metadata],
        ], $history);
        $this->actingAs($user)->postJson('/api/user/subscription/payment-intent/'.$plan->id, ['delivery_type' => 'both', 'amount' => 1, 'delivery_address' => $this->address()])
            ->assertOk()->assertJsonPath('delivery_type', 'both')->assertJsonPath('amount', 20000);
        parse_str((string) $history[1]['request']->getBody(), $sent);
        $this->assertEquals(20000, $sent['amount']);
        $plan->update(['both_price' => 999]);
        $this->postJson('/api/user/subscription/confirm-payment', ['payment_intent_id' => 'pi_both'])->assertCreated()
            ->assertJsonPath('subscription.delivery_type', 'both')->assertJsonPath('subscription.amount_paid', '200.00');
        $this->assertTrue($user->canReadMagazine($this->issue()));
    }

    public function test_unsupported_formats_missing_addresses_and_repeat_trials_are_rejected(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $plan = $this->plan(); $plan->update(['print_price' => null]);
        $this->actingAs($user)->postJson('/api/user/subscription/payment-intent/'.$plan->id, ['delivery_type' => 'print'])->assertUnprocessable()->assertJsonValidationErrors('delivery_type');
        $this->postJson('/api/user/subscription/payment-intent/'.$plan->id, ['delivery_type' => 'both'])->assertUnprocessable()->assertJsonValidationErrors('delivery_address');
        $trial = RegistrationTrial::grant($user);
        $this->postJson('/api/user/subscribe/'.$trial->plan_id)->assertUnprocessable()->assertJsonValidationErrors('accept_trial_billing');
        $this->assertSame(1, Subscription::count());
    }

    public function test_trial_uses_admin_duration_and_does_not_rewrite_existing_expiry(): void
    {
        $first = RegistrationTrial::grant($this->user());
        $this->assertSame('2026-11-03 12:00:00', $first->end_date->format('Y-m-d H:i:s'));
        $first->plan->update(['duration' => 3]);
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'Password123!']);
        $second = RegistrationTrial::grant($other);
        $this->assertSame('2026-10-30 12:00:00', $second->end_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-03 12:00:00', $first->fresh()->end_date->format('Y-m-d H:i:s'));
    }

    public function test_dashboard_includes_archive_articles_and_all_magazines_with_active_subscription(): void
    {
        $user = $this->user(); RegistrationTrial::grant($user);
        $current = $this->issue(); $old = $this->issue('2026-09-01');
        $covered = Article::create(['title' => 'Covered article', 'issue_id' => $current->id, 'status' => 'published', 'access_type' => 'paid']);
        $archive = Article::create(['title' => 'Archive article', 'issue_id' => $old->id, 'status' => 'published', 'access_type' => 'paid']);
        Article::create(['title' => 'Unpurchased free article', 'status' => 'published', 'access_type' => 'free']);
        $owned = Article::create(['title' => 'My individual article', 'issue_id' => $old->id, 'status' => 'published', 'access_type' => 'paid']);
        $user->articlePurchases()->create(['article_id' => $owned->id, 'payment_status' => 1]);
        $request = Request::create('/user/dashboard'); $request->setUserResolver(fn () => $user);
        $data = (new \App\Http\Controllers\UserDashboardController)->index($request)->getData();
        $this->assertEqualsCanonicalizing([$covered->id, $archive->id, $owned->id], $data['articles']->pluck('id')->all());
        $this->assertSame([$current->id, $old->id], $data['magazines']->pluck('id')->all());
    }

    public function test_search_returns_only_published_matches_and_hides_paid_content(): void
    {
        Article::create(['title' => 'Faith today', 'summary' => 'Hope', 'content' => 'Private text', 'status' => 'published', 'access_type' => 'paid']);
        Article::create(['title' => 'Faith draft', 'status' => 'draft']);
        Article::create(['title' => 'Another article', 'status' => 'published']);
        $this->getJson('/api/articles/search?q=faith&per_page=1')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Faith today')->assertJsonPath('data.0.content', null)
            ->assertJsonPath('data.0.is_locked', true)->assertJsonPath('meta.total', 1)->assertJsonPath('meta.per_page', 1);
        $this->getJson('/api/articles/search?q=f')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/api/articles/search?q=faith&per_page=1000')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_article_editor_creates_and_reassigns_magazine_mapping(): void
    {
        (require database_path('migrations/2026_10_28_000003_add_author_name_to_articles.php'))->up();
        $user = $this->user(); $issue = $this->issue(); $other = $this->issue('2026-11-01');
        $category = DB::table('categories')->insertGetId(['category_name' => 'Faith']);
        $controller = new \App\Http\Controllers\Admin\ArticleController;
        $data = ['title' => 'Mapped article', 'author_name' => 'Guest Writer', 'categories' => [$category], 'issue_id' => $issue->id, 'access_type' => 'paid', 'status' => 'published'];
        $controller->store(Request::create('/admin/articles', 'POST', $data));
        $article = Article::firstOrFail();
        $this->assertSame($issue->id, $article->issue_id);
        $data['issue_id'] = $other->id;
        $controller->update(Request::create('/admin/articles/1', 'PUT', $data), $article);
        $this->assertSame($other->id, $article->fresh()->issue_id);
    }

    public function test_new_account_returns_to_selected_magazine_without_automatic_trial(): void
    {
        $issue = $this->issue('2026-09-01');
        $article = Article::create(['title' => 'Past issue preview', 'status' => 'published', 'issue_id' => $issue->id]);
        $buy = route('user.article.magazine.buy', $article);
        $this->get($buy)->assertRedirect(route('register'));
        $this->post('/register', ['name' => 'New reader', 'email' => 'new@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertRedirect($buy);
        $this->get($buy)->assertRedirect(route('user.subscriptions', ['magazine' => $issue->id]));
        $this->assertSame($issue->id, session('selected_magazine_id'));
    }

    public function test_api_registration_preserves_magazine_context_for_purchase_options(): void
    {
        $issue = $this->issue('2026-09-01');
        $this->postJson('/api/register', ['name' => 'New reader', 'email' => 'new@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'magazine_id' => $issue->id])
            ->assertCreated()->assertJsonPath('purchase_context.magazine_id', $issue->id)
            ->assertJsonPath('purchase_context.next_action', 'subscriptions');
        $this->assertSame(0, Subscription::count());
    }

    public function test_trial_unlocks_all_issue_dates_and_stops_at_expiry(): void
    {
        $user = $this->user(); RegistrationTrial::grant($user);
        $before = $this->issue('2026-10-26'); $during = $this->issue('2026-10-30'); $after = $this->issue('2026-11-04');
        $this->assertTrue($user->canReadMagazine($before));
        $this->assertTrue($user->canReadMagazine($during));
        $this->assertTrue($user->canReadMagazine($after));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-03 12:00:01'));
        $this->assertFalse($user->canReadMagazine($during));
    }

    public function test_package_tabs_preserve_selected_magazine_and_display_selected_price(): void
    {
        $user = $this->user(); $this->plan(); $magazine = $this->issue('2026-09-01');
        $this->actingAs($user)->get(route('user.subscriptions', ['magazine' => $magazine->id, 'type' => 'both']))
            ->assertOk()->assertSee('INR 200.00')->assertSee('INR 25.00')->assertSee('Your selected magazine')
            ->assertSee('Digital + Print')->assertSee('Does not unlock your selected magazine digitally.');
        $this->get(route('user.subscriptions', ['magazine' => $magazine->id, 'type' => 'print']))->assertOk()->assertSee('INR 150.00')
            ->assertSee($magazine->title)->assertSee('name="delivery_type" value="print"', false);
    }

    public function test_admin_can_offer_only_print_then_add_digital_and_both_prices(): void
    {
        $controller = new \App\Http\Controllers\Admin\PlanController;
        $data = ['name' => 'Flexible package', 'duration' => 1, 'duration_type' => 'months', 'print_price' => 150];
        $controller->store(Request::create('/admin/plans', 'POST', $data));
        $plan = Plan::firstOrFail();
        $this->assertSame(['print' => '150.00'], $plan->prices());
        $data['digital_price'] = 100; $data['both_price'] = 200;
        $controller->update(Request::create('/admin/plans/'.$plan->id, 'PUT', $data), $plan->id);
        $this->assertSame(['digital' => '100.00', 'print' => '150.00', 'both' => '200.00'], $plan->fresh()->prices());
    }

    public function test_api_dashboard_and_buy_options_return_library_destination(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue();
        $owned = Article::create(['title' => 'My issue article', 'status' => 'published', 'access_type' => 'paid', 'issue_id' => $issue->id]);
        $other = Article::create(['title' => 'Locked article', 'status' => 'published', 'access_type' => 'paid', 'issue_id' => $this->issue('2026-09-01')->id]);
        MagazinePurchase::create(['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'amount_paid' => 25, 'transaction_id' => 'cs_owned']);
        $this->actingAs($user)->getJson('/api/user/dashboard')->assertOk()->assertJsonCount(1, 'latest_articles')
            ->assertJsonPath('latest_articles.0.id', $owned->id)->assertJsonCount(1, 'magazines');
        $this->getJson('/api/user/articles/'.$owned->id.'/buy-options')->assertOk()->assertJsonPath('next_action', 'dashboard');
        $this->getJson('/api/user/articles/'.$other->id.'/buy-options')->assertOk()->assertJsonPath('next_action', 'subscriptions');
    }

    public function test_admin_magazine_prices_allow_print_only_without_reader(): void
    {
        $controller = new \App\Http\Controllers\Admin\MagazineIssueController;
        $controller->store(Request::create('/admin/magazine-issues', 'POST', ['title' => 'Print edition', 'print_price' => 40]));
        $issue = MagazineIssue::firstOrFail();
        $this->assertSame(['print' => '40.00'], $issue->prices());
        $controller->update(Request::create('/admin/magazine-issues/1', 'PUT', ['title' => 'Print edition', 'digital_price' => 25, 'print_price' => 40, 'both_price' => 50, 'pdf_url' => 'https://example.com/reader']), $issue);
        $this->assertSame(['digital' => '25.00', 'print' => '40.00', 'both' => '50.00'], $issue->fresh()->prices());
    }

    public function test_magazine_both_checkout_uses_server_price_and_saves_delivery_snapshot(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue();
        $issue->update(['digital_price' => 25, 'print_price' => 40, 'both_price' => 50]);
        $metadata = ['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'delivery_type' => 'both', 'print_enabled' => '1'];
        foreach ($this->address() as $key => $value) $metadata['delivery_'.$key] = $value;
        $session = ['id' => 'cs_magazine_both', 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 5000, 'metadata' => $metadata];
        \Illuminate\Support\Facades\Http::fake(['api.stripe.com/*' => \Illuminate\Support\Facades\Http::sequence()
            ->push(['id' => 'cs_magazine_both', 'url' => 'https://checkout.stripe.com/example'])->push($session)->push($session)]);
        $this->actingAs($user)->postJson(route('user.magazine.checkout', $issue), ['delivery_type' => 'both', 'price' => 1, 'delivery_address' => $this->address()])
            ->assertOk()->assertJsonPath('delivery_type', 'both')->assertJsonPath('price', '50.00');
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['line_items'][0]['price_data']['unit_amount'] === 5000 && $r['metadata']['delivery_city'] === 'Kochi');
        $issue->update(['both_price' => 500]);
        $url = '/api/user/magazines/payment/success?session_id=cs_magazine_both';
        $this->getJson($url)->assertOk()->assertJsonPath('can_read', true)->assertJsonPath('delivery_type', 'both');
        $this->getJson($url)->assertOk();
        $this->assertSame(1, MagazinePurchase::count());
        $purchase = MagazinePurchase::firstOrFail();
        $this->assertSame('50.00', $purchase->amount_paid);
        $this->assertSame('Kochi', $purchase->delivery_address['city']);
        $this->assertNull($purchase->print_acknowledged_at);
        (new \App\Http\Controllers\Admin\MagazinePurchaseController)->acknowledge($purchase);
        $this->assertNotNull($purchase->fresh()->print_acknowledged_at);
    }

    public function test_print_magazine_purchase_does_not_expose_reader_and_appears_in_order_history(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue();
        $metadata = ['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'delivery_type' => 'print', 'print_enabled' => '1'];
        foreach ($this->address() as $key => $value) $metadata['delivery_'.$key] = $value;
        \Illuminate\Support\Facades\Http::fake(['api.stripe.com/*' => \Illuminate\Support\Facades\Http::response([
            'id' => 'cs_print_magazine', 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 4000, 'metadata' => $metadata,
        ])]);
        $this->actingAs($user)->getJson('/api/user/magazines/payment/success?session_id=cs_print_magazine')
            ->assertOk()->assertJsonPath('delivery_type', 'print')->assertJsonPath('can_read', false)->assertJsonPath('reader_url', null);
        $this->getJson('/api/user/magazine-purchases')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.magazine.pdf_url', null)->assertJsonPath('data.0.delivery_address.city', 'Kochi');
        $this->getJson('/api/user/magazines')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/user/dashboard')->assertOk()->assertSee('My magazine delivery orders')->assertSee('Print');
        $this->assertFalse($user->canReadMagazine($issue));
    }

    public function test_user_can_buy_digital_after_print_for_the_same_magazine(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue();
        MagazinePurchase::create(['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'delivery_type' => 'print', 'amount_paid' => 40, 'transaction_id' => 'cs_earlier_print']);
        $this->assertFalse($user->canReadMagazine($issue));
        \Illuminate\Support\Facades\Http::fake(['api.stripe.com/*' => \Illuminate\Support\Facades\Http::response([
            'id' => 'cs_later_digital', 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 2500,
            'metadata' => ['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'delivery_type' => 'digital'],
        ])]);
        $this->actingAs($user)->getJson('/api/user/magazines/payment/success?session_id=cs_later_digital')->assertOk()->assertJsonPath('can_read', true);
        $this->assertSame(2, MagazinePurchase::count());
        $this->assertTrue($user->canReadMagazine($issue));
    }

    public function test_magazine_checkout_rejects_unavailable_formats_and_requires_print_address(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); $issue = $this->issue(); $issue->update(['digital_price' => 25, 'print_price' => 40]);
        \Illuminate\Support\Facades\Http::fake();
        $this->actingAs($user)->postJson('/api/user/magazines/'.$issue->id.'/checkout', ['delivery_type' => 'both'])->assertUnprocessable()->assertJsonValidationErrors('delivery_type');
        $this->postJson('/api/user/magazines/'.$issue->id.'/checkout', ['delivery_type' => 'print'])->assertUnprocessable()->assertJsonValidationErrors('delivery_address');
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_magazine_purchase_page_displays_all_format_prices(): void
    {
        $issue = $this->issue(); $issue->update(['digital_price' => 25, 'print_price' => 40, 'both_price' => 50]);
        $this->actingAs($this->user())->get(route('user.subscriptions', ['magazine' => $issue->id]))->assertOk()
            ->assertSee('Digital — INR 25.00')->assertSee('Print — INR 40.00')->assertSee('Digital + Print — INR 50.00');
    }

    public function test_digital_entitlement_does_not_skip_print_magazine_checkout(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user(); RegistrationTrial::grant($user); $issue = $this->issue(); $issue->update(['print_price' => 40]);
        $this->assertTrue($user->canReadMagazine($issue));
        \Illuminate\Support\Facades\Http::fake(['api.stripe.com/*' => \Illuminate\Support\Facades\Http::response(['id' => 'cs_print_upsell', 'url' => 'https://checkout.stripe.com/example'])]);
        $this->actingAs($user)->postJson(route('user.magazine.checkout', $issue), ['delivery_type' => 'print', 'delivery_address' => $this->address()])
            ->assertOk()->assertJsonPath('requires_purchase', true)->assertJsonPath('delivery_type', 'print');
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['line_items'][0]['price_data']['unit_amount'] === 4000);
    }

    public function test_article_format_selection_survives_registration_and_keeps_print_purchase_available(): void
    {
        $issue = $this->issue(); $issue->update(['digital_price' => 25, 'print_price' => 40, 'both_price' => 50]);
        $article = Article::create(['title' => 'Choose a format', 'status' => 'published', 'issue_id' => $issue->id]);
        $url = route('user.article.magazine.buy', [$article, 'type' => 'print']);
        $this->get($url)->assertRedirect(route('register'));
        $this->assertSame($url, session('url.intended'));
        $user = $this->user(); RegistrationTrial::grant($user);
        $this->actingAs($user)->get($url)->assertRedirect(route('user.subscriptions', ['magazine' => $issue->id, 'type' => 'print']));
        $this->get(route('user.subscriptions', ['magazine' => $issue->id, 'type' => 'print']))->assertOk()
            ->assertViewHas('purchaseView', 'magazines')->assertViewHas('selectedType', 'print');
    }

    public function test_packages_have_independent_format_selectors_without_main_purchase_tabs(): void
    {
        $this->plan();
        $this->actingAs($this->user())->get(route('user.subscriptions', ['view' => 'packages']))->assertOk()
            ->assertViewHas('purchaseView', 'packages')->assertSee('role="tablist"', false)
            ->assertSee('INR 100.00')->assertSee('INR 150.00')->assertSee('INR 200.00')
            ->assertDontSee('aria-label="Purchase options"', false)
            ->assertSee('id="memberships"', false)->assertSee('id="single-magazines"', false);
    }
}
