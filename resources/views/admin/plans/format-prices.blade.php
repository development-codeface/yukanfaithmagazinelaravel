<div class="form-group">
    <label for="support_amount">Optional support amount (INR)</label>
    <input class="form-control mb-2" type="number" id="support_amount" name="support_amount" min="0" max="99999999.99" step="0.01" value="{{ old('support_amount', $plan->support_amount ?? 0) }}">
    <p class="text-muted">Optional one-time support paid with this package. Set 0 to hide it.</p>
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
        <p class="text-info">Users must choose this 7-day trial and set up payment details. It is not assigned at registration.</p>
        <label for="trial_conversion_plan_id">Annual Basic Digital package charged after the trial</label>
        <select class="form-control" id="trial_conversion_plan_id" name="trial_conversion_plan_id">
            <option value="">Select annual Digital package</option>
            @foreach(\App\Models\Plan::where('is_trial', false)->where('status', 1)->where('digital_price', '>', 0)->get() as $target)
                @if(($target->duration_type === 'years' && (int) $target->duration === 1) || ($target->duration_type === 'months' && (int) $target->duration === 12))
                    <option value="{{ $target->id }}" @selected(old('trial_conversion_plan_id', $plan->trial_conversion_plan_id) == $target->id)>{{ $target->name }} — {{ $target->digital_price }}</option>
                @endif
            @endforeach
        </select>
    @endif
</div>
