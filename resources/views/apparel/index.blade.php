@extends('apparel.layout')
@section('title', $selectedCategory?->name ?? 'All products')
@section('content')
@include('apparel.banners')
<h1 class="collection-title">{{ $selectedCategory?->name ?? 'All products' }}</h1>
@if($selectedCategory?->description)<p class="collection-description">{{ $selectedCategory->description }}</p>@endif
<form class="sort-bar" id="product-filters" method="get">
    <label class="sr-only" for="product-search">Search apparel</label>
    <input type="search" id="product-search" name="search" value="{{ request('search') }}" placeholder="Search apparel" maxlength="200">
    <label class="sr-only" for="sort">Sort products</label>
    <select name="sort" id="sort">
        @foreach(['recommended' => 'Recommended', 'price-asc' => 'Price: Low to high', 'price-desc' => 'Price: High to low', 'newest' => 'Newest', 'name' => 'Name: A to Z'] as $value => $label)
            <option value="{{ $value }}" @selected(request('sort', 'recommended') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <noscript><button type="submit">Apply filters</button></noscript>
</form>
<p id="product-search-status" role="status" aria-live="polite"></p>
<div id="product-results">
    @include('apparel.product-results')
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/apparel-catalogue.js') }}" defer></script>
@endpush
