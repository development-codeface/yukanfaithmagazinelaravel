<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApparelOrder;
use App\Models\ApparelProduct;
use App\Models\ApparelProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApparelOrderController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $orders = ApparelOrder::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('number', 'like', $search)->orWhere('email', 'like', $search)->orWhere('name', 'like', $search));
            })->latest()->paginate(20)->withQueryString();

        return view('admin.apparel_orders.index', compact('orders'));
    }

    public function show(Request $request, ApparelOrder $order)
    {
        $this->authorizeAdmin($request);

        return view('admin.apparel_orders.show', ['order' => $order->load('items')]);
    }

    public function update(Request $request, ApparelOrder $order)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled'])],
            'admin_notes' => 'nullable|string|max:5000', 'tracking_number' => 'nullable|string|max:255',
        ]);
        DB::transaction(function () use ($order, $data) {
            $order = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->api_checkout && $order->payment_intent_id && $data['status'] === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'This order has a Stripe payment. Cancel or refund the payment before cancelling the order; automatic payment cancellation is not supported.']);
            }
            if ($order->api_checkout && $order->payment_status !== 'paid' && ! in_array($data['status'], ['pending', 'cancelled'], true)) {
                throw ValidationException::withMessages(['status' => 'Verify payment before processing this order.']);
            }
            $transitions = ['pending' => ['pending', 'processing', 'cancelled'], 'processing' => ['processing', 'shipped', 'cancelled'], 'shipped' => ['shipped', 'completed'], 'completed' => ['completed'], 'cancelled' => ['cancelled']];
            if (! in_array($data['status'], $transitions[$order->status], true)) {
                throw ValidationException::withMessages(['status' => 'This status change is not allowed.']);
            }
            if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items()->orderBy('product_id')->get() as $item) {
                    // Match checkout lock order: product first, then variant.
                    $product = ApparelProduct::withTrashed()->lockForUpdate()->find($item->product_id);
                    if ($item->variant_id) {
                        ApparelProductVariant::where('apparel_product_id', $item->product_id)->whereKey($item->variant_id)->increment('stock', $item->quantity);
                    } elseif ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }
            }
            $order->update($data);
        });

        return back()->with('success', 'Order updated.');
    }
}
