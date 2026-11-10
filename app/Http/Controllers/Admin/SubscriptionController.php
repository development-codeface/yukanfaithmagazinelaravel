<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function acknowledgePrint(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->print_enabled && $subscription->payment_status, 404);
        if (!$subscription->print_acknowledged_at) {
            $subscription->update(['print_acknowledged_at' => now()]);
        }
        return back()->with('success', 'Print delivery notification acknowledged.');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'status' => 'nullable|in:active,expired,scheduled,inactive',
            'payment_status' => 'nullable|in:0,1',
            'delivery_type' => 'nullable|in:digital,print,both',
            'support' => 'nullable|in:paid,none',
            'review' => 'nullable|in:pending,reviewed',
            'date_field' => 'nullable|in:created_at,start_date,end_date',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d'.($request->filled('date_from') ? '|after_or_equal:date_from' : ''),
            'per_page' => 'nullable|integer|in:15,25,50,100',
            'page' => 'nullable|integer|min:1',
        ]);
        $query = Subscription::with(['user', 'plan']);
        if ($search = trim($filters['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($user) => $user->where(fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
                    ->orWhere('transaction_id', 'like', '%'.$search.'%');
                if (ctype_digit($search)) $q->orWhere('id', $search);
            });
        }
        foreach (['plan_id', 'payment_status', 'delivery_type'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') $query->where($field, $filters[$field]);
        }
        $now = now();
        switch ($filters['status'] ?? '') {
            case 'active':
                $query->whereIn('status', ['active', '1'])->where('payment_status', 1)->where('start_date', '<=', $now)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $now));
                break;
            case 'expired': $query->where('end_date', '<', $now); break;
            case 'scheduled': $query->whereIn('status', ['active', '1'])->where('payment_status', 1)->where('start_date', '>', $now); break;
            case 'inactive': $query->where(fn ($q) => $q->whereNotIn('status', ['active', '1'])->orWhereNull('status')); break;
        }
        if (($filters['support'] ?? '') === 'paid') $query->where('payment_status', 1)->where('support_amount', '>', 0);
        if (($filters['support'] ?? '') === 'none') $query->where(fn ($q) => $q->whereNull('support_amount')->orWhere('support_amount', '<=', 0));
        if (!empty($filters['review'])) {
            $query->where('print_enabled', true)->where('payment_status', 1);
            ($filters['review'] === 'reviewed') ? $query->whereNotNull('print_acknowledged_at') : $query->whereNull('print_acknowledged_at');
        }
        $dateField = $filters['date_field'] ?? 'created_at';
        if (!empty($filters['date_from'])) $query->where($dateField, '>=', $filters['date_from'].' 00:00:00');
        if (!empty($filters['date_to'])) $query->where($dateField, '<', \Carbon\Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        $subscriptions = $query->latest('id')->paginate($filters['per_page'] ?? 25)->withQueryString();
        $plans = Plan::orderBy('name')->get(['id', 'name']);

        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'filters'));
    }
}
