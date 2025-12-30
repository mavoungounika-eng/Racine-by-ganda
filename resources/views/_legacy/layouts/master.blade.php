<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RACINE BY GANDA - Mode Africaine Authentique')</title>

    {{-- CSS & JS compilés avec Vite --}}
    {{-- CSS & JS compilés avec Vite --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @stack('styles')
</head>
<body class="bg-white text-gray-900">
    <!-- Header -->
    <!-- Header -->
    <header class="bg-racine-black text-white shadow-lg">
        <div class="container py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <h1 class="h3 font-weight-bold text-racine-orange mb-0">RACINE BY GANDA</h1>
                </div>
                <nav class="d-none d-md-flex">
                    <a href="/" class="text-white text-decoration-none mr-4 hover-orange">Accueil</a>
                    <a href="/boutique" class="text-white text-decoration-none mr-4 hover-orange">Boutique</a>
                    <a href="/showroom" class="text-white text-decoration-none mr-4 hover-orange">Showroom</a>
                    <a href="/contact" class="text-white text-decoration-none hover-orange">Contact</a>
                </nav>
                <div class="d-flex align-items-center">
                    <a href="/panier" class="text-white mr-3 hover-orange">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <a href="/login" class="text-white hover-orange">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <!-- Footer -->
    <footer class="bg-racine-black text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h3 class="h5 font-weight-bold text-racine-orange mb-3">RACINE BY GANDA</h3>
                    <p class="text-gray-300">Mode africaine authentique et contemporaine</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h4 class="font-weight-bold mb-3">Liens Rapides</h4>
                    <ul class="list-unstyled text-gray-300">
                        <li><a href="/boutique" class="text-white text-decoration-none hover-orange">Boutique</a></li>
                        <li><a href="/a-propos-de-nous" class="text-white text-decoration-none hover-orange">À Propos</a></li>
                        <li><a href="/contact" class="text-white text-decoration-none hover-orange">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4 class="font-weight-bold mb-3">Nous Suivre</h4>
                    <div class="d-flex">
                        <a href="#" class="text-white mr-3 h4 hover-orange"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white mr-3 h4 hover-orange"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white h4 hover-orange"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-top border-secondary mt-4 pt-4 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} RACINE BY GANDA. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    {{-- SCROLL TO TOP BUTTON --}}
    @include('components.scroll-to-top')
    
    @stack('scripts')
</body>
</html>
