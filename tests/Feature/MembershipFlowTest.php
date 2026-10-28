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
        (require database_path('migrations/2026_05_11_000000_create_api_tokens_table.php'))->up();
        config(['services.stripe.secret' => 'sk_test', 'services.stripe.key' => 'pk_test']);
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
        $this->postJson('/api/user/subscribe/'.$trial->plan_id)->assertUnprocessable()->assertJsonValidationErrors('plan');
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

    public function test_dashboard_contains_only_owned_articles_and_covered_magazines(): void
    {
        $user = $this->user(); RegistrationTrial::grant($user);
        $current = $this->issue(); $old = $this->issue('2026-09-01');
        $covered = Article::create(['title' => 'Covered article', 'issue_id' => $current->id, 'status' => 'published', 'access_type' => 'paid']);
        Article::create(['title' => 'Old locked article', 'issue_id' => $old->id, 'status' => 'published', 'access_type' => 'paid']);
        Article::create(['title' => 'Unpurchased free article', 'status' => 'published', 'access_type' => 'free']);
        $owned = Article::create(['title' => 'My individual article', 'issue_id' => $old->id, 'status' => 'published', 'access_type' => 'paid']);
        $user->articlePurchases()->create(['article_id' => $owned->id, 'payment_status' => 1]);
        $request = Request::create('/user/dashboard'); $request->setUserResolver(fn () => $user);
        $data = (new \App\Http\Controllers\UserDashboardController)->index($request)->getData();
        $this->assertEqualsCanonicalizing([$covered->id, $owned->id], $data['articles']->pluck('id')->all());
        $this->assertSame([$current->id], $data['magazines']->pluck('id')->all());
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

    public function test_new_account_returns_to_selected_magazine_after_trial_is_granted(): void
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
        $this->assertEquals(7, Subscription::firstOrFail()->start_date->diffInDays(Subscription::firstOrFail()->end_date));
    }

    public function test_trial_checks_issue_date_boundaries_and_stops_at_expiry(): void
    {
        $user = $this->user(); RegistrationTrial::grant($user);
        $before = $this->issue('2026-10-26'); $during = $this->issue('2026-10-30'); $after = $this->issue('2026-11-04');
        $this->assertFalse($user->canReadMagazine($before));
        $this->assertTrue($user->canReadMagazine($during));
        $this->assertFalse($user->canReadMagazine($after));
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
