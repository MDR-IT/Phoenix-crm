<section>
    <h2 class="h5 mb-1">Delete account</h2>
    <p class="text-secondary small mb-3">Once your account is deleted, its data cannot be recovered.</p>

    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account?')">
        @csrf
        @method('delete')

        <div class="mb-3" style="max-width: 320px;">
            <label for="password" class="form-label">Confirm with password</label>
            <input id="password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Password">
            @error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-outline-danger">Delete account</button>
    </form>
</section>
