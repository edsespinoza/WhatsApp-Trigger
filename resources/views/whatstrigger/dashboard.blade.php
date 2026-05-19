@extends('whatstrigger.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ── Estatísticas ──────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Card: Mensagens enviadas --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Mensagens este mês</span>
                    <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center"
                          style="width:36px;height:36px;">
                        <i class="bi bi-send"></i>
                    </span>
                </div>
                <p class="display-6 fw-bold mb-0">{{ number_format($sentThisMonth) }}</p>
                @if($subscription && $subscription->messages_limit > 0)
                    <p class="text-muted small mb-2">de {{ number_format($subscription->messages_limit) }} no plano</p>
                    @php
                        $usagePct = $subscription->messages_limit > 0
                            ? min(100, round($subscription->messages_sent / $subscription->messages_limit * 100))
                            : 0;
                        $barColor = $usagePct >= 90 ? 'danger' : ($usagePct >= 70 ? 'warning' : 'success');
                    @endphp
                    <div class="progress" style="height:5px;" title="{{ $usagePct }}% usado">
                        <div class="progress-bar bg-{{ $barColor }}"
                             role="progressbar"
                             style="width:{{ $usagePct }}%"
                             aria-valuenow="{{ $usagePct }}"
                             aria-valuemin="0"
                             aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">{{ $usagePct }}% utilizado</p>
                @elseif($subscription && $subscription->messages_limit === -1)
                    <p class="text-muted small mb-0">Ilimitado (Enterprise)</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Card: Campanhas ativas --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Campanhas ativas</span>
                    <span class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center"
                          style="width:36px;height:36px;">
                        <i class="bi bi-megaphone"></i>
                    </span>
                </div>
                <p class="display-6 fw-bold mb-1">{{ $activeCampaigns }}</p>
                <a href="{{ route('wt.campaigns.index', ['status' => 'sending']) }}"
                   class="text-muted small text-decoration-none">
                    Ver campanhas em andamento <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Card: Plano atual --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Plano atual</span>
                    <span class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center"
                          style="width:36px;height:36px;">
                        <i class="bi bi-credit-card"></i>
                    </span>
                </div>
                @if($subscription)
                    @php
                        $plan      = $subscription->plan;
                        $planClass = match($plan) {
                            'free'       => 'badge-plan-free',
                            'starter'    => 'badge-plan-starter',
                            'pro'        => 'badge-plan-pro',
                            'enterprise' => 'badge-plan-enterprise',
                            default      => 'bg-secondary',
                        };
                        $planLabel = ucfirst($plan);
                    @endphp
                    <p class="mb-2">
                        <span class="badge fs-6 {{ $planClass }}">{{ $planLabel }}</span>
                    </p>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-calendar3 me-1"></i>
                        Renova em {{ $subscription->period_end->format('d/m/Y') }}
                    </p>
                    <a href="{{ route('wt.subscription.index') }}"
                       class="text-muted small text-decoration-none">
                        Gerenciar assinatura <i class="bi bi-arrow-right"></i>
                    </a>
                @else
                    <p class="text-muted small">Nenhuma assinatura encontrada.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Últimas campanhas ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold">Últimas campanhas</h6>
        <a href="{{ route('wt.campaigns.create') }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg me-1"></i> Nova Campanha
        </a>
    </div>
    <div class="card-body p-0">
        @if($recentCampaigns->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-megaphone" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">Nenhuma campanha criada ainda.</p>
                <a href="{{ route('wt.campaigns.create') }}" class="btn btn-success btn-sm mt-3">
                    Criar primeira campanha
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nome</th>
                            <th>Status</th>
                            <th>Agendado</th>
                            <th>Enviados</th>
                            <th>Falhas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCampaigns as $campaign)
                            @php
                                [$badgeBg, $badgeText] = match($campaign->status) {
                                    'draft'     => ['secondary', 'Rascunho'],
                                    'scheduled' => ['warning',   'Agendada'],
                                    'sending'   => ['info',      'Enviando'],
                                    'completed' => ['success',   'Concluída'],
                                    'cancelled' => ['danger',    'Cancelada'],
                                    default     => ['secondary', $campaign->status],
                                };
                            @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $campaign->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $badgeBg }}
                                        {{ $campaign->status === 'scheduled' || $campaign->status === 'sending' ? 'text-dark' : '' }}">
                                        {{ $badgeText }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ $campaign->scheduled_at ? $campaign->scheduled_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td>{{ number_format($campaign->sent_count) }}</td>
                                <td>
                                    @if($campaign->failed_count > 0)
                                        <span class="text-danger">{{ number_format($campaign->failed_count) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('wt.campaigns.show', $campaign->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if(! $recentCampaigns->isEmpty())
        <div class="card-footer bg-white text-center py-2">
            <a href="{{ route('wt.campaigns.index') }}" class="text-muted small text-decoration-none">
                Ver todas as campanhas <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    @endif
</div>

@endsection
