<div class="product-grid">
@forelse($products as $product)
    @php($image = $product->primaryImage ?? $product->variants->where('status', true)->flatMap->images->first())
    <article class="product-card">
    <a href="{{ route('apparel.product', $product->slug) }}">
        <div class="card-image">@if($image)<img src="{{ asset($image->image_path) }}" alt="{{ $image->alt_text ?: $product->name }}" loading="lazy">@else<span>Image coming soon</span>@endif</div>
        <h2>{{ $product->name }}</h2>
    </a>
        @php($swatches = $product->variants->where('status', true)->map(fn ($variant) => ['variant' => $variant, 'color' => $variant->colorSwatch()])->filter(fn ($swatch) => $swatch['color'])->unique(fn ($swatch) => $swatch['color']['value']))
        @if($swatches->isNotEmpty())
        <div class="card-swatches" aria-label="Available colors">
            @foreach($swatches as $swatch)
                <a class="color-swatch" href="{{ route('apparel.product', $product->slug) }}?variant={{ $swatch['variant']->id }}" title="{{ $swatch['color']['value'] }}" aria-label="View {{ $swatch['color']['value'] }}" @if($swatch['color']['hex']) style="--swatch: {{ $swatch['color']['hex'] }}" @endif>@if(!$swatch['color']['hex'])<span>{{ $swatch['color']['value'] }}</span>@endif</a>
            @endforeach
        </div>
        @endif
        <p>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($product->price, 2) }} @if($product->compare_at_price > $product->price)<del>{{ \App\Support\ApparelMoney::prefix() }}{{ number_format($product->compare_at_price, 2) }}</del>@endif</p>
    </article>
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
