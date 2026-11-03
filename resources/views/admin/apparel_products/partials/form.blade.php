<style>
    .apparel-product-form .btn,
    .apparel-product-form button {
        background-color: #932c25;
        border-color: #932c25;
        color: #fff;
    }

    .apparel-product-form .btn:hover,
    .apparel-product-form button:hover {
        background-color: #75221d;
        border-color: #75221d;
        color: #fff;
    }

    .apparel-product-form .btn:focus-visible,
    .apparel-product-form button:focus-visible {
        outline: 3px solid #932c25;
        outline-offset: 3px;
    }

    .apparel-product-form input[type="file"]::file-selector-button {
        background-color: #932c25;
        border: 1px solid #932c25;
        border-radius: 4px;
        color: #fff;
        padding: 6px 12px;
        cursor: pointer;
    }

    .variant-table input,
    .variant-table select {
        min-width: 110px;
    }

    .product-image-preview {
        display: inline-flex;
        flex-direction: column;
        gap: 6px;
        margin: 0 10px 10px 0;
    }

    .product-image-preview img {
        width: 92px;
        height: 92px;
        object-fit: cover;
        border: 1px solid #dee2e6;
    }

    .uploaded-image-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .uploaded-image-card {
        width: 120px;
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        background: #fff;
    }

    .uploaded-image-card img {
        width: 100%;
        height: 90px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #edf0f2;
    }

    .variant-image-cell {
        min-width: 260px;
    }

    .apparel-product-form .option-row {
        background: #faf8f7;
        border: 1px solid #e6ddda !important;
        border-radius: 12px !important;
        padding: 20px !important;
    }
    .apparel-product-form .option-row label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #443b38;
        margin-bottom: 6px;
    }
    .apparel-product-form .option-row .row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(200px, .65fr) 145px;
        gap: 12px;
        align-items: end;
        margin: 0 0 12px;
    }
    .apparel-product-form .option-row .row > div { width: auto; max-width: none; padding: 0; }
    .apparel-product-form .option-row [hidden] { display: none !important; }
    .apparel-product-form .option-row .form-control { height: 46px; border: 1px solid #cfc5c1; border-radius: 8px; font-size: 14px; }
    .apparel-product-form .option-row .btn { min-height: 42px; padding: 10px 14px; font-size: 13px; }
    .apparel-product-form .option-row .remove-option-row,
    .apparel-product-form .option-row .remove-option-value { background: #fff; color: #932c25; border: 1px solid #dfc7c3; }
    .apparel-product-form .option-row .option-help { font-size: 13px; color: #706560; margin: 8px 0 0; }
    .apparel-product-form .product-save-actions {
        position: sticky;
        bottom: 12px;
        z-index: 20;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        margin-top: 24px;
        background: #fff;
        border: 1px solid #e6ddda;
        border-radius: 12px;
        box-shadow: 0 4px 24px #40251b14;
    }
    .apparel-product-form .product-save-actions .product-save-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 180px;
        min-height: 48px;
        padding: 12px 24px;
        background: #932c25 !important;
        color: #fff !important;
        -webkit-text-fill-color: #fff;
        opacity: 1;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.4;
        border-radius: 8px;
    }
    .apparel-product-form .product-save-actions .product-save-button:hover { background: #75221d !important; }
    .apparel-product-form .product-save-actions .product-cancel-button { background: #fff; color: #51433e; border: 1px solid #d4c8c3; padding: 12px 20px; border-radius: 8px; }
    @media (max-width: 767px) {
        .apparel-product-form .option-row .row { grid-template-columns: minmax(0, 1fr); }
        .apparel-product-form .product-save-actions { bottom: 0; padding: 12px; }
    }
    .apparel-product-form [data-product-panel][hidden] { display: none !important; }
    .apparel-product-form [data-legacy-product-details] { display: none !important; }
    .apparel-product-form .product-tabs[hidden] { display: none; }
    .apparel-product-form .product-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid #e6ddda; }
    .apparel-product-form .product-tabs button { padding: 10px 18px; border: 1px solid #dfd4cf; border-radius: 8px; background: #fff; color: #51433e; font-weight: 600; }
    .apparel-product-form .product-tabs button[aria-selected="true"] { background: #932c25; color: #fff; border-color: #932c25; }
    .apparel-product-form .product-tabs button.has-error::after { content: ' •'; color: #c82c22; }
    .apparel-product-form .pending-image-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
    .apparel-product-form .pending-image-card { width: 112px; padding: 8px; border: 1px solid #e6ddda; border-radius: 8px; background: #fff; }
    .apparel-product-form .pending-image-card img { width: 94px; height: 88px; object-fit: cover; border-radius: 5px; }
    .apparel-product-form .pending-image-card span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; margin: 6px 0; }
    .apparel-product-form .pending-image-card button { padding: 5px 8px; font-size: 12px; border-radius: 5px; }
    .apparel-product-form .upload-help { display: block; margin-top: 8px; font-size: 13px; color: #706560; }
    .apparel-product-form .variant-table { display: block; border: 0; background: transparent; }
    .apparel-product-form .variant-table thead { display: none; }
    .apparel-product-form .variant-table tbody { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 400px), 1fr)); gap: 18px; }
    .apparel-product-form .variant-table tr { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; padding: 20px; border: 1px solid #e6ddda; border-radius: 12px; background: #faf8f7; }
    .apparel-product-form .variant-table td { display: block; padding: 0; border: 0; min-width: 0; text-align: left !important; }
    .apparel-product-form .variant-table td:first-child,
    .apparel-product-form .variant-table td:nth-child(5) { grid-column: 1 / -1; }
    .apparel-product-form .variant-table td:first-child { padding-bottom: 10px; border-bottom: 1px solid #e6ddda; }
    .apparel-product-form .variant-table td::before { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #51433e; }
    .apparel-product-form .variant-table td:nth-child(2)::before { content: 'SKU'; }
    .apparel-product-form .variant-table td:nth-child(3)::before { content: 'Price override (optional)'; }
    .apparel-product-form .variant-table td:nth-child(4)::before { content: 'Stock'; }
    .apparel-product-form .variant-table td:nth-child(5)::before { content: 'Variant images'; }
    .apparel-product-form .variant-table td:nth-child(6)::before { content: 'Active'; }
    .apparel-product-form .variant-table input { min-width: 0; max-width: 100%; }
    .apparel-product-form .variant-table input[type="checkbox"] { appearance: auto; -webkit-appearance: checkbox; width: 18px; height: 18px; margin: 4px; }
    .apparel-product-form .variant-table input[type="checkbox"]::before,
    .apparel-product-form .variant-table input[type="checkbox"]::after { display: none !important; }
</style>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Please correct these fields before saving:</strong>
        <ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        <p class="mb-0 mt-2">Select any images again after correcting the errors.</p>
    </div>
@endif
<div class="product-tabs" role="tablist" aria-label="Product sections" hidden>
    @foreach(['basics' => 'Basic information', 'content' => 'Details & size guide', 'images' => 'Product images', 'variants' => 'Options & variants'] as $tab => $label)
        <button type="button" role="tab" id="product-tab-{{ $tab }}" aria-controls="product-panel-{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-product-tab="{{ $tab }}">{{ $label }}</button>
    @endforeach
</div>
<section id="product-panel-basics" data-product-panel="basics" role="tabpanel" aria-labelledby="product-tab-basics">
<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label class="required" for="name">Product Name</label>
            <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" value="{{ old('name', $product->name ?? '') }}" required>
            @if($errors->has('name'))
                <div class="invalid-feedback">{{ $errors->first('name') }}</div>
            @endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="required" for="apparel_category_id">Category</label>
            <select class="form-control {{ $errors->has('apparel_category_id') ? 'is-invalid' : '' }}" name="apparel_category_id" id="apparel_category_id" required>
                <option value="">Select Category</option>
                @foreach($categories as $id => $name)
                    <option value="{{ $id }}" {{ (string) old('apparel_category_id', $product->apparel_category_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label class="required" for="price">Price</label>
            <input class="form-control" type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price', $product->price ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="compare_at_price">Compare At Price</label>
            <input class="form-control" type="number" step="0.01" min="0" name="compare_at_price" id="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="sku">SKU</label>
            <input class="form-control" type="text" name="sku" id="sku" value="{{ old('sku', $product->sku ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="stock">Base Stock</label>
            <input class="form-control" type="number" min="0" name="stock" id="stock" value="{{ old('stock', $product->stock ?? 0) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="required" for="status">Status</label>
            <select class="form-control" name="status" id="status" required>
                <option value="draft" {{ old('status', $product->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ old('status', $product->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input class="form-control" type="number" min="0" name="sort_order" id="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group form-check" style="margin-top: 35px;">
            <input type="checkbox" class="form-check-input" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_featured">Featured Product</label>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="short_description">Short Description</label>
    <textarea class="form-control" name="short_description" id="short_description" rows="3">{{ old('short_description', $product->short_description ?? '') }}</textarea>
</div>

</section>
<section id="product-panel-content" data-product-panel="content" role="tabpanel" aria-labelledby="product-tab-content">
@include('admin.apparel_products.partials.content-editor')
<div class="form-group">
    <label for="description">About The Product</label>
    <textarea class="form-control" name="description" id="description" rows="6">{{ old('description', $product->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="size_guide">Size Guide (legacy text / optional note below the chart)</label>
    <textarea class="form-control" name="size_guide" id="size_guide" rows="4" placeholder="Example: Width, in | S 18.00 | M 20.00">{{ old('size_guide', $product->size_guide ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6" data-legacy-product-details hidden>
        <div class="form-group">
            <label for="product_details">Product Details (fallback when no detail rows are added)</label>
            <textarea class="form-control" name="product_details" id="product_details" rows="4">{{ old('product_details', $product->product_details ?? '') }}</textarea>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="care_instructions">Care Instructions</label>
            <textarea class="form-control" name="care_instructions" id="care_instructions" rows="4">{{ old('care_instructions', $product->care_instructions ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="shipping_delivery">Shipping & Delivery</label>
            <textarea class="form-control" name="shipping_delivery" id="shipping_delivery" rows="4">{{ old('shipping_delivery', $product->shipping_delivery ?? '') }}</textarea>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="return_policy">Return Policy</label>
            <textarea class="form-control" name="return_policy" id="return_policy" rows="4">{{ old('return_policy', $product->return_policy ?? '30 days return policy.') }}</textarea>
        </div>
    </div>
</div>

</section>
<section id="product-panel-images" data-product-panel="images" role="tabpanel" aria-labelledby="product-tab-images">
<div class="form-group">
    <label>Product Images</label>
    @if(($product->exists ?? false) && $product->images->isNotEmpty())
        <div class="small text-muted mb-2">Already uploaded product images</div>
        <div class="mb-2">
            @foreach($product->images as $image)
                <label class="product-image-preview">
                    <img src="{{ asset($image->image_path) }}" alt="{{ $image->alt_text }}">
                    <span class="small">
                        <input type="checkbox" name="remove_images[]" value="{{ $image->id }}">
                        Remove
                    </span>
                </label>
            @endforeach
        </div>
    @endif
    <input class="form-control" type="file" name="images[]" id="images" accept="image/*" multiple>
    <span class="help-block">Upload front/back/gallery images for the product detail page. Select multiple files together, or choose files again to add more. Save Product to upload them.</span>
</div>
</section>
<section id="product-panel-variants" data-product-panel="variants" role="tabpanel" aria-labelledby="product-tab-variants">

@php
    $oldOptions = old('options');
    $options = $oldOptions !== null ? collect($oldOptions) : (($product->exists ?? false) ? $product->options : collect());
    $oldVariants = old('variants');
    $variants = $oldVariants !== null ? collect($oldVariants) : (($product->exists ?? false) ? $product->variants : collect());
@endphp

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Product Variants</h5>
    <button type="button" class="btn btn-sm btn-outline-dark" id="add-option-row">Add Option</button>
</div>

<div class="small text-muted mb-3">
    Define options such as Colour, Size, Capacity or Model first. Then generate combinations and edit SKU, price, stock, status and images per variant.
</div>

<div id="options-list">
    @forelse($options as $optionIndex => $option)
        @php
            $option = is_array($option) ? (object) $option : $option;
            $values = isset($option->values) ? collect($option->values) : collect();
        @endphp
        <div class="border rounded p-3 mb-3 option-row">
            <input type="hidden" name="options[{{ $optionIndex }}][id]" value="{{ $option->id ?? '' }}">
            <div class="row">
                <div class="col-md-5">
                    <label>Option Name</label>
                    <input class="form-control option-name" name="options[{{ $optionIndex }}][name]" value="{{ $option->name ?? '' }}" placeholder="Colour">
                </div>
                <div class="col-md-4">
                    <label>Option Type</label>
                    <select class="form-control option-type" name="options[{{ $optionIndex }}][type]">
                        @foreach(['text' => 'Text', 'color' => 'Colour', 'size' => 'Size'] as $value => $label)
                            <option value="{{ $value }}" {{ ($option->type ?? 'text') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-danger remove-option-row">Remove Option</button>
                </div>
            </div>
            <div class="option-values mt-3">
                @forelse($values as $valueIndex => $value)
                    @php $value = is_array($value) ? (object) $value : $value; @endphp
                    <div class="row mb-2 option-value-row">
                        <input type="hidden" name="options[{{ $optionIndex }}][values][{{ $valueIndex }}][id]" value="{{ $value->id ?? '' }}">
                        <div class="col-md-5">
                            <input class="form-control option-value" name="options[{{ $optionIndex }}][values][{{ $valueIndex }}][value]" value="{{ $value->value ?? '' }}" placeholder="Black">
                        </div>
                        <div class="col-md-4">
                            <input class="form-control option-color" name="options[{{ $optionIndex }}][values][{{ $valueIndex }}][color_hex]" value="{{ $value->color_hex ?? '' }}" placeholder="#000000">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-option-value">Remove</button>
                        </div>
                    </div>
                @empty
                    <div class="row mb-2 option-value-row">
                        <div class="col-md-5"><input class="form-control option-value" name="options[{{ $optionIndex }}][values][0][value]" placeholder="Black"></div>
                        <div class="col-md-4"><input class="form-control option-color" name="options[{{ $optionIndex }}][values][0][color_hex]" placeholder="#000000"></div>
                        <div class="col-md-3"><button type="button" class="btn btn-sm btn-outline-danger remove-option-value">Remove</button></div>
                    </div>
                @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-option-value">Add Value</button>
        </div>
    @empty
        <div class="border rounded p-3 mb-3 option-row">
            <div class="row">
                <div class="col-md-5"><label>Option Name</label><input class="form-control option-name" name="options[0][name]" placeholder="Colour"></div>
                <div class="col-md-4"><label>Option Type</label><select class="form-control option-type" name="options[0][type]"><option value="text">Text</option><option value="color">Colour</option><option value="size">Size</option></select></div>
                <div class="col-md-3 d-flex align-items-end"><button type="button" class="btn btn-sm btn-danger remove-option-row">Remove Option</button></div>
            </div>
            <div class="option-values mt-3">
                <div class="row mb-2 option-value-row">
                    <div class="col-md-5"><input class="form-control option-value" name="options[0][values][0][value]" placeholder="Black"></div>
                    <div class="col-md-4"><input class="form-control option-color" name="options[0][values][0][color_hex]" placeholder="#000000"></div>
                    <div class="col-md-3"><button type="button" class="btn btn-sm btn-outline-danger remove-option-value">Remove</button></div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-option-value">Add Value</button>
        </div>
    @endforelse
</div>

<button type="button" class="btn btn-outline-dark mb-3" id="generate-variants">Generate Variants</button>
<span class="small text-muted ml-2" id="variant-count"></span>

<div class="table-responsive">
    <table class="table table-bordered variant-table" id="variants-table">
        <thead>
            <tr>
                <th>Combination</th>
                <th>SKU</th>
                <th>Price Override</th>
                <th>Stock</th>
                <th>Variant Images</th>
                <th>Active</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($variants as $index => $variant)
                @php
                    $variant = is_array($variant) ? (object) $variant : $variant;
                    $valueIds = isset($variant->optionValues) ? $variant->optionValues->pluck('id')->implode(',') : ($variant->option_value_ids ?? '');
                @endphp
                <tr data-key="{{ $valueIds ?: ($variant->option_value ?? '') }}">
                    <td>
                        <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id ?? '' }}">
                        <input type="hidden" class="variant-value-ids" name="variants[{{ $index }}][option_value_ids]" value="{{ $valueIds }}">
                        <input type="hidden" name="variants[{{ $index }}][option_value]" value="{{ $variant->option_value ?? '' }}">
                        <strong>{{ $variant->option_value ?: 'Variant' }}</strong>
                    </td>
                    <td><input class="form-control" name="variants[{{ $index }}][sku]" value="{{ $variant->sku ?? '' }}"></td>
                    <td><input class="form-control" type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" value="{{ $variant->price ?? '' }}"></td>
                    <td><input class="form-control" type="number" min="0" name="variants[{{ $index }}][stock]" value="{{ $variant->stock ?? 0 }}"></td>
                    <td class="variant-image-cell">
                        @if(!empty($variant->images) && $variant->images instanceof \Illuminate\Support\Collection && $variant->images->isNotEmpty())
                            <div class="mb-2">
                                @foreach($variant->images as $image)
                                    <label class="product-image-preview">
                                        <img src="{{ asset($image->image_path) }}" alt="{{ $image->alt_text }}">
                                        <span class="small"><input type="checkbox" name="remove_variant_images[]" value="{{ $image->id }}"> Remove</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <input class="form-control" type="file" name="variants[{{ $index }}][images][]" accept="image/*" multiple>
                    </td>
                    <td class="text-center"><input type="checkbox" name="variants[{{ $index }}][status]" value="1" {{ ($variant->status ?? true) ? 'checked' : '' }}></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-variant-row">Exclude</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

</section>
<div class="product-save-actions">
    <button class="btn product-save-button" type="submit">Save Product</button>
    <a href="{{ route('admin.apparel-products.index') }}" class="btn product-cancel-button">Cancel</a>
</div>

<script>
    (function () {
        var optionsList = document.getElementById('options-list');
        var addOptionButton = document.getElementById('add-option-row');
        var generateButton = document.getElementById('generate-variants');
        var countLabel = document.getElementById('variant-count');
        var table = document.getElementById('variants-table').querySelector('tbody');

        function updateOptionFields() {
            optionRows().forEach(function (row, optionIndex) {
                var type = row.querySelector('.option-type').value;
                var colour = type === 'color';
                var fields = [
                    [row.querySelector('.option-name'), 'Option name'],
                    [row.querySelector('.option-type'), 'Display type']
                ];
                row.querySelectorAll('.option-value-row').forEach(function (valueRow) {
                    var value = valueRow.querySelector('.option-value');
                    var hex = valueRow.querySelector('.option-color');
                    value.placeholder = colour ? 'e.g. Black or Navy' : (type === 'size' ? 'e.g. S, M or One size' : 'e.g. Thin or 11oz');
                    hex.placeholder = 'Optional, e.g. #000000';
                    hex.parentElement.hidden = !colour;
                    fields.push([value, colour ? 'Colour name' : (type === 'size' ? 'Size value' : 'Option value')]);
                    fields.push([hex, 'Colour code (optional)']);
                });
                fields.forEach(function (entry, index) {
                    var field = entry[0];
                    field.id = 'product-option-' + optionIndex + '-field-' + index;
                    var label = field.previousElementSibling;
                    if (!label || label.tagName !== 'LABEL') {
                        label = document.createElement('label');
                        field.before(label);
                    }
                    label.htmlFor = field.id;
                    label.textContent = entry[1];
                });
                var help = row.querySelector('.option-help');
                if (!help) {
                    help = document.createElement('p');
                    help.className = 'option-help';
                    row.append(help);
                }
                help.textContent = colour
                    ? 'Add each colour separately. A colour code shows a swatch; leave it blank to show the colour name as a button.'
                    : 'Add each choice separately using Add Value. Colour codes are not needed.';
            });
        }

        function optionRows() {
            return Array.prototype.slice.call(optionsList.querySelectorAll('.option-row'));
        }

        function renumberOptions() {
            optionRows().forEach(function (optionRow, optionIndex) {
                optionRow.querySelectorAll('input, select').forEach(function (field) {
                    if (field.name) {
                        field.name = field.name.replace(/options\[\d+\]/, 'options[' + optionIndex + ']');
                    }
                });

                Array.prototype.slice.call(optionRow.querySelectorAll('.option-value-row')).forEach(function (valueRow, valueIndex) {
                    valueRow.querySelectorAll('input').forEach(function (field) {
                        if (field.name) {
                            field.name = field.name.replace(/\[values\]\[\d+\]/, '[values][' + valueIndex + ']');
                        }
                    });
                });
            });
            updateOptionFields();
        }

        function variantRows() {
            return Array.prototype.slice.call(table.querySelectorAll('tr'));
        }

        function renumberVariants() {
            variantRows().forEach(function (row, index) {
                row.querySelectorAll('input').forEach(function (field) {
                    if (field.name && field.name.indexOf('variants[') === 0) {
                        field.name = field.name.replace(/variants\[\d+\]/, 'variants[' + index + ']');
                    }
                });
            });
        }

        function optionTemplate(index) {
            return '<div class="border rounded p-3 mb-3 option-row">' +
                '<div class="row">' +
                '<div class="col-md-5"><label>Option Name</label><input class="form-control option-name" name="options[' + index + '][name]" placeholder="Colour"></div>' +
                '<div class="col-md-4"><label>Option Type</label><select class="form-control option-type" name="options[' + index + '][type]"><option value="text">Text</option><option value="color">Colour</option><option value="size">Size</option></select></div>' +
                '<div class="col-md-3 d-flex align-items-end"><button type="button" class="btn btn-sm btn-danger remove-option-row">Remove Option</button></div>' +
                '</div><div class="option-values mt-3">' +
                valueTemplate(index, 0) +
                '</div><button type="button" class="btn btn-sm btn-outline-secondary add-option-value">Add Value</button></div>';
        }

        function valueTemplate(optionIndex, valueIndex) {
            return '<div class="row mb-2 option-value-row">' +
                '<div class="col-md-5"><input class="form-control option-value" name="options[' + optionIndex + '][values][' + valueIndex + '][value]" placeholder="Black"></div>' +
                '<div class="col-md-4"><input class="form-control option-color" name="options[' + optionIndex + '][values][' + valueIndex + '][color_hex]" placeholder="#000000"></div>' +
                '<div class="col-md-3"><button type="button" class="btn btn-sm btn-outline-danger remove-option-value">Remove</button></div>' +
                '</div>';
        }

        function collectOptions() {
            return optionRows().map(function (optionRow, optionIndex) {
                var name = optionRow.querySelector('.option-name').value.trim();
                var values = Array.prototype.slice.call(optionRow.querySelectorAll('.option-value-row')).map(function (valueRow, valueIndex) {
                    var idField = valueRow.querySelector('input[type="hidden"]');
                    var value = valueRow.querySelector('.option-value').value.trim();

                    return {
                        key: idField && idField.value ? 'id:' + idField.value : optionIndex + ':' + valueIndex,
                        label: value
                    };
                }).filter(function (value) {
                    return value.label !== '';
                });

                return { name: name, values: values };
            }).filter(function (option) {
                return option.name !== '' && option.values.length > 0;
            });
        }

        function combinations(groups) {
            return groups.reduce(function (carry, group) {
                var result = [];
                carry.forEach(function (prefix) {
                    group.values.forEach(function (value) {
                        result.push(prefix.concat([{ name: group.name, key: value.key, label: value.label }]));
                    });
                });
                return result;
            }, [[]]);
        }

        addOptionButton.addEventListener('click', function () {
            optionsList.insertAdjacentHTML('beforeend', optionTemplate(optionRows().length));
            updateOptionFields();
        });

        optionsList.addEventListener('change', function (event) {
            if (event.target.classList.contains('option-type')) updateOptionFields();
        });
        updateOptionFields();

        optionsList.addEventListener('click', function (event) {
            if (event.target.classList.contains('add-option-value')) {
                var optionRow = event.target.closest('.option-row');
                var optionIndex = optionRows().indexOf(optionRow);
                var values = optionRow.querySelector('.option-values');
                values.insertAdjacentHTML('beforeend', valueTemplate(optionIndex, values.querySelectorAll('.option-value-row').length));
                renumberOptions();
            }

            if (event.target.classList.contains('remove-option-value')) {
                var valuesList = event.target.closest('.option-values');
                if (valuesList.querySelectorAll('.option-value-row').length > 1) {
                    event.target.closest('.option-value-row').remove();
                    renumberOptions();
                }
            }

            if (event.target.classList.contains('remove-option-row')) {
                if (optionRows().length > 1) {
                    event.target.closest('.option-row').remove();
                    renumberOptions();
                }
            }
        });

        generateButton.addEventListener('click', function () {
            var existing = {};

            variantRows().forEach(function (row) {
                existing[row.dataset.key || ''] = row;
            });

            combinations(collectOptions()).forEach(function (combo) {
                var key = combo.map(function (item) { return item.key; }).join(',');

                if (existing[key]) {
                    return;
                }

                var label = combo.map(function (item) { return item.label; }).join(' / ');
                var index = table.querySelectorAll('tr').length;
                var row = document.createElement('tr');
                row.dataset.key = key;
                row.innerHTML =
                    '<td><input type="hidden" class="variant-value-ids" name="variants[' + index + '][option_value_ids]" value="' + key + '">' +
                    '<input type="hidden" name="variants[' + index + '][option_value]" value="' + label.replace(/"/g, '&quot;') + '"><strong>' + label + '</strong></td>' +
                    '<td><input class="form-control" name="variants[' + index + '][sku]"></td>' +
                    '<td><input class="form-control" type="number" step="0.01" min="0" name="variants[' + index + '][price]"></td>' +
                    '<td><input class="form-control" type="number" min="0" name="variants[' + index + '][stock]" value="0"></td>' +
                    '<td class="variant-image-cell"><input class="form-control" type="file" name="variants[' + index + '][images][]" accept="image/*" multiple></td>' +
                    '<td class="text-center"><input type="checkbox" name="variants[' + index + '][status]" value="1" checked></td>' +
                    '<td><button type="button" class="btn btn-sm btn-danger remove-variant-row">Exclude</button></td>';
                table.appendChild(row);
            });

            countLabel.textContent = table.querySelectorAll('tr').length + ' variants ready';
            renumberVariants();
        });

        table.addEventListener('click', function (event) {
            if (!event.target.classList.contains('remove-variant-row')) {
                return;
            }

            event.target.closest('tr').remove();
            renumberVariants();
        });
    })();
</script>
<script type="application/json" data-product-errors>@json($errors->keys())</script>
<script src="{{ asset('js/apparel-product-form.js') }}?v={{ filemtime(public_path('js/apparel-product-form.js')) }}" defer></script>
