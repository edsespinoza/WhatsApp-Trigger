@extends('whatstrigger.layouts.app')

@section('title', 'Conexão WhatsApp')
@section('page-title', 'Conexão WhatsApp')

@section('content')

<div class="row g-4">
    {{-- ── Card principal de status / QR Code ──────────────────────── --}}
    <div class="col-12 col-lg-6"
         x-data="{
             connected: {{ $connected ? 'true' : 'false' }},
             qrUrl: '{{ $qrUrl ?? '' }}',
             loading: false,
             pollInterval: null,

             init() {
                 if (! this.connected) {
                     this.startPolling();
                 }
             },

             startPolling() {
                 this.pollInterval = setInterval(() => {
                     this.checkStatus();
                 }, 5000);
             },

             stopPolling() {
                 if (this.pollInterval) {
                     clearInterval(this.pollInterval);
                     this.pollInterval = null;
                 }
             },

             async checkStatus() {
                 try {
                     const resp = await fetch('/whatstrigger/whatsapp/status', {
                         headers: {
                             'X-Requested-With': 'XMLHttpRequest',
                             'Accept': 'application/json',
                         }
                     });
                     if (resp.ok) {
                         const data = await resp.json();
                         if (data.connected) {
                             this.connected = true;
                             this.stopPolling();
                             window.location.reload();
                         } else if (data.qr_url) {
                             this.qrUrl = data.qr_url;
                         }
                     }
                 } catch (e) {
                     console.error('Polling error:', e);
                 }
             },

             destroy() {
                 this.stopPolling();
             }
         }"
    >
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">

                {{-- Erro de conexão com API --}}
                @if($error)
                    <div class="alert alert-warning text-start mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ $error }}
                    </div>
                @endif

                {{-- Status: Conectado --}}
                <template x-if="connected">
                    <div>
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10"
                                  style="width:80px;height:80px;">
                                <i class="bi bi-whatsapp text-success" style="font-size:2.5rem;"></i>
                            </span>
                        </div>
                        <h5 class="fw-bold text-success mb-1">WhatsApp Conectado</h5>
                        <p class="text-muted small mb-4">Seu número está ativo e pronto para enviar mensagens.</p>

                        <form method="POST" action="{{ route('wt.whatsapp.disconnect') }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-outline-danger"
                                    onclick="return confirm('Deseja desconectar o WhatsApp? As campanhas ativas serão pausadas.')">
                                <i class="bi bi-plug me-1"></i> Desconectar
                            </button>
                        </form>
                    </div>
                </template>

                {{-- Status: Desconectado + QR Code --}}
                <template x-if="! connected">
                    <div>
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10"
                                  style="width:80px;height:80px;">
                                <i class="bi bi-whatsapp text-danger" style="font-size:2.5rem;"></i>
                            </span>
                        </div>
                        <h5 class="fw-bold text-danger mb-1">WhatsApp Desconectado</h5>
                        <p class="text-muted small mb-4">Escaneie o QR Code abaixo com seu WhatsApp para conectar.</p>

                        {{-- QR Code --}}
                        <div class="mb-3">
                            <template x-if="qrUrl">
                                <div>
                                    <img :src="qrUrl"
                                         alt="QR Code para conexão WhatsApp"
                                         class="img-fluid rounded border shadow-sm"
                                         style="max-width:220px;"
                                    >
                                    <p class="text-muted mt-2 mb-0" style="font-size:.75rem;">
                                        <i class="bi bi-arrow-repeat me-1"></i>
                                        Atualizando a cada 5 segundos...
                                    </p>
                                </div>
                            </template>

                            <template x-if="! qrUrl">
                                <div class="d-flex align-items-center justify-content-center"
                                     style="width:220px;height:220px;background:#f8f9fa;border-radius:.5rem;margin:0 auto;">
                                    <div class="text-center text-muted">
                                        <div class="spinner-border spinner-border-sm mb-2"></div>
                                        <p class="small mb-0">Carregando QR Code...</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <a href="{{ route('wt.whatsapp.connect') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Recarregar página
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Instruções de uso ─────────────────────────────────────────── --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Como conectar seu WhatsApp</h6>
            </div>
            <div class="card-body">
                <ol class="list-group list-group-numbered mb-0">
                    <li class="list-group-item border-0 d-flex gap-3 px-0">
                        <div>
                            <p class="fw-semibold mb-1">Abra o WhatsApp no celular</p>
                            <p class="text-muted small mb-0">
                                No app do WhatsApp, toque em <strong>Menu</strong> (Android) ou
                                <strong>Configurações</strong> (iPhone).
                            </p>
                        </div>
                    </li>
                    <li class="list-group-item border-0 d-flex gap-3 px-0">
                        <div>
                            <p class="fw-semibold mb-1">Acesse "Aparelhos conectados"</p>
                            <p class="text-muted small mb-0">
                                Toque em <strong>Aparelhos conectados</strong> e depois em
                                <strong>Conectar um aparelho</strong>.
                            </p>
                        </div>
                    </li>
                    <li class="list-group-item border-0 d-flex gap-3 px-0">
                        <div>
                            <p class="fw-semibold mb-1">Escaneie o QR Code</p>
                            <p class="text-muted small mb-0">
                                Aponte a câmera para o QR Code exibido ao lado.
                                A conexão será confirmada automaticamente.
                            </p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>

        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    Atenção
                </h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1">
                        Use um número dedicado para automações — evite misturar com uso pessoal.
                    </li>
                    <li class="mb-1">
                        Realize o <strong>aquecimento gradual</strong>: comece com poucos envios por dia
                        e aumente progressivamente.
                    </li>
                    <li class="mb-1">
                        Envie apenas para contatos que <strong>autorizaram</strong> receber mensagens.
                    </li>
                    <li>
                        Você é responsável pelo consentimento dos destinatários, conforme
                        os Termos de Serviço do WhatsApp.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
