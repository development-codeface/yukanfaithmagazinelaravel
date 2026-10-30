<section id="cart-drawer" class="cart-panel" role="dialog" aria-modal="true" aria-labelledby="cart-heading" tabindex="-1" hidden>
<header class="cart-panel-header"><h1 id="cart-heading">Cart</h1><a href="{{ route('apparel.index') }}" data-cart-close aria-label="Close cart">&times;</a></header>
<div class="cart-panel-items">
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
@if(session('cart_warning'))<div class="notice error" role="alert">{{ session('cart_warning') }}</div>@endif
@forelse($items as $item)
@php
$image = $item['variant']?->images->first() ?? $item['product']->primaryImage;
$stock = $item['variant']?->stock ?? $item['product']->stock;
@endphp
<article class="cart-row">
<a class="cart-image" href="{{ route('apparel.product', $item['product']->slug) }}">@if($image)<img src="{{ asset($image->image_path) }}" alt="{{ $item['product']->name }}">@else<span>No image</span>@endif</a>
<div class="cart-row-details"><a class="cart-product-name" href="{{ route('apparel.product', $item['product']->slug) }}">{{ $item['product']->name }}</a>
@if($item['variant'])
@foreach($item['variant']->optionValues as $value)<small>{{ $value->option->name }}: {{ $value->value }}</small>@endforeach
@if($item['variant']->optionValues->isEmpty())<small>{{ implode(' / ', array_filter([$item['variant']->color_name, $item['variant']->size, $item['variant']->option_value])) }}</small>@endif
@endif
<div class="cart-row-bottom"><form class="cart-quantity" method="post" action="{{ route('apparel.cart.update', $item['key']) }}">@csrf @method('PATCH')
<button name="quantity" value="{{ max(1, $item['quantity'] - 1) }}" @disabled($item['quantity'] <= 1) aria-label="Decrease quantity">&minus;</button><span aria-label="Quantity">{{ $item['quantity'] }}</span><button name="quantity" value="{{ $item['quantity'] + 1 }}" @disabled($item['quantity'] >= min(99, $stock)) aria-label="Increase quantity">+</button></form><strong>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($item['price'] * $item['quantity'], 2) }}</strong></div>
@if($item['quantity'] > $stock)<small class="stock-error">Only {{ $stock }} available. Reduce quantity or remove item.</small>@endif
</div><form class="cart-remove" method="post" action="{{ route('apparel.cart.remove', $item['key']) }}">@csrf @method('DELETE')<button aria-label="Remove {{ $item['product']->name }}">&#128465;</button></form>
</article>
@empty<p class="empty-state">Your cart is empty. <a href="{{ route('apparel.index') }}">Browse products</a></p>@endforelse
</div>
@if($items->isNotEmpty())<footer class="cart-panel-footer"><div class="cart-summary"><span>Subtotal</span><strong>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($items->sum(fn ($item) => $item['price'] * $item['quantity']), 2) }}</strong></div><p>Continue to checkout for secure online payment. Shipping and tax charges are not included.</p><a class="checkout-button" href="{{ route('apparel.checkout') }}">Go to checkout</a><form method="post" action="{{ route('apparel.cart.clear') }}">@csrf @method('DELETE')<button type="submit" class="cancel-order">Clear cart</button></form></footer>@endif
</section>
