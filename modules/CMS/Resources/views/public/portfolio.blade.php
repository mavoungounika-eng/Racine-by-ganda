@extends('layouts.frontend')

@section('title', $portfolio->title)

@section('content')
<div class="container py-5">
    <article>
        <header class="mb-4">
            <h1 class="display-4">{{ $portfolio->title }}</h1>
            @if($portfolio->description)
                <p class="lead text-muted">{{ $portfolio->description }}</p>
            @endif
            @if($portfolio->featured_image)
                <img src="{{ asset('storage/' . $portfolio->featured_image) }}" 
                     alt="{{ $portfolio->title }}" 
                     class="img-fluid rounded mt-3">
            @endif
        </header>

        <div class="row mb-4">
            @if($portfolio->category)
                <div class="col-md-3">
                    <p><strong>Catégorie :</strong> {{ $portfolio->category }}</p>
                </div>
            @endif
            @if($portfolio->client)
                <div class="col-md-3">
                    <p><strong>Client :</strong> {{ $portfolio->client }}</p>
                </div>
            @endif
            @if($portfolio->project_date)
                <div class="col-md-3">
                    <p><strong>Date :</strong> {{ $portfolio->project_date->format('d/m/Y') }}</p>
                </div>
            @endif
        </div>

        @if($portfolio->content)
            <div class="content mb-4">
                {!! $portfolio->content !!}
            </div>
        @endif

        @if($portfolio->gallery && count($portfolio->gallery) > 0)
            <div class="gallery row g-3">
                @foreach($portfolio->gallery as $image)
                    <div class="col-md-4">
                        <img src="{{ asset('storage/' . $image) }}" 
                             alt="Image galerie" 
                             class="img-fluid rounded">
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</div>
@endsection

