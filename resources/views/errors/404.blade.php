@extends('whatstrigger.layouts.app')

@section('title', '404 — Página não encontrada')
@section('page-title', '404 — Página não encontrada')

@section('content')
<div class="text-center py-5">
    <i class="bi bi-search text-warning" style="font-size:4rem;opacity:.6;"></i>
    <h1 class="fw-bold mt-3">404</h1>
    <p class="text-muted mb-4">A página que você procura não existe ou foi movida.</p>
    <a href="{{ route('wt.dashboard') }}" class="btn btn-success">
        <i class="bi bi-house me-1"></i> Voltar ao Dashboard
    </a>
</div>
@endsection
