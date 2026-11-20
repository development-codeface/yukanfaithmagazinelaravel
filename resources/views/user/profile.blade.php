@extends('layouts.frontend')
@section('title', 'My Profile')
@push('styles')
<style>
.profile-page { background: #faf8f6; min-height: 55vh; padding: 60px 0 80px; color: #292120; }
.profile-wrap { max-width: 1100px; }
.profile-heading { margin-bottom: 30px; }
.profile-heading .eyebrow { color: #8e2d26; font-size: .75rem; letter-spacing: .12em; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
.profile-heading h1 { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 700; margin-bottom: 8px; }
.profile-heading p { color: #746663; margin: 0; }
.profile-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; align-items: start; }
.profile-card { background: #fff; border: 1px solid #ebdfda; border-radius: 16px; padding: 28px; box-shadow: 0 10px 35px rgba(66, 37, 30, .05); }
.profile-card h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; }
.profile-intro { color: #746663; font-size: .92rem; margin-bottom: 24px; }
.profile-card label { display: block; font-size: .88rem; font-weight: 600; margin-bottom: 7px; }
.profile-card .form-control { min-height: 48px; border-color: #ded4cf; border-radius: 8px; }
.profile-card .form-control:focus { border-color: #a55a50; box-shadow: 0 0 0 .2rem rgba(142,45,38,.12); }
.profile-card .form-control[readonly] { background: #f8f5f3; }
.profile-button { background: #8e2d26; border: 1px solid #8e2d26; color: #fff; border-radius: 8px; padding: 12px 20px; font-weight: 700; }
.profile-button:hover { background: #75251f; color: #fff; }
@media(max-width: 767px) { .profile-page { padding: 40px 0 60px; } .profile-grid { grid-template-columns: 1fr; } .profile-card { padding: 22px; } }
</style>
@endpush
@section('content')
<div class="profile-page"><div class="container profile-wrap">
    <header class="profile-heading"><p class="eyebrow">My account</p><h1>My Profile</h1><p>Manage your personal details and password.</p></header>
    <div class="profile-grid">
        <section class="profile-card" aria-labelledby="profile-details-heading">
            <h2 id="profile-details-heading">Personal details</h2>
            <p class="profile-intro">Update the name shown on your account.</p>
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if($errors->profile->any())<div class="alert alert-danger" role="alert">{{ $errors->profile->first() }}</div>@endif
            <form method="POST" action="{{ route('user.profile.update') }}">
                @csrf @method('PUT')
                <div class="mb-3"><label for="profile-name">Name</label><input id="profile-name" class="form-control" name="name" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required></div>
                <div class="mb-4"><label for="profile-email">Email</label><input id="profile-email" class="form-control" value="{{ $user->email }}" readonly></div>
                <button class="profile-button" type="submit">Save profile</button>
            </form>
        </section>
        <section class="profile-card" aria-labelledby="profile-password-heading">
            <h2 id="profile-password-heading">Change password</h2>
            <p class="profile-intro">Enter your current password and choose a new one.</p>
            @if(session('password_success'))<div class="alert alert-success" role="status">{{ session('password_success') }}</div>@endif
            @if($errors->password->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->password->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('user.profile.password.update') }}">
                @csrf @method('PUT')
                <div class="mb-3"><label for="current-password">Current password</label><input id="current-password" class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
                <div class="mb-3"><label for="new-password">New password</label><input id="new-password" class="form-control" type="password" name="password" autocomplete="new-password" minlength="8" required><small class="text-muted">At least 8 characters.</small></div>
                <div class="mb-4"><label for="confirm-password">Confirm new password</label><input id="confirm-password" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></div>
                <button class="profile-button" type="submit">Update password</button>
            </form>
        </section>
    </div>
</div></div>
@endsection
