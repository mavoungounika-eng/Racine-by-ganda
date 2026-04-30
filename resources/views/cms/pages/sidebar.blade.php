@extends('layouts.frontend')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? '')

@section('content')
<div class="container py-8 mx-auto px-4">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content -->
        <main class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 pb-2 border-b">
                {{ $page->title }}
            </h1>

            <div class="cms-content prose prose-lg max-w-none prose-indigo">
                {!! $page->content !!}
            </div>
        </main>

        <!-- Sidebar -->
        <aside class="w-full lg:w-72">
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 shadow-sm sticky top-24">
                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    En savoir plus
                </h3>
                <nav class="space-y-2">
                    @foreach(\App\Models\Page::published()->forFooter()->get() as $footerPage)
                        <a href="{{ $footerPage->url }}" 
                           class="block py-2 px-3 rounded-lg text-gray-600 hover:bg-primary/5 hover:text-primary transition-colors {{ request()->is('pages/'.$footerPage->slug) ? 'bg-primary/10 text-primary font-medium' : '' }}">
                            {{ $footerPage->title }}
                        </a>
                    @endforeach
                </nav>
                
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold text-gray-700 mb-2">Besoin d'aide ?</h4>
                    <p class="text-sm text-gray-500 mb-4">Notre service client est disponible du lundi au vendredi.</p>
                    <a href="{{ route('frontend.contact') }}" class="btn btn-primary btn-sm w-full block text-center">
                        Nous contacter
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
