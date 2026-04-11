<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès Refusé</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 32px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
        }
        
        .error-message {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 40px;
            text-align: left;
        }
        
        .error-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }
        
        .error-details p {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .error-details ul {
            margin-left: 20px;
            color: #4a5568;
        }
        
        .error-details li {
            margin-bottom: 4px;
        }
        
        .actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">🔒</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Accès Refusé</h1>
        <p class="error-message">
            @if(request()->is('erp*'))
                Vous n'avez pas l'autorisation d'accéder au module ERP.
            @elseif(request()->is('admin*'))
                Vous n'avez pas l'autorisation d'accéder à l'administration.
            @else
                Cette action n'est pas autorisée.
            @endif
        </p>
        
        @if(request()->is('erp*'))
        <div class="error-details">
            <h3>📋 Accès au Module ERP</h3>
            <p>Le module ERP est réservé aux utilisateurs ayant l'un des rôles suivants :</p>
            <ul>
                <li><strong>Super Administrateur</strong> (super_admin)</li>
                <li><strong>Administrateur</strong> (admin)</li>
                <li><strong>Personnel</strong> (staff)</li>
            </ul>
            <p style="margin-top: 12px;">
                <strong>Votre rôle actuel :</strong> 
                @auth
                    {{ auth()->user()->getRoleSlug() ?? 'Non défini' }}
                @else
                    Non connecté
                @endauth
            </p>
        </div>
        @endif
        
        <div class="actions">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                    Retour au Tableau de Bord
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    Se Connecter
                </a>
            @endauth
            <a href="javascript:history.back()" class="btn btn-secondary">
                Retour en Arrière
            </a>
        </div>
    </div>
</body>
</html>

