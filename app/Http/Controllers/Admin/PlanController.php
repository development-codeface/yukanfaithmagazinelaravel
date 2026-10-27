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
            'digital_price' => 'nullable|required_without_all:print_price,both_price|numeric|min:0|max:99999999.99',
            'print_price' => 'nullable|required_without_all:digital_price,both_price|numeric|min:0|max:99999999.99',
            'both_price' => 'nullable|required_without_all:digital_price,print_price|numeric|min:0|max:99999999.99',
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
            'digital_price' => 'nullable|required_without_all:print_price,both_price|numeric|min:0|max:99999999.99',
            'print_price' => 'nullable|required_without_all:digital_price,both_price|numeric|min:0|max:99999999.99',
            'both_price' => 'nullable|required_without_all:digital_price,print_price|numeric|min:0|max:99999999.99',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($id);
        if ($plan->isTrial()) {
            $data['digital_price'] = 0;
            $data['print_price'] = $data['both_price'] = null;
        }
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
        abort_if($plan->isTrial(), 422, 'The registration trial cannot be deleted. Edit its duration instead.');
        $plan->delete();

        return redirect()->back()->with('success', 'Plan deleted successfully.');
    }

    private function planPayload(array $data, bool $creating = false): array
    {
        $payload = [
            'digital_price' => $data['digital_price'] ?? null,
            'print_price' => $data['print_price'] ?? null,
            'both_price' => $data['both_price'] ?? null,
            'print_enabled' => isset($data['print_price']) || isset($data['both_price']),
            'name' => $data['name'],
            'price' => min(array_filter([$data['digital_price'] ?? null, $data['print_price'] ?? null, $data['both_price'] ?? null], fn ($price) => $price !== null)),
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
