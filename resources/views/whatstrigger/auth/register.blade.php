<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta — WhatsTrigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1d23 0%, #25303e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }
        .auth-brand { color: #25d366; font-size: 1.8rem; }
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
        .plan-badge {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: .5rem;
            padding: .75rem 1rem;
            font-size: .8rem;
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="auth-card card p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-whatsapp auth-brand"></i>
            <h1 class="h4 mt-2 fw-bold">Criar conta grátis</h1>
            <p class="text-muted small">50 mensagens/mês no plano Free</p>
        </div>

        {{-- Plano Free destaque --}}
        <div class="plan-badge mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-gift-fill" style="color:#25d366;"></i>
            <span>Plano <strong>Free</strong> ativado automaticamente — sem cartão de crédito.</span>
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

        <form method="POST" action="{{ route('wt.register.submit') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Nome completo</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="João Silva"
                    autocomplete="name"
                    required
                    autofocus
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

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
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
                    required
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label fw-semibold">Confirmar senha</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Repita a senha"
                    autocomplete="new-password"
                    required
                >
            </div>

            <p class="text-muted" style="font-size:.75rem;">
                Ao criar uma conta, você concorda que é responsável pelo consentimento dos destinatários
                de suas mensagens, conforme os Termos de Serviço do WhatsApp.
            </p>

            <button type="submit" class="btn btn-wt w-100">
                <i class="bi bi-person-plus me-1"></i> Criar conta grátis
            </button>
        </form>

        <hr class="my-4">
        <p class="text-center text-muted small mb-0">
            Já tem uma conta?
            <a href="{{ route('wt.login') }}" class="text-decoration-none fw-semibold" style="color:#25d366;">
                Entrar
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
