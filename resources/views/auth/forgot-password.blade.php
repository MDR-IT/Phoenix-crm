<x-guest-layout>
    <h1 class="h4 mb-3">Reset password</h1>
    <p class="text-secondary small mb-4">Enter your email and we will send a password reset link.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('login') }}" class="small">Back to login</a>
            <button type="submit" class="btn btn-pc-primary">Email reset link</button>
        </div>
    </form>
</x-guest-layout>
