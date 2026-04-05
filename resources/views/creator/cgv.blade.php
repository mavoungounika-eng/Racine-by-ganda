@extends('layouts.guest')

@section('title', 'CGV Créateur - RACINE BY GANDA')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Conditions Générales de Vente - Créateurs
            </h1>
            <p class="text-lg text-gray-600">
                RACINE BY GANDA
            </p>
            <p class="text-sm text-gray-500 mt-2">
                Date de dernière mise à jour: 4 janvier 2026
            </p>
        </div>

        {{-- Content --}}
        <div class="bg-white shadow-lg rounded-lg p-8 prose prose-lg max-w-none">
            {!! \Illuminate\Support\Str::markdown(file_get_contents(base_path('docs/cgv-createur.md'))) !!}
        </div>

        {{-- Footer Actions --}}
        <div class="mt-8 text-center">
            <a href="{{ route('creator.register') }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à l'inscription
            </a>
        </div>
    </div>
</div>
@endsection
