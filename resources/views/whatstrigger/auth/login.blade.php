<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — WhatsTrigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1d23 0%, #25303e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }
        .auth-brand {
            color: #25d366;
            font-size: 1.8rem;
        }
        .btn-wt {
            background-color: #25d366;
            border-color: #25d366;
            color: #fff;
            font-weight: 600;
        }
        .btn-wt:hover {
            background-color: #1ebe5d;
            border-color: #1ebe5d;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="auth-card card p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-whatsapp auth-brand"></i>
            <h1 class="h4 mt-2 fw-bold">WhatsTrigger</h1>
            <p class="text-muted small">Acesse sua conta para continuar</p>
        </div>

        {{-- Erros de validação --}}
        @if($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('wt.login.submit') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">E-mail</label>
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
                <label for="password" class="form-label fw-semibold">Senha</label>
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

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label small" for="remember">Lembrar de mim</label>
            </div>

            <button type="submit" class="btn btn-wt w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
            </button>
        </form>

        <hr class="my-4">
        <p class="text-center text-muted small mb-0">
            Não tem uma conta?
            <a href="{{ route('wt.register') }}" class="text-decoration-none fw-semibold" style="color:#25d366;">
                Criar conta grátis
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
