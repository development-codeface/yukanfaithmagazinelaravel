@extends('layouts.frontend')
@section('title', $magazineIssue->title)
@section('content')
<div class="container py-5">
    <h1>{{ $magazineIssue->title }}</h1>
    <p>{{ $magazineIssue->description }}</p>
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if(auth()->check() && auth()->user()->canReadMagazine($magazineIssue))
        <a class="btn btn-dark" href="{{ route('user.magazine.reader', $magazineIssue) }}">Read More</a>
        <p class="mt-2">This magazine is included in your access.</p>
    @elseif(auth()->guest())
        <a class="btn btn-dark" href="{{ route('user.magazine.buy', $magazineIssue) }}">Buy Now</a>
        <p class="mt-2">Sign in or register to check your subscription and purchase options.</p>
    @else
        <a class="btn btn-outline-dark mt-3" href="{{ route('user.subscriptions') }}">Choose a subscription package</a>
    @endif
    @if(auth()->check() && $magazineIssue->prices())
        <div class="mt-4">@include('partials.magazine-payment')</div>
    @endif
    <h2 class="mt-5">In this issue</h2>
    @forelse($magazineIssue->articles as $article)
        <p><a href="{{ route('frontend.article', $article) }}">{{ $article->title }}</a></p>
    @empty
        <p>Article details will be added soon.</p>
    @endforelse
</div>
@endsection
