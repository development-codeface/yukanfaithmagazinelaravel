@extends('apparel.layout')
@section('title', $selectedCategory?->name ?? 'All products')
@section('content')
<h1 class="collection-title">{{ $selectedCategory?->name ?? 'All products' }}</h1>
@if($selectedCategory?->description)<p class="collection-description">{{ $selectedCategory->description }}</p>@endif
<form class="sort-bar" method="get">
    <label class="sr-only" for="sort">Sort products</label>
    <select name="sort" id="sort" onchange="this.form.submit()">
        @foreach(['recommended' => 'Recommended', 'price-asc' => 'Price: Low to high', 'price-desc' => 'Price: High to low', 'newest' => 'Newest', 'name' => 'Name: A to Z'] as $value => $label)
            <option value="{{ $value }}" @selected(request('sort', 'recommended') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <noscript><button type="submit">Sort</button></noscript>
</form>
<div class="product-grid">
@forelse($products as $product)
    @php($image = $product->primaryImage ?? $product->variants->where('status', true)->flatMap->images->first())
    <a class="product-card" href="{{ route('apparel.product', $product->slug) }}">
        <div class="card-image">@if($image)<img src="{{ asset($image->image_path) }}" alt="{{ $image->alt_text ?: $product->name }}" loading="lazy">@else<span>Image coming soon</span>@endif</div>
        <h2>{{ $product->name }}</h2>
        <p>${{ number_format($product->price, 2) }} @if($product->compare_at_price > $product->price)<del>${{ number_format($product->compare_at_price, 2) }}</del>@endif</p>
    </a>
@empty
    <p class="empty-state">No products are available in this collection yet.</p>
@endforelse
</div>
@if($products->hasPages())
<nav class="pagination" aria-label="Product pages">
    @if($products->onFirstPage())<span aria-disabled="true">&lsaquo;</span>@else<a href="{{ $products->previousPageUrl() }}" aria-label="Previous page">&lsaquo;</a>@endif
    @foreach($products->getUrlRange(max(1, $products->currentPage()-2), min($products->lastPage(), $products->currentPage()+2)) as $page => $url)<a href="{{ $url }}" @if($products->currentPage() === $page) aria-current="page" @endif>{{ $page }}</a>@endforeach
    @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}" aria-label="Next page">&rsaquo;</a>@else<span aria-disabled="true">&rsaquo;</span>@endif
</nav>
@endif
@endsection
