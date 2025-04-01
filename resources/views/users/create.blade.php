@extends('layouts.app')

@section('title', 'Nuovo Utente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuovo Utente</h1>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                        <label for="resource_id">Associa a Risorsa (opzionale)</label>
                        <select id="resource_id" name="resource_id" class="form-select @error('resource_id') is-invalid @enderror">
                            <option value="">Nessuna risorsa</option>
                            @foreach($availableResources as $resource)
                                <option value="{{ $resource->id }}" {{ old('resource_id') == $resource->id ? 'selected' : '' }}>
                                    {{ $resource->name }} ({{ $resource->role }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Seleziona una risorsa per creare un utente con accesso limitato alle attività della risorsa.
                            Solo le risorse attive e non associate ad altri utenti sono disponibili.
                        </small>
                        @error('resource_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="d-block">Tipo di Utente</label>
                        <div class="form-check form-check-inline mt-2">
                            <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" {{ old('is_admin') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_admin">
                                Amministratore (accesso completo)
                            </label>
                        </div>
                        <div class="mt-2">
                            <small class="form-text text-muted">
                                <strong>Amministratore:</strong> Accesso completo a tutte le funzionalità
                                <br>
                                <strong>Utente Risorsa:</strong> Accesso limitato solo alle proprie attività, task e calendario
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Crea Utente</button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Controlla se l'utente è amministratore o meno
        const adminCheckbox = document.getElementById('is_admin');
        const resourceSelect = document.getElementById('resource_id');
        
        // Funzione per aggiornare lo stato del form in base alle selezioni
        function updateFormState() {
            // Se l'utente è un admin, la risorsa è opzionale
            // Se non è admin, la risorsa dovrebbe essere obbligatoria
            if (adminCheckbox.checked) {
                resourceSelect.required = false;
                resourceSelect.parentElement.querySelector('small').textContent = 
                    'Seleziona una risorsa (opzionale per amministratori).';
            } else {
                resourceSelect.required = true;
                resourceSelect.parentElement.querySelector('small').textContent = 
                    'Seleziona una risorsa per creare un utente con accesso limitato alle attività della risorsa.';
            }
        }
        
        // Imposta lo stato iniziale
        updateFormState();
        
        // Aggiorna lo stato quando cambia la selezione dell'admin
        adminCheckbox.addEventListener('change', updateFormState);
    });
</script>
@endpush