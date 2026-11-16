<div class="mb-3">
    <label for="name" class="form-label">Full name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $user?->name) }}" required class="form-control @error('name') is-invalid @enderror">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required class="form-control @error('email') is-invalid @enderror">
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="role" class="form-label">Role</label>
    <select id="role" name="role" required class="form-select @error('role') is-invalid @enderror">
        <option value="">Select role</option>
        @foreach ($roles as $role)
            <option value="{{ $role->name }}" @selected(old('role', $user?->roles->first()?->name) === $role->name)>{{ $role->name }}</option>
        @endforeach
    </select>
    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="password" class="form-label">{{ $user ? 'New password (optional)' : 'Password' }}</label>
    <input id="password" type="password" name="password" {{ $user ? '' : 'required' }} autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="password_confirmation" class="form-label">Confirm password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" {{ $user ? '' : 'required' }} autocomplete="new-password" class="form-control">
</div>

<div class="mb-4 form-check">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $user?->is_active ?? true))>
    <label class="form-check-label" for="is_active">Active account</label>
</div>
