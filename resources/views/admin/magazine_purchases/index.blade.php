@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header"><h4>Magazine Delivery Requests</h4></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @include('admin.magazine_purchases.filters')
        <p class="text-muted">{{ $purchases->total() }} purchase(s) found.</p>
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Purchase date</th><th>Customer</th><th>Magazine</th><th>Format</th><th>Amount paid</th><th>Payment ID</th><th>Delivery address</th><th>Review status</th></tr></thead>
            <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ $purchase->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ $purchase->user?->name }}<br>{{ $purchase->user?->email }}</td>
                    <td>{{ $purchase->magazine?->title ?? 'Deleted magazine' }}</td>
                    <td>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$purchase->delivery_type] }}</td>
                    <td>{{ strtoupper($purchase->currency ?: config('services.stripe.currency', 'usd')) }} {{ number_format((float) $purchase->amount_paid, 2) }}</td>
                    <td class="text-break">{{ $purchase->transaction_id ?: 'Not available' }}</td>
                    <td>@foreach($purchase->delivery_address ?? [] as $value)@if($value)<div>{{ $value }}</div>@endif @endforeach</td>
                    <td>
                        @if(in_array($purchase->delivery_type, ['print', 'both'], true))
                            @if($purchase->print_acknowledged_at)
                                <span class="badge badge-success">Reviewed</span>
                            @else
                                <form method="POST" action="{{ route('admin.magazine-purchases.acknowledge', $purchase) }}" class="delivery-review-form">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-delivery-review" type="submit">Mark as reviewed</button>
                                    <div class="text-danger review-error" role="alert"></div>
                                </form>
                            @endif
                        @else
                            <span class="text-muted">Not applicable</span>
                        @endif
                    </td>
                </tr>
            @empty <tr><td colspan="9">No magazine purchases match these filters.</td></tr> @endforelse
            </tbody>
        </table></div>
        {{ $purchases->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.delivery-review-form');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button');
    const error = form.querySelector('.review-error');
    button.disabled = true;
    error.textContent = '';
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        if (!response.ok) throw new Error('Request failed');
        const badge = document.createElement('span');
        badge.className = 'badge badge-success';
        badge.textContent = 'Reviewed';
        form.replaceWith(badge);
    } catch (exception) {
        error.textContent = 'Could not update review status. Please try again.';
        button.disabled = false;
    }
});
</script>
@endsection
