@extends('layouts.frontend')
@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? '')
@section('content')
<div class="container py-8 max-w-4xl mx-auto px-4">

    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => $page->title, 'url' => null],
    ]" />

    <h1 class="text-4xl font-bold text-gray-900 mb-8 border-b pb-4">
        {{ $page->title }}
    </h1>

    <div class="cms-content prose prose-lg max-w-none prose-indigo">
        @if($page->content)
            {!! $page->content !!}
        @else
            <p class="text-gray-400 italic">Contenu en cours de rédaction...</p>
        @endif
    </div>

    @if($page->updated_at)
    <div class="mt-12 pt-6 border-t text-sm text-gray-400 italic">
        Dernière mise à jour le {{ $page->updated_at->format('d/m/Y') }}
    </div>
    @endif
</div>
@endsection
