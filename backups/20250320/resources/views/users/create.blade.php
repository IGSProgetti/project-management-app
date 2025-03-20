@extends('layouts.app')

@section('title', 'Nuovo Utente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuovo Utente</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation">Conferma Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="resource_id">Associa a Risorsa</label>
                        <select id="resource_id" name="resource_id" class="form-select @error('resource_id') is-invalid @enderror">
                            <option value="">Nessuna risorsa associata</option>
                            @foreach($availableResources as $resource)
                                <option value="{{ $resource->id }}" {{ old('resource_id') == $resource->id ? 'selected' : '' }}>
                                    {{ $resource->name }} ({{ $resource->role }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Associando una risorsa, l'utente potrà accedere come questa risorsa nel sistema.
                        </small>
                        @error('resource_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input @error('is_admin') is-invalid @enderror" type="checkbox" id="is_admin" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_admin">
                                Amministratore
                            </label>
                            <div class="form-text">Gli amministratori hanno accesso completo al sistema, inclusa la gestione degli utenti.</div>
                            @error('is_admin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salva Utente</button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
