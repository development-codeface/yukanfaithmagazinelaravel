@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        <h4>User Subscription List</h4>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Subscription Status</th>
                        <th>Payment Status</th>
                        <th>Amount Paid</th>
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
                            </td>

                            <td>{{ $subscription->start_date }}</td>
                            <td>{{ $subscription->end_date }}</td>

                            {{-- Subscription Status --}}
                            <td>
                                @if(in_array($subscription->status, [1, 'active']))
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

                            <td>
                                @if(!is_null($subscription->amount_paid))
                                    {{ number_format($subscription->amount_paid, 2) }}
                                @else
                                    -
                                @endif
                            </td>

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
                            <td colspan="9" class="text-center">
                                No Subscriptions Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
</div>

@endsection
