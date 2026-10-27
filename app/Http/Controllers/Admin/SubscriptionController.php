<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
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

    public function index()
    {
        $subscriptions = Subscription::with(['user', 'plan'])
            ->latest()
            ->get();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }
}
