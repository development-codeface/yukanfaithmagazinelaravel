<?php

namespace App\Http\Controllers;

use App\Models\MagazineIssue;
use App\Models\MagazinePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MagazineController extends Controller
{
    public function show(Request $request, MagazineIssue $magazineIssue)
    {
        $magazineIssue->load(['articles' => fn ($q) => $q->where('status', 'published')]);

        return view('frontend.magazine', compact('magazineIssue'));
    }

    public function buy(Request $request, MagazineIssue $magazineIssue)
    {
        if ($request->user()->canReadMagazine($magazineIssue)) {
            return redirect()->route('user.magazine.reader', $magazineIssue);
        }

        return $this->show($request, $magazineIssue);
    }

    public function checkout(Request $request, MagazineIssue $magazineIssue)
    {
        if ($request->user()->canReadMagazine($magazineIssue)) {
            return redirect()->route('user.magazine.reader', $magazineIssue);
        }
        abort_unless($magazineIssue->pdf_url && $magazineIssue->price > 0, 422);
        if (! config('services.stripe.secret')) {
            return back()->with('error', 'Payment is currently unavailable. Please contact us.');
        }
        $purchase = MagazinePurchase::where('user_id', $request->user()->id)->where('magazine_issue_id', $magazineIssue->id)->first();
        if ($purchase) {
            return redirect()->route('user.magazine.reader', $magazineIssue);
        }
        try {
            $session = Http::asForm()->withBasicAuth(config('services.stripe.secret'), '')->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'success_url' => route('user.magazine.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('user.magazine.buy', $magazineIssue),
                'customer_email' => $request->user()->email,
                'line_items' => [['quantity' => 1, 'price_data' => [
                    'currency' => config('services.stripe.currency', 'inr'),
                    'unit_amount' => (int) round($magazineIssue->price * 100),
                    'product_data' => ['name' => $magazineIssue->title],
                ]]],
                'metadata' => ['user_id' => $request->user()->id, 'magazine_issue_id' => $magazineIssue->id],
            ])->throw()->json();

            return redirect()->away($session['url']);
        } catch (\Illuminate\Http\Client\HttpClientException $e) {
            report($e);

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

            return redirect()->route('user.dashboard')->with('error', 'Unable to verify payment. Please try your payment confirmation link again.');
        }
        abort_unless((int) ($session['metadata']['user_id'] ?? 0) === $request->user()->id, 403);
        abort_unless(($session['payment_status'] ?? '') === 'paid' && ($session['mode'] ?? '') === 'payment', 422);
        $this->fulfill($session);

        return redirect()->route('user.dashboard')->with('success', 'Magazine purchased. Find it in My Magazines.');
    }

    private function fulfill(array $session): void
    {
        $issue = MagazineIssue::findOrFail($session['metadata']['magazine_issue_id'] ?? null);
        MagazinePurchase::firstOrCreate(['user_id' => (int) $session['metadata']['user_id'], 'magazine_issue_id' => $issue->id], [
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
