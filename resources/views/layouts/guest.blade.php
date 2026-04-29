<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RACINE BY GANDA')</title>
    <meta name="theme-color" content="#ED5F1E">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 4 --}}
    {{-- Bootstrap 5 via Vite --}}

    {{-- RACINE Design System --}}
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    @yield('styles')

    <style nonce="{{ csp_nonce() }}">
        body {
            font-family: 'Aileron', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .guest-header {
            background: #fff;
            border-bottom: 2px solid #ED5F1E;
            padding: 1rem 0;
        }
        .guest-header .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ED5F1E;
            text-decoration: none;
        }
        .guest-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .guest-footer {
            text-align: center;
            padding: 1rem;
            color: #6c757d;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    {{-- Header minimal --}}
    <header class="guest-header">
        <div class="container">
            <a href="{{ url('/') }}" class="brand">RACINE BY GANDA</a>
        </div>
    </header>

    {{-- Contenu principal --}}
    <main class="guest-main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    {{-- Footer minimal --}}
    <footer class="guest-footer">
        &copy; {{ date('Y') }} RACINE BY GANDA. Tous droits réservés.
    </footer>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('racine/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
