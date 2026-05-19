@extends('whatstrigger.layouts.app')

@section('title', $campaign->name)
@section('page-title', 'Relatório da Campanha')

@section('content')

{{-- ── Header da campanha ────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('wt.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h5 class="mb-0 fw-bold">{{ $campaign->name }}</h5>
    </div>
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
    <span class="badge bg-{{ $badgeBg }} fs-6 {{ $textClass }}">{{ $badgeText }}</span>

    {{-- Ações --}}
    @if(in_array($campaign->status, ['draft', 'scheduled']))
        <form method="POST" action="{{ route('wt.campaigns.send', $campaign->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-send me-1"></i> Disparar
            </button>
        </form>
    @endif

    @if(in_array($campaign->status, ['draft', 'scheduled', 'sending']))
        <form method="POST" action="{{ route('wt.campaigns.cancel', $campaign->id) }}" class="d-inline">
            @csrf
            <button
                type="submit"
                class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Deseja cancelar esta campanha?')"
            >
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </button>
        </form>
    @endif
</div>

{{-- ── Cards de métricas ─────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $total       = max($campaign->total_contacts, 1);
        $sentPct     = round($campaign->sent_count / $total * 100);
        $delivPct    = round($deliveredCount / $total * 100);
        $readPct     = round($readCount / $total * 100);
        $failPct     = round($campaign->failed_count / $total * 100);
    @endphp

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-4">
                <p class="display-5 fw-bold text-primary mb-1">{{ number_format($campaign->sent_count) }}</p>
                <p class="text-muted small mb-0 fw-semibold">ENVIADOS</p>
                <p class="text-muted" style="font-size:.75rem;">{{ $sentPct }}%</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-4">
                <p class="display-5 fw-bold text-success mb-1">{{ number_format($deliveredCount) }}</p>
                <p class="text-muted small mb-0 fw-semibold">ENTREGUES</p>
                <p class="text-muted" style="font-size:.75rem;">{{ $delivPct }}%</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-4">
                <p class="display-5 fw-bold mb-1" style="color:#25d366;">{{ number_format($readCount) }}</p>
                <p class="text-muted small mb-0 fw-semibold">LIDOS</p>
                <p class="text-muted" style="font-size:.75rem;">{{ $readPct }}%</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-4">
                <p class="display-5 fw-bold text-danger mb-1">{{ number_format($campaign->failed_count) }}</p>
                <p class="text-muted small mb-0 fw-semibold">FALHAS</p>
                <p class="text-muted" style="font-size:.75rem;">{{ $failPct }}%</p>
            </div>
        </div>
    </div>
</div>

{{-- ── Progresso e taxa de entrega ──────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Progresso do disparo</h6>
                <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span>{{ number_format($campaign->sent_count + $campaign->failed_count) }} processados</span>
                    <span>{{ number_format($campaign->total_contacts) }} total</span>
                </div>
                <div class="progress mb-2" style="height:12px;">
                    <div
                        class="progress-bar bg-success"
                        role="progressbar"
                        style="width:{{ $sentPct }}%"
                        aria-valuenow="{{ $sentPct }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                    <div
                        class="progress-bar bg-danger"
                        role="progressbar"
                        style="width:{{ $failPct }}%"
                        aria-valuenow="{{ $failPct }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
                <div class="d-flex gap-3 small">
                    <span><span class="badge bg-success">{{ $sentPct }}%</span> Enviados</span>
                    <span><span class="badge bg-danger">{{ $failPct }}%</span> Falhas</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Informações</h6>
                <table class="table table-sm table-borderless mb-0 small">
                    <tr>
                        <td class="text-muted">Agendamento</td>
                        <td class="fw-semibold">
                            {{ $campaign->scheduled_at ? $campaign->scheduled_at->format('d/m/Y H:i') : 'Imediato' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tags alvo</td>
                        <td>
                            @if(! empty($campaign->target_tags))
                                @foreach($campaign->target_tags as $tag)
                                    <span class="badge bg-light text-dark border">{{ $tag }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Todos os contatos</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Taxa de entrega</td>
                        <td class="fw-semibold text-success">
                            {{ $campaign->sent_count > 0 ? $delivPct . '%' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Taxa de leitura</td>
                        <td class="fw-semibold" style="color:#25d366;">
                            {{ $campaign->sent_count > 0 ? $readPct . '%' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Criada em</td>
                        <td>{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Mensagem enviada ──────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Conteúdo da mensagem</h6>
    </div>
    <div class="card-body">
        <pre class="mb-0" style="white-space:pre-wrap; font-family:inherit; font-size:.9rem;">{{ $campaign->message }}</pre>
    </div>
</div>

{{-- ── Tabela de mensagens recentes ─────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Últimas mensagens enviadas</h6>
    </div>
    <div class="card-body p-0">
        @if($messages->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-envelope" style="font-size:2rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">Nenhuma mensagem processada ainda.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Telefone</th>
                            <th>Status</th>
                            <th>Atualizado em</th>
                            <th class="pe-3">Erro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $msg)
                            @php
                                [$msgBadge, $msgText] = match($msg->status) {
                                    'pending'   => ['secondary', 'Pendente'],
                                    'queued'    => ['secondary', 'Na fila'],
                                    'sent'      => ['primary',   'Enviado'],
                                    'delivered' => ['success',   'Entregue'],
                                    'read'      => ['success',   'Lido'],
                                    'failed'    => ['danger',    'Falhou'],
                                    default     => ['secondary', $msg->status],
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $msg->contact->phone ?? $msg->phone ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $msgBadge }}">{{ $msgText }}</span>
                                </td>
                                <td class="text-muted">{{ $msg->updated_at->format('d/m H:i') }}</td>
                                <td class="pe-3 text-danger">
                                    {{ $msg->error_message ?? '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
