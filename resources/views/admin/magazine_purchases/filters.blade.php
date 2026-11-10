@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
<style>.magazine-delivery-filters label { display:block !important; font-weight:600; margin-bottom:6px; }</style>
<form method="GET" action="{{ route('admin.magazine-purchases.index') }}" class="mb-4 magazine-delivery-filters">
    <input type="hidden" name="search_by" value="name">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="purchase-search">Customer name</label>
            <input class="form-control" id="purchase-search" name="search" value="{{ request('search') }}" maxlength="255" placeholder="Search by customer name">
        </div>
        {{-- Hidden for now; keep these filters available for future use.
        <div class="col-md-4 mb-3">
            <label for="purchase-magazine">Magazine</label>
            <select class="form-control" id="purchase-magazine" name="magazine_id">
                <option value="">All magazines</option>
                @foreach($magazines as $magazine)<option value="{{ $magazine->id }}" @selected((string) request('magazine_id') === (string) $magazine->id)>{{ $magazine->title }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="purchase-format">Format</label>
            <select class="form-control" id="purchase-format" name="delivery_type">
                <option value="">All formats</option>
                @foreach(['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'] as $value => $label)<option value="{{ $value }}" @selected(request('delivery_type') === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
        --}}
        <div class="col-md-4 mb-3">
            <label for="purchase-review">Review status</label>
            <select class="form-control" id="purchase-review" name="review">
                <option value="">All review statuses</option>
                <option value="pending" @selected(request('review') === 'pending')>Pending review</option>
                <option value="reviewed" @selected(request('review') === 'reviewed')>Reviewed</option>
            </select>
        </div>
        {{-- Hidden for now; keep these filters available for future use.
        <div class="col-md-4 mb-3"><label for="purchase-from">Purchased from</label><input class="form-control" type="date" id="purchase-from" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-4 mb-3"><label for="purchase-to">Purchased to</label><input class="form-control" type="date" id="purchase-to" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-md-4 mb-3">
            <label for="purchase-per-page">Rows per page</label>
            <select class="form-control" id="purchase-per-page" name="per_page">@foreach([15,30,50,100] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 30) === $size)>{{ $size }}</option>@endforeach</select>
        </div>
        --}}
    </div>
    <button class="btn btn-primary" type="submit">Apply filters</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.magazine-purchases.index') }}">Reset</a>
</form>
