<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\Article;
use App\Models\ArticlePurchase;
use App\Models\MagazineIssue;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription()->with('plan')->first();
        $articleSearch = trim((string) $request->query('search', ''));

        $articles = Article::where('status', 'published')
            ->with('category')
            ->when($articleSearch !== '', function ($query) use ($articleSearch) {
                $query->where(function ($query) use ($articleSearch) {
                    $query->where('title', 'like', "%{$articleSearch}%")
                        ->orWhere('summary', 'like', "%{$articleSearch}%")
                        ->orWhere('content', 'like', "%{$articleSearch}%")
                        ->orWhereHas('category', function ($query) use ($articleSearch) {
                            $query->where('category_name', 'like', "%{$articleSearch}%");
                        })
                        ->orWhereHas('categories', function ($query) use ($articleSearch) {
                            $query->where('category_name', 'like', "%{$articleSearch}%");
                        });
                });
            })
            ->orderByIssueTitleDate()
            ->paginate(12)
            ->withQueryString();
        $magazines = MagazineIssue::latest('published_at')->take(12)->get();

        return view('user.dashboard', compact('subscription', 'articles', 'magazines', 'articleSearch'));
    }

    public function subscriptions(Request $request)
    {
        $subscription = $request->user()->activeSubscription()->with('plan')->first();
        $plans = Plan::where('status', 1)->latest()->get();

        return view('user.subscriptions', compact('subscription', 'plans'));
    }

    public function subscribe(Request $request, Plan $plan)
    {
        if ((int) ($plan->status ?? 1) !== 1) {
            return back()->with('error', 'This plan is not active.');
        }

        if ((float) $plan->price <= 0) {
            $this->activateSubscription($request->user()->id, $plan);

            return redirect()->route('user.subscriptions')
                ->with('success', 'Subscription activated successfully.');
        }

        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            return back()->with('error', 'Stripe is not configured yet. Please add STRIPE_SECRET in .env.');
        }

        try {
            $session = $this->createStripeCheckoutSession($request, $plan, $stripeSecret);
        } catch (GuzzleException $exception) {
            report($exception);

            return back()->with('error', 'Unable to start Stripe checkout. Please try again.');
        }

        return redirect()->away($session['url']);
    }

    public function stripeSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('user.subscriptions')
                ->with('error', 'Missing Stripe checkout session.');
        }

        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            return redirect()->route('user.subscriptions')
                ->with('error', 'Stripe is not configured yet.');
        }

        try {
            $session = $this->retrieveStripeCheckoutSession($sessionId, $stripeSecret);
        } catch (GuzzleException $exception) {
            report($exception);

            return redirect()->route('user.subscriptions')
                ->with('error', 'Unable to verify Stripe payment. Please contact support.');
        }

        $metadata = $session['metadata'] ?? [];
        $plan = Plan::find($metadata['plan_id'] ?? null);

        if (!$plan || (int) ($metadata['user_id'] ?? 0) !== $request->user()->id) {
            return redirect()->route('user.subscriptions')
                ->with('error', 'Stripe payment does not match this account.');
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return redirect()->route('user.subscriptions')
                ->with('error', 'Stripe payment was not completed.');
        }

        $transactionId = $session['payment_intent'] ?? $session['id'];

        if (!empty($transactionId)) {
            $existingSubscription = Subscription::where('transaction_id', $transactionId)->first();

            if ($existingSubscription) {
                return redirect()->route('user.subscriptions')
                    ->with('success', 'Subscription already activated.');
            }
        }

        $subscription = $this->activateSubscription(
            $request->user()->id,
            $plan,
            (float) (($session['amount_total'] ?? 0) / 100),
            $transactionId
        );

        return redirect()->route('user.subscriptions')
            ->with('success', 'Payment successful. Subscription valid until ' . $subscription->end_date->format('d M Y') . '.');
    }

    public function stripeCancel()
    {
        return redirect()->route('user.subscriptions')
            ->with('error', 'Stripe checkout was cancelled.');
    }

    public function article(Request $request, Article $article)
    {
        abort_unless($article->status === 'published', 404);

        if (($article->access_type ?? 'free') === 'paid' && !$request->user()->canReadArticle($article)) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Please subscribe or buy this article to read it.');
        }

        $article->load('blocks', 'galleryImages');

        return view('user.article-reader', compact('article'));
    }

    public function magazineReader(Request $request, MagazineIssue $magazineIssue)
    {
        $magazineIssue->load(['articles' => function ($query) {
            $query->where('status', 'published')
                ->with('blocks')
                ->latest('published_at');
        }]);

        $user = $request->user();
        $hasLockedPaidArticles = $magazineIssue->articles->contains(function (Article $article) use ($user) {
            return ($article->access_type ?? 'free') === 'paid' && !$user->canReadArticle($article);
        });

        if ($hasLockedPaidArticles) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Please subscribe or buy the paid articles in this magazine.');
        }

        return view('user.magazine-reader', compact('magazineIssue'));
    }

    public function purchaseArticle(Request $request, Article $article)
    {
        abort_unless($article->status === 'published', 404);

        if (($article->access_type ?? 'free') !== 'paid') {
            return redirect()->route('user.article', $article)
                ->with('error', 'This article is free.');
        }

        ArticlePurchase::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'article_id' => $article->id,
            ],
            [
                'amount_paid' => $article->single_article_price,
                'payment_status' => 1,
                'transaction_id' => $request->input('transaction_id'),
                'purchased_at' => now(),
            ]
        );

        return redirect()->route('user.article', $article)
            ->with('success', 'Article purchased successfully.');
    }

    private function calculateEndDate(Plan $plan, ?Carbon $startsFrom = null): Carbon
    {
        $duration = (int) ($plan->duration ?? 1);
        $startsFrom ??= now();

        return match ($plan->duration_type) {
            'years' => $startsFrom->copy()->addYears($duration),
            'months' => $startsFrom->copy()->addMonths($duration),
            default => $startsFrom->copy()->addDays($duration),
        };
    }

    private function activateSubscription(int $userId, Plan $plan, ?float $amountPaid = null, ?string $transactionId = null): Subscription
    {
        $activeSubscription = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->where('payment_status', 1)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest('end_date')
            ->latest('id')
            ->first();

        if ($activeSubscription && is_null($activeSubscription->end_date)) {
            return $activeSubscription;
        }

        $startsFrom = $activeSubscription?->end_date
            ? Carbon::parse($activeSubscription->end_date)
            : now();

        return Subscription::create([
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'start_date' => $startsFrom,
            'end_date' => $this->calculateEndDate($plan, $startsFrom),
            'status' => 'active',
            'payment_status' => 1,
            'amount_paid' => $amountPaid ?? (float) $plan->price,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * @throws GuzzleException
     */
    private function createStripeCheckoutSession(Request $request, Plan $plan, string $stripeSecret): array
    {
        $client = new Client(['base_uri' => 'https://api.stripe.com/v1/']);

        $response = $client->post('checkout/sessions', [
            'auth' => [$stripeSecret, ''],
            'form_params' => [
                'mode' => 'payment',
                'success_url' => route('user.subscribe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('user.subscribe.cancel'),
                'customer_email' => $request->user()->email,
                'client_reference_id' => $request->user()->id,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => config('services.stripe.currency', 'inr'),
                'line_items[0][price_data][unit_amount]' => $this->stripeAmount($plan),
                'line_items[0][price_data][product_data][name]' => $plan->name,
                'line_items[0][price_data][product_data][description]' => $plan->description ?: 'Yukan Faith Magazine subscription',
                'metadata[user_id]' => $request->user()->id,
                'metadata[plan_id]' => $plan->id,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    private function retrieveStripeCheckoutSession(string $sessionId, string $stripeSecret): array
    {
        $client = new Client(['base_uri' => 'https://api.stripe.com/v1/']);

        $response = $client->get('checkout/sessions/' . $sessionId, [
            'auth' => [$stripeSecret, ''],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    private function stripeAmount(Plan $plan): int
    {
        return max(1, (int) round(((float) $plan->price) * 100));
    }
}
