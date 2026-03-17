@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')

<div class="auth-card-header">
    <h1>New password 🔒</h1>
    <p>Set a strong new password for your account.</p>
</div>

<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group">
        <label>Email Address <span class="req">*</span></label>
        <input type="email" name="email"
            value="{{ old('email', $email) }}"
            placeholder="you@example.com"
            inputmode="email"
            class="{{ $errors->has('email') ? 'invalid' : '' }}">
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label>New Password <span class="req">*</span></label>
        <div class="input-wrap">
            <input type="password" name="password" id="password"
                placeholder="Min. 8 characters"
                class="{{ $errors->has('password') ? 'invalid' : '' }}">
            <button type="button" class="pwd-toggle" onclick="togglePassword('password', this)">👁</button>
        </div>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <div class="input-wrap">
            <input type="password" name="password_confirmation" id="password_confirmation"
                placeholder="Repeat password">
            <button type="button" class="pwd-toggle" onclick="togglePassword('password_confirmation', this)">👁</button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        Reset Password →
    </button>

</form>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}
</script>

@endsection
