@extends('whatstrigger.layouts.app')

@section('title', '403 — Acesso Negado')
@section('page-title', '403 — Acesso Negado')

@section('content')
<div class="text-center py-5">
    <i class="bi bi-shield-lock text-danger" style="font-size:4rem;opacity:.6;"></i>
    <h1 class="fw-bold mt-3">403</h1>
    <p class="text-muted mb-4">Você não tem permissão para acessar esta página.</p>
    <a href="{{ route('wt.dashboard') }}" class="btn btn-success">
        <i class="bi bi-house me-1"></i> Voltar ao Dashboard
    </a>
</div>
@endsection
