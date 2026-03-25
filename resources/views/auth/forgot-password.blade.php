@extends('layouts.auth')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body">
            <div class="auth-header">
                <div class="auth-brand">ShiftGen</div>
                <div class="auth-subtitle">Kirim tautan reset password</div>
            </div>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input {{ $errors->has('email') ? 'input-error' : '' }}" autocomplete="username" />
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Kirim Link</button>
                </div>
            </form>

            <div class="auth-links">
                <a href="{{ route('login') }}">Kembali ke login</a>
            </div>
        </div>
    </div>
</div>
@endsection
