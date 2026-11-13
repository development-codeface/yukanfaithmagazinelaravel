@if($apparelBanners->isNotEmpty())
<section class="apparel-banners" aria-label="Apparel highlights">
    @foreach($apparelBanners as $banner)
        <div class="apparel-banner">
            @if($banner->link)<a href="{{ $banner->link }}">@endif
                <img src="{{ asset($banner->image) }}" alt="{{ $banner->title ?: 'YuKanFaith Apparel' }}" @if(!$loop->first) loading="lazy" @endif>
            @if($banner->link)</a>@endif
        </div>
    @endforeach
</section>
@endif
