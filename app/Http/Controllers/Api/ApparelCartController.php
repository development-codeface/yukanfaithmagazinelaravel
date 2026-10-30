<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApparelCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApparelCartController extends Controller
{
    public function handle(Request $request, ApparelCartService $service, ?string $item = null)
    {
        $data = [];
        if ($request->isMethod('post')) {
            $data = $request->validate(['product_id' => 'required|integer|min:1', 'variant_id' => 'nullable|integer|min:1', 'quantity' => 'required|integer|min:1|max:999']);
        } elseif ($request->isMethod('patch')) {
            $data = $request->validate(['quantity' => 'required|integer|min:1|max:999']);
        }

        return DB::transaction(function () use ($request, $service, $item, $data) {
            $cart = $service->cart($request->user()->id);
            $items = $cart->items;
            if ($request->isMethod('post')) {
                $key = $data['product_id'].':'.($data['variant_id'] ?? 0);
                $entry = ['product_id' => $data['product_id'], 'variant_id' => $data['variant_id'] ?? null, 'quantity' => ($items[$key]['quantity'] ?? 0) + $data['quantity']];
                $service->resolve($entry);
                $items[$key] = $entry;
            } elseif ($request->isMethod('patch')) {
                abort_unless(isset($items[$item]), 404);
                $items[$item]['quantity'] = $data['quantity'];
                $service->resolve($items[$item]);
            } elseif ($request->isMethod('delete')) {
                if ($item !== null) {
                    unset($items[$item]);
                } else {
                    $items = [];
                }
            }
            $cart->update(['items' => $items]);

            return response()->json(['data' => $service->summary($cart)]);
        });
    }
}
