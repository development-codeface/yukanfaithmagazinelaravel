@extends('layouts.frontend')

@section('title', 'Subscriptions')

@section('content')
<div class="container my-5">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="subscription-hero mb-4">
        <div>
            <p class="text-uppercase text-muted fw-semibold small mb-2">Premium reading</p>
            <h1 class="fw-bold mb-2">Choose your subscription</h1>
            <p class="text-muted mb-0">Unlock paid articles and the web magazine reader with one active plan.</p>
        </div>

        @if($subscription)
            <div class="active-plan">
                <span class="text-muted small">Current plan</span>
                <strong>{{ $subscription->plan->name ?? 'Active Plan' }}</strong>
                <span class="small">
                    Valid until {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') : 'No expiry date' }}
                </span>
            </div>
        @endif
    </div>

    <div class="row g-4">
        @forelse($plans as $plan)
            @php
                $features = collect(preg_split('/\r\n|\r|\n/', $plan->features ?? ''))
                    ->map(fn ($feature) => trim($feature))
                    ->filter();
                $isCurrent = $subscription && $subscription->plan_id === $plan->id;
            @endphp

            <div class="col-lg-4 col-md-6">
                <div class="plan-card h-100 {{ $isCurrent ? 'is-current' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 class="h5 fw-bold mb-1">{{ $plan->name }}</h3>
                            <p class="text-muted small mb-0">{{ $plan->description }}</p>
                        </div>

                        @if($isCurrent)
                            <span class="badge text-bg-success">Active</span>
                        @endif
                    </div>

                    <div class="plan-price mb-3">
                        INR {{ number_format((float) $plan->price, 2) }}
                        <span>/ {{ $plan->duration }} {{ rtrim($plan->duration_type, 's') }}{{ (int) $plan->duration === 1 ? '' : 's' }}</span>
                    </div>

                    @if($features->isNotEmpty())
                        <ul class="plan-features">
                            @foreach($features as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($isCurrent)
                        <button class="btn btn-outline-success w-100 mt-auto" disabled>Current Plan</button>
                    @else
                        <form method="POST" action="{{ route('user.subscribe', $plan) }}" class="mt-auto">
                            @csrf
                            <button class="btn btn-dark w-100">Select Plan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <h2 class="h5 fw-bold">No active plans yet</h2>
                    <p class="text-muted mb-0">Please add plans from the admin subscription plan section.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
    .subscription-hero {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: stretch;
        padding: 28px;
        border: 1px solid #e7e0dc;
        background: #fff;
    }

    .active-plan,
    .plan-card,
    .empty-state {
        border: 1px solid #e7e0dc;
        background: #fff;
    }

    .active-plan {
        min-width: 260px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
    }

    .plan-card {
        padding: 24px;
        display: flex;
        flex-direction: column;
        border-radius: 8px;
    }

    .plan-card.is-current {
        border-color: #198754;
        box-shadow: 0 8px 24px rgba(25, 135, 84, 0.12);
    }

    .plan-price {
        font-size: 28px;
        font-weight: 800;
    }

    .plan-price span {
        font-size: 14px;
        color: #6c757d;
        font-weight: 500;
    }

    .plan-features {
        padding-left: 18px;
        margin-bottom: 24px;
        color: #495057;
        line-height: 1.8;
    }

    .empty-state {
        padding: 32px;
        border-radius: 8px;
    }

    @media (max-width: 767.98px) {
        .subscription-hero {
            flex-direction: column;
            padding: 20px;
        }

        .active-plan {
            min-width: 0;
        }
    }
</style>
@endpush
