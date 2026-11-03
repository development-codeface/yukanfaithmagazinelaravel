<style>
    /* The admin theme hides labels globally; these labels also contain the fields. */
    #product-content-editor label { display: block; }
    #product-content-editor [data-hidden-chart-preview] { display: none !important; }
    #product-content-editor .detail-icon-preview img { width: 64px; height: 64px; object-fit: contain; margin: 8px 0; }
    #product-content-editor .chart-preview-scroll { overflow-x: auto; }
    #product-content-editor .chart-preview-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    #product-content-editor .chart-preview-table th,
    #product-content-editor .chart-preview-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
</style>
<section class="card my-4" id="product-content-editor">
    <div class="card-header">Size chart &amp; product detail rows</div>
    <div class="card-body">
        <input type="hidden" name="content_editor" value="1">
        <h5>Product size chart</h5>
        <p>Enter sizes separated by commas. Add each measurement name and one value per size, in the same order, for inches and centimeters, then save. Remove all measurements to hide the chart.</p>
        <label for="size-chart-columns">Size columns, separated by commas</label>
        <input class="form-control mb-3" id="size-chart-columns" name="size_chart_columns" value="{{ old('size_chart_columns', implode(', ', $product->size_chart['columns'] ?? [])) }}" placeholder="11oz, 15oz">
        <div data-content-list="measurements">
        @foreach(old('size_chart_rows', collect($product->size_chart['rows'] ?? [])->map(fn ($row) => ['label' => $row['label'], 'imperial' => implode(', ', $row['imperial']), 'metric' => implode(', ', $row['metric'])])->all()) as $row)
            @include('admin.apparel_products.partials.measurement-row', ['row' => $row, 'index' => $loop->index])
        @endforeach
        </div>
        <button type="button" class="btn btn-sm mb-4" data-add-content="measurements">+ Add measurement</button>
        <div data-hidden-chart-preview hidden>
        <h6>Table preview</h6>
        <div data-chart-preview aria-live="polite"></div>
        <p>The storefront shows this table under Size guide.</p>
        </div>
        <h5>Product detail rows</h5>
        <p>Upload an icon image, then add a heading and description for each detail. Details appear as cards on the storefront in the order entered.</p>
        <div data-content-list="details">
        @foreach(old('detail_sections', $product->detail_sections ?? []) as $row)
            @include('admin.apparel_products.partials.detail-row', ['row' => $row, 'index' => $loop->index])
        @endforeach
        </div>
        <button type="button" class="btn btn-sm" data-add-content="details">+ Add product detail</button>
        @foreach($errors->getMessages() as $key => $messages)
            @if(str_starts_with($key, 'size_chart') || str_starts_with($key, 'detail_sections'))<p class="text-danger mt-2">{{ $messages[0] }}</p>@endif
        @endforeach
    </div>
    <template id="measurement-template">@include('admin.apparel_products.partials.measurement-row', ['row' => [], 'index' => 0])</template>
    <template id="detail-template">@include('admin.apparel_products.partials.detail-row', ['row' => [], 'index' => 0])</template>
</section>
<script src="{{ asset('js/apparel-content-editor.js') }}" defer></script>
