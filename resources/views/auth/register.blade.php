@extends('layouts.auth')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body">
            <div class="auth-header">
                <div class="auth-brand">ShiftGen</div>
                <div class="auth-subtitle">Buat akun baru</div>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Nama</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="form-input {{ $errors->has('name') ? 'input-error' : '' }}" autocomplete="name" />
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-input {{ $errors->has('email') ? 'input-error' : '' }}" autocomplete="username" />
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" required class="form-input {{ $errors->has('password') ? 'input-error' : '' }}" autocomplete="new-password" />
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required class="form-input" autocomplete="new-password" />
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Daftar</button>
                </div>
            </form>

            <div class="auth-links">
                <a href="{{ route('login') }}">Sudah punya akun? Masuk</a>
            </div>
        </div>
    </div>
</div>
@endsection
