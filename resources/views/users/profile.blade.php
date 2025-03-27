@extends('layouts.app')

@section('title', 'Profilo Utente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Il Mio Profilo</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Modifica Profilo</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.updateProfile') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name">Nome</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="current_password">Password Attuale (richiesta per cambiare password)</label>
                            <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password">Nuova Password (lasciare vuoto per mantenere invariata)</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmation">Conferma Nuova Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Aggiorna Profilo</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Utente</h5>
                </div>
                <div class="card-body">
                    <p><strong>Ruolo:</strong> 
                        @if($user->isAdmin())
                            <span class="badge bg-danger">Amministratore</span>
                        @else
                            <span class="badge bg-info">{{ $user->getRole() }}</span>
                        @endif
                    </p>
                    <p><strong>Account creato il:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
                    
                    @if($user->resource)
                        <hr>
                        <h5>Risorsa Associata</h5>
                        <p><strong>Nome Risorsa:</strong> {{ $user->resource->name }}</p>
                        <p><strong>Ruolo:</strong> {{ $user->resource->role }}</p>
                        <p><strong>Stato:</strong>
                            <span class="badge {{ $user->resource->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $user->resource->is_active ? 'Attivo' : 'Inattivo' }}
                            </span>
                        </p>
                        
                        <a href="{{ route('resources.show', $user->resource->id) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> Visualizza Dettagli Risorsa
                        </a>
                    @else
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> Il tuo account non è associato a nessuna risorsa nel sistema.
                            <p class="mt-2 mb-0">Contatta un amministratore se hai bisogno di associare il tuo account a una risorsa.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection