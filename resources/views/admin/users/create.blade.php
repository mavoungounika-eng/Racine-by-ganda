@extends('layouts.admin')

@section('title', 'Créer un Utilisateur')
@section('page-title', 'Nouvel Utilisateur')

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
                <i class="fas fa-user-plus text-racine-orange mr-2"></i>
                Créer un Utilisateur
            </h2>
            <p class="text-slate-400">Remplissez les informations pour créer un nouvel utilisateur</p>
        </div>
        
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                {{-- Name --}}
                <div class="form-group">
                    <label class="form-label">
                        Nom complet <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name') }}"
                           required
                           placeholder="Nom complet de l'utilisateur"
                           class="form-control @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label class="form-label">
                        Email <span class="required">*</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email') }}"
                           required
                           placeholder="email@example.com"
                           class="form-control @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label class="form-label">
                        Mot de passe <span class="required">*</span>
                    </label>
                    <input type="password" 
                           name="password" 
                           required
                           placeholder="••••••••"
                           class="form-control @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Confirmation --}}
                <div class="form-group">
                    <label class="form-label">
                        Confirmer le mot de passe <span class="required">*</span>
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           required
                           placeholder="••••••••"
                           class="form-control">
                </div>

                {{-- Role --}}
                <div class="form-group">
                    <label class="form-label">
                        Rôle <span class="required">*</span>
                    </label>
                    <select name="role_id" 
                            required
                            class="form-control @error('role_id') border-red-500 @enderror">
                        <option value="">Sélectionnez un rôle</option>
                        @foreach(\App\Models\Role::all() as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex gap-4 pt-6 border-t border-slate-700">
                    <button type="submit" class="premium-btn">
                        <i class="fas fa-save"></i>
                        Créer l'Utilisateur
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="premium-btn-secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
