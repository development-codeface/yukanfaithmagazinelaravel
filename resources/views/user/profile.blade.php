@extends('layouts.frontend')
@section('title', 'My Profile')
@section('content')
<div class="container py-5" style="max-width: 760px">
    <h1 class="mb-4">My Profile</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('user.profile.update') }}" class="card p-4">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label" for="profile-name">Name</label><input id="profile-name" class="form-control" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required></div>
        <div class="mb-4"><label class="form-label" for="profile-email">Email</label><input id="profile-email" class="form-control" value="{{ $user->email }}" readonly></div>
        <div><button class="btn btn-dark" type="submit">Save profile</button></div>
    </form>
</div>
@endsection
