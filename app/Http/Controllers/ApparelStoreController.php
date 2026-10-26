<?php

namespace App\Http\Controllers;

use App\Models\ApparelCategory;
use App\Models\ApparelProduct;
use Illuminate\Http\Request;

class ApparelStoreController extends Controller
{
    private function categories()
    {
        return ApparelCategory::where('status', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    private function published()
    {
        return ApparelProduct::where('status', 'published')->whereHas('category', fn ($q) => $q->where('status', true));
    }

    public function index(Request $request, ?string $category = null)
    {
        $selectedCategory = $category ? ApparelCategory::where('status', true)->where('slug', $category)->firstOrFail() : null;
        $query = $this->published()->with(['primaryImage', 'variants.images']);
        if ($selectedCategory) {
            $query->where('apparel_category_id', $selectedCategory->id);
        }
        $sort = $request->string('sort')->toString();
        match ($sort) {
            'price-asc' => $query->orderBy('price'),
            'price-desc' => $query->orderByDesc('price'),
            'newest' => $query->orderByDesc('created_at'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order'),
        };
        return view('apparel.index', [
            'categories' => $this->categories(), 'selectedCategory' => $selectedCategory,
            'products' => $query->orderBy('id')->paginate(12)->withQueryString(),
        ]);
    }

    public function show(string $product)
    {
        $product = $this->published()->where('slug', $product)->with(['images', 'variants' => fn ($q) => $q->where('status', true), 'variants.images', 'variants.optionValues.option'])->firstOrFail();
        return view('apparel.show', ['product' => $product, 'categories' => $this->categories()]);
    }

    public function addToCart(Request $request, string $product)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:99', 'variant_id' => 'nullable|integer']);
        $product = $this->published()->where('slug', $product)->firstOrFail();
        $variant = null;
        if ($product->variants()->exists()) {
            $variant = $product->variants()->where('status', true)->where('id', $data['variant_id'] ?? 0)->first();
            if (!$variant) {
                if ($request->expectsJson()) throw \Illuminate\Validation\ValidationException::withMessages(['variant_id' => 'Please select an available option.']);
                return back()->withErrors(['variant_id' => 'Please select an available option.'])->withInput();
            }
        }
        $cart = $request->session()->get('apparel_cart', []);
        $key = $product->id . ':' . ($variant?->id ?? 0);
        $quantity = ($cart[$key]['quantity'] ?? 0) + $data['quantity'];
        if ($quantity > ($variant ? $variant->stock : $product->stock)) {
            if ($request->expectsJson()) throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'The requested quantity is not available.']);
            return back()->withErrors(['quantity' => 'The requested quantity is not available.'])->withInput();
        }
        $cart[$key] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $quantity];
        $request->session()->put('apparel_cart', $cart);
        if ($request->expectsJson()) return $this->cartResponse($request);
        return back()->with('success', 'Added to your cart.')->with('cart_open', true);
    }

    public function cart(Request $request)
    {
        if ($request->expectsJson()) return $this->cartResponse($request);
        $items = $this->cartItems($request);
        $cart = $request->session()->get('apparel_cart', []);
        $available = array_intersect_key($cart, $items->all());
        if (count($available) !== count($cart)) {
            $request->session()->put('apparel_cart', $available);
            $request->session()->now('cart_warning', 'Unavailable products were removed from your cart.');
        }
        return view('apparel.cart', [
            'categories' => $this->categories(), 'items' => $items,
            'products' => $this->published()->with('primaryImage')->orderByDesc('is_featured')->orderBy('sort_order')->limit(8)->get(),
        ]);
    }

    public function cartItems(Request $request)
    {
        $items = collect($request->session()->get('apparel_cart', []))->map(function ($item, $key) {
            $product = $this->published()->with(['primaryImage', 'variants.images', 'variants.optionValues.option'])->find($item['product_id']);
            if (!$product) return null;
            $variant = $item['variant_id'] ? $product->variants->firstWhere('id', $item['variant_id']) : null;
            if ($item['variant_id'] && (!$variant || !$variant->status)) return null;
            if (!$item['variant_id'] && $product->variants->isNotEmpty()) return null;
            return compact('product', 'variant', 'key') + ['quantity' => $item['quantity'], 'price' => $variant?->price ?? $product->price];
        })->filter();
        return $items;
    }

    public function removeFromCart(Request $request, string $key)
    {
        $cart = $request->session()->get('apparel_cart', []);
        unset($cart[$key]);
        $request->session()->put('apparel_cart', $cart);
        if ($request->expectsJson()) return $this->cartResponse($request);
        return back()->with('success', 'Item removed.')->with('cart_open', true);
    }

    public function updateCart(Request $request, string $key)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:99']);
        $cart = $request->session()->get('apparel_cart', []);
        abort_unless(isset($cart[$key]), 404);
        $item = $cart[$key];
        $product = $this->published()->findOrFail($item['product_id']);
        $stock = $product->stock;
        if ($item['variant_id']) {
            $stock = $product->variants()->where('status', true)->findOrFail($item['variant_id'])->stock;
        } elseif ($product->variants()->exists()) {
            if ($request->expectsJson()) throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'Please remove this item and select an available option.']);
            return back()->withErrors(['quantity' => 'Please remove this item and select an available option.']);
        }
        if ($data['quantity'] > $stock) {
            if ($request->expectsJson()) throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'The requested quantity is not available.']);
            return back()->withErrors(['quantity' => 'The requested quantity is not available.']);
        }
        $cart[$key]['quantity'] = $data['quantity'];
        $request->session()->put('apparel_cart', $cart);
        if ($request->expectsJson()) return $this->cartResponse($request);
        return back()->with('cart_open', true);
    }

    private function cartResponse(Request $request)
    {
        $items = $this->cartItems($request);
        $cart = array_intersect_key($request->session()->get('apparel_cart', []), $items->all());
        $request->session()->put('apparel_cart', $cart);
        return response()->json([
            'html' => view('apparel.partials.cart-drawer', compact('items'))->render(),
            'count' => collect($cart)->sum('quantity'),
        ]);
    }
}
