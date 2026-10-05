<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\MagazineIssueResource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\Article;
use App\Models\ArticlePurchase;
use App\Models\MagazineIssue;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription()->with('plan')->first();

        return response()->json([
            'user' => $user,
            'subscription' => $subscription,
            'can_read_paid_articles' => (bool) $subscription,
            'purchased_article_ids' => $user->articlePurchases()
                ->where('payment_status', 1)
                ->pluck('article_id'),
            'latest_articles' => ArticleResource::collection(
                Article::where('status', 'published')
                    ->with($this->articleRelations())
                    ->latest('published_at')
                    ->take(6)
                    ->get()
            ),
            'magazines' => MagazineIssueResource::collection(
                MagazineIssue::latest('published_at')->take(6)->get()
            ),
        ]);
    }

    public function subscriptionStatus(Request $request)
    {
        $subscription = $request->user()
            ->activeSubscription()
            ->with('plan')
            ->first();

        return response()->json([
            'has_active_subscription' => (bool) $subscription,
            'can_read_paid_articles' => (bool) $subscription,
            'subscription' => $subscription,
            'plan' => $subscription?->plan,
            'expires_at' => $subscription?->end_date,
        ]);
    }

    public function subscribe(Request $request, Plan $plan)
    {
        if ((int) ($plan->status ?? 1) !== 1) {
            return response()->json(['message' => 'This plan is not active.'], 422);
        }

        if ((float) $plan->price > 0) {
            return response()->json([
                'message' => 'Payment is required for this plan.',
                'payment_endpoint' => route('api.user.subscription.payment-intent', $plan),
            ], 402);
        }

        $subscription = $this->activateSubscription($request->user()->id, $plan);

        return response()->json([
            'message' => 'Subscription activated successfully.',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    public function createSubscriptionPaymentIntent(Request $request, Plan $plan)
    {
        if ((int) ($plan->status ?? 1) !== 1) {
            return response()->json(['message' => 'This plan is not active.'], 422);
        }

        if ((float) $plan->price <= 0) {
            $subscription = $this->activateSubscription($request->user()->id, $plan);

            return response()->json([
                'message' => 'Subscription activated successfully.',
                'requires_payment' => false,
                'subscription' => $subscription->load('plan'),
            ], 201);
        }

        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        try {
            $customer = $this->createStripeCustomer($request, $stripeSecret);
            $ephemeralKey = $this->createStripeEphemeralKey($customer['id'], $stripeSecret);
            $paymentIntent = $this->createStripePaymentIntent($request, $plan, $stripeSecret, $customer['id']);
        } catch (GuzzleException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to start Stripe payment.'], 502);
        }

        return response()->json([
            'requires_payment' => true,
            'publishableKey' => config('services.stripe.key'),
            'client_secret' => $paymentIntent['client_secret'] ?? null,
            'paymentIntent' => $paymentIntent['client_secret'] ?? null,
            'paymentIntentId' => $paymentIntent['id'] ?? null,
            'ephemeralKey' => $ephemeralKey['secret'] ?? null,
            'customer' => $customer['id'] ?? null,
            'amount' => $paymentIntent['amount'] ?? $this->stripeAmount($plan),
            'currency' => $paymentIntent['currency'] ?? config('services.stripe.currency', 'inr'),
            'plan' => $plan,
        ]);
    }

    public function confirmSubscriptionPayment(Request $request)
    {
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string', 'max:255'],
        ]);

        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        try {
            $paymentIntent = $this->retrieveStripePaymentIntent($data['payment_intent_id'], $stripeSecret);
        } catch (GuzzleException $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to verify Stripe payment.'], 502);
        }

        $metadata = $paymentIntent['metadata'] ?? [];
        $plan = Plan::find($metadata['plan_id'] ?? null);

        if (!$plan || (int) ($metadata['user_id'] ?? 0) !== $request->user()->id) {
            return response()->json(['message' => 'Stripe payment does not match this account.'], 422);
        }

        if (($paymentIntent['status'] ?? null) !== 'succeeded') {
            return response()->json([
                'message' => 'Stripe payment is not complete.',
                'payment_status' => $paymentIntent['status'] ?? null,
            ], 422);
        }

        $existingSubscription = Subscription::where('transaction_id', $paymentIntent['id'])->first();

        if ($existingSubscription) {
            return response()->json([
                'message' => 'Subscription already activated.',
                'subscription' => $existingSubscription->load('plan'),
            ]);
        }

        $subscription = $this->activateSubscription(
            $request->user()->id,
            $plan,
            (float) (($paymentIntent['amount_received'] ?? $paymentIntent['amount'] ?? 0) / 100),
            $paymentIntent['id']
        );

        return response()->json([
            'message' => 'Payment successful. Subscription activated.',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    public function article(Request $request, Article $article)
    {
        if ($article->status !== 'published') {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        if (($article->access_type ?? 'free') === 'paid' && !$request->user()->canReadArticle($article)) {
            return response()->json([
                'message' => 'Please subscribe or buy this article to read it.',
                'single_article_price' => $article->single_article_price,
                'purchase_endpoint' => route('api.user.articles.purchase', $article),
            ], 403);
        }

        $article->load($this->articleRelations());

        return new ArticleResource($article);
    }

    public function magazineReader(Request $request, MagazineIssue $magazine_issue)
    {
        $magazine_issue->load(['articles' => function ($query) {
            $query->where('status', 'published')
                ->with($this->articleRelations())
                ->latest('published_at');
        }]);

        $user = $request->user();
        $hasLockedPaidArticles = $magazine_issue->articles->contains(function (Article $article) use ($user) {
            return ($article->access_type ?? 'free') === 'paid' && !$user->canReadArticle($article);
        });

        if ($hasLockedPaidArticles) {
            return response()->json(['message' => 'A subscription or individual article purchases are required to read this magazine.'], 403);
        }

        return response()->json([
            'magazine' => new MagazineIssueResource($magazine_issue),
            'reader' => [
                'type' => $magazine_issue->pdf_url ? 'pdf' : 'article',
                'pdf_url' => $this->absoluteAssetUrl($magazine_issue->pdf_url),
                'has_pdf' => !empty($magazine_issue->pdf_url),
                'has_articles' => $magazine_issue->articles->isNotEmpty(),
            ],
        ]);
    }

    public function purchaseArticle(Request $request, Article $article)
    {
        if ($article->status !== 'published') {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        if (($article->access_type ?? 'free') !== 'paid') {
            return response()->json(['message' => 'This article is free.'], 422);
        }

        $data = $request->validate([
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ]);

        $purchase = ArticlePurchase::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'article_id' => $article->id,
            ],
            [
                'amount_paid' => $data['amount_paid'] ?? $article->single_article_price,
                'payment_status' => 1,
                'transaction_id' => $data['transaction_id'] ?? null,
                'purchased_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Article purchased successfully.',
            'purchase' => $purchase,
            'article' => new ArticleResource($article->load($this->articleRelations())),
        ], 201);
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
    private function createStripePaymentIntent(Request $request, Plan $plan, string $stripeSecret, string $customerId): array
    {
        $client = new Client(['base_uri' => 'https://api.stripe.com/v1/']);

        $response = $client->post('payment_intents', [
            'auth' => [$stripeSecret, ''],
            'form_params' => [
                'amount' => $this->stripeAmount($plan),
                'currency' => config('services.stripe.currency', 'inr'),
                'customer' => $customerId,
                'automatic_payment_methods[enabled]' => 'true',
                'receipt_email' => $request->user()->email,
                'description' => $plan->name . ' subscription',
                'metadata[user_id]' => $request->user()->id,
                'metadata[plan_id]' => $plan->id,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    private function createStripeCustomer(Request $request, string $stripeSecret): array
    {
        $client = new Client(['base_uri' => 'https://api.stripe.com/v1/']);
        $user = $request->user();

        $response = $client->post('customers', [
            'auth' => [$stripeSecret, ''],
            'form_params' => [
                'email' => $user->email,
                'name' => $user->name,
                'metadata[user_id]' => $user->id,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    private function createStripeEphemeralKey(string $customerId, string $stripeSecret): array
    {
        $client = new Client(['base_uri' => 'https://api.stripe.com/v1/']);

        $response = $client->post('ephemeral_keys', [
            'auth' => [$stripeSecret, ''],
            'headers' => [
                'Stripe-Version' => '2024-06-20',
            ],
            'form_params' => [
                'customer' => $customerId,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    private function retrieveStripePaymentIntent(string $paymentIntentId, string $stripeSecret): array
    {
        $client = new Client(['base_uri' => 'https://api.stripe.com/v1/']);

        $response = $client->get('payment_intents/' . $paymentIntentId, [
            'auth' => [$stripeSecret, ''],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    private function stripeAmount(Plan $plan): int
    {
        return max(1, (int) round(((float) $plan->price) * 100));
    }

    private function absoluteAssetUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    private function articleRelations(): array
    {
        return ['category', 'categories', 'author', 'issue', 'blocks', 'galleryImages'];
    }
}
