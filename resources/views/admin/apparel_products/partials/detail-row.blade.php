<fieldset data-content-row class="border rounded p-3 mb-3">
    <legend style="font-size:15px;width:auto">Product detail</legend>
    <label>Icon image (optional)<input type="file" class="form-control mb-2" name="detail_sections[{{ $index }}][icon_upload]" accept="image/svg+xml,.svg,image/png,image/jpeg,image/webp" data-detail-icon-upload></label>
    <small>SVG, PNG, JPG or WebP, up to 2 MB. Use a static SVG or transparent PNG. Save Product to upload.</small>
    <input type="hidden" name="detail_sections[{{ $index }}][icon_image_path]" value="{{ $row['icon_image_path'] ?? '' }}">
    <div class="detail-icon-preview" data-detail-icon-preview data-saved-icon="{{ !empty($row['icon_image_path']) ? asset($row['icon_image_path']) : '' }}">
        @if(!empty($row['icon_image_path']))<img src="{{ asset($row['icon_image_path']) }}" alt="Current detail icon">@endif
    </div>
    @if(!empty($row['icon_image_path']))
        <label><input type="checkbox" name="detail_sections[{{ $index }}][remove_icon_image]" value="1" @checked(!empty($row['remove_icon_image']))> Remove saved icon image on save</label>
    @endif
    <label>Fallback icon (used when no image is uploaded)<select class="form-control mb-2" name="detail_sections[{{ $index }}][icon]">@foreach(['info' => 'Information', 'ruler' => 'Ruler / sizing', 'shirt' => 'Clothing / fabric', 'leaf' => 'Leaf / materials', 'shield' => 'Shield / quality', 'truck' => 'Truck / delivery', 'return' => 'Return arrow', 'wash' => 'Wash / care', 'star' => 'Star', 'heart' => 'Heart'] as $value => $label)<option value="{{ $value }}" @selected(($row['icon'] ?? 'info') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label style="display:block">Heading<input class="form-control mb-2" name="detail_sections[{{ $index }}][title]" value="{{ $row['title'] ?? '' }}" required maxlength="255"></label>
    <label style="display:block">Content<textarea class="form-control mb-2" name="detail_sections[{{ $index }}][content]" rows="3" required maxlength="10000">{{ $row['content'] ?? '' }}</textarea></label>
    <div>@include('admin.apparel_products.partials.row-actions')</div>
</fieldset>
