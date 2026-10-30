<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApparelProductResource;
use App\Models\ApparelProduct;
use App\Models\ApparelCart;
use Illuminate\Http\Request;

class ApparelProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:200', 'per_page' => 'nullable|integer|min:1|max:50', 'category_id' => 'nullable|integer', 'category_slug' => 'nullable|string|max:255']);
        $query = ApparelProduct::where('status', 'published')
            ->whereHas('category', fn ($q) => $q->where('status', true))
            ->with(['category', 'primaryImage', 'images', 'variants' => fn ($q) => $q->where('status', true), 'variants.images', 'variants.optionValues.option']);

        if ($request->filled('category_id')) {
            $query->where('apparel_category_id', $request->category_id);
        }

        if ($request->filled('category_slug')) {
            $query->whereHas('category', fn ($query) => $query->where('slug', $request->category_slug));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $sortBy = in_array($request->get('sort_by'), ['name', 'price', 'created_at', 'sort_order'], true)
            ? $request->get('sort_by')
            : 'sort_order';
        $sortOrder = $request->get('sort_order') === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) $request->get('per_page', 12), 50));

        return ApparelProductResource::collection(
            $query->orderBy($sortBy, $sortOrder)->latest()->paginate($perPage)
        );
    }

    public function show(Request $request, ApparelProduct $product)
    {
        $request->validate(['user_id' => 'nullable|integer|min:1']);
        $user = $request->user();
        if ($request->filled('user_id')) {
            abort_unless($user, 401, 'Unauthenticated.');
            abort_unless((string) $user->id === (string) $request->input('user_id'), 403, 'User ID must match the authenticated user.');
        }
        abort_unless($product->status === 'published' && $product->category?->status, 404);

        $product->load(['category', 'primaryImage', 'images', 'variants' => fn ($q) => $q->where('status', true), 'variants.images', 'variants.optionValues.option']);
        $items = $user ? (ApparelCart::where('user_id', $user->id)->first()?->items ?? []) : [];
        $cartVariants = [];
        foreach ($items as $item) {
            if ((int) ($item['product_id'] ?? 0) === $product->id && ($item['quantity'] ?? 0) > 0) {
                $cartVariants[(int) ($item['variant_id'] ?? 0)] = true;
            }
        }
        foreach ($product->variants as $variant) {
            $variant->setAttribute('is_cart', isset($cartVariants[$variant->id]));
        }

        return (new ApparelProductResource($product))->response()->header('Cache-Control', 'private, no-store');
    }

    public function byCategory(Request $request, \App\Models\ApparelCategory $category)
    {
        abort_unless($category->status, 404);
        $request->merge(['category_id' => $category->id]);

        return $this->index($request);
    }
}
