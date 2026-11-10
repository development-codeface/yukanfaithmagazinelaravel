@extends('layouts.frontend')

@section('title', 'Payment History')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Payment History</h1>
        <a href="{{ route('user.dashboard') }}" class="btn btn-outline-dark">Back to Dashboard</a>
    </div>
    <p class="text-muted">Your successful subscription, magazine, article, and apparel payments.</p>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Date recorded</th><th>Type</th><th>Description / Order</th><th>Support</th><th>Total Paid</th><th>Status</th><th>Transaction</th></tr></thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->recorded_at }}</td>
                        <td>{{ ucfirst($payment->type) }}</td>
                        <td>{{ $payment->description }}</td>
                        <td>{{ (float) $payment->support_amount > 0 ? $payment->currency.' '.$payment->support_amount : 'Not available' }}</td>
                        <td class="text-nowrap">{{ $payment->currency }} {{ $payment->amount }}</td>
                        <td>Paid</td>
                        <td>{{ $payment->transaction_id ?: 'Not available' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links('pagination::bootstrap-4') }}
</div>
@endsection
