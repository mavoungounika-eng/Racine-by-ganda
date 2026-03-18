@extends('layouts.frontend')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? '')

@section('content')
<div class="container py-8 max-w-4xl mx-auto px-4">
    <nav class="mb-6">
        <ol class="flex text-sm text-gray-500">
            <li><a href="/" class="hover:text-primary">Accueil</a></li>
            <li class="mx-2">/</li>
            <li class="font-medium text-gray-900">{{ $page->title }}</li>
        </ol>
    </nav>

    <h1 class="text-4xl font-bold text-gray-900 mb-8 border-b pb-4">
        {{ $page->title }}
    </h1>

    <div class="cms-content prose prose-lg max-w-none prose-indigo">
        {!! $page->content !!}
    </div>

    @if($page->updated_at)
    <div class="mt-12 pt-6 border-t text-sm text-gray-400 italic">
        Dernière mise à jour le {{ $page->updated_at->format('d/m/Y') }}
    </div>
    @endif
</div>
@endsection
