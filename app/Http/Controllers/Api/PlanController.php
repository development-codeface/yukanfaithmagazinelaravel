<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display active subscription plans for mobile/web clients.
     */
    public function index(Request $request)
    {
        $current = $this->currentPackages($request);
        $plans = Plan::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->get('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get()
            ->filter(fn (Plan $plan) => $this->isActive($plan))
            ->values();

        foreach ($plans as $plan) {
            $plan->setAttribute('current_formats', $current->get($plan->id, collect())->pluck('delivery_type')->all());
        }
        return PlanResource::collection($plans)->response()->header('Cache-Control', 'private, no-store');
    }

    /**
     * Display one active subscription plan.
     */
    public function show(Request $request, Plan $plan)
    {
        $current = $this->currentPackages($request);
        if (!$this->isActive($plan)) {
            return response()->json([
                'message' => 'Plan not found.',
            ], 404);
        }

        $plan->setAttribute('current_formats', $current->get($plan->id, collect())->pluck('delivery_type')->all());
        return (new PlanResource($plan))->response()->header('Cache-Control', 'private, no-store');
    }

    private function currentPackages(Request $request)
    {
        $request->validate(['user_id' => 'nullable|integer|min:1']);
        if ($request->filled('user_id')) {
            abort_unless($request->user(), 401, 'Unauthenticated.');
            abort_unless((string) $request->user()->id === (string) $request->input('user_id'), 403, 'User ID must match the authenticated user.');
        }
        return $request->user()
            ? $request->user()->subscriptions()->where('status', 'active')->where('payment_status', 1)
                ->where('start_date', '<=', now())
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->get(['plan_id', 'delivery_type'])->groupBy('plan_id')
            : collect();
    }

    private function isActive(Plan $plan): bool
    {
        if (array_key_exists('status', $plan->getAttributes())) {
            return (int) $plan->status === 1;
        }

        if (array_key_exists('is_active', $plan->getAttributes())) {
            return (bool) $plan->is_active;
        }

        return true;
    }
}
