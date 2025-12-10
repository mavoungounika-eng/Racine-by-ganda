@extends('layouts.frontend')

@section('title', 'Codes de Récupération - RACINE BY GANDA')

@push('styles')
<style>
    .recovery-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #1a0f09 0%, #2C1810 50%, #1a0f09 100%);
        padding: 4rem 0;
        margin-top: -70px;
        padding-top: calc(4rem + 70px);
    }
    
    .recovery-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        max-width: 550px;
        margin: 0 auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    }
    
    .recovery-header {
        background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }
    
    .recovery-header .icon {
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
    }
    
    .recovery-header h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .recovery-header p {
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    .recovery-body {
        padding: 2rem;
    }
    
    .warning-box {
        background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
        border: 1px solid #F59E0B;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .warning-box i {
        color: #D97706;
        font-size: 1.25rem;
        margin-top: 2px;
    }
    
    .warning-box p {
        color: #92400E;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .codes-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .code-item {
        background: #F8F6F3;
        border: 1px solid #E5DDD3;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-family: 'Courier New', monospace;
        font-size: 1rem;
        font-weight: 600;
        text-align: center;
        color: #2C1810;
        letter-spacing: 1px;
    }
    
    .actions {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .btn {
        flex: 1;
        padding: 0.85rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    
    .btn-outline {
        background: white;
        border: 2px solid #E5DDD3;
        color: #5C4A3D;
    }
    
    .btn-outline:hover {
        border-color: #ED5F1E;
        color: #ED5F1E;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        border: none;
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(237, 95, 30, 0.3);
    }
    
    .info-box {
        background: #F8F6F3;
        border-radius: 12px;
        padding: 1.25rem;
    }
    
    .info-box h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-box ul {
        margin: 0;
        padding-left: 1.25rem;
        color: #5C4A3D;
        font-size: 0.85rem;
    }
    
    .info-box ul li {
        margin-bottom: 0.5rem;
    }
    
    .success-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="recovery-page">
    <div class="container">
        <div class="recovery-card">
            <div class="recovery-header">
                <div class="icon">
                    @if(isset($regenerated))
                    <i class="fas fa-sync-alt"></i>
                    @else
                    <i class="fas fa-check-circle"></i>
                    @endif
                </div>
                <h1>
                    @if(isset($regenerated))
                    Codes Régénérés
                    @else
                    Double Authentification Activée !
                    @endif
                </h1>
                <p>Sauvegardez ces codes de récupération</p>
            </div>
            
            <div class="recovery-body">
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>
                        <strong>Important !</strong> Ces codes ne seront plus affichés. 
                        Conservez-les dans un endroit sûr. Ils vous permettront de vous connecter si vous perdez l'accès à votre application d'authentification.
                    </p>
                </div>
                
                <div class="codes-grid" id="codes-grid">
                    @foreach($recoveryCodes as $code)
                    <div class="code-item">{{ $code }}</div>
                    @endforeach
                </div>
                
                <div class="actions">
                    <button class="btn btn-outline" onclick="copyAllCodes()">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                    <button class="btn btn-outline" onclick="downloadCodes()">
                        <i class="fas fa-download"></i> Télécharger
                    </button>
                    <button class="btn btn-outline" onclick="printCodes()">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
                
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Comment utiliser ces codes ?</h4>
                    <ul>
                        <li>Chaque code ne peut être utilisé qu'<strong>une seule fois</strong></li>
                        <li>Utilisez-les si vous perdez votre téléphone</li>
                        <li>Conservez-les dans un gestionnaire de mots de passe</li>
                        <li>Vous pouvez les régénérer depuis vos paramètres de sécurité</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="{{ route('frontend.home') }}" class="btn btn-primary" style="display: inline-flex; width: auto; padding: 1rem 2rem;">
                        <i class="fas fa-check"></i> J'ai sauvegardé mes codes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const codes = @json($recoveryCodes);
const appName = '{{ config("app.name") }}';
const userEmail = '{{ $user->email }}';

function copyAllCodes() {
    const text = codes.join('\n');
    navigator.clipboard.writeText(text).then(() => {
        alert('Codes copiés dans le presse-papier !');
    });
}

function downloadCodes() {
    const content = `CODES DE RÉCUPÉRATION - ${appName}
==========================================
Compte : ${userEmail}
Date : ${new Date().toLocaleDateString('fr-FR')}
==========================================

${codes.map((c, i) => `${i + 1}. ${c}`).join('\n')}

==========================================
IMPORTANT : Chaque code ne peut être utilisé qu'une seule fois.
Conservez ce fichier dans un endroit sûr.
`;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'racine-recovery-codes.txt';
    a.click();
    URL.revokeObjectURL(url);
}

function printCodes() {
    window.print();
}
</script>
@endpush

