@extends('layouts.app')

@section('title', 'Nuovo Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuovo Task</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('tasks.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="activity_id">Attività</label>
                        <select id="activity_id" name="activity_id" class="form-select @error('activity_id') is-invalid @enderror" required>
                            <option value="">Seleziona un'attività</option>
                            @foreach($activities as $activity)
                                <option value="{{ $activity->id }}" {{ old('activity_id', $selectedActivity->id ?? '') == $activity->id ? 'selected' : '' }}>
                                    {{ $activity->name }} ({{ $activity->project->name ?? 'N/D' }})
                                </option>
                            @endforeach
                        </select>
                        @error('activity_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Task</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="resource_id">Risorsa Assegnata</label>
                        <select id="resource_id" name="resource_id" class="form-select @error('resource_id') is-invalid @enderror">
                            <option value="">Seleziona una risorsa</option>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}" {{ old('resource_id', Auth::user()->resource_id ?? '') == $resource->id ? 'selected' : '' }}>
                                    {{ $resource->name }} ({{ $resource->role }})
                                </option>
                            @endforeach
                        </select>
                        @error('resource_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="estimated_minutes">Minuti Stimati</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes') }}" min="1" required>
                        @error('estimated_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="due_date">Data Scadenza</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status">Stato</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>In attesa</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In corso</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completato</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description">Descrizione</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salva Task</button>
                        <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Annulla</a>
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
        // Gestione del cambio attività per caricare le risorse disponibili
        const activitySelect = document.getElementById('activity_id');
        const resourceSelect = document.getElementById('resource_id');
        
        if (activitySelect && resourceSelect) {
            activitySelect.addEventListener('change', function() {
                const activityId = this.value;
                
                if (!activityId) {
                    return;
                }
                
                // Salva la risorsa attualmente selezionata
                const currentResourceId = resourceSelect.value;
                
                // Effettua una chiamata AJAX per ottenere le risorse dell'attività
                fetch(`/api/activity-resources/${activityId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Pulisci le opzioni esistenti
                            resourceSelect.innerHTML = '<option value="">Seleziona una risorsa</option>';
                            
                            // Aggiungi le nuove opzioni
                            data.resources.forEach(resource => {
                                const option = document.createElement('option');
                                option.value = resource.id;
                                option.textContent = `${resource.name} (${resource.role})`;
                                
                                // Se questa era la risorsa precedentemente selezionata, mantieni la selezione
                                if (resource.id == currentResourceId) {
                                    option.selected = true;
                                }
                                
                                resourceSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Errore durante il caricamento delle risorse:', error);
                    });
            });
        }
    });
</script>
@endpush