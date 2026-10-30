<?php

namespace App\Services;

use App\Models\ApparelCart;
use App\Models\ApparelProduct;
use Illuminate\Validation\ValidationException;

class ApparelCartService
{
    // Caller holds a transaction when changing a cart or creating an order.
    public function cart(int $userId): ApparelCart
    {
        ApparelCart::query()->insertOrIgnore(['user_id' => $userId, 'items' => '[]', 'created_at' => now(), 'updated_at' => now()]);

        return ApparelCart::where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    public function resolve(array $item, bool $lock = false): array
    {
        $product = ApparelProduct::where('status', 'published')->whereHas('category', fn ($q) => $q->where('status', true))
            ->when($lock, fn ($q) => $q->lockForUpdate())->find($item['product_id']);
        $variant = $product && $item['variant_id'] ? $product->variants()->where('status', true)->when($lock, fn ($q) => $q->lockForUpdate())->find($item['variant_id']) : null;
        $stock = $variant ?? $product;
        if (! $product || ($item['variant_id'] && ! $variant) || (! $variant && $product->variants()->exists()) || $item['quantity'] < 1 || $item['quantity'] > $stock->stock) {
            throw ValidationException::withMessages(['cart' => 'An item is unavailable, requires a variant, or has insufficient stock. Update your cart.']);
        }

        return [$product, $variant, (int) round((float) ($variant?->price ?? $product->price) * 100)];
    }

    public function summary(ApparelCart $cart): array
    {
        $total = 0;
        $items = [];
        foreach ($cart->items as $key => $item) {
            try {
                [$product, $variant, $cents] = $this->resolve($item);
                $total += $cents * $item['quantity'];
                $items[] = $item + ['key' => $key, 'available' => true, 'name' => $product->name, 'price' => $cents / 100, 'subtotal' => $cents * $item['quantity'] / 100];
            } catch (ValidationException $e) {
                $items[] = $item + ['key' => $key, 'available' => false];
            }
        }

        return ['items' => $items, 'count' => array_sum(array_column($cart->items, 'quantity')), 'total' => $total / 100, 'currency' => strtolower(config('services.stripe.currency', 'inr')), 'can_checkout' => count($items) > 0 && ! in_array(false, array_column($items, 'available'), true)];
    }
}
