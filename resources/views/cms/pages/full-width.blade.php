@extends('layouts.frontend')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? '')

@section('content')
<div class="w-full">
    <div class="relative h-64 bg-gray-900 flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-primary/50 to-secondary/50 mix-blend-multiply opacity-60"></div>
        <h1 class="relative text-4xl md:text-5xl font-extrabold text-white text-center px-4">
            {{ $page->title }}
        </h1>
    </div>

    <div class="py-12 px-4 md:px-8 lg:px-16">
        <div class="cms-content prose prose-xl max-w-none prose-indigo">
            {!! $page->content !!}
        </div>
    </div>
</div>
@endsection
