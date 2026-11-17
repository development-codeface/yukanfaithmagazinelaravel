@if($purchasedMagazines->isNotEmpty())
<section id="magazines" class="single-magazine-section" aria-labelledby="library-title">
    <div class="pricing-section-heading"><h2 id="library-title">My purchased magazines</h2></div>
    <div class="magazine-grid">
        @foreach($purchasedMagazines as $magazine)
            <article class="single-magazine-card">
                <h3>{{ $magazine->title }}</h3>
                <p>{{ $magazine->issue_date?->format('F Y') }}</p>
                @if($magazine->purchases->whereIn('delivery_type', ['digital', 'both'])->isNotEmpty())
                    <a href="{{ route('user.magazine.reader', $magazine) }}" class="btn btn-dark">Read Magazine</a>
                @endif
                @foreach($magazine->purchases->whereIn('delivery_type', ['print', 'both']) as $purchase)
                    <p>Print delivery: {{ $purchase->print_acknowledged_at ? 'acknowledged' : 'received' }}</p>
                @endforeach
            </article>
        @endforeach
    </div>
</section>
@endif
