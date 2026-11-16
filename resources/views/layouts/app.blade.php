<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Phoenix Capital CRM') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/phoenix.css') }}" rel="stylesheet">
</head>
<body>
<div class="pc-shell">
    <aside class="pc-sidebar">
        <div class="brand">
            <div class="mark">PC</div>
            <div>
                <strong>Phoenix Capital</strong>
                <small>CRM</small>
            </div>
        </div>
        <nav class="pc-nav">
            <div class="nav-section">Workspace</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

            @can('users.view')
                <div class="nav-section">Administration</div>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            @endcan
            @can('roles.view')
                <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles &amp; permissions</a>
            @endcan

            <div class="nav-section">Later phases</div>
            <a href="#" class="nav-later">Leads (Phase 2)</a>
            <a href="#" class="nav-later">Call centre (Phase 5)</a>
            <a href="#" class="nav-later">Integrations (Phase 4)</a>
        </nav>
    </aside>

    <div class="pc-main">
        <header class="pc-topbar">
            <div>
                <div class="fw-semibold">{{ $header ?? 'Dashboard' }}</div>
                <div class="text-secondary small">Signed in as {{ auth()->user()->name }} · {{ auth()->user()->roles->pluck('name')->join(', ') ?: 'No role' }}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-pc-primary">Log out</button>
                </form>
            </div>
        </header>

        <main class="pc-content">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
