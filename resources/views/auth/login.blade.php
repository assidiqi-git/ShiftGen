@extends('layouts.auth')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body">
            <div class="auth-header">
                <div class="auth-brand">ShiftGen</div>
                <div class="auth-subtitle">Masuk untuk melanjutkan</div>
            </div>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="grid">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input {{ $errors->has('email') ? 'input-error' : '' }}" autocomplete="username" />
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" required class="form-input {{ $errors->has('password') ? 'input-error' : '' }}" autocomplete="current-password" />
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="form-group-inline">
                        <input type="checkbox" name="remember" />
                        <span class="form-label">Ingat saya</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                </div>
            </form>

            <div class="auth-links">
                <a href="{{ route('password.request') }}">Lupa password?</a>
                <span>•</span>
                <a href="{{ route('register') }}">Buat akun</a>
            </div>
        </div>
    </div>
</div>
@endsection
