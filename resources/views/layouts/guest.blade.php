<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Phoenix Capital CRM') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/phoenix.css') }}" rel="stylesheet">
</head>
<body class="pc-guest-body">
    <div class="pc-login-panel">
        <div class="pc-brand-mark">
            <div class="mark">PC</div>
            <div>
                <p class="title">Phoenix Capital</p>
                <p class="subtitle">CRM · Secure staff access</p>
            </div>
        </div>
        {{ $slot }}
    </div>
</body>
</html>
