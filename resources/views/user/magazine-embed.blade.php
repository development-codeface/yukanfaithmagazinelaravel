@extends('layouts.frontend')

@section('title', $magazineIssue->title)

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <a href="{{ route('user.subscriptions') }}" class="btn btn-outline-dark">Back to My Magazines</a>
        <h1 class="h4 mb-0">{{ $magazineIssue->title }}</h1>
    </div>
    <iframe
        src="{{ $magazineIssue->pdf_url }}"
        title="{{ $magazineIssue->title }} magazine reader"
        width="100%"
        height="800"
        scrolling="no"
        allow="clipboard-write; autoplay; fullscreen"
        allowfullscreen
        class="publuuflip"
        style="display: block; border: 0; height: 85vh; height: 85dvh;"
    ></iframe>
</div>
@endsection
