@extends('whatstrigger.layouts.app')

@section('title', 'Nova Campanha')
@section('page-title', 'Nova Campanha')

@section('content')

<div class="row g-4">
    {{-- ── Formulário ────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <a href="{{ route('wt.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h6 class="mb-0 fw-bold">Configurar campanha</h6>
            </div>

            <div class="card-body"
                 x-data="{
                     message: '{{ old('message', '') }}',
                     maxChars: 4096,
                     sendNow: {{ old('scheduled_at') ? 'false' : 'true' }},
                     get remaining() { return this.maxChars - this.message.length; },
                     get charClass() {
                         if (this.remaining < 100)  return 'text-danger';
                         if (this.remaining < 500)  return 'text-warning';
                         return 'text-muted';
                     }
                 }">

                <form method="POST" action="{{ route('wt.campaigns.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Nome da campanha <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="Ex: Follow-up Turma Excel — Maio 2026"
                            required
                            autofocus
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="message" class="form-label fw-semibold mb-0">
                                Mensagem <span class="text-danger">*</span>
                            </label>
                            <span :class="charClass" class="small" x-text="remaining + ' caracteres restantes'"></span>
                        </div>
                        <textarea
                            id="message"
                            name="message"
                            rows="7"
                            maxlength="4096"
                            class="form-control @error('message') is-invalid @enderror"
                            placeholder="Olá {nome}, tudo bem? Passando para avisar que..."
                            x-model="message"
                            required
                        >{{ old('message') }}</textarea>
                        <div class="form-text">
                            Dica: use uma linguagem pessoal e amigável para maior taxa de leitura.
                        </div>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="target_tags" class="form-label fw-semibold">Tags dos destinatários</label>
                        <input
                            type="text"
                            id="target_tags"
                            name="target_tags"
                            value="{{ old('target_tags') }}"
                            class="form-control @error('target_tags') is-invalid @enderror"
                            placeholder="Ex: alunos, turma-excel (deixe em branco para todos)"
                        >
                        <div class="form-text">
                            Separe por vírgula. Contatos com pelo menos uma dessas tags serão incluídos.
                            @if(! empty($tags))
                                Tags disponíveis:
                                @foreach($tags as $tag)
                                    <span class="badge bg-light text-dark border">{{ $tag }}</span>
                                @endforeach
                            @endif
                        </div>
                        @error('target_tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Agendamento --}}
                    <div class="mb-4">
                        <div class="form-check mb-2">
                            <input
                                type="checkbox"
                                id="send_now"
                                class="form-check-input"
                                x-model="sendNow"
                            >
                            <label for="send_now" class="form-check-label fw-semibold">
                                Enviar imediatamente ao disparar
                            </label>
                        </div>

                        <div x-show="! sendNow" x-transition>
                            <label for="scheduled_at" class="form-label fw-semibold">
                                Data e horário de envio
                            </label>
                            <input
                                type="datetime-local"
                                id="scheduled_at"
                                name="scheduled_at"
                                value="{{ old('scheduled_at') }}"
                                class="form-control @error('scheduled_at') is-invalid @enderror"
                                min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                            >
                            <div class="form-text">
                                O disparo ocorrerá automaticamente no horário indicado.
                            </div>
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-floppy me-1"></i> Salvar campanha
                        </button>
                        <a href="{{ route('wt.campaigns.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Preview da mensagem ───────────────────────────────────────── --}}
    <div class="col-12 col-lg-5"
         x-data="{
             get previewText() {
                 const el = document.getElementById('message');
                 return el ? el.value : '';
             }
         }">
        <div class="card border-0 shadow-sm" style="position: sticky; top: 70px;">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-phone me-1"></i> Preview da mensagem
                </h6>
            </div>
            <div class="card-body">
                <div style="background:#e5ddd5; border-radius:.75rem; padding:1rem; min-height:180px;">
                    <div style="background:#dcf8c6; border-radius:.5rem; padding:.75rem 1rem; max-width:90%; margin-left:auto; box-shadow:0 1px 1px rgba(0,0,0,.1);">
                        <p class="mb-1" style="white-space:pre-wrap; font-size:.875rem; line-height:1.5; color:#111;"
                           x-data
                           x-text="document.getElementById('message') ? document.getElementById('message').value || 'A mensagem aparece aqui...' : 'A mensagem aparece aqui...'"
                           x-init="
                               const ta = document.getElementById('message');
                               if (ta) {
                                   ta.addEventListener('input', () => {
                                       $el.textContent = ta.value || 'A mensagem aparece aqui...';
                                   });
                               }
                           "
                        >A mensagem aparece aqui...</p>
                        <p class="mb-0 text-end" style="font-size:.68rem; color:#8c9a89;">
                            {{ now()->format('H:i') }} <i class="bi bi-check2-all"></i>
                        </p>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Preview ilustrativo — formatação final pode variar no WhatsApp.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
