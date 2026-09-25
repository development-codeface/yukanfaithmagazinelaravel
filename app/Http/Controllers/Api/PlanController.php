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

        return PlanResource::collection($plans);
    }

    /**
     * Display one active subscription plan.
     */
    public function show(Plan $plan)
    {
        if (!$this->isActive($plan)) {
            return response()->json([
                'message' => 'Plan not found.',
            ], 404);
        }

        return new PlanResource($plan);
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
