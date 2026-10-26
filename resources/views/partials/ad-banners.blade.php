@if($adBanners->isNotEmpty())
<section class="container py-4" aria-label="Advertisements">
    @foreach($adBanners as $ad)
        <a href="{{ $ad->link }}" target="_blank" rel="sponsored noopener noreferrer" class="d-block mb-3">
            <img src="{{ asset($ad->image) }}" alt="{{ $ad->title ?: 'Advertisement' }}" loading="lazy" style="display:block;width:100%;height:auto;">
        </a>
    @endforeach
</section>
@endif
