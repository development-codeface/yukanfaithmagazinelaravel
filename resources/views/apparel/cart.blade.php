@extends('apparel.layout')
@section('title', 'Your cart')
@section('content')
<div class="cart-backdrop">
<h2 class="collection-title">All products</h2>
<p><a href="{{ route('apparel.index') }}">Continue shopping &rarr;</a></p>
<div class="product-grid">
@foreach($products as $product)
<a class="product-card" href="{{ route('apparel.product', $product->slug) }}"><div class="card-image">@if($product->primaryImage)<img src="{{ asset($product->primaryImage->image_path) }}" alt="{{ $product->name }}">@else<span>{{ $product->name }}</span>@endif</div><h2>{{ $product->name }}</h2><p>${{ number_format($product->price, 2) }}</p></a>
@endforeach
</div>
</div>
@endsection

