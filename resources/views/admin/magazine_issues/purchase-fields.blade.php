<div class="form-group">
<label>Publication date</label>
<input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', isset($magazineIssue) ? $magazineIssue->published_at?->format('Y-m-d\TH:i') : '') }}">
<small>Issue Date determines subscription coverage; Publication date records when the magazine was published.</small>
</div>
<div class="form-group">
<label>Single issue price ({{ strtoupper(config('services.stripe.currency', 'inr')) }})</label>
<input type="number" name="price" min="0.01" step="0.01" class="form-control" value="{{ old('price', $magazineIssue->price ?? '') }}">
<small>Leave blank for subscription access only.</small>
</div>
<div class="form-group">
<label for="issue-articles">Articles in this issue</label>
<select id="issue-articles" name="article_ids[]" multiple class="form-control select2" style="width: 100%" data-placeholder="Search articles by title…" data-close-on-select="false" aria-describedby="issue-articles-help">
@foreach(\App\Models\Article::orderBy('title')->get() as $mappedArticle)
<option value="{{ $mappedArticle->id }}" @selected(in_array($mappedArticle->id, old('article_ids', isset($magazineIssue) ? $magazineIssue->articles()->pluck('id')->all() : [])))>{{ $mappedArticle->title }}</option>
@endforeach
</select>
<small id="issue-articles-help">Type to search and select multiple articles. Each article belongs to one issue. Selecting an article moves it to this issue.</small>
</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
