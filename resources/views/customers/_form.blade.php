@php($customer = $customer ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">First name *</label>
        <input type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" class="form-control" required>
        @error('first_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Last name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone *</label>
        <input type="text" name="phone" value="{{ old('phone', $customer?->phone) }}" class="form-control" required placeholder="07xxxxxxxx">
        @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Alt phone</label>
        <input type="text" name="phone_alt" value="{{ old('phone_alt', $customer?->phone_alt) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $customer?->email) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">—</option>
            @foreach ($genders as $key => $label)
                <option value="{{ $key }}" @selected(old('gender', $customer?->gender) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">County</label>
        <select name="county_id" class="form-select">
            <option value="">—</option>
            @foreach ($counties as $county)
                <option value="{{ $county->id }}" @selected((string) old('county_id', $customer?->county_id) === (string) $county->id)>{{ $county->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Town</label>
        <input type="text" name="town" value="{{ old('town', $customer?->town) }}" class="form-control">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="consent_marketing" value="1" id="consent_marketing" @checked(old('consent_marketing', $customer?->consent_marketing))>
            <label class="form-check-label" for="consent_marketing">Marketing / contact consent (Kenya DPA)</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $customer?->notes) }}</textarea>
    </div>
</div>
