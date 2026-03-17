@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')

<div class="auth-card-header">
    <h1>Create account ✨</h1>
    <p>Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</div>

<form method="POST" action="{{ route('register.submit') }}">
    @csrf

    <div class="form-group">
        <label>Full Name <span class="req">*</span></label>
        <input type="text" name="name"
            placeholder="John Doe"
            value="{{ old('name') }}"
            autocomplete="name"
            class="{{ $errors->has('name') ? 'invalid' : '' }}">
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
    </div>

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
        <label>Company <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
        <input type="text" name="company"
            placeholder="Your company name"
            value="{{ old('company') }}"
            autocomplete="organization">
        @error('company') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label>Password <span class="req">*</span></label>
        <div class="input-wrap">
            <input type="password" name="password" id="password"
                placeholder="Min. 8 characters"
                autocomplete="new-password"
                class="{{ $errors->has('password') ? 'invalid' : '' }}">
            <button type="button" class="pwd-toggle" onclick="togglePassword('password', this)">👁</button>
        </div>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <div class="input-wrap">
            <input type="password" name="password_confirmation" id="password_confirmation"
                placeholder="Repeat password"
                autocomplete="new-password"
                class="{{ $errors->has('password_confirmation') ? 'invalid' : '' }}">
            <button type="button" class="pwd-toggle" onclick="togglePassword('password_confirmation', this)">👁</button>
        </div>
    </div>

    <div class="checkbox-group">
        <input type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }}>
        <label for="terms">
            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
        </label>
    </div>
    @error('terms') <p class="field-error" style="margin-top:-12px;margin-bottom:14px;">{{ $message }}</p> @enderror

    <button type="submit" class="btn btn-primary">
        Create Account →
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
