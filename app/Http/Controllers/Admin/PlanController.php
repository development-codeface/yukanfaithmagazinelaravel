<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Facades\Schema;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::latest()->get();
        return view('admin.plans.index', compact('plans'));
    }



    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        Plan::create($this->planPayload($data, true));

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($id);
        $plan->update($this->planPayload($data));

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    // Status Change
    public function changeStatus($id)
    {
        $plan = Plan::findOrFail($id);

        $plan->status = $plan->status == 1 ? 0 : 1;
        $plan->save();

        return redirect()->back()->with('success', 'Plan status updated successfully.');
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return redirect()->back()->with('success', 'Plan deleted successfully.');
    }

    private function planPayload(array $data, bool $creating = false): array
    {
        $payload = [
            'name' => $data['name'],
            'price' => $data['price'],
            'duration' => $data['duration'],
            'duration_type' => $data['duration_type'],
            'description' => $data['description'] ?? null,
        ];

        if (Schema::hasColumn('plans', 'features')) {
            $payload['features'] = $data['features'] ?? null;
        }

        if (Schema::hasColumn('plans', 'duration_days')) {
            $payload['duration_days'] = $this->durationDays((int) $data['duration'], $data['duration_type']);
        }

        if ($creating) {
            if (Schema::hasColumn('plans', 'status')) {
                $payload['status'] = 1;
            }

            if (Schema::hasColumn('plans', 'is_active')) {
                $payload['is_active'] = 1;
            }
        }

        return $payload;
    }

    private function durationDays(int $duration, string $durationType): int
    {
        return match ($durationType) {
            'years' => $duration * 365,
            'months' => $duration * 30,
            default => $duration,
        };
    }
}
