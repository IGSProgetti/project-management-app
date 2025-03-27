@extends('layouts.app')

@section('title', 'Dettagli Utente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $user->name }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Utente</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nome:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p>
                        <strong>Ruolo:</strong>
                        @if($user->isAdmin())
                            <span class="badge bg-danger">Amministratore</span>
                        @else
                            <span class="badge bg-info">{{ $user->getRole() }}</span>
                        @endif
                    </p>
                    <p><strong>Data Creazione:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Ultimo Aggiornamento:</strong> {{ $user->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Risorsa Associata</h5>
                </div>
                <div class="card-body">
                    @if($user->resource)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>{{ $user->resource->name }}</h5>
                            <a href="{{ route('resources.show', $user->resource->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Visualizza Risorsa
                            </a>
                        </div>
                        <p><strong>Ruolo:</strong> {{ $user->resource->role }}</p>
                        <p><strong>Email:</strong> {{ $user->resource->email }}</p>
                        <p><strong>Telefono:</strong> {{ $user->resource->phone }}</p>
                        <p>
                            <strong>Stato:</strong>
                            <span class="badge {{ $user->resource->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $user->resource->is_active ? 'Attivo' : 'Inattivo' }}
                            </span>
                        </p>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nessuna risorsa associata a questo utente.
                        </div>
                        
                        <p>
                            Associando una risorsa, l'utente potrà operare come quella risorsa nel sistema,
                            visualizzando i progetti e le attività assegnate alla risorsa.
                        </p>
                        
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
                            <i class="fas fa-link"></i> Associa una Risorsa
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection