@php
    $prices = $plan->prices();
    $initialType = array_key_exists($selectedType, $prices) ? $selectedType : array_key_first($prices);
    $features = collect(preg_split('/\r\n|\r|\n/', $plan->features ?? ''))->map(fn ($feature) => trim($feature))->filter();
@endphp
<article class="package-card">
    <div class="package-card-header"><h3>{{ $plan->name }}</h3></div>
    <p class="package-description">{{ $plan->description }}</p>
    <div class="package-format-tabs" role="tablist" aria-label="{{ $plan->name }} format">
        @foreach($prices as $type => $price)
            <button type="button" role="tab" id="format-{{ $plan->id }}-{{ $type }}" aria-controls="price-{{ $plan->id }}-{{ $type }}" aria-selected="{{ $type === $initialType ? 'true' : 'false' }}" tabindex="{{ $type === $initialType ? '0' : '-1' }}">
                <span>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Both'][$type] }}</span>
                <strong>INR {{ number_format((float) $price, 2) }}</strong>
            </button>
        @endforeach
    </div>
    @foreach($prices as $type => $price)
        @php
            $startsAt = $packageStartsByType[$type]->copy();
            $preview = new \App\Models\Subscription(['start_date' => $startsAt, 'end_date' => $plan->endsAt($startsAt)]);
            [$coverageStart, $coverageEnd] = $preview->magazinePeriod();
            $issueDate = $selectedMagazine?->issue_date ?? $selectedMagazine?->published_at;
            $coversSelected = $type !== 'print' && $issueDate && $issueDate->copy()->startOfDay()->betweenIncluded($coverageStart, $coverageEnd);
            $current = $subscription && $subscription->plan_id === $plan->id && ($subscription->delivery_type ?? 'digital') === $type;
        @endphp
        <div class="package-format-panel" role="tabpanel" id="price-{{ $plan->id }}-{{ $type }}" aria-labelledby="format-{{ $plan->id }}-{{ $type }}" tabindex="0" @if($type !== $initialType) hidden @endif>
            <div class="package-price">INR {{ number_format((float) $price, 2) }}</div>
            <p class="package-duration">for {{ $plan->duration }} {{ \Illuminate\Support\Str::plural(rtrim($plan->duration_type, 's'), (int) $plan->duration) }}</p>
            <p class="format-label">{{ ['digital' => 'Digital reading, wherever you are', 'print' => 'Printed magazines, delivered to you', 'both' => 'Digital reading + printed magazines'][$type] }}</p>
            <p class="format-description">{{ $type === 'print' ? 'Delivery included. Digital reader access is not included.' : ($type === 'both' ? 'Read online and enjoy print delivery at no extra charge.' : 'Read eligible issues online from your dashboard.') }}</p>
            @if($features->isNotEmpty())<ul>@foreach($features as $feature)<li>{{ $feature }}</li>@endforeach</ul>@endif
            @if($selectedMagazine && !$selectedMagazineCanRead)
                <p class="package-coverage {{ $coversSelected ? 'text-success' : 'text-muted' }}">{{ $coversSelected ? 'Covers your selected magazine for digital reading.' : 'Does not unlock your selected magazine digitally. You can buy it separately.' }}</p>
            @endif
            <form method="POST" action="{{ route('user.subscribe', $plan) }}" class="package-select" @if($type !== 'digital') onsubmit="return openDeliveryDialog(this)" @endif>
                @csrf
                <input type="hidden" name="delivery_type" value="{{ $type }}">
                <button class="btn btn-dark w-100" type="submit">{{ $current ? 'Renew' : 'Choose' }} {{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type] }} &rarr;</button>
            </form>
        </div>
    @endforeach
</article>
