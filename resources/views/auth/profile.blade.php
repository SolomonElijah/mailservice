@extends('layouts.app')

@section('title', 'My Profile')

@section('content')

<div class="page-header">
    <h1 class="page-title">My Profile</h1>
    <p class="page-subtitle">Manage your account details and password</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:860px;">

    {{-- Profile Info --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👤</div>
            <div>
                <h3>Account Details</h3>
                <p>Update your name, email and company</p>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom:20px;">✅ {{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="{{ $errors->has('name') ? 'invalid' : '' }}">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="{{ $errors->has('email') ? 'invalid' : '' }}">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" value="{{ old('company', $user->company) }}"
                        placeholder="Your company name">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" disabled
                        style="background:#f5f2ed;color:var(--muted);cursor:not-allowed;">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    {{-- Change Password --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🔒</div>
            <div>
                <h3>Change Password</h3>
                <p>Keep your account secure</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="••••••••"
                        class="{{ $errors->has('current_password') ? 'invalid' : '' }}">
                    @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Min. 8 characters"
                        class="{{ $errors->has('password') ? 'invalid' : '' }}">
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="password_confirmation" placeholder="Repeat password">
                </div>

                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>

</div>

@endsection
