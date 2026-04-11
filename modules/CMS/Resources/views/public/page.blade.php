@extends('layouts.frontend')

@section('title', $page->title)

@section('content')
<div class="container py-5">
    <article>
        <header class="mb-4">
            <h1 class="display-4">{{ $page->title }}</h1>
            @if($page->excerpt)
                <p class="lead text-muted">{{ $page->excerpt }}</p>
            @endif
            @if($page->featured_image)
                <img src="{{ asset('storage/' . $page->featured_image) }}" 
                     alt="{{ $page->title }}" 
                     class="img-fluid rounded mt-3">
            @endif
        </header>

        <div class="content">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection

