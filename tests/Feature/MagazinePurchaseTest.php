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
            $t->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('status');
            $t->boolean('payment_status');
            $t->dateTime('end_date')->nullable();
        });
        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('status')->default('published');
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
        return MagazineIssue::create(['title' => 'October', 'pdf_url' => 'https://example.com/reader', 'price' => 20]);
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
        \Illuminate\Support\Facades\DB::table('subscriptions')->insert(['user_id' => $user->id, 'status' => 'active', 'payment_status' => 1, 'end_date' => now()->addDay()]);
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
        $this->actingAs($user)->get($url)->assertRedirect(route('user.dashboard'));
        $this->get($url)->assertRedirect(route('user.dashboard'));
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
}
