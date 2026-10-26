{{-- Shared application layout and asset loading. --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | WebAdmin</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('laravel-icon.svg') }}">

    <link rel="stylesheet"
          href="{{ asset('css/layout.css') }}?v=4">

    <link rel="stylesheet"
          href="{{ asset('css/sidebar.css') }}?v=13">
    <link rel="stylesheet"
          href="{{ asset('css/status-toggle.css') }}?v=1">

    @stack('styles')

    <link rel="stylesheet"
          href="{{ asset('css/form-icons.css') }}?v=2">
    <link rel="stylesheet"
          href="{{ asset('css/button-system.css') }}?v=3">
    <link rel="stylesheet"
          href="{{ asset('css/delete-confirmation.css') }}?v=1">
</head>

<body>

<div class="wa-app">

    <x-sidebar />

    <div class="wa-overlay" id="sidebarOverlay"></div>

    <div class="wa-main">

        <x-topbar />

        <main class="wa-content">
            @yield('content')
        </main>

    </div>

</div>

<x-delete-confirmation-modal />

<script src="{{ asset('js/sidebar.js') }}?v=12" defer></script>
<script src="{{ asset('js/delete-confirmation.js') }}?v=2" defer></script>
<script src="{{ asset('js/status-toggle.js') }}?v=1" defer></script>

@stack('scripts')

</body>
</html>
