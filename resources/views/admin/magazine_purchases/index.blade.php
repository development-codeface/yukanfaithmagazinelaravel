@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header"><h4>Magazine purchases</h4></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Customer</th><th>Magazine</th><th>Format</th><th>Amount paid</th><th>Delivery address</th><th>Notification</th></tr></thead>
            <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ $purchase->user?->name }}<br>{{ $purchase->user?->email }}</td>
                    <td>{{ $purchase->magazine?->title ?? 'Deleted magazine' }}</td>
                    <td>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$purchase->delivery_type] }}</td>
                    <td>INR {{ number_format((float) $purchase->amount_paid, 2) }}</td>
                    <td>@foreach($purchase->delivery_address ?? [] as $value)@if($value)<div>{{ $value }}</div>@endif @endforeach</td>
                    <td>
                        @if($purchase->delivery_type !== 'digital' && !$purchase->print_acknowledged_at)
                            <span class="badge badge-warning">New print purchase</span>
                            <form method="POST" action="{{ route('admin.magazine-purchases.acknowledge', $purchase) }}">
                                @csrf @method('PATCH')<button class="btn btn-sm btn-primary" type="submit">Acknowledge</button>
                            </form>
                        @elseif($purchase->print_acknowledged_at) Acknowledged {{ $purchase->print_acknowledged_at }} @else Digital access @endif
                    </td>
                </tr>
            @empty <tr><td colspan="7">No magazine purchases yet.</td></tr> @endforelse
            </tbody>
        </table></div>
        {{ $purchases->links() }}
    </div>
</div>
@endsection
