<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — WhatsTrigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0a1628 0%, #0f1f3a 30%, #162d50 60%, #0a1628 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow: hidden;
            position: relative;
        }

        .waves-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 200px;
            pointer-events: none;
            z-index: 0;
        }

        .wave {
            position: absolute;
            bottom: 0;
            width: 200%;
            height: 100%;
            background-repeat: repeat-x;
            animation: waveMove 8s linear infinite;
        }

        .wave:nth-child(1) {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 200'%3E%3Cpath fill='rgba(37, 99, 235, 0.15)' d='M0,128 C320,180 480,60 720,96 C960,132 1120,20 1440,64 L1440,200 L0,200 Z'/%3E%3C/svg%3E") repeat-x;
            background-size: 1440px 200px;
            animation-duration: 10s;
            opacity: 0.6;
        }

        .wave:nth-child(2) {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 200'%3E%3Cpath fill='rgba(37, 99, 235, 0.25)' d='M0,160 C320,100 480,180 720,140 C960,100 1120,180 1440,120 L1440,200 L0,200 Z'/%3E%3C/svg%3E") repeat-x;
            background-size: 1440px 200px;
            animation-duration: 14s;
            animation-direction: reverse;
            opacity: 0.4;
            bottom: -10px;
        }

        .wave:nth-child(3) {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 200'%3E%3Cpath fill='rgba(59, 130, 246, 0.2)' d='M0,96 C240,160 600,40 960,96 C1200,140 1320,60 1440,80 L1440,200 L0,200 Z'/%3E%3C/svg%3E") repeat-x;
            background-size: 1440px 200px;
            animation-duration: 18s;
            opacity: 0.3;
            bottom: -20px;
        }

        @keyframes waveMove {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.97);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .auth-card .card-body {
            padding: 2.5rem 2rem;
        }

        .auth-brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #25d366, #128C7E);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: #fff;
            font-size: 1.6rem;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
        }

        .auth-card h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1a1d23;
        }

        .auth-card .subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
        }

        .form-control {
            border: 1.5px solid #e0e4e8;
            border-radius: 0.5rem;
            padding: 0.65rem 0.9rem;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.7rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            width: 100%;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
            color: #fff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.85;
        }

        .progress-bar-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-login.loading .progress-bar-container {
            opacity: 1;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: #fff;
            border-radius: 2px;
            animation: progressLoad 2s ease-in-out infinite;
        }

        @keyframes progressLoad {
            0% { width: 0%; margin-left: 0; }
            50% { width: 70%; margin-left: 0; }
            100% { width: 0%; margin-left: 100%; }
        }

        .btn-login i.bi-box-arrow-in-right {
            margin-right: 0.4rem;
        }

        .form-check-label {
            font-size: 0.82rem;
            color: #6c757d;
        }

        .auth-divider {
            border: 0;
            border-top: 1px solid #e9ecef;
            margin: 1.5rem 0;
        }

        .register-link {
            font-size: 0.85rem;
        }

        .register-link a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.5rem;
            font-size: 0.82rem;
            padding: 0.6rem 0.9rem;
        }

        .alert-danger ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        @media (max-width: 480px) {
            .auth-card .card-body { padding: 1.8rem 1.2rem; }
            .waves-container { height: 120px; }
        }
    </style>
</head>
<body>

    <div class="waves-container">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>

    <div class="auth-card card">
        <div class="card-body">
            <div class="text-center">
                <div class="auth-brand-icon">
                    <i class="bi bi-whatsapp"></i>
                </div>
                <h1>WhatsTrigger</h1>
                <p class="subtitle">Acesse sua conta para continuar</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('wt.login.submit') }}" novalidate id="loginForm">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="seu@email.com"
                        autocomplete="email"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Lembrar de mim</label>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                    <span class="progress-bar-container">
                        <span class="progress-bar-fill"></span>
                    </span>
                </button>
            </form>

            <hr class="auth-divider">

            <p class="register-link text-center mb-0">
                Não tem uma conta?
                <a href="{{ route('wt.register') }}">Criar conta grátis</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loginBtn').classList.add('loading');
        });
    </script>
</body>
</html>
