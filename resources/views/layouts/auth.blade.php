<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Connexion') - RACINE BY GANDA</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Aileron:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    {{-- Bootstrap 4 --}}
    <link rel="stylesheet" href="{{ asset('racine/css/bootstrap.min.css') }}">
    
    {{-- RACINE Design System --}}
    <link rel="stylesheet" href="{{ asset('css/racine-variables.css') }}">
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #FFF8F0 0%, #F5E6D3 100%);
            min-height: 100vh;
            font-family: var(--font-body);
        }
        
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .auth-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo img {
            height: 60px;
            margin-bottom: 1rem;
        }
        
        .auth-title {
            font-family: var(--font-heading);
            font-size: var(--font-size-3xl);
            color: var(--racine-black);
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            text-align: center;
            color: #666;
            font-size: var(--font-size-sm);
            margin-bottom: 2rem;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <img src="{{ asset('images/logo-racine.png') }}" alt="RACINE BY GANDA">
                <h1 class="auth-title">RACINE BY GANDA</h1>
                <p class="auth-subtitle">@yield('auth_subtitle', 'Connexion à votre espace')</p>
            </div>
            @yield('content')
        </div>
    </div>
    
    <script src="{{ asset('racine/js/jquery.min.js') }}"></script>
    <script src="{{ asset('racine/js/bootstrap.min.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
