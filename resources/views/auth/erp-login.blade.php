@extends('layouts.auth')

@section('title', 'Accès ERP')

@section('content')
<div class="min-h-screen flex flex-col bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    {{-- Header --}}
    <header class="bg-slate-800/50 backdrop-blur border-b border-slate-700">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-halved text-blue-400 text-xl"></i>
                <span class="font-display font-bold text-xl tracking-wide text-white">
                    NIKA DIGITAL HUB
                </span>
            </div>
            
            <a href="{{ route('auth.hub') }}" class="text-sm text-gray-400 hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </a>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            {{-- Card --}}
            <div class="bg-slate-800/50 backdrop-blur border border-slate-700 rounded-2xl shadow-2xl p-8 md:p-10">
                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-2xl text-blue-400"></i>
                    </div>
                    <h1 class="font-display text-3xl font-bold text-white mb-2">
                        Accès ERP
                    </h1>
                    <p class="text-gray-400">
                        Espace réservé aux membres de l'équipe
                    </p>
                    
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-blue-500/10 border border-blue-500/20 rounded-full">
                        <i class="fas fa-shield-check text-blue-400 text-sm"></i>
                        <span class="text-sm text-blue-300 font-medium">Connexion sécurisée</span>
                    </div>
                </div>

                {{-- Errors --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                            <div class="flex-1">
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm text-red-300">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                            Adresse email professionnelle
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-500"></i>
                            </div>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}"
                                   required 
                                   autofocus
                                   class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500 @error('email') border-red-500 @enderror"
                                   placeholder="nom@nikahub.com">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                            Mot de passe
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-500"></i>
                            </div>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required
                                   class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-gray-500 @error('password') border-red-500 @enderror"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    {{-- Remember --}}
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="remember" 
                               id="remember"
                               class="w-4 h-4 text-blue-500 bg-slate-900 border-slate-600 rounded focus:ring-blue-500">
                        <label for="remember" class="ml-2 text-sm text-gray-400">
                            Rester connecté sur cet appareil
                        </label>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" 
                            class="w-full px-6 py-3 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-all duration-300 shadow-lg hover:shadow-blue-500/50 font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter à l'ERP
                    </button>
                </form>

                {{-- Security Notice --}}
                <div class="mt-8 p-4 bg-slate-900/50 border border-slate-700 rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-sm text-gray-400 leading-relaxed">
                                Cet espace est réservé aux administrateurs et membres de l'équipe. 
                                Toutes les connexions sont enregistrées et surveillées.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Back to Hub --}}
            <div class="mt-6 text-center">
                <a href="{{ route('auth.hub') }}" class="text-sm text-gray-500 hover:text-gray-300 transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour à l'espace membre
                </a>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-800 py-6">
        <div class="max-w-6xl mx-auto px-4 text-center text-gray-500 text-sm">
            <p>&copy; {{ date('Y') }} NIKA DIGITAL HUB - Système ERP sécurisé</p>
        </div>
    </footer>
</div>
@endsection
