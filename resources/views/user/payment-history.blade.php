@extends('layouts.frontend')
@section('title', 'Payment History')
@push('styles')
    <style>
        .history-page {
            background: #faf8f6;
            min-height: 55vh;
            padding: 60px 0 80px;
            color: #292120;
        }

        .history-heading {
            margin-bottom: 30px;
        }

        .history-heading .eyebrow {
            color: #e89145;
            font-size: .75rem;
            letter-spacing: .12em;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .history-heading h1 {
            font-size: 33px;
            font-weight: 600;
text-transform: uppercase;
            margin-bottom: 8px;
        }

        .history-heading p {
            color: #746663;
            margin: 0;
        }

        .history-card {
            background: #fff;
            border: 1px solid #ebdfda;
            border-radius: 0px;
            box-shadow: 0 10px 35px rgba(66, 37, 30, .05);
            overflow: hidden;
        }

        .history-card-header {
            padding: 22px 26px;
            border-bottom: 1px solid #eee5e1;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .history-card-header h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .history-count {
            color: #746663;
            font-size: .9rem;
        }

        .history-table {
            margin: 0;
        }

        .history-table thead th {
            background: #f8f4f2;
            color: #746663;
            font-size: .75rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            border: 0;
            padding: 15px 20px;
            white-space: nowrap;
        }

        .history-table tbody td {
            padding: 19px 20px;
            border-color: #f0eae7;
            vertical-align: middle;
        }

        .history-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .history-type {
            color: #8e2d26;
            font-size: .82rem;
            font-weight: 600;
        }

        .history-amount {
            font-weight: 700;
            white-space: nowrap;
        }

        .history-status {
            display: inline-block;
            background: #eaf5ed;
            color: #27613a;
            border-radius: 0px;
            padding: 5px 11px;
            font-size: .78rem;
            font-weight: 700;
        }

        .history-transaction {
            color: #746663;
            font-size: .85rem;
            overflow-wrap: anywhere;
            min-width: 150px;
        }

        .history-empty {
            padding: 60px 20px;
            text-align: center;
        }

        .history-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #f9efed;
            color: #e89145;
            font-size: 1.25rem;
            margin-bottom: 16px;
        }

        .history-empty h3 {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .history-empty p {
            color: #746663;
            margin: 0;
        }

        .history-pagination {
            margin-top: 24px;
        }

        @media(max-width: 767px) {
            .history-page {
                padding: 40px 0 60px;
            }

            .history-card-header {
                padding: 20px;
            }

            .history-table {
                min-width: 900px;
            }
        }
    </style>
@endpush
@section('content')
    <div class="history-page">
        <div class="container">
            <header class="history-heading">
                <p class="eyebrow">My account</p>
                <h1>Payment History</h1>
                <p>View your successful subscription, magazine, article, and apparel payments.</p>
            </header>
            <section class="history-card" aria-labelledby="history-heading">
                <div class="history-card-header">
                    <h2 id="history-heading">Your payments</h2><span class="history-count">{{ $payments->total() }}
                        {{ \Illuminate\Support\Str::plural('payment', $payments->total()) }}</span>
                </div>
                @if ($payments->count())
                    <div class="table-responsive">
                        <table class="table history-table">
                            <thead>
                                <tr>
                                    <th scope="col">Date recorded</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Description / Order</th>
                                    <th scope="col">Support</th>
                                    <th scope="col">Total paid</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td class="text-nowrap">{{ $payment->recorded_at }}</td>
                                        <td><span
                                                class="history-type">{{ ucwords(str_replace('_', ' ', $payment->type)) }}</span>
                                        </td>
                                        <td>{{ $payment->description }}</td>
                                        <td class="text-nowrap">
                                            {{ (float) $payment->support_amount > 0 ? $payment->currency . ' ' . $payment->support_amount : 'Not available' }}
                                        </td>
                                        <td class="history-amount">{{ $payment->currency }} {{ $payment->amount }}</td>
                                        <td><span class="history-status">Paid</span></td>
                                        <td class="history-transaction">{{ $payment->transaction_id ?: 'Not available' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="history-empty"><span class="history-empty-icon" aria-hidden="true"><i
                                class="fa-solid fa-receipt"></i></span>
                        <h3>No payments yet</h3>
                        <p>Your completed payments will appear here.</p>
                    </div>
                @endif
            </section>
            <div class="history-pagination">{{ $payments->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
@endsection
