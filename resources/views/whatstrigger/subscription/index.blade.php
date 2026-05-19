@extends('whatstrigger.layouts.app')

@section('title', 'Assinatura')
@section('page-title', 'Assinatura')

@section('content')

{{-- ── Card do plano atual ───────────────────────────────────────────── --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Seu plano atual</h6>
            </div>
            <div class="card-body">
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
                        $planLabel  = ucfirst($plan);
                        $isUnlimited = $subscription->messages_limit === -1;
                        $usagePct   = ! $isUnlimited && $subscription->messages_limit > 0
                            ? min(100, round($subscription->messages_sent / $subscription->messages_limit * 100))
                            : 0;
                        $barColor   = $usagePct >= 90 ? 'danger' : ($usagePct >= 70 ? 'warning' : 'success');
                    @endphp

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="badge fs-5 {{ $planClass }}">{{ $planLabel }}</span>
                        @if($subscription->status === 'active')
                            <span class="badge bg-success bg-opacity-10 text-success">Ativo</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger">{{ ucfirst($subscription->status) }}</span>
                        @endif
                    </div>

                    <table class="table table-sm table-borderless small mb-4">
                        <tr>
                            <td class="text-muted ps-0">Período</td>
                            <td class="fw-semibold">
                                {{ $subscription->period_start->format('d/m/Y') }}
                                a
                                {{ $subscription->period_end->format('d/m/Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Limite de mensagens</td>
                            <td class="fw-semibold">
                                {{ $isUnlimited ? 'Ilimitado' : number_format($subscription->messages_limit) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Mensagens utilizadas</td>
                            <td class="fw-semibold">{{ number_format($subscription->messages_sent) }}</td>
                        </tr>
                    </table>

                    @if(! $isUnlimited)
                        <div class="mb-1 d-flex justify-content-between small">
                            <span class="text-muted">Uso de mensagens</span>
                            <span class="fw-semibold text-{{ $barColor }}">{{ $usagePct }}%</span>
                        </div>
                        <div class="progress mb-2" style="height:8px;">
                            <div
                                class="progress-bar bg-{{ $barColor }}"
                                role="progressbar"
                                style="width:{{ $usagePct }}%"
                                aria-valuenow="{{ $usagePct }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <p class="text-muted small mb-0">
                            {{ number_format($subscription->messages_sent) }} de
                            {{ number_format($subscription->messages_limit) }} mensagens utilizadas
                        </p>

                        @if($usagePct >= 90)
                            <div class="alert alert-warning mt-3 py-2 small">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Você está próximo do limite. Considere fazer upgrade para continuar enviando.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-success py-2 small mt-2">
                            <i class="bi bi-infinity me-1"></i> Mensagens ilimitadas no plano Enterprise.
                        </div>
                    @endif

                    @if($plan === 'enterprise')
                        <div class="alert alert-info py-2 small mt-3">
                            <i class="bi bi-headset me-1"></i>
                            Plano Enterprise ativo. Para suporte dedicado ou white-label,
                            <a href="mailto:espinozatecnico@gmail.com" class="alert-link">entre em contato</a>.
                        </div>
                    @endif
                @else
                    <div class="text-center text-muted py-4">
                        <p>Nenhuma assinatura encontrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Overage info ──────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Mensagens adicionais (overage)</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Ao atingir o limite do seu plano, você pode continuar enviando pelo custo adicional de
                    <strong>R$ 0,02 por mensagem</strong>.
                </p>
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded p-3">
                            <p class="fw-bold mb-0">100 msgs</p>
                            <p class="text-muted small mb-0">R$ 2,00</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded p-3">
                            <p class="fw-bold mb-0">500 msgs</p>
                            <p class="text-muted small mb-0">R$ 10,00</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded p-3">
                            <p class="fw-bold mb-0">1.000 msgs</p>
                            <p class="text-muted small mb-0">R$ 20,00</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded p-3">
                            <p class="fw-bold mb-0">5.000 msgs</p>
                            <p class="text-muted small mb-0">R$ 100,00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Tabela comparativa de planos ─────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Comparativo de planos</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Plano</th>
                        <th class="text-center">Preço</th>
                        <th class="text-center">Mensagens/mês</th>
                        <th class="text-center">Webhooks</th>
                        <th class="text-center">Relatórios</th>
                        <th class="text-center">White-label</th>
                        <th class="text-center pe-3">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $planDetails = [
                            'free'       => [
                                'label'    => 'Free',
                                'class'    => 'badge-plan-free',
                                'price'    => 'Grátis',
                                'limit'    => '50 + marca d\'água',
                                'webhooks' => false,
                                'reports'  => false,
                                'white'    => false,
                            ],
                            'starter'    => [
                                'label'    => 'Starter',
                                'class'    => 'badge-plan-starter',
                                'price'    => 'R$ 29,90/mês',
                                'limit'    => '2.000',
                                'webhooks' => false,
                                'reports'  => false,
                                'white'    => false,
                            ],
                            'pro'        => [
                                'label'    => 'Pro',
                                'class'    => 'badge-plan-pro',
                                'price'    => 'R$ 79,90/mês',
                                'limit'    => '10.000',
                                'webhooks' => true,
                                'reports'  => true,
                                'white'    => false,
                            ],
                            'enterprise' => [
                                'label'    => 'Enterprise',
                                'class'    => 'badge-plan-enterprise',
                                'price'    => 'R$ 497/mês',
                                'limit'    => 'Ilimitado',
                                'webhooks' => true,
                                'reports'  => true,
                                'white'    => true,
                            ],
                        ];
                    @endphp

                    @foreach($planDetails as $planKey => $detail)
                        @php $isCurrent = $subscription && $subscription->plan === $planKey; @endphp
                        <tr class="{{ $isCurrent ? 'table-active' : '' }}">
                            <td class="ps-3">
                                <span class="badge {{ $detail['class'] }}">{{ $detail['label'] }}</span>
                                @if($isCurrent)
                                    <span class="badge bg-success bg-opacity-10 text-success ms-1 small">Atual</span>
                                @endif
                            </td>
                            <td class="text-center fw-semibold">{{ $detail['price'] }}</td>
                            <td class="text-center">{{ $detail['limit'] }}</td>
                            <td class="text-center">
                                @if($detail['webhooks'])
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-x-circle text-muted"></i>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($detail['reports'])
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-x-circle text-muted"></i>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($detail['white'])
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-x-circle text-muted"></i>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                @if($isCurrent)
                                    <span class="text-muted small">Plano ativo</span>
                                @elseif($planKey === 'enterprise')
                                    <a href="mailto:espinozatecnico@gmail.com?subject=WhatsTrigger%20Enterprise"
                                       class="btn btn-sm btn-warning text-dark">
                                        <i class="bi bi-envelope me-1"></i> Contato
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-outline-primary" disabled title="Em breve via Stripe">
                                        Upgrade
                                        <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Em breve</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <p class="text-muted small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Pagamento via Stripe em breve. Por ora, entre em contato para upgrade manual:
            <a href="mailto:espinozatecnico@gmail.com">espinozatecnico@gmail.com</a>
        </p>
    </div>
</div>

@endsection
