@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        <h4>User Subscription List</h4>
    </div>

    <div class="card-body">
        @include('admin.subscriptions.filters')
        <p class="text-muted">{{ $subscriptions->total() }} subscription(s) found.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Print delivery</th>
                        <th>Delivery address</th>
                        <th>Review status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Subscription Status</th>
                        <th>Payment Status</th>
                        <th>Package Amount</th>
                        <th>Support</th>
                        <th>Original Total Paid</th>
                        <th>Upgrade Paid</th>
                        <th>Payment ID</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($subscriptions as $subscription)
                        <tr>
                            <td>{{ $subscription->id }}</td>

                            <td>
                                {{ $subscription->user->name ?? '-' }}
                                <br>
                                <small>{{ $subscription->user->email ?? '' }}</small>
                            </td>

                            <td>
                                {{ $subscription->plan->name ?? '-' }}
                                <div><small>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$subscription->delivery_type ?? 'digital'] ?? 'Digital' }}</small></div>
                            </td>

                            <td>
                                @if($subscription->print_enabled)
                                    <strong>Print included</strong>
                                @else
                                    Print not included
                                @endif
                            </td>
                            <td>
                                @if($subscription->print_enabled)
                                    @forelse(array_filter($subscription->delivery_address ?? []) as $value)
                                        <div>{{ $value }}</div>
                                    @empty
                                        <span class="text-muted">Not provided</span>
                                    @endforelse
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @if($subscription->print_enabled && $subscription->payment_status)
                                    @include('partials.delivery-review-status', ['reviewedAt' => $subscription->print_acknowledged_at, 'reviewUrl' => route('admin.subscriptions.acknowledge-print', $subscription)])
                                @else
                                    <span class="text-muted">Not applicable</span>
                                @endif
                            </td>
                            <td>{{ $subscription->start_date }}</td>
                            <td>{{ $subscription->end_date }}</td>

                            {{-- Subscription Status --}}
                            <td>
                                @if($subscription->end_date && $subscription->end_date->lt(now()))
                                    <span class="badge bg-secondary">Expired</span>
                                @elseif(in_array($subscription->status, [1, 'active']) && $subscription->payment_status && $subscription->start_date?->gt(now()))
                                    <span class="badge bg-info">Scheduled</span>
                                @elseif(in_array($subscription->status, [1, 'active']) && $subscription->payment_status && $subscription->start_date?->lte(now()))
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>

                            {{-- Payment Status --}}
                            <td>
                                @if($subscription->payment_status == 1)
                                    <span class="badge bg-success">Success</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>

                            <td>{{ number_format(max(0, (float) $subscription->amount_paid - (float) $subscription->support_amount), 2) }}</td>
                            <td>{{ (float) $subscription->support_amount > 0 ? number_format((float) $subscription->support_amount, 2) : 'Not available' }}</td>
                            <td>
                                @if(!is_null($subscription->amount_paid))
                                    {{ number_format($subscription->amount_paid, 2) }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>{{ $subscription->upgrade_amount_minor ? number_format($subscription->upgrade_amount_minor / 100, 2) : 'Not available' }}</td>
                            <td>
                                @if($subscription->transaction_id)
                                    <small class="text-break">{{ $subscription->transaction_id }}</small>
                                @else
                                    -
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center">
                                No Subscriptions Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        {{ $subscriptions->links('pagination::bootstrap-4') }}
    </div>
</div>

@endsection
