@extends('whatstrigger.layouts.app')

@section('title', 'Fila de Jobs')
@section('page-title', 'Monitor da Fila')

@section('content')

<div class="row g-3 mb-4">

    {{-- Card: Pendentes --}}
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Pendentes</span>
                    <span class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center"
                          style="width:36px;height:36px;">
                        <i class="bi bi-hourglass-split"></i>
                    </span>
                </div>
                <p class="display-6 fw-bold mb-0">{{ number_format($jobCounts['pending']) }}</p>
            </div>
        </div>
    </div>

    {{-- Card: Falhos --}}
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Falhos</span>
                    <span class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center"
                          style="width:36px;height:36px;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </span>
                </div>
                <p class="display-6 fw-bold mb-0">{{ number_format($jobCounts['failed']) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- ── Tabela de jobs falhos ──────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold">Jobs com falha</h6>
        <span class="text-muted small">Últimos 20 registros</span>
    </div>
    <div class="card-body p-0">
        @if($failed->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">Nenhum job com falha.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Job</th>
                            <th>Fila</th>
                            <th>Falhou em</th>
                            <th>Erro</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failed as $job)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $job->id }}</td>
                                <td class="fw-semibold small">{{ class_basename($job->job) }}</td>
                                <td><span class="badge bg-secondary">{{ $job->queue }}</span></td>
                                <td class="text-muted small">{{ \Carbon\Carbon::parse($job->failed_at)->format('d/m/Y H:i:s') }}</td>
                                <td style="max-width:300px;">
                                    <span class="text-danger small d-inline-block text-truncate" style="max-width:280px;">
                                        {{ str($job->exception)->limit(120) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <form action="{{ route('wt.queue.failed.retry', $job->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Reenviar este job para a fila?')">
                                            <i class="bi bi-arrow-repeat"></i> Retry
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($failed->hasPages())
        <div class="card-footer bg-white d-flex justify-content-center py-2">
            {{ $failed->links() }}
        </div>
    @endif
</div>

@endsection
