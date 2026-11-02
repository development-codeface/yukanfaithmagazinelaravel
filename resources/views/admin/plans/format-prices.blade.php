<div class="form-group">
    <h5>Package formats and prices (INR)</h5>
    <p>Enter a price to offer a format. Leave it blank to disable that format. Each price is the full package price, including delivery where applicable.</p>
    @foreach(['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'] as $type => $label)
        <label for="{{ $type }}_price">{{ $label }} price</label>
        <input class="form-control mb-2" type="number" step="0.01" min="0" max="99999999.99" id="{{ $type }}_price" name="{{ $type }}_price" value="{{ old($type.'_price', isset($plan) ? ($plan->prices()[$type] ?? '') : '') }}" placeholder="Not offered">
        @error($type.'_price')<p class="text-danger">{{ $message }}</p>@enderror
        <label for="{{ $type }}_title">{{ $label }} heading</label>
        <input class="form-control mb-2" id="{{ $type }}_title" name="format_content[{{ $type }}][title]" maxlength="255" value="{{ old('format_content.'.$type.'.title', isset($plan) ? $plan->formatContent($type)['title'] : (new \App\Models\Plan)->formatContent($type)['title']) }}">
        @error('format_content.'.$type.'.title')<p class="text-danger">{{ $message }}</p>@enderror
        <label for="{{ $type }}_description">{{ $label }} description</label>
        <textarea class="form-control mb-3" id="{{ $type }}_description" name="format_content[{{ $type }}][description]" maxlength="2000" rows="2">{{ old('format_content.'.$type.'.description', isset($plan) ? $plan->formatContent($type)['description'] : (new \App\Models\Plan)->formatContent($type)['description']) }}</textarea>
        @error('format_content.'.$type.'.description')<p class="text-danger">{{ $message }}</p>@enderror
    @endforeach
    @if(isset($plan) && $plan->isTrial())
        <p class="text-info">This is the automatic new-account trial. It always grants free digital access. Change Duration below to control future trials; existing subscriptions keep their expiry dates.</p>
    @endif
</div>
