@extends('layouts.admin')

@section('title', 'Créer un Rôle')
@section('page-title', 'Créer un Rôle')

@push('styles')
<style>
    .premium-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .form-group {
        margin-bottom: 1.75rem;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #e2e8f0;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }
    
    .form-label .required {
        color: #EF4444;
        margin-left: 0.25rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.875rem 1.25rem;
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        font-size: 0.95rem;
        color: #e2e8f0;
        background: rgba(22, 13, 12, 0.6);
        transition: all 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
    
    .form-control::placeholder {
        color: #64748B;
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .premium-btn-secondary {
        background: rgba(51, 65, 85, 0.6);
        color: #e2e8f0;
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    
    .premium-btn-secondary:hover {
        background: rgba(51, 65, 85, 0.8);
        border-color: rgba(212, 165, 116, 0.4);
        color: #e2e8f0;
    }
    
    .error-message {
        color: #EF4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="premium-card">
        <div class="mb-8 pb-6 border-b-2 border-slate-700">
            <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-user-tag text-racine-orange mr-2"></i>
                Créer un nouveau rôle
            </h2>
            <p class="text-slate-400">Remplissez le formulaire ci-dessous pour créer un nouveau rôle</p>
        </div>

        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="form-group">
                    <label for="name" class="form-label">Nom du rôle <span class="required">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           placeholder="Ex: Administrateur"
                           class="form-control @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="slug" class="form-label">Slug <span class="required">*</span></label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                           placeholder="Ex: admin"
                           class="form-control @error('slug') border-red-500 @enderror">
                    <p class="text-xs text-slate-400 mt-2">Identifiant unique en minuscules (ex: admin, moderator)</p>
                    @error('slug')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2 form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Description du rôle..."
                              class="form-control @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <div class="flex items-center gap-3 p-4 bg-[#160D0C]/40 rounded-xl border border-slate-700/50">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-5 h-5 text-racine-orange border-slate-700 rounded focus:ring-racine-orange bg-[#160D0C]">
                        <label for="is_active" class="text-slate-300 font-medium cursor-pointer">Rôle actif</label>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t border-slate-700">
                <button type="submit" class="premium-btn">
                    <i class="fas fa-save"></i>
                    Créer le rôle
                </button>
                <a href="{{ route('admin.roles.index') }}" class="premium-btn-secondary">
                    <i class="fas fa-times"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
