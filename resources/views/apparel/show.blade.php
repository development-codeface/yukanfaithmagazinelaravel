@extends('apparel.layout')
@section('title', $product->name)
@section('content')
@php
    $gallery = $product->images->concat($product->variants->flatMap->images)->unique('id')->values();
    $firstVariant = $product->variants->first(fn ($v) => $v->stock > 0) ?? $product->variants->first();
    $initialImage = $firstVariant?->images->first() ?? $gallery->first();
@endphp
<div class="product-layout">
    <section class="gallery" aria-label="Product images">
        <div class="main-image">@if($initialImage)<img id="main-image" src="{{ asset($initialImage->image_path) }}" alt="{{ $initialImage->alt_text ?: $product->name }}">@else<span>Image coming soon</span>@endif</div>
        <div class="thumbnails">
            @foreach($gallery as $image)<button type="button" class="thumbnail" data-image="{{ asset($image->image_path) }}" data-alt="{{ $image->alt_text ?: $product->name }}" aria-label="View product image {{ $loop->iteration }}" aria-pressed="{{ $image->id === $initialImage?->id ? 'true' : 'false' }}"><img src="{{ asset($image->image_path) }}" alt="" loading="lazy"></button>@endforeach
        </div>
    </section>
    <section class="product-info">
        <h1>{{ $product->name }}</h1>
        <p class="product-price" id="product-price">${{ number_format($firstVariant?->price ?? $product->price, 2) }}</p>
        <form method="post" action="{{ route('apparel.cart.add', $product->slug) }}" id="purchase-form">
            @csrf
            @if($product->variants->isNotEmpty())
                <fieldset class="variant-options"><legend>Choose an option</legend>
                @foreach($product->variants as $variant)
                    @php($label = $variant->optionValues->map(fn ($v) => $v->option->name.': '.$v->value)->implode(' / ') ?: ($variant->option_value ?: trim(($variant->color_name ?? '').' '.($variant->size ?? ''))) ?: 'Standard')
                    <label class="variant-option"><input type="radio" name="variant_id" value="{{ $variant->id }}" data-price="{{ $variant->price ?? $product->price }}" data-stock="{{ $variant->stock }}" data-image="{{ $variant->images->first() ? asset($variant->images->first()->image_path) : ($gallery->first() ? asset($gallery->first()->image_path) : '') }}" @checked($variant->id === $firstVariant?->id) @disabled($variant->stock < 1) required><span>{{ $label }}{{ $variant->stock < 1 ? ' — Sold out' : '' }}</span></label>
                @endforeach
                </fieldset>
            @endif
            @if($product->size_guide)<details class="size-guide"><summary>Size guide</summary><div class="prose">{{ strip_tags($product->size_guide) }}</div></details>@endif
            <label for="quantity" class="quantity-label">Quantity</label>
            <div class="quantity-control"><button type="button" data-step="-1" aria-label="Decrease quantity">&minus;</button><input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ min(99, max(1, $firstVariant?->stock ?? $product->stock)) }}" required><button type="button" data-step="1" aria-label="Increase quantity">+</button></div>
            <button class="add-button" type="submit" @disabled(($firstVariant?->stock ?? $product->stock) < 1)>{{ ($firstVariant?->stock ?? $product->stock) < 1 ? 'Sold out' : 'Add to cart' }}</button>
        </form>
        @if($product->return_policy)<a class="return-link" href="#return-policy">&#8634; Return policy &middot; See details</a>@endif
        @if($product->description || $product->short_description)<section class="about-product"><h2>About the product</h2><div class="prose">{{ strip_tags($product->description ?: $product->short_description) }}</div></section>@endif
        @foreach(['product_details' => 'Product details', 'care_instructions' => 'Care Instructions', 'shipping_delivery' => 'Shipping & delivery', 'return_policy' => 'Return policy'] as $field => $heading)
            @if($product->$field)<details class="product-detail" id="{{ str_replace('_', '-', $field) }}" @if($loop->first) open @endif><summary>{{ $heading }}</summary><div class="prose">{{ strip_tags($product->$field) }}</div></details>@endif
        @endforeach
    </section>
</div>
@endsection
