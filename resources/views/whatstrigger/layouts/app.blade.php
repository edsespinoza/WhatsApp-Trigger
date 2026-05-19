<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WhatsTrigger') — WhatsTrigger</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --wt-sidebar-width: 240px;
            --wt-sidebar-bg: #1a1d23;
            --wt-sidebar-hover: #2a2d35;
            --wt-sidebar-active: #25d366;
            --wt-sidebar-text: #adb5bd;
            --wt-sidebar-text-active: #ffffff;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        /* ── Sidebar ──────────────────────────────────────────── */
        #wt-sidebar {
            width: var(--wt-sidebar-width);
            min-height: 100vh;
            background-color: var(--wt-sidebar-bg);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        #wt-sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        #wt-sidebar .sidebar-brand .brand-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
        }

        #wt-sidebar .sidebar-brand .brand-icon {
            color: #25d366;
            font-size: 1.4rem;
        }

        #wt-sidebar .nav-link {
            color: var(--wt-sidebar-text);
            padding: .6rem 1.5rem;
            border-radius: 0;
            font-size: .875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .625rem;
            transition: background .15s, color .15s;
        }

        #wt-sidebar .nav-link:hover {
            background-color: var(--wt-sidebar-hover);
            color: var(--wt-sidebar-text-active);
        }

        #wt-sidebar .nav-link.active {
            background-color: rgba(37, 211, 102, .12);
            color: var(--wt-sidebar-active);
            border-left: 3px solid var(--wt-sidebar-active);
        }

        #wt-sidebar .nav-link i {
            font-size: 1rem;
            width: 1.1rem;
            text-align: center;
        }

        #wt-sidebar .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,.08);
            padding: 1rem 1.5rem;
        }

        /* ── Topbar ───────────────────────────────────────────── */
        #wt-topbar {
            margin-left: var(--wt-sidebar-width);
            height: 56px;
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 99;
        }

        /* ── Main content ─────────────────────────────────────── */
        #wt-content {
            margin-left: var(--wt-sidebar-width);
            padding: 1.5rem;
            min-height: calc(100vh - 56px);
        }

        /* ── Misc helpers ─────────────────────────────────────── */
        .badge-plan-free       { background-color: #6c757d !important; }
        .badge-plan-starter    { background-color: #0dcaf0 !important; color: #000 !important; }
        .badge-plan-pro        { background-color: #0d6efd !important; }
        .badge-plan-enterprise { background-color: #ffc107 !important; color: #000 !important; }

        .status-badge-draft     { --bs-badge-bg: #6c757d; }
        .status-badge-scheduled { --bs-badge-bg: #ffc107; color: #000; }
        .status-badge-sending   { --bs-badge-bg: #0dcaf0; color: #000; }
        .status-badge-completed { --bs-badge-bg: #198754; }
        .status-badge-cancelled { --bs-badge-bg: #dc3545; }

        .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }

        @media (max-width: 768px) {
            #wt-sidebar { transform: translateX(-100%); }
            #wt-topbar, #wt-content { margin-left: 0; }
        }
    </style>

    @yield('head')
</head>
<body>

<!-- ═══════════════════════════════════════════════════════ SIDEBAR ══ -->
<nav id="wt-sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2">
        <i class="bi bi-whatsapp brand-icon"></i>
        <span class="brand-name">WhatsTrigger</span>
    </div>

    <ul class="nav flex-column mt-2">
        <li class="nav-item">
            <a href="{{ route('wt.dashboard') }}"
               class="nav-link {{ request()->routeIs('wt.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wt.contacts.index') }}"
               class="nav-link {{ request()->routeIs('wt.contacts.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Contatos
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wt.campaigns.index') }}"
               class="nav-link {{ request()->routeIs('wt.campaigns.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Campanhas
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wt.whatsapp.connect') }}"
               class="nav-link {{ request()->routeIs('wt.whatsapp.*') ? 'active' : '' }}">
                <i class="bi bi-phone"></i> WhatsApp
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wt.queue.monitor') }}"
               class="nav-link {{ request()->routeIs('wt.queue.*') ? 'active' : '' }}">
                <i class="bi bi-activity"></i> Fila
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wt.webhooks.logs') }}"
               class="nav-link {{ request()->routeIs('wt.webhooks.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Webhooks
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wt.subscription.index') }}"
               class="nav-link {{ request()->routeIs('wt.subscription.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card"></i> Assinatura
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <p class="text-muted mb-1" style="font-size:.75rem;">Logado como</p>
        <p class="text-white mb-0 fw-semibold" style="font-size:.85rem;">{{ auth()->user()->name }}</p>
    </div>
</nav>

<!-- ════════════════════════════════════════════════════════ TOPBAR ══ -->
<div id="wt-topbar">
    <span class="fw-semibold text-dark me-auto">@yield('page-title', 'Painel')</span>

    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-md-inline">{{ auth()->user()->email }}</span>
        <form method="POST" action="{{ route('wt.logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-right"></i> Sair
            </button>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════ MAIN CONTENT ══ -->
<main id="wt-content">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Alpine.js 3.x -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

@yield('scripts')
</body>
</html>
