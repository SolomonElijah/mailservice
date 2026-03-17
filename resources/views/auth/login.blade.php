@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')

<div class="auth-card-header">
    <h1>Welcome back 👋</h1>
    <p>Don't have an account? <a href="{{ route('register') }}">Create one free</a></p>
</div>

<form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <div class="form-group">
        <label>Email Address <span class="req">*</span></label>
        <input type="email" name="email"
            placeholder="you@example.com"
            value="{{ old('email') }}"
            autocomplete="email"
            inputmode="email"
            class="{{ $errors->has('email') ? 'invalid' : '' }}">
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label>Password <span class="req">*</span></label>
        <div class="input-wrap">
            <input type="password" name="password" id="password"
                placeholder="••••••••"
                autocomplete="current-password"
                class="{{ $errors->has('password') ? 'invalid' : '' }}">
            <button type="button" class="pwd-toggle" onclick="togglePassword('password', this)">👁</button>
        </div>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-row-inline">
        <label>
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}
                style="width:15px;height:15px;accent-color:var(--gold);">
            Remember me
        </label>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary">
        Sign In →
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
