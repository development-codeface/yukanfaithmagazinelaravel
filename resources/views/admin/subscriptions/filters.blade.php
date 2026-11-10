@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
<style>.subscription-list-filters label { display:block !important; font-weight:600; margin-bottom:6px; }</style>
<form method="GET" action="{{ route('admin.subscriptions.index') }}" class="mb-4 subscription-list-filters">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="filter-search">Search user or payment</label>
            <input class="form-control" id="filter-search" name="search" value="{{ request('search') }}" maxlength="255" placeholder="Name, email, payment ID or subscription ID">
        </div>
        <div class="col-md-4 mb-3">
            <label for="filter-plan">Package</label>
            <select class="form-control" id="filter-plan" name="plan_id">
                <option value="">All packages</option>
                @foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((string) request('plan_id') === (string) $plan->id)>{{ $plan->name }}</option>@endforeach
            </select>
        </div>
        @foreach([
            'status' => ['Subscription status', ['active' => 'Active now', 'expired' => 'Expired', 'scheduled' => 'Scheduled', 'inactive' => 'Inactive / cancelled']],
            'payment_status' => ['Payment status', ['1' => 'Success', '0' => 'Unpaid / failed']],
            'delivery_type' => ['Format', ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print']],
            'support' => ['Support payment', ['paid' => 'Support paid', 'none' => 'No support']],
            'review' => ['Print delivery review', ['pending' => 'Pending review', 'reviewed' => 'Reviewed']],
        ] as $name => [$label, $options])
            <div class="col-md-4 mb-3" @if(!in_array($name, ['delivery_type', 'support'])) hidden style="display:none" @endif>
                <label for="filter-{{ $name }}">{{ $label }}</label>
                <select class="form-control" id="filter-{{ $name }}" name="{{ $name }}" @disabled(!in_array($name, ['delivery_type', 'support']))>
                    <option value="">{{ ['delivery_type' => 'All formats', 'support' => 'All support payments'][$name] ?? 'All' }}</option>
                    @foreach($options as $value => $text)<option value="{{ $value }}" @selected((string) request($name) === (string) $value)>{{ $text }}</option>@endforeach
                </select>
            </div>
        @endforeach
        {{-- Hidden for now; keep these filters available for future use.
        <div class="col-md-4 mb-3">
            <label for="filter-date-field">Filter dates by</label>
            <select class="form-control" id="filter-date-field" name="date_field">
                @foreach(['created_at' => 'Date subscribed', 'start_date' => 'Subscription start', 'end_date' => 'Subscription end'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('date_field', 'created_at') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3"><label for="filter-date-from">From date</label><input type="date" class="form-control" id="filter-date-from" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-4 mb-3"><label for="filter-date-to">To date</label><input type="date" class="form-control" id="filter-date-to" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-md-4 mb-3">
            <label for="filter-per-page">Rows per page</label>
            <select class="form-control" id="filter-per-page" name="per_page">@foreach([15, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }}</option>@endforeach</select>
        </div>
        --}}
    </div>
    <button class="btn btn-primary" type="submit">Apply filters</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.subscriptions.index') }}">Reset</a>
</form>
