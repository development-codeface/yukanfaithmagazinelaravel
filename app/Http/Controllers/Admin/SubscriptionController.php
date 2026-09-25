<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['user', 'plan'])
            ->latest()
            ->get();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }
}
