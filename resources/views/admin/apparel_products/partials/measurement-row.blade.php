<fieldset data-content-row class="border rounded p-3 mb-3">
    <legend style="font-size:15px;width:auto">Measurement</legend>
    <label>Attribute / measurement name<input class="form-control mb-2" name="size_chart_rows[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Height, Width or Diameter" required maxlength="150"></label>
    <label>Imperial values (inches)<input class="form-control mb-2" name="size_chart_rows[{{ $index }}][imperial]" value="{{ $row['imperial'] ?? '' }}" placeholder="3.78, 4.76" required></label>
    <label>Metric values (cm)<input class="form-control mb-2" name="size_chart_rows[{{ $index }}][metric]" value="{{ $row['metric'] ?? '' }}" placeholder="9.60, 12.09" required></label>
    <div>@include('admin.apparel_products.partials.row-actions')</div>
</fieldset>
