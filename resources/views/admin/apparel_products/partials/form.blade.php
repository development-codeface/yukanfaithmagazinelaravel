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
</style>

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

<div class="form-group">
    <label for="description">About The Product</label>
    <textarea class="form-control" name="description" id="description" rows="6">{{ old('description', $product->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="size_guide">Size Guide</label>
    <textarea class="form-control" name="size_guide" id="size_guide" rows="4" placeholder="Example: Width, in | S 18.00 | M 20.00">{{ old('size_guide', $product->size_guide ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="product_details">Product Details</label>
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

<hr>

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
    <span class="help-block">Upload front/back/gallery images for the product detail page.</span>
</div>

<hr>

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
                        @if(!empty($variant->images) && $variant->images->isNotEmpty())
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

<div class="form-group mt-4">
    <button class="btn btn-success min-w-200" type="submit">Save Product</button>
    <a href="{{ route('admin.apparel-products.index') }}" class="btn btn-secondary">Cancel</a>
</div>

<script>
    (function () {
        var optionsList = document.getElementById('options-list');
        var addOptionButton = document.getElementById('add-option-row');
        var generateButton = document.getElementById('generate-variants');
        var countLabel = document.getElementById('variant-count');
        var table = document.getElementById('variants-table').querySelector('tbody');

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
        });

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
