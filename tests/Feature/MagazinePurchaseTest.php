<?php

namespace Tests\Feature;

use App\Models\MagazineIssue;
use App\Models\MagazinePurchase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MagazinePurchaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('magazine_issues', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('pdf_url')->nullable();
            $t->date('issue_date')->nullable();
            $t->dateTime('published_at')->nullable();
            $t->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('plan_id')->nullable();
            $t->dateTime('start_date')->nullable();
            $t->string('status');
            $t->boolean('payment_status');
            $t->decimal('amount_paid', 10, 2)->nullable();
            $t->dateTime('end_date')->nullable();
            $t->timestamps();
        });
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('duration_type')->nullable();
            $t->integer('duration')->nullable();
            $t->integer('duration_days')->nullable();
            $t->decimal('price', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });
        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('status')->default('published');
            $t->string('access_type')->default('free');
            $t->unsignedBigInteger('issue_id')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_22_000001_add_magazine_purchases.php'))->up();
        config(['services.stripe.secret' => 'sk_test_example', 'services.stripe.magazine_webhook_secret' => 'whsec_example']);
    }

    private function user(): User
    {
        return User::create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'Password123!']);
    }

    private function issue(): MagazineIssue
    {
        return MagazineIssue::create(['title' => 'October', 'issue_date' => now()->startOfMonth(), 'pdf_url' => 'https://example.com/reader', 'price' => 20]);
    }

    private function stripeSession(User $user, MagazineIssue $issue): array
    {
        return ['id' => 'cs_test_example', 'mode' => 'payment', 'payment_status' => 'paid', 'amount_total' => 2000, 'metadata' => ['user_id' => $user->id, 'magazine_issue_id' => $issue->id]];
    }

    public function test_admin_can_save_external_link_price_and_article_mapping(): void
    {
        $article = \App\Models\Article::create(['title' => 'Faith', 'status' => 'published']);
        $controller = new \App\Http\Controllers\Admin\MagazineIssueController;
        $controller->store(\Illuminate\Http\Request::create('/admin/magazine-issues', 'POST', [
            'title' => 'New issue', 'pdf_url' => 'https://example.com/issue', 'price' => 15, 'article_ids' => [$article->id],
        ]));
        $issue = MagazineIssue::firstOrFail();
        $this->assertEquals('https://example.com/issue', $issue->pdf_url);
        $this->assertEquals(15, $issue->price);
        $this->assertEquals($issue->id, $article->fresh()->issue_id);
        $controller->update(\Illuminate\Http\Request::create('/admin/magazine-issues/1', 'PUT', ['title' => 'New issue']), $issue);
        $this->assertNull($article->fresh()->issue_id);
    }

    public function test_checkout_uses_server_price_and_account_metadata(): void
    {
        $user = $this->user();
        $issue = $this->issue();
        Http::fake(['api.stripe.com/*' => Http::response(['url' => 'https://checkout.stripe.com/test'])]);
        $this->actingAs($user)->post(route('user.magazine.checkout', $issue), ['price' => 1])->assertRedirect('https://checkout.stripe.com/test');
        Http::assertSent(fn ($request) => $request['line_items'][0]['price_data']['unit_amount'] === 2000 && $request['metadata']['user_id'] === $user->id);
        $this->assertEquals(0, MagazinePurchase::count());
    }

    public function test_guests_are_sent_to_login_with_issue_saved(): void
    {
        $issue = $this->issue();
        $this->get(route('user.magazine.buy', $issue))->assertRedirect(route('login'));
        $this->assertEquals(route('user.magazine.buy', $issue), session('url.intended'));
    }

    public function test_unowned_issue_cannot_open_external_reader(): void
    {
        $this->actingAs($this->user())->get(route('user.magazine.reader', $issue = $this->issue()))->assertRedirect(route('user.magazine.buy', $issue));
    }

    public function test_subscription_skips_checkout(): void
    {
        $user = $this->user();
        $issue = $this->issue();
        \Illuminate\Support\Facades\DB::table('subscriptions')->insert(['user_id' => $user->id, 'status' => 'active', 'payment_status' => 1, 'start_date' => now(), 'end_date' => now()->addMonthNoOverflow()]);
        Http::fake();
        $this->actingAs($user)->post(route('user.magazine.checkout', $issue))->assertRedirect(route('user.magazine.reader', $issue));
        $this->get(route('user.magazine.reader', $issue))->assertRedirect('https://example.com/reader');
        Http::assertNothingSent();
    }

    public function test_verified_payment_grants_access_once_and_rejects_other_accounts(): void
    {
        $user = $this->user();
        $issue = $this->issue();
        $session = $this->stripeSession($user, $issue);
        Http::fake(['api.stripe.com/*' => Http::response($session)]);
        $url = route('user.magazine.success', ['session_id' => 'cs_test_example']);
        $this->actingAs($user)->get($url)->assertRedirect(route('user.magazine.reader', $issue));
        $this->get($url)->assertRedirect(route('user.magazine.reader', $issue));
        $this->assertEquals(1, MagazinePurchase::count());
        $this->get(route('user.magazine.reader', $issue))->assertRedirect('https://example.com/reader');
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'Password123!']);
        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_unpaid_session_does_not_grant_access(): void
    {
        $user = $this->user();
        $session = $this->stripeSession($user, $this->issue());
        $session['payment_status'] = 'unpaid';
        Http::fake(['api.stripe.com/*' => Http::response($session)]);
        $this->actingAs($user)->get(route('user.magazine.success', ['session_id' => 'cs_test_example']))->assertStatus(422);
        $this->assertEquals(0, MagazinePurchase::count());
    }

    public function test_signed_webhook_grants_access_without_return_visit(): void
    {
        $body = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => $this->stripeSession($this->user(), $this->issue())]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_example');
        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', '/stripe/magazines/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t=$timestamp,v1=$signature"], $body)->assertOk();
        }
        $this->assertEquals(1, MagazinePurchase::count());
        $this->postJson('/stripe/magazines/webhook', [])->assertStatus(400);
    }

    public function test_monthly_access_is_limited_to_signup_month_even_when_trial_extends_into_next_month(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-26 12:00:00'));
        $user = $this->user();
        $subscription = \App\Models\Subscription::create([
            'user_id' => $user->id, 'start_date' => now(), 'end_date' => now()->addMonthNoOverflow(),
            'status' => 'active', 'payment_status' => 1,
        ]);
        $october = $this->issue();
        $september = $this->issue();
        $september->update(['issue_date' => '2026-09-01']);
        $november = $this->issue();
        $november->update(['issue_date' => '2026-11-01']);
        $this->assertTrue($user->canReadMagazine($october));
        $this->assertFalse($user->canReadMagazine($september));
        $this->assertFalse($user->canReadMagazine($november));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-10'));
        $this->assertTrue($user->canReadMagazine($october));
        $this->assertFalse($user->canReadMagazine($november));
        $subscription->update(['end_date' => now()->subDay()]);
        $this->assertFalse($user->canReadMagazine($october));
    }

    public function test_longer_subscription_checks_both_boundaries_and_purchase_survives_expiry(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-26'));
        $user = $this->user();
        $subscription = \App\Models\Subscription::create([
            'user_id' => $user->id, 'start_date' => '2026-09-01', 'end_date' => '2026-11-30 23:59:59',
            'status' => 'active', 'payment_status' => 1,
        ]);
        $issue = $this->issue();
        foreach (['2026-09-01' => true, '2026-11-30' => true, '2026-08-31' => false, '2026-12-01' => false] as $date => $expected) {
            $issue->update(['issue_date' => $date]);
            $this->assertSame($expected, $user->canReadMagazine($issue), $date);
        }
        MagazinePurchase::create(['user_id' => $user->id, 'magazine_issue_id' => $issue->id, 'amount_paid' => 20, 'transaction_id' => 'cs_owned']);
        $subscription->update(['status' => 'expired']);
        $this->assertTrue($user->canReadMagazine($issue));
        $this->assertSame([$issue->id], MagazineIssue::accessibleTo($user)->pluck('id')->all());
    }

    public function test_embedded_checkout_returns_secret_and_uses_server_price(): void
    {
        $user = $this->user();
        $issue = $this->issue();
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_embedded', 'client_secret' => 'secret_test'])]);
        $this->actingAs($user)->postJson(route('user.magazine.checkout', $issue), ['embedded' => true, 'price' => 1])
            ->assertOk()->assertJsonPath('client_secret', 'secret_test');
        Http::assertSent(fn ($request) => $request['ui_mode'] === 'embedded_page'
            && $request['line_items'][0]['price_data']['unit_amount'] === 2000
            && isset($request['return_url']) && !isset($request['success_url']));
    }

    public function test_api_access_and_reader_reject_unowned_issue_even_without_paid_articles(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);
        $user = $this->user();
        $issue = $this->issue();
        $this->actingAs($user)->getJson('/api/user/magazines/'.$issue->id.'/access')
            ->assertOk()->assertJsonPath('requires_purchase', true)->assertJsonPath('reader_url', null);
        $this->getJson('/api/user/magazines/'.$issue->id.'/reader')->assertForbidden();
        $this->getJson('/api/user/magazines')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_signup_creates_complimentary_trial_and_only_unlocks_current_month(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-26'));
        $controller = new \App\Http\Controllers\Auth\RegisterController;
        $method = new \ReflectionMethod($controller, 'create');
        $user = $method->invoke($controller, ['name' => 'New Reader', 'email' => 'new@example.com', 'password' => 'Password123!']);
        $trial = $user->subscriptions()->firstOrFail();
        $this->assertTrue($trial->payment_status);
        $this->assertSame('Free Trial', $trial->plan->name);
        $this->assertSame('2026-11-26', $trial->end_date->toDateString());
        $current = $this->issue();
        $old = $this->issue();
        $old->update(['issue_date' => '2026-09-01']);
        $this->assertTrue($user->canReadMagazine($current));
        $this->assertFalse($user->canReadMagazine($old));
    }

    public function test_public_magazine_catalogue_hides_reader_links(): void
    {
        $this->issue();
        $this->getJson('/api/magazines')->assertOk()
            ->assertJsonPath('data.0.pdf_url', null)->assertJsonPath('data.0.can_read', false);
    }

    public function test_article_buy_preserves_article_through_login_and_returns_to_article_for_payment(): void
    {
        $issue = $this->issue();
        $article = \App\Models\Article::create(['title' => 'Sample preview', 'status' => 'published', 'issue_id' => $issue->id]);
        $buyUrl = route('user.article.magazine.buy', $article);
        $this->get($buyUrl)->assertRedirect(route('login'));
        $this->assertEquals($buyUrl, session('url.intended'));
        $this->actingAs($this->user())->get($buyUrl)->assertRedirect(route('frontend.article', $article));
    }

    public function test_article_buy_uses_its_linked_issue_and_redirects_entitled_user_to_reader(): void
    {
        $user = $this->user();
        $owned = $this->issue();
        $other = $this->issue();
        MagazinePurchase::create(['user_id' => $user->id, 'magazine_issue_id' => $owned->id, 'amount_paid' => 20, 'transaction_id' => 'cs_article']);
        $article = \App\Models\Article::create(['title' => 'Owned preview', 'status' => 'published', 'issue_id' => $owned->id]);
        $this->actingAs($user)->get(route('user.article.magazine.buy', $article))->assertRedirect(route('user.magazine.reader', $owned));
        $article->update(['issue_id' => $other->id]);
        $this->get(route('user.article.magazine.buy', $article))->assertRedirect(route('frontend.article', $article));
    }

    public function test_linked_paid_article_keeps_preview_available_before_login_without_unlocking_body(): void
    {
        $issue = $this->issue();
        $article = \App\Models\Article::create(['title' => 'Paid preview', 'status' => 'published', 'access_type' => 'paid', 'issue_id' => $issue->id]);
        $view = (new \App\Http\Controllers\FrontendController)->article($article);
        $this->assertSame('frontend.article', $view->name());
        $this->assertFalse($view->getData()['canReadArticle']);
        $this->assertEquals($issue->id, $view->getData()['article']->issue->id);
    }
}
