<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'HisabKitab')) — Digital Udhaar Khata</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Compiled Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
    <div class="hk-wrapper">
        <!-- Reusable Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Mobile Backdrop -->
        <div class="hk-backdrop" id="hkBackdrop"></div>

        <!-- Main Content Wrapper -->
        <div class="hk-content">
            <!-- Reusable Top Header -->
            @include('layouts.partials.header')

            <!-- Main Content Area -->
            <main class="hk-main">
                <div class="container-fluid px-0">
                    <!-- Flash Alerts Component -->
                    @include('layouts.partials.alerts')

                    <!-- Page Header / Breadcrumbs -->
                    @hasSection('header')
                        <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h1 class="h3 fw-bold text-slate-800 mb-1">@yield('header')</h1>
                                @hasSection('subheader')
                                    <p class="text-muted small mb-0">@yield('subheader')</p>
                                @endif
                            </div>
                            <div>
                                @yield('header-actions')
                            </div>
                        </div>
                    @endif

                    <!-- Content Injected Here -->
                    @yield('content')
                </div>
            </main>

            <!-- Reusable Footer -->
            @include('layouts.partials.footer')
        </div>
    </div>

    @stack('scripts')
</body>
</html>

