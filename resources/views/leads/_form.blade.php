@php
    $lead = $lead ?? null;
    $customer = $selectedCustomer ?? $lead?->customer;
    $old = $old ?? [];
    $val = fn (string $key, $default = null) => old($key, $old[$key] ?? $default);
@endphp

@if(($duplicates ?? collect())->isNotEmpty())
    <div class="alert alert-warning">
        <strong>Possible duplicates</strong>
        <ul class="mb-2">
            @foreach ($duplicates as $dup)
                <li>
                    <a href="{{ route('leads.show', $dup) }}">{{ $dup->reference }}</a>
                    — {{ $dup->customer?->full_name }} ({{ $dup->customer?->phone }}) · {{ $dup->statusLabel() }}
                </li>
            @endforeach
        </ul>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="acknowledge_duplicates" value="1" id="acknowledge_duplicates" @checked(old('acknowledge_duplicates'))>
            <label class="form-check-label" for="acknowledge_duplicates">I have reviewed possible duplicates and want to continue</label>
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Link existing customer (optional)</label>
        <select name="customer_id" class="form-select">
            <option value="">New customer from fields below</option>
            @foreach ($customers as $c)
                <option value="{{ $c->id }}" @selected((string) $val('customer_id', $customer?->id) === (string) $c->id)>
                    {{ $c->full_name }} — {{ $c->phone }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">First name *</label>
        <input type="text" name="first_name" class="form-control" value="{{ $val('first_name', $customer?->first_name) }}" required>
        @error('first_name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Last name</label>
        <input type="text" name="last_name" class="form-control" value="{{ $val('last_name', $customer?->last_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">—</option>
            @foreach ($genders as $key => $label)
                <option value="{{ $key }}" @selected($val('gender', $customer?->gender) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Phone *</label>
        <input type="text" name="phone" class="form-control" value="{{ $val('phone', $customer?->phone) }}" required>
        @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Alt phone</label>
        <input type="text" name="phone_alt" class="form-control" value="{{ $val('phone_alt', $customer?->phone_alt) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ $val('email', $customer?->email) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">County</label>
        <select name="county_id" class="form-select">
            <option value="">—</option>
            @foreach ($counties as $county)
                <option value="{{ $county->id }}" @selected((string) $val('county_id', $lead?->county_id ?? $customer?->county_id) === (string) $county->id)>{{ $county->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Town</label>
        <input type="text" name="town" class="form-control" value="{{ $val('town', $lead?->town ?? $customer?->town) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Product</label>
        <select name="product_id" class="form-select">
            <option value="">—</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) $val('product_id', $lead?->product_id) === (string) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Source</label>
        <select name="lead_source_id" class="form-select">
            <option value="">—</option>
            @foreach ($sources as $source)
                <option value="{{ $source->id }}" @selected((string) $val('lead_source_id', $lead?->lead_source_id) === (string) $source->id)>{{ $source->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Campaign</label>
        <input type="text" name="campaign" class="form-control" value="{{ $val('campaign', $lead?->campaign) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Priority *</label>
        <select name="priority" class="form-select" required>
            @foreach ($priorities as $key => $label)
                <option value="{{ $key }}" @selected($val('priority', $lead?->priority ?? 'medium') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected($val('status', $lead?->status ?? 'new') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Follow-up</label>
        <input type="datetime-local" name="follow_up_at" class="form-control"
               value="{{ $val('follow_up_at', optional($lead?->follow_up_at)->format('Y-m-d\\TH:i')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Assignee</label>
        <select name="assigned_to" class="form-select">
            <option value="">Unassigned</option>
            @foreach ($agents as $agent)
                <option value="{{ $agent->id }}" @selected((string) $val('assigned_to', $lead?->assigned_to) === (string) $agent->id)>{{ $agent->name }}</option>
            @endforeach
        </select>
    </div>

    @unless($lead)
        <div class="col-md-4">
            <label class="form-label">Auto-assign strategy</label>
            <select name="assignment_strategy" class="form-select">
                <option value="">None (use assignee above)</option>
                <option value="round_robin" @selected($val('assignment_strategy') === 'round_robin')>Round-robin</option>
                <option value="product" @selected($val('assignment_strategy') === 'product')>By product</option>
                <option value="region" @selected($val('assignment_strategy') === 'region')>By region/county</option>
                <option value="team" @selected($val('assignment_strategy') === 'team')>By team</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Team (if strategy = team)</label>
            <select name="team_id" class="form-select">
                <option value="">—</option>
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}" @selected((string) $val('team_id') === (string) $team->id)>{{ $team->name }}</option>
                @endforeach
            </select>
        </div>
    @endunless

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="2" class="form-control">{{ $val('description', $lead?->description) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="2" class="form-control">{{ $val('notes', $lead?->notes) }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="consent_contact" value="1" id="consent_contact"
                   @checked($val('consent_contact', $lead?->consent_contact ?? true))>
            <label class="form-check-label" for="consent_contact">Contact consent captured (Kenya DPA)</label>
        </div>
    </div>
</div>
