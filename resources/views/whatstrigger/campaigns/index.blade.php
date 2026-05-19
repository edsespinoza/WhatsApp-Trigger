@extends('whatstrigger.layouts.app')

@section('title', 'Campanhas')
@section('page-title', 'Campanhas')

@section('content')

{{-- ── Tabs de filtro por status ──────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-0 d-flex align-items-center justify-content-between">
        <ul class="nav nav-tabs card-header-tabs border-0" role="tablist">
            @php
                $tabs = [
                    ''           => 'Todas',
                    'draft'      => 'Rascunho',
                    'scheduled'  => 'Agendadas',
                    'sending'    => 'Enviando',
                    'completed'  => 'Concluídas',
                    'cancelled'  => 'Canceladas',
                ];
            @endphp
            @foreach($tabs as $tabStatus => $tabLabel)
                <li class="nav-item">
                    <a
                        href="{{ route('wt.campaigns.index', $tabStatus ? ['status' => $tabStatus] : []) }}"
                        class="nav-link py-3 {{ $status === $tabStatus || ($tabStatus === '' && ! $status) ? 'active' : '' }}"
                    >{{ $tabLabel }}</a>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('wt.campaigns.create') }}" class="btn btn-sm btn-success ms-3">
            <i class="bi bi-plus-lg me-1"></i> Nova Campanha
        </a>
    </div>

    <div class="card-body p-0">
        @if($campaigns->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-megaphone" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">Nenhuma campanha encontrada nesta categoria.</p>
                @if(! $status)
                    <a href="{{ route('wt.campaigns.create') }}" class="btn btn-success btn-sm mt-3">
                        Criar primeira campanha
                    </a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nome</th>
                            <th>Status</th>
                            <th>Agendado para</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Enviadas</th>
                            <th class="text-end">Falhas</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaigns as $campaign)
                            @php
                                [$badgeBg, $badgeText, $textClass] = match($campaign->status) {
                                    'draft'     => ['secondary', 'Rascunho',  ''],
                                    'scheduled' => ['warning',   'Agendada',  'text-dark'],
                                    'sending'   => ['info',      'Enviando',  'text-dark'],
                                    'completed' => ['success',   'Concluída', ''],
                                    'cancelled' => ['danger',    'Cancelada', ''],
                                    default     => ['secondary', $campaign->status, ''],
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $campaign->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $badgeBg }} {{ $textClass }}">
                                        {{ $badgeText }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ $campaign->scheduled_at ? $campaign->scheduled_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="text-end">{{ number_format($campaign->total_contacts) }}</td>
                                <td class="text-end text-success fw-semibold">{{ number_format($campaign->sent_count) }}</td>
                                <td class="text-end">
                                    @if($campaign->failed_count > 0)
                                        <span class="text-danger fw-semibold">{{ number_format($campaign->failed_count) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        {{-- Ver relatório --}}
                                        @if(in_array($campaign->status, ['completed', 'sending', 'scheduled', 'draft']))
                                            <a href="{{ route('wt.campaigns.show', $campaign->id) }}"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Ver relatório">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif

                                        {{-- Disparar --}}
                                        @if(in_array($campaign->status, ['draft', 'scheduled']))
                                            <form method="POST" action="{{ route('wt.campaigns.send', $campaign->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Disparar campanha">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Cancelar --}}
                                        @if(in_array($campaign->status, ['draft', 'scheduled', 'sending']))
                                            <form method="POST" action="{{ route('wt.campaigns.cancel', $campaign->id) }}" class="d-inline">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Cancelar campanha"
                                                    onclick="return confirm('Deseja cancelar a campanha \'{{ addslashes($campaign->name) }}\'?')"
                                                >
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($campaigns->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-muted small">
                    Mostrando {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }}
                    de {{ $campaigns->total() }} campanhas
                </span>
                {{ $campaigns->links() }}
            </div>
        </div>
    @endif
</div>

@endsection
