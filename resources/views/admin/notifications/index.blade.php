@extends('layouts.admin')

@section('title', 'Notifications - RACINE BY GANDA')
@section('page_title', 'Notifications')
@section('page_subtitle', 'Centre de notifications')
@section('breadcrumb', 'Notifications')

@section('content')

<div class="card-racine">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
        <h5 class="mb-0">Notifications</h5>
    </div>
    <div class="card-body">
        @if(count($notifications) > 0)
            <div class="list-group">
                @foreach($notifications as $notification)
                    <div class="list-group-item">
                        {{ $notification }}
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                <p class="mb-0">Aucune notification pour le moment</p>
            </div>
        @endif
    </div>
</div>

@endsection
