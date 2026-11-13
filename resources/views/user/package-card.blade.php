@php
    $prices = $plan->prices();
    $initialType = array_key_exists($selectedType, $prices) ? $selectedType : array_key_first($prices);
    $features = collect(preg_split('/\r\n|\r|\n/', $plan->features ?? ''))->map(fn ($feature) => trim($feature))->filter();
@endphp
<article class="package-card" id="package-{{ $plan->id }}">
    <div class="package-card-header"><h3>{{ $plan->name }}</h3></div>
    <p class="package-description">{{ $plan->description }}</p>
    <div class="package-format-tabs" role="tablist" aria-label="{{ $plan->name }} format">
        @foreach($prices as $type => $price)
            <button type="button" role="tab" id="format-{{ $plan->id }}-{{ $type }}" aria-controls="price-{{ $plan->id }}-{{ $type }}" aria-selected="{{ $type === $initialType ? 'true' : 'false' }}" tabindex="{{ $type === $initialType ? '0' : '-1' }}">
                <span>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type] }}</span>
                <strong>INR {{ number_format((float) $price, 2) }}</strong>
            </button>
        @endforeach
    </div>
    @foreach($prices as $type => $price)
        @php
            $coversSelected = $type !== 'print';
            $current = auth()->user()?->hasCurrentPackage($plan->id, $type);
        @endphp
        <div class="package-format-panel" role="tabpanel" id="price-{{ $plan->id }}-{{ $type }}" aria-labelledby="format-{{ $plan->id }}-{{ $type }}" tabindex="0" @if($type !== $initialType) hidden @endif>
            <div class="package-price">INR {{ number_format((float) $price, 2) }}</div>
            <p class="package-duration">for {{ $plan->duration }} {{ \Illuminate\Support\Str::plural(rtrim($plan->duration_type, 's'), (int) $plan->duration) }}</p>
            <p class="format-label">{{ $plan->formatContent($type)['title'] }}</p>
            <p class="format-description">{{ $plan->formatContent($type)['description'] }}</p>
            @if($features->isNotEmpty())<ul>@foreach($features as $feature)<li>{{ $feature }}</li>@endforeach</ul>@endif
            @if($selectedMagazine && !$selectedMagazineCanRead)
                <p class="package-coverage {{ $coversSelected ? 'text-success' : 'text-muted' }}">{{ $coversSelected ? 'Covers your selected magazine for digital reading.' : 'Does not unlock your selected magazine digitally. You can buy it separately.' }}</p>
            @endif
            @auth
            @if($type === 'both' && isset($upgradeOptions[$plan->id]))
                @foreach($upgradeOptions[$plan->id] as $upgradeOption)
                    <div class="package-select">
                        <p>Upgrade your {{ ucfirst($upgradeOption['from_type']) }} subscription. Keep the same expiry date.</p>
                        <a class="btn btn-dark w-100" href="{{ $upgradeOption['upgrade_url'] }}">Upgrade — {{ $upgradeOption['currency'] }} {{ $upgradeOption['upgrade_amount'] }}</a>
                    </div>
                @endforeach
            @else
            <form method="POST" action="{{ route('user.subscribe', $plan) }}" class="package-select" @if($type !== 'digital') onsubmit="return openDeliveryDialog(this)" @endif>
                @csrf
                <input type="hidden" name="delivery_type" value="{{ $type }}">
                @if((float) $plan->support_amount > 0)
                    <label class="d-block mb-3"><input type="checkbox" name="include_support" value="1"> Add optional support: {{ strtoupper(config('services.stripe.currency', 'inr')) }} {{ number_format((float) $plan->support_amount, 2) }} (one-time)</label>
                @endif
                <button class="btn btn-dark w-100" type="submit" @if($current) disabled aria-disabled="true" @endif>@if($current) Current package @else Choose {{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type] }} &rarr; @endif</button>
            </form>
            @endif
            @else
                @if((float) $plan->support_amount > 0)<p>Optional support: {{ number_format((float) $plan->support_amount, 2) }}</p>@endif
                <a class="btn btn-dark" href="{{ route('user.package.select', $plan) }}">Sign in to choose {{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type] }}</a>
            @endauth
            @if($current)<p class="text-muted small mt-2">You already have this package. Available again after your subscription expires.</p>@endif
        </div>
    @endforeach
</article>
