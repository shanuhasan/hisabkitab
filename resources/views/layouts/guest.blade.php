<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'UdhaarHisab')) — Indian Shopkeeper Udhaar SaaS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <!-- Guest Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}">
                <i class="bi bi-journal-bookmark-fill text-primary me-2 fs-4"></i>
                <span>Udhaar<span class="text-primary">Hisab</span></span>
            </a>

            <div class="ms-auto d-flex align-items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm px-3">Log in</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm px-3">Start Free Trial</a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container py-4">
            @include('layouts.partials.alerts')
            @yield('content')
        </div>
    </main>

    <!-- Guest Footer -->
    <footer class="bg-white border-top py-4 mt-auto">
        <div class="container text-center text-muted small">
            <p class="mb-1">&copy; {{ date('Y') }} UdhaarHisab SaaS. Built for Indian Small Businesses & Kirana Stores.</p>
            <p class="mb-0 text-secondary">Simple, Secure & Reliable Digital Credit/Udhaar Management.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

