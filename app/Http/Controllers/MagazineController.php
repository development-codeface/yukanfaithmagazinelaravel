<?php

namespace App\Http\Controllers;

use App\Models\MagazineIssue;
use App\Models\MagazinePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MagazineController extends Controller
{
    public function buyArticle(Request $request, \App\Models\Article $article)
    {
        abort_unless($article->status === 'published' && $article->issue, 404);
        $request->validate(['type' => 'nullable|in:digital,print,both']);
        $type = $request->query('type');
        if ($type) {
            abort_unless(array_key_exists($type, $article->issue->prices()), 422);
            $request->session()->put('selected_magazine_type', $type);
        }
        $request->session()->put('selected_magazine_id', $article->issue->id);
        $request->session()->put('selected_magazine_type', $type ?: 'digital');
        if (!$request->user()) {
            $request->session()->put('url.intended', route('user.article.magazine.buy', [$article, 'type' => $type]));
            return redirect()->route('register');
        }
        if ((!$type || $type === 'digital') && $request->user()->canReadMagazine($article->issue)) {
            return redirect()->route('user.dashboard');
        }

        return redirect()->route('user.subscriptions', array_filter(['magazine' => $article->issue->id, 'type' => $type]));
    }

    public function show(Request $request, MagazineIssue $magazineIssue)
    {
        $magazineIssue->load(['articles' => fn ($q) => $q->where('status', 'published')]);

        return view('frontend.magazine', compact('magazineIssue'));
    }

    public function buy(Request $request, MagazineIssue $magazineIssue)
    {
        if ($request->user()->canReadMagazine($magazineIssue)) {
            return redirect()->route('user.dashboard');
        }

        return redirect()->route('user.subscriptions', ['magazine' => $magazineIssue->id]);
    }

    public function checkout(Request $request, MagazineIssue $magazineIssue)
    {
        $request->validate(['delivery_type' => 'nullable|in:digital,print,both']);
        $prices = $magazineIssue->prices();
        $type = $request->input('delivery_type') ?: (isset($prices['digital']) ? 'digital' : (count($prices) === 1 ? array_key_first($prices) : null));
        if (!$type || !isset($prices[$type])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['delivery_type' => 'Choose an available magazine format.']);
        }
        if ($type === 'digital' && $request->user()->canReadMagazine($magazineIssue)) {
            if ($request->expectsJson()) return response()->json(['can_read' => true, 'reader_url' => $magazineIssue->pdf_url]);
            return redirect()->route('user.magazine.reader', $magazineIssue);
        }
        abort_unless($type === 'print' || $magazineIssue->pdf_url, 422, 'The digital reader is not available.');
        $address = $type === 'digital' ? [] : \App\Support\PrintDelivery::address($request->input('delivery_address'));
        $metadata = ['user_id' => $request->user()->id, 'magazine_issue_id' => $magazineIssue->id,
            'delivery_type' => $type, 'print_enabled' => $type === 'digital' ? '0' : '1'];
        foreach ($address as $field => $value) $metadata['delivery_'.$field] = $value ?? '';
        if (! config('services.stripe.secret')) {
            if ($request->expectsJson()) return response()->json(['message' => 'Payment is currently unavailable.'], 503);
            return back()->with('error', 'Payment is currently unavailable. Please contact us.');
        }
        $purchase = MagazinePurchase::where('user_id', $request->user()->id)->where('magazine_issue_id', $magazineIssue->id)
            ->whereIn('delivery_type', $type === 'both' ? ['both'] : [$type, 'both'])->first();
        if ($purchase) {
            $canRead = $request->user()->canReadMagazine($magazineIssue);
            if ($request->expectsJson()) return response()->json(['already_purchased' => true, 'requires_purchase' => false,
                'delivery_type' => $purchase->delivery_type, 'can_read' => $canRead, 'reader_url' => $canRead ? $magazineIssue->pdf_url : null]);
            return redirect()->route('user.dashboard')->with('success', 'This magazine format is already purchased.');
        }
        try {
            $embedded = $request->boolean('embedded');
            $session = Http::asForm()->withBasicAuth(config('services.stripe.secret'), '')->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                ...($embedded ? [
                    'ui_mode' => 'embedded_page',
                    'return_url' => route('user.magazine.success').'?session_id={CHECKOUT_SESSION_ID}',
                ] : [
                    'success_url' => route('user.magazine.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('user.magazine.buy', $magazineIssue),
                ]),
                'customer_email' => $request->user()->email,
                'line_items' => [['quantity' => 1, 'price_data' => [
                    'currency' => config('services.stripe.currency', 'inr'),
                    'unit_amount' => (int) round(((float) $prices[$type]) * 100),
                    'product_data' => ['name' => $magazineIssue->title.' — '.ucfirst($type)],
                ]]],
                'metadata' => $metadata,
            ])->throw()->json();

            if ($request->expectsJson()) return response()->json([
                'magazine_id' => $magazineIssue->id,
                'delivery_type' => $type,
                'price' => $prices[$type],
                'can_read' => false,
                'requires_purchase' => true,
                'session_id' => $session['id'],
                'client_secret' => $session['client_secret'] ?? null,
                'checkout_url' => $session['url'] ?? null,
            ]);
            return redirect()->away($session['url']);
        } catch (\Illuminate\Http\Client\HttpClientException $e) {
            report($e);
            if ($request->expectsJson()) return response()->json(['message' => 'Unable to start payment. Please try again.'], 502);
            return back()->with('error', 'Unable to start payment. Please try again.');
        }
    }

    public function success(Request $request)
    {
        $request->validate(['session_id' => 'required|string|regex:/^cs_[a-zA-Z0-9_]+$/']);
        abort_unless(config('services.stripe.secret'), 503);
        try {
            $session = Http::withBasicAuth(config('services.stripe.secret'), '')->get('https://api.stripe.com/v1/checkout/sessions/'.$request->session_id)->throw()->json();
        } catch (\Illuminate\Http\Client\HttpClientException $e) {
            report($e);

            if ($request->expectsJson()) return response()->json(['message' => 'Unable to verify payment. Please try again.'], 502);
            return redirect()->route('user.dashboard')->with('error', 'Unable to verify payment. Please try your payment confirmation link again.');
        }
        abort_unless((int) ($session['metadata']['user_id'] ?? 0) === $request->user()->id, 403);
        abort_unless(($session['payment_status'] ?? '') === 'paid' && ($session['mode'] ?? '') === 'payment', 422);
        $purchase = $this->fulfill($session);
        $magazine = MagazineIssue::findOrFail($purchase->magazine_issue_id);
        $canRead = $request->user()->canReadMagazine($magazine);

        if ($request->expectsJson()) return response()->json([
            'message' => 'Magazine purchased.',
            'magazine_id' => (int) $session['metadata']['magazine_issue_id'],
            'already_purchased' => true,
            'delivery_type' => $purchase->delivery_type,
            'purchase' => $purchase,
            'can_read' => $canRead,
            'requires_purchase' => false,
            'reader_url' => $canRead ? $magazine->pdf_url : null,
        ]);
        return redirect()->route('user.dashboard')->with('success', $purchase->delivery_type === 'print'
            ? 'Printed magazine purchased. Your delivery order is listed below.' : 'Magazine purchased. You can read it from My Magazines.');
    }

    public function access(Request $request, MagazineIssue $magazineIssue)
    {
        $user = $request->user();
        $purchasedTypes = $magazineIssue->purchases()->where('user_id', $user->id)->pluck('delivery_type');
        $purchased = $purchasedTypes->intersect(['digital', 'both'])->isNotEmpty();
        $canRead = $user->canReadMagazine($magazineIssue);
        $issueDate = $magazineIssue->issue_date ?? $magazineIssue->published_at;
        $subscriptions = $user->subscriptions()->with('plan')->latest('id')->get()->map(function ($subscription) use ($issueDate) {
            $active = $subscription->status === 'active' && $subscription->payment_status
                && $subscription->start_date && $subscription->end_date
                && $subscription->start_date->lte(now()) && $subscription->end_date->gte(now());
            $period = $subscription->start_date && $subscription->end_date ? $subscription->magazinePeriod() : null;
            $inPeriod = $issueDate && $period ? $issueDate->betweenIncluded($period[0], $period[1]) : false;
            return [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $subscription->plan?->name,
                'status' => $subscription->status,
                'payment_status' => (bool) $subscription->payment_status,
                'starts_at' => $subscription->start_date?->toIso8601String(),
                'expires_at' => $subscription->end_date?->toIso8601String(),
                'is_active' => (bool) $active,
                'coverage_start' => $period ? $period[0]->toDateString() : null,
                'coverage_end' => $period ? $period[1]->toDateString() : null,
                'magazine_in_period' => (bool) $inPeriod,
                'delivery_type' => $subscription->delivery_type ?? 'digital',
                'grants_access' => (bool) ($active && $inPeriod && $subscription->includesDigital()),
            ];
        });
        $subscription = $subscriptions->firstWhere('grants_access', true)
            ?? $subscriptions->firstWhere('is_active', true) ?? $subscriptions->first();
        return response()->json([
            'magazine_id' => $magazineIssue->id,
            'issue_date' => $magazineIssue->issue_date?->toDateString(),
            'effective_issue_date' => $issueDate?->toDateString(),
            'already_purchased' => $purchased,
            'purchased_types' => $purchasedTypes,
            'prices' => $magazineIssue->prices(),
            'available_types' => array_keys($magazineIssue->prices()),
            'access_source' => $purchased ? 'single_purchase' : ($canRead ? 'subscription' : null),
            'has_active_subscription' => $subscriptions->contains('is_active', true),
            'subscription' => $subscription,
            'subscription_checks' => $subscriptions->values(),
            'can_read' => $canRead,
            'requires_purchase' => !$canRead,
            'single_purchase_available' => !empty($magazineIssue->prices()),
            'price' => $magazineIssue->price,
            'currency' => config('services.stripe.currency', 'inr'),
            'reader_url' => $canRead ? $magazineIssue->pdf_url : null,
        ]);
    }

    public function owned(Request $request)
    {
        return \App\Http\Resources\MagazineIssueResource::collection(
            MagazineIssue::accessibleTo($request->user())->latest('issue_date')->paginate(12)
        );
    }

    public function purchases(Request $request)
    {
        return \App\Http\Resources\MagazinePurchaseResource::collection(MagazinePurchase::with('magazine')->where('user_id', $request->user()->id)->latest()->paginate(12));
    }

    private function fulfill(array $session): MagazinePurchase
    {
        $issue = MagazineIssue::findOrFail($session['metadata']['magazine_issue_id'] ?? null);
        $type = $session['metadata']['delivery_type'] ?? 'digital';
        abort_unless(in_array($type, ['digital', 'print', 'both'], true), 422);
        $address = $type === 'digital' ? null : \App\Support\PrintDelivery::fromMetadata(array_merge($session['metadata'], ['print_enabled' => '1']));
        return MagazinePurchase::firstOrCreate(['user_id' => (int) $session['metadata']['user_id'], 'magazine_issue_id' => $issue->id, 'delivery_type' => $type], [
            'delivery_address' => $address,
            'amount_paid' => $session['amount_total'] / 100,
            'transaction_id' => $session['id'],
        ]);
    }

    public function webhook(Request $request)
    {
        $secret = config('services.stripe.magazine_webhook_secret');
        abort_unless($secret, 503);
        $parts = [];
        foreach (explode(',', $request->header('Stripe-Signature', '')) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) {
                $parts[$pair[0]][] = $pair[1];
            }
        }
        $timestamp = $parts['t'][0] ?? '';
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 400);
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        $valid = false;
        foreach ($parts['v1'] ?? [] as $signature) {
            $valid = $valid || hash_equals($expected, $signature);
        }
        abort_unless($valid, 400);
        $event = $request->json()->all();
        $session = $event['data']['object'] ?? [];
        if (in_array($event['type'] ?? '', ['checkout.session.completed', 'checkout.session.async_payment_succeeded'])
            && ($session['payment_status'] ?? '') === 'paid'
            && ($session['mode'] ?? '') === 'payment'
            && ! empty($session['metadata']['magazine_issue_id'])
            && ! empty($session['metadata']['user_id'])) {
            $this->fulfill($session);
        }

        return response()->json(['received' => true]);
    }
}
