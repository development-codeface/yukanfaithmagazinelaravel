@extends('layouts.frontend')

@section('title', $event->title)

@section('content')

<div class="container my-5">

    <div class="row g-5">

        {{-- LEFT IMAGE COLUMN --}}
        <div class="col-lg-5">

            <div class="position-sticky" style="top: 100px;">

                <img src="{{ asset($event->image) }}"
                     class="img-fluid w-100 shadow-sm rounded">

                @if($event->subtitle)
                    <p class="text-muted mt-3 small">
                        {{ $event->subtitle }}
                    </p>
                @endif

            </div>

        </div>


        {{-- RIGHT CONTENT COLUMN --}}
        <div class="col-lg-7">

            <h1 class="fw-bold mb-4">
                {{ $event->title }}
            </h1>

            @if($event->subtitle)
                <p class="text-muted fs-5 mb-4">
                    {{ $event->subtitle }}
                </p>
            @endif

            @if($event->description)
                <div class="mb-5" style="line-height: 1.8; font-size: 1.1rem;">
                    {!! nl2br(e($event->description)) !!}
                </div>
            @endif

            @if($event->button_link)
                <div class="mt-5">
                    <a href="{{ $event->button_link }}"
                       target="_blank"
                       class="btn btn-dark px-5 py-3 fw-bold">
                        Register Now
                    </a>
                </div>
            @endif

            {{-- EVENT DETAILS CARD --}}
            <div class="card mt-5 border-0 bg-light">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3">Event Details</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-success">{{ $event->status ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Posted On</small>
                            <span>{{ $event->created_at->format('F d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

@endsection
