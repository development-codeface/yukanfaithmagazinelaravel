@php
    $paths = [
        'info' => 'M12 11v6m0-10v1 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'ruler' => 'M3 7h18v10H3z M7 7v5m5-5v3m5-3v5',
        'shirt' => 'M8 3 3 5 1 10l5 2v9h12v-9l5-2-2-5-5-2c0 5-8 5-8 0z',
        'leaf' => 'M20 3C5 1 1 10 6 16s16 1 14-13z M4 21 16 9',
        'shield' => 'M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z M8 12l3 3 5-6',
        'truck' => 'M1 4h13v13H1z M14 9h5l4 5v3h-9 M8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0 M21 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0',
        'return' => 'M3 3v6h6 M3 9a9 9 0 1 1 0 6',
        'wash' => 'M2 6l3 14h14l3-14 M4 10c3-4 5 4 8 0s5 4 8 0 M9 2v3m6-3v3',
        'star' => 'm12 2 3 6 7 1-5 5 1 8-6-4-6 4 1-8-5-5 7-1z',
        'heart' => 'M12 21S1 14 1 7c0-6 8-7 11-1 3-6 11-5 11 1 0 7-11 14-11 14z',
    ];
@endphp
<svg class="product-detail-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$icon] ?? $paths['info'] }}"/></svg>
