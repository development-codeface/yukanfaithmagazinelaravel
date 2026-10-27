<div class="form-group">
    <label>Publication date</label>
    <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', isset($magazineIssue) ? $magazineIssue->published_at?->format('Y-m-d\TH:i') : '') }}">
    <small>Issue Date determines subscription coverage; Publication date records when the magazine was published.</small>
</div>
<div class="form-group">
    <h5>Single magazine prices ({{ strtoupper(config('services.stripe.currency', 'inr')) }})</h5>
    <p>Each price includes delivery where applicable. Leave a format blank to disable it, or leave all blank for subscription access only.</p>
    @foreach(['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'] as $type => $label)
        <label for="{{ $type }}_price">{{ $label }} price</label>
        <input id="{{ $type }}_price" type="number" name="{{ $type }}_price" min="0.01" max="99999999.99" step="0.01" class="form-control mb-2" value="{{ old($type.'_price', isset($magazineIssue) ? ($magazineIssue->prices()[$type] ?? '') : '') }}" placeholder="Not offered">
    @endforeach
    <small>Digital and Both require an external reader URL. Assign articles from the article create or edit page.</small>
</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
