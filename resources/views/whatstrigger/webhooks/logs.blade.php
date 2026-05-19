@extends('whatstrigger.layouts.app')

@section('title', 'Logs de Webhooks')
@section('page-title', 'Logs de Webhooks')

@section('content')

{{-- ── Filtros ──────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small text-muted">Provedor</label>
                <select name="provider" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="evolution" {{ request('provider') === 'evolution' ? 'selected' : '' }}>Evolution</option>
                    <option value="stripe" {{ request('provider') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="{{ route('wt.webhooks.logs') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ── Tabela ───────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">Nenhum webhook registrado.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Provedor</th>
                            <th>Evento</th>
                            <th>Status</th>
                            <th>IP</th>
                            <th>Data</th>
                            <th class="text-end pe-3">Payload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $log->id }}</td>
                                <td>
                                    <span class="badge {{ $log->provider === 'evolution' ? 'bg-success' : 'bg-primary' }}">
                                        {{ $log->provider }}
                                    </span>
                                </td>
                                <td class="small">{{ $log->event ?? '—' }}</td>
                                <td>
                                    @php
                                        $statusClass = match($log->status) {
                                            'success' => 'bg-success',
                                            'failed'  => 'bg-danger',
                                            default   => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $log->status }}</span>
                                </td>
                                <td class="text-muted small">{{ $log->ip_address ?? '—' }}</td>
                                <td class="text-muted small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payloadModal{{ $log->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    {{-- Modal de payload --}}
                                    <div class="modal fade" id="payloadModal{{ $log->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content text-start">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Webhook #{{ $log->id }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <h6 class="text-muted small text-uppercase mb-1">Payload</h6>
                                                    <pre class="bg-light p-3 rounded" style="font-size:.8rem;max-height:300px;overflow:auto;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    @if($log->response)
                                                        <h6 class="text-muted small text-uppercase mt-3 mb-1">Resposta</h6>
                                                        <pre class="bg-light p-3 rounded" style="font-size:.8rem;max-height:300px;overflow:auto;">{{ json_encode($log->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white d-flex justify-content-center py-2">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection
