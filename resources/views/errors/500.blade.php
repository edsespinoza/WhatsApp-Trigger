@extends('whatstrigger.layouts.app')

@section('title', '500 — Erro interno')
@section('page-title', '500 — Erro interno')

@section('content')
<div class="text-center py-5">
    <i class="bi bi-gear text-danger" style="font-size:4rem;opacity:.6;"></i>
    <h1 class="fw-bold mt-3">500</h1>
    <p class="text-muted mb-4">Ocorreu um erro interno no servidor. Tente novamente mais tarde.</p>
    <a href="{{ route('wt.dashboard') }}" class="btn btn-success">
        <i class="bi bi-house me-1"></i> Voltar ao Dashboard
    </a>
</div>
@endsection
