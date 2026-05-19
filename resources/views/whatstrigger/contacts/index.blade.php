@extends('whatstrigger.layouts.app')

@section('title', 'Contatos')
@section('page-title', 'Contatos')

@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <form method="GET" action="{{ route('wt.contacts.index') }}" class="d-flex gap-2">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search ?? '' }}"
                        class="form-control form-control-sm"
                        placeholder="Buscar por nome ou telefone..."
                        aria-label="Buscar contatos"
                    >
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-search"></i>
                    </button>
                    @if($search)
                        <a href="{{ route('wt.contacts.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpar busca">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </form>
            </div>
            <div class="col-12 col-md-6 text-md-end">
                <a href="{{ route('wt.contacts.create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-lg me-1"></i> Adicionar Contato
                </a>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        @if($contacts->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">
                    @if($search)
                        Nenhum contato encontrado para "<strong>{{ $search }}</strong>".
                    @else
                        Nenhum contato cadastrado ainda.
                    @endif
                </p>
                @unless($search)
                    <a href="{{ route('wt.contacts.create') }}" class="btn btn-success btn-sm mt-3">
                        Adicionar primeiro contato
                    </a>
                @endunless
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nome</th>
                            <th>Telefone</th>
                            <th>Tags</th>
                            <th class="text-center">Opt-in</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $contact)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $contact->name }}</td>
                                <td class="text-muted">{{ $contact->phone }}</td>
                                <td>
                                    @if(! empty($contact->tags))
                                        @foreach($contact->tags as $tag)
                                            <span class="badge bg-light text-dark border me-1">{{ $tag }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($contact->opted_in)
                                        <i class="bi bi-check-circle-fill text-success" title="Opt-in confirmado"></i>
                                    @else
                                        <i class="bi bi-x-circle text-muted" title="Sem opt-in"></i>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    {{-- Botão Excluir com confirmação via Alpine.js --}}
                                    <div
                                        x-data="{ confirming: false }"
                                        class="d-inline-block"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            x-show="! confirming"
                                            x-on:click="confirming = true"
                                            title="Excluir contato"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>

                                        <div x-show="confirming" x-cloak class="d-inline-flex align-items-center gap-1">
                                            <span class="small text-danger fw-semibold">Confirmar?</span>
                                            <form
                                                method="POST"
                                                action="{{ route('wt.contacts.destroy', $contact->id) }}"
                                                class="d-inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Sim</button>
                                            </form>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                x-on:click="confirming = false"
                                            >Não</button>
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

    @if($contacts->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-muted small">
                    Mostrando {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }}
                    de {{ $contacts->total() }} contatos
                </span>
                {{ $contacts->links() }}
            </div>
        </div>
    @endif
</div>

@endsection
