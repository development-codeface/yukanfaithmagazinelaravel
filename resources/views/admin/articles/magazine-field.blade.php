<div class="form-group">
    <label for="article-magazine">Magazine issue</label>
    <select name="issue_id" id="article-magazine" class="form-control select2">
        <option value="">No magazine</option>
        @foreach($issues as $id => $title)
            <option value="{{ $id }}" @selected((string) old('issue_id', $article->issue_id ?? '') === (string) $id)>{{ $title }}</option>
        @endforeach
    </select>
    <small>Select the magazine this article belongs to. Its issue date determines subscription eligibility.</small>
    @error('issue_id')<div class="text-danger">{{ $message }}</div>@enderror
</div>
