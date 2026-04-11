@extends('layouts.frontend')

@section('title', $event->title)

@section('content')
<div class="container py-5">
    <article>
        <header class="mb-4">
            <h1 class="display-4">{{ $event->title }}</h1>
            @if($event->description)
                <p class="lead text-muted">{{ $event->description }}</p>
            @endif
            @if($event->featured_image)
                <img src="{{ asset('storage/' . $event->featured_image) }}" 
                     alt="{{ $event->title }}" 
                     class="img-fluid rounded mt-3">
            @endif
        </header>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Type :</strong> {{ $event->type_label }}</p>
                <p><strong>Date début :</strong> {{ $event->start_date->format('d/m/Y H:i') }}</p>
                @if($event->end_date)
                    <p><strong>Date fin :</strong> {{ $event->end_date->format('d/m/Y H:i') }}</p>
                @endif
                @if($event->location)
                    <p><strong>Lieu :</strong> {{ $event->location }}</p>
                @endif
                @if($event->price && !$event->is_free)
                    <p><strong>Prix :</strong> {{ number_format($event->price, 2) }} €</p>
                @elseif($event->is_free)
                    <p><strong>Prix :</strong> Gratuit</p>
                @endif
            </div>
        </div>

        @if($event->content)
            <div class="content">
                {!! $event->content !!}
            </div>
        @endif
    </article>
</div>
@endsection

