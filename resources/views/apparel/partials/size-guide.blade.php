@if($product->size_chart || $product->size_guide)
<button type="button" class="size-guide-trigger" data-size-guide-open>Size guide</button>
<dialog id="product-size-guide" class="size-guide-dialog" aria-labelledby="size-guide-heading">
    <button type="button" class="size-guide-close" data-size-guide-close aria-label="Close size guide">&times;</button>
    <h2 id="size-guide-heading">Size guide</h2>
    <p class="size-guide-note">{{ $product->name }}</p>
    @if($product->size_chart)
    <p class="size-guide-note">Measurements refer to product dimensions in inches (imperial) and centimeters (metric).</p>
    <div class="size-unit-tabs" role="tablist" aria-label="Measurement units">
        @foreach(['imperial' => 'Imperial', 'metric' => 'Metric'] as $unit => $label)
        <button type="button" role="tab" id="unit-{{ $unit }}" data-size-unit="{{ $unit }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="chart-{{ $unit }}" tabindex="{{ $loop->first ? '0' : '-1' }}">{{ $label }}</button>
        @endforeach
    </div>
    @foreach(['imperial' => 'inches', 'metric' => 'cm'] as $unit => $label)
    <div class="size-chart-scroll" role="tabpanel" id="chart-{{ $unit }}" aria-labelledby="unit-{{ $unit }}" tabindex="0" @if(!$loop->first) hidden @endif>
        <table class="size-chart"><caption class="visually-hidden">{{ $product->name }} dimensions in {{ $label }}</caption><thead><tr><th scope="col">({{ $label }})</th>@foreach($product->size_chart['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead><tbody>
        @foreach($product->size_chart['rows'] as $row)<tr><th scope="row">{{ $row['label'] }}</th>@foreach($row[$unit] as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach
        </tbody></table>
    </div>
    @endforeach
    @endif
    @if($product->size_guide)<div class="prose size-guide-note">{{ strip_tags($product->size_guide) }}</div>@endif
</dialog>
@endif
