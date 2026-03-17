@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')

<div class="auth-card-header">
    <h1>Reset password 🔑</h1>
    <p>Remember it? <a href="{{ route('login') }}">Back to sign in</a></p>
</div>

<p style="font-size:14px;color:var(--muted);margin-bottom:24px;line-height:1.6;">
    Enter your email address and we'll send you a link to reset your password.
</p>

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="form-group">
        <label>Email Address <span class="req">*</span></label>
        <input type="email" name="email"
            placeholder="you@example.com"
            value="{{ old('email') }}"
            inputmode="email"
            class="{{ $errors->has('email') ? 'invalid' : '' }}">
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="btn btn-primary">
        Send Reset Link →
    </button>

</form>

@endsection
