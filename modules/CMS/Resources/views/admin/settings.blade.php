@extends('layouts.admin-master')

@section('title', 'Paramètres CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">⚙️ Paramètres CMS</h1>
            <p class="text-muted mb-0">Configurez les paramètres du CMS</p>
        </div>
        <a href="{{ route('cms.admin.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('cms.admin.settings.update') }}" method="POST">
                @csrf

                @foreach($settings as $group => $groupSettings)
                    <div class="mb-5">
                        <h5 class="mb-3 border-bottom pb-2">
                            <i class="fas fa-cog me-2"></i>{{ ucfirst($group) }}
                        </h5>

                        <div class="row">
                            @foreach($groupSettings as $setting)
                                <div class="col-md-6 mb-3">
                                    <label for="setting_{{ $setting->key }}" class="form-label">
                                        {{ $setting->label ?? $setting->key }}
                                        @if($setting->description)
                                            <small class="text-muted d-block">{{ $setting->description }}</small>
                                        @endif
                                    </label>

                                    @if($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="setting_{{ $setting->key }}" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   value="1"
                                                   {{ old('settings.' . $setting->key, $setting->value == '1' || $setting->value == 'true') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                Activer
                                            </label>
                                        </div>
                                    @elseif($setting->type === 'textarea')
                                        <textarea class="form-control @error('settings.' . $setting->key) is-invalid @enderror" 
                                                  id="setting_{{ $setting->key }}" 
                                                  name="settings[{{ $setting->key }}]" 
                                                  rows="4">{{ old('settings.' . $setting->key, $setting->value) }}</textarea>
                                        @error('settings.' . $setting->key)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @elseif($setting->type === 'json')
                                        <textarea class="form-control @error('settings.' . $setting->key) is-invalid @enderror" 
                                                  id="setting_{{ $setting->key }}" 
                                                  name="settings[{{ $setting->key }}]" 
                                                  rows="4">{{ old('settings.' . $setting->key, is_string($setting->value) ? $setting->value : json_encode($setting->value, JSON_PRETTY_PRINT)) }}</textarea>
                                        <small class="form-text text-muted">Format JSON</small>
                                        @error('settings.' . $setting->key)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" 
                                               class="form-control @error('settings.' . $setting->key) is-invalid @enderror" 
                                               id="setting_{{ $setting->key }}" 
                                               name="settings[{{ $setting->key }}]" 
                                               value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                               @if($setting->type === 'integer') step="1" @endif>
                                        @error('settings.' . $setting->key)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if($settings->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-cog fa-3x mb-3 d-block"></i>
                        <p>Aucun paramètre configuré pour le moment.</p>
                    </div>
                @else
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les paramètres
                        </button>
                        <a href="{{ route('cms.admin.dashboard') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

