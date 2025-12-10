<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console - RACINE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0C0C0C;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        /* MATRIX RAIN EFFECT */
        .matrix-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            opacity: 0.1;
            z-index: 0;
        }
        
        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            color: #00FF88;
            animation: matrixFall linear infinite;
            white-space: nowrap;
        }
        
        @keyframes matrixFall {
            0% { transform: translateY(0); }
            100% { transform: translateY(200vh); }
        }
        
        /* SCAN LINE */
        .scanline {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, #00FF88, transparent);
            animation: scan 3s linear infinite;
            z-index: 1;
        }
        
        @keyframes scan {
            0% { top: 0; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        
        .container {
            width: 100%;
            max-width: 440px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        
        .card {
            background: rgba(18, 18, 18, 0.95);
            border: 1px solid #222;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 
                0 0 0 1px rgba(0, 255, 136, 0.1),
                0 25px 80px rgba(0, 0, 0, 0.5);
        }
        
        /* TERMINAL HEADER */
        .terminal-header {
            background: #1A1A1A;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #222;
        }
        
        .terminal-dots {
            display: flex;
            gap: 8px;
        }
        
        .terminal-dots span {
            width: 12px; height: 12px;
            border-radius: 50%;
        }
        
        .terminal-dots span:nth-child(1) { background: #FF5F57; }
        .terminal-dots span:nth-child(2) { background: #FEBC2E; }
        .terminal-dots span:nth-child(3) { background: #28C840; }
        
        .terminal-title {
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            color: #666;
        }
        
        .terminal-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Fira Code', monospace;
            font-size: 0.75rem;
            color: #00FF88;
        }
        
        .terminal-status::before {
            content: '';
            width: 6px; height: 6px;
            background: #00FF88;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #00FF88 0%, #00CC6A 100%);
            border-radius: 16px;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .logo-badge::before {
            content: '';
            position: absolute;
            inset: -3px;
            background: linear-gradient(135deg, #00FF88, #00CC6A);
            border-radius: 18px;
            z-index: -1;
            opacity: 0.3;
            animation: glow 2s ease-in-out infinite;
        }
        
        @keyframes glow {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }
        
        .logo-badge i {
            font-size: 1.75rem;
            color: #0C0C0C;
        }
        
        .header-section h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #FFF;
            margin-bottom: 0.5rem;
        }
        
        .header-section p {
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            color: #666;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-left: 3px solid #EF4444;
            color: #FCA5A5;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #888;
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .form-group label i {
            color: #00FF88;
            font-size: 0.7rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: #1A1A1A;
            border: 1px solid #333;
            border-radius: 10px;
            color: #FFF;
            font-family: 'Fira Code', monospace;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-group input::placeholder {
            color: #444;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00FF88;
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.15);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-wrapper input {
            width: 16px; height: 16px;
            accent-color: #00FF88;
        }
        
        .checkbox-wrapper span {
            color: #888;
            font-size: 0.85rem;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #00FF88 0%, #00CC6A 100%);
            color: #0C0C0C;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.2);
        }
        
        .security-note {
            margin-top: 2rem;
            text-align: center;
            font-family: 'Fira Code', monospace;
            font-size: 0.75rem;
            color: #444;
        }
        
        .security-note i {
            color: #00FF88;
            margin-right: 0.5rem;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #00FF88;
        }
    </style>
</head>
<body>
    <div class="matrix-bg" id="matrix"></div>
    <div class="scanline"></div>
    
    <div class="container">
        <div class="card">
            <div class="terminal-header">
                <div class="terminal-dots">
                    <span></span><span></span><span></span>
                </div>
                <span class="terminal-title">admin@racine:~</span>
                <span class="terminal-status">secure</span>
            </div>
            
            <div class="card-body">
                <div class="header-section">
                    <div class="logo-badge">
                        <i class="fas fa-terminal"></i>
                    </div>
                    <h1>Admin Console</h1>
                    <p>// super_admin access required</p>
                </div>
                
                @if($errors->any())
                    <div class="alert-error">
                        <i class="fas fa-xmark"></i> error: {{ $errors->first() }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="form-group">
                        <label><i class="fas fa-chevron-right"></i> email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@racine.com" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-chevron-right"></i> password</label>
                        <input type="password" name="password" placeholder="••••••••••••" required>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="remember">
                            <span>remember_session</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        authenticate
                    </button>
                </form>
                
                <p class="security-note">
                    <i class="fas fa-shield-halved"></i>
                    256-bit encrypted session • 2FA enabled
                </p>
                
                <div class="back-link">
                    <a href="{{ route('frontend.home') }}"><i class="fas fa-arrow-left"></i> exit</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Matrix rain effect
        const matrix = document.getElementById('matrix');
        const chars = '01アイウエオカキクケコサシスセソタチツテト';
        
        for(let i = 0; i < 30; i++) {
            const col = document.createElement('div');
            col.className = 'matrix-column';
            col.style.left = (i * 3.5) + '%';
            col.style.animationDuration = (Math.random() * 3 + 4) + 's';
            col.style.animationDelay = (Math.random() * 5) + 's';
            
            let text = '';
            for(let j = 0; j < 30; j++) {
                text += chars.charAt(Math.floor(Math.random() * chars.length)) + '<br>';
            }
            col.innerHTML = text;
            matrix.appendChild(col);
        }
    </script>
</body>
</html>
