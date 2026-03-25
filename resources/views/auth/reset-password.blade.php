@extends('layouts.auth')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body">
            <div class="auth-header">
                <div class="auth-brand">ShiftGen</div>
                <div class="auth-subtitle">Atur password baru</div>
            </div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}" />

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus class="form-input {{ $errors->has('email') ? 'input-error' : '' }}" autocomplete="username" />
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password Baru</label>
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
                    <button type="submit" class="btn btn-primary btn-block">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
