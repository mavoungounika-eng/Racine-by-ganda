@extends('layouts.frontend')

@section('title', $album->title)

@section('content')
<div class="container py-5">
    <article>
        <header class="mb-4">
            <h1 class="display-4">{{ $album->title }}</h1>
            @if($album->description)
                <p class="lead text-muted">{{ $album->description }}</p>
            @endif
        </header>

        @if($album->cover_image)
            <div class="mb-4">
                <img src="{{ asset('storage/' . $album->cover_image) }}" 
                     alt="{{ $album->title }}" 
                     class="img-fluid rounded">
            </div>
        @endif

        @if($album->photos && count($album->photos) > 0)
            <div class="row g-3">
                @foreach($album->photos as $photo)
                    <div class="col-md-4">
                        <img src="{{ asset('storage/' . ($photo['path'] ?? $photo)) }}" 
                             alt="{{ $photo['caption'] ?? 'Photo' }}" 
                             class="img-fluid rounded">
                        @if(isset($photo['caption']) && $photo['caption'])
                            <p class="text-muted small mt-2">{{ $photo['caption'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</div>
@endsection

