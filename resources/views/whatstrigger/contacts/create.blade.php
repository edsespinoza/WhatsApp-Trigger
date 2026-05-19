@extends('whatstrigger.layouts.app')

@section('title', 'Novo Contato')
@section('page-title', 'Novo Contato')

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <a href="{{ route('wt.contacts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h6 class="mb-0 fw-bold">Adicionar novo contato</h6>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('wt.contacts.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Nome <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="Ex: Maria Souza"
                            required
                            autofocus
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">
                            Telefone <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="form-control @error('phone') is-invalid @enderror"
                            placeholder="Ex: 11987654321 (sem formatação)"
                            required
                        >
                        <div class="form-text">
                            Informe apenas os dígitos. O código do país (55) será adicionado automaticamente.
                        </div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label fw-semibold">Tags</label>
                        <input
                            type="text"
                            id="tags"
                            name="tags"
                            value="{{ old('tags') }}"
                            class="form-control @error('tags') is-invalid @enderror"
                            placeholder="Ex: alunos, curso-excel, turma-2025"
                        >
                        <div class="form-text">
                            Separe múltiplas tags por vírgula. Usadas para segmentar campanhas.
                        </div>
                        @error('tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="opted_in"
                                name="opted_in"
                                value="1"
                                class="form-check-input"
                                {{ old('opted_in') ? 'checked' : '' }}
                            >
                            <label for="opted_in" class="form-check-label fw-semibold">
                                Contato autorizou receber mensagens (opt-in)
                            </label>
                        </div>
                        <div class="form-text ms-4">
                            Recomendamos enviar apenas para contatos que explicitamente aceitaram receber mensagens.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Salvar contato
                        </button>
                        <a href="{{ route('wt.contacts.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
