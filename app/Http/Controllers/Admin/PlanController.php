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
            'support_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'name' => 'required|string|max:255',
            'digital_price' => 'nullable|required_without_all:print_price,both_price|numeric|min:0|max:99999999.99',
            'print_price' => 'nullable|required_without_all:digital_price,both_price|numeric|min:0|max:99999999.99',
            'both_price' => 'nullable|required_without_all:digital_price,print_price|numeric|min:0|max:99999999.99',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'format_content' => 'nullable|array:digital,print,both',
            'format_content.*' => 'array:title,description',
            'format_content.*.title' => 'nullable|string|max:255',
            'format_content.*.description' => 'nullable|string|max:2000',
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
            'support_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'trial_conversion_plan_id' => 'nullable|integer|exists:plans,id',
            'name' => 'required|string|max:255',
            'digital_price' => 'nullable|required_without_all:print_price,both_price|numeric|min:0|max:99999999.99',
            'print_price' => 'nullable|required_without_all:digital_price,both_price|numeric|min:0|max:99999999.99',
            'both_price' => 'nullable|required_without_all:digital_price,print_price|numeric|min:0|max:99999999.99',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'format_content' => 'nullable|array:digital,print,both',
            'format_content.*' => 'array:title,description',
            'format_content.*.title' => 'nullable|string|max:255',
            'format_content.*.description' => 'nullable|string|max:2000',
        ]);

        $plan = Plan::findOrFail($id);
        if ($plan->isTrial()) {
            $request->validate([
                'duration' => 'required|integer|min:1|max:730',
                'duration_type' => 'required|in:days',
            ]);
            if (!empty($data['trial_conversion_plan_id'])) {
                $target = Plan::findOrFail($data['trial_conversion_plan_id']);
                abort_unless(!$target->isTrial() && (int) $target->status === 1 && (float) $target->digital_price > 0
                    && (($target->duration_type === 'years' && (int) $target->duration === 1) || ($target->duration_type === 'months' && (int) $target->duration === 12)), 422, 'Choose an active annual Digital package.');
            }
            $data['support_amount'] = 0;
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
        abort_if($plan->isTrial(), 422, 'The trial package cannot be deleted. Disable it or edit its conversion package instead.');
        $plan->delete();

        return redirect()->back()->with('success', 'Plan deleted successfully.');
    }

    private function planPayload(array $data, bool $creating = false): array
    {
        $payload = [
            'support_amount' => $data['support_amount'] ?? 0,
            ... (array_key_exists('trial_conversion_plan_id', $data) ? ['trial_conversion_plan_id' => $data['trial_conversion_plan_id']] : []),
            'digital_price' => $data['digital_price'] ?? null,
            'print_price' => $data['print_price'] ?? null,
            'both_price' => $data['both_price'] ?? null,
            'print_enabled' => isset($data['print_price']) || isset($data['both_price']),
            'format_content' => $data['format_content'] ?? null,
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
