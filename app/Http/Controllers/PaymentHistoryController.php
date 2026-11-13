<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'user_id' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);
        $userId = $request->user()->id;
        abort_if(isset($input['user_id']) && (int) $input['user_id'] !== (int) $userId, 403, 'You can only view your own payment history.');
        // Older subscription and magazine records do not store currency.
        $currency = strtoupper(config('services.stripe.currency', 'usd'));

        $subscriptions = DB::table('subscriptions as s')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.user_id', $userId)->where('s.payment_status', 1)->where('s.amount_paid', '>', 0)
            ->selectRaw("s.id as source_id, 'subscription' as type, COALESCE(p.name, 'Subscription') as description, s.amount_paid as amount, COALESCE(s.currency, ?) as currency, s.transaction_id, s.created_at as recorded_at, s.support_amount", [$currency]);
        $magazines = DB::table('magazine_purchases as m')
            ->leftJoin('magazine_issues as i', 'i.id', '=', 'm.magazine_issue_id')
            ->where('m.user_id', $userId)->where('m.amount_paid', '>', 0)
            ->selectRaw("m.id as source_id, 'magazine' as type, COALESCE(i.title, 'Magazine') as description, m.amount_paid as amount, ? as currency, m.transaction_id, m.created_at as recorded_at, 0 as support_amount", [$currency]);
        $articles = DB::table('article_purchases as a')
            ->leftJoin('articles as r', 'r.id', '=', 'a.article_id')
            ->where('a.user_id', $userId)->where('a.payment_status', 1)->where('a.amount_paid', '>', 0)
            ->selectRaw("a.id as source_id, 'article' as type, COALESCE(r.title, 'Article') as description, a.amount_paid as amount, ? as currency, a.transaction_id, COALESCE(a.purchased_at, a.created_at) as recorded_at, 0 as support_amount", [$currency]);
        $apparel = DB::table('apparel_orders as o')
            ->where('o.user_id', $userId)->where('o.payment_status', 'paid')->where('o.total', '>', 0)
            ->selectRaw("o.id as source_id, 'apparel' as type, o.number as description, o.total as amount, o.currency, o.payment_intent_id as transaction_id, COALESCE(o.paid_at, o.created_at) as recorded_at, 0 as support_amount");
        $upgrades = DB::table('subscription_upgrades as u')->leftJoin('plans as p', 'p.id', '=', 'u.plan_id')
            ->where('u.user_id', $userId)->where('u.status', 'paid')
            ->selectRaw("u.id as source_id, 'subscription_upgrade' as type, COALESCE(p.name, 'Package upgrade') as description, u.amount_minor / 100.0 as amount, u.currency, u.payment_intent_id as transaction_id, u.paid_at as recorded_at, 0 as support_amount");

        $payments = DB::query()->fromSub($subscriptions->unionAll($magazines)->unionAll($articles)->unionAll($apparel)->unionAll($upgrades), 'payments')
            ->orderByDesc('recorded_at')->orderBy('type')->orderByDesc('source_id')
            ->paginate($input['per_page'] ?? 15)->withQueryString();
        $payments->through(function ($payment) {
            $payment->source_id = (int) $payment->source_id;
            $payment->amount = number_format((float) $payment->amount, 2, '.', '');
            $payment->support_amount = number_format((float) $payment->support_amount, 2, '.', '');
            $payment->package_amount = $payment->type === 'subscription' ? number_format((float) $payment->amount - (float) $payment->support_amount, 2, '.', '') : null;
            $payment->currency = strtoupper($payment->currency);
            $payment->status = 'paid';
            return $payment;
        });

        return $request->is('api/*')
            ? response()->json($payments)
            : view('user.payment-history', compact('payments'));
    }
}
