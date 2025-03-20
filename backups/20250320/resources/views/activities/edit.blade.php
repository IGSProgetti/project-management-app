@extends('layouts.app')

@section('title', 'Modifica Attività')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Modifica Attività</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('activities.update', $activity->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Attività</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $activity->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="project_id">Progetto</label>
                        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                            <option value="">Seleziona un progetto</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $activity->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }} ({{ $project->client->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="area_id">Area (opzionale)</label>
                        <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                            <option value="">Seleziona un'area</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id', $activity->area_id) == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="resource_id">Risorsa</label>
                        <select id="resource_id" name="resource_id" class="form-select @error('resource_id') is-invalid @enderror" required>
                            <option value="">Seleziona una risorsa</option>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}" {{ old('resource_id', $activity->resource_id) == $resource->id ? 'selected' : '' }}>
                                    {{ $resource->name }} ({{ $resource->role }})
                                </option>
                            @endforeach
                        </select>
                        @error('resource_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="hours_type">Tipo di Ore</label>
                        <select id="hours_type" name="hours_type" class="form-select @error('hours_type') is-invalid @enderror" required>
                            <option value="standard" {{ old('hours_type', $activity->hours_type) == 'standard' ? 'selected' : '' }}>Ore Standard</option>
                            <option value="extra" {{ old('hours_type', $activity->hours_type) == 'extra' ? 'selected' : '' }}>Ore Extra</option>
                        </select>
                        @error('hours_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="estimated_minutes">Minuti Preventivati</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes', $activity->estimated_minutes) }}" min="1" required>
                        @error('estimated_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="actual_minutes">Minuti Effettivi</label>
                        <input type="number" id="actual_minutes" name="actual_minutes" class="form-control @error('actual_minutes') is-invalid @enderror" value="{{ old('actual_minutes', $activity->actual_minutes) }}" min="0">
                        @error('actual_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="due_date">Data Scadenza (opzionale)</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $activity->due_date ? $activity->due_date->format('Y-m-d') : '') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status">Stato</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="pending" {{ old('status', $activity->status) == 'pending' ? 'selected' : '' }}>In attesa</option>
                            <option value="in_progress" {{ old('status', $activity->status) == 'in_progress' ? 'selected' : '' }}>In corso</option>
                            <option value="completed" {{ old('status', $activity->status) == 'completed' ? 'selected' : '' }}>Completata</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Aggiorna Attività</button>
                        <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-secondary">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Disponibilità Risorsa</h5>
    </div>
    <div class="card-body resource-availability-info">
        <div class="row">
            <div class="col-md-6">
                <h6>Ore Standard</h6>
                <p><strong>Disponibili/Anno:</strong> <span id="standardHoursPerYear">-</span></p>
                <p><strong>Utilizzate (stimate):</strong> <span id="standardHoursUsed">-</span></p>
                <p><strong>Rimanenti:</strong> <span id="standardHoursRemaining">-</span></p>
                <div class="progress mb-3" style="height: 10px;">
                    <div id="standardHoursProgress" class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h6>Ore Extra</h6>
                <p><strong>Disponibili/Anno:</strong> <span id="extraHoursPerYear">-</span></p>
                <p><strong>Utilizzate (stimate):</strong> <span id="extraHoursUsed">-</span></p>
                <p><strong>Rimanenti:</strong> <span id="extraHoursRemaining">-</span></p>
                <div class="progress mb-3" style="height: 10px;">
                    <div id="extraHoursProgress" class="progress-bar bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3" id="hoursTypeInfo">
            <i class="fas fa-info-circle"></i> Seleziona una risorsa per visualizzare le informazioni sulla disponibilità.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione aree in base al progetto selezionato
        const projectSelect = document.getElementById('project_id');
        const areaSelect = document.getElementById('area_id');
        const resourceSelect = document.getElementById('resource_id');
        const hoursTypeSelect = document.getElementById('hours_type');
        
        if (projectSelect && areaSelect) {
            projectSelect.addEventListener('change', function() {
                const projectId = this.value;
                
                if (projectId) {
                    // Salva l'area selezionata attualmente
                    const currentAreaId = areaSelect.value;
                    
                    // Svuota il select delle aree
                    areaSelect.innerHTML = '<option value="">Seleziona un\'area</option>';
                    
                    // Fetch delle aree per il progetto selezionato
                    fetch(`/api/areas-by-project/${projectId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                data.areas.forEach(area => {
                                    const option = document.createElement('option');
                                    option.value = area.id;
                                    option.textContent = area.name;
                                    
                                    // Imposta come selezionata se era l'area corrente
                                    if (area.id == currentAreaId) {
                                        option.selected = true;
                                    }
                                    
                                    areaSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(error => console.error('Errore nel recupero delle aree:', error));
                }
            });
            
            // Trigger il change event se un progetto è già selezionato
            if (projectSelect.value) {
                projectSelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Gestione delle informazioni sulla disponibilità della risorsa
        if (resourceSelect) {
            resourceSelect.addEventListener('change', updateResourceAvailability);
            // Aggiorna anche quando cambia il tipo di ore
            if (hoursTypeSelect) {
                hoursTypeSelect.addEventListener('change', updateResourceAvailability);
            }
            
            // Aggiorna subito se una risorsa è già selezionata
            if (resourceSelect.value) {
                updateResourceAvailability();
            }
        }
        
        function updateResourceAvailability() {
            const resourceId = resourceSelect.value;
            
            if (!resourceId) {
                document.getElementById('hoursTypeInfo').textContent = 'Seleziona una risorsa per visualizzare le informazioni sulla disponibilità.';
                document.getElementById('hoursTypeInfo').style.display = 'block';
                return;
            }
            
            // Ottieni i dati della risorsa selezionata
            fetch(`/api/resource-availability/${resourceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Aggiorna i campi con i dati ricevuti
                        document.getElementById('standardHoursPerYear').textContent = data.standardHoursPerYear.toFixed(2);
                        document.getElementById('standardHoursUsed').textContent = data.standardHoursUsed.toFixed(2);
                        document.getElementById('standardHoursRemaining').textContent = data.standardHoursRemaining.toFixed(2);
                        
                        document.getElementById('extraHoursPerYear').textContent = data.extraHoursPerYear.toFixed(2);
                        document.getElementById('extraHoursUsed').textContent = data.extraHoursUsed.toFixed(2);
                        document.getElementById('extraHoursRemaining').textContent = data.extraHoursRemaining.toFixed(2);
                        
                        // Calcola e aggiorna le percentuali di utilizzo
                        const standardUsagePercentage = data.standardHoursPerYear > 0 ? 
                            Math.min(100, (data.standardHoursUsed / data.standardHoursPerYear) * 100) : 0;
                        
                        const extraUsagePercentage = data.extraHoursPerYear > 0 ? 
                            Math.min(100, (data.extraHoursUsed / data.extraHoursPerYear) * 100) : 0;
                        
                        // Aggiorna le barre di progresso
                        const standardProgressBar = document.getElementById('standardHoursProgress');
                        standardProgressBar.style.width = `${standardUsagePercentage}%`;
                        standardProgressBar.textContent = `${standardUsagePercentage.toFixed(1)}%`;
                        standardProgressBar.setAttribute('aria-valuenow', standardUsagePercentage);
                        
                        if (standardUsagePercentage > 90) {
                            standardProgressBar.classList.remove('bg-primary');
                            standardProgressBar.classList.add('bg-danger');
                        } else {
                            standardProgressBar.classList.remove('bg-danger');
                            standardProgressBar.classList.add('bg-primary');
                        }
                        
                        const extraProgressBar = document.getElementById('extraHoursProgress');
                        extraProgressBar.style.width = `${extraUsagePercentage}%`;
                        extraProgressBar.textContent = `${extraUsagePercentage.toFixed(1)}%`;
                        extraProgressBar.setAttribute('aria-valuenow', extraUsagePercentage);
                        
                        if (extraUsagePercentage > 90) {
                            extraProgressBar.classList.remove('bg-warning');
                            extraProgressBar.classList.add('bg-danger');
                        } else {
                            extraProgressBar.classList.remove('bg-danger');
                            extraProgressBar.classList.add('bg-warning');
                        }
                        
                        // Aggiorna il messaggio informativo per il tipo di ore selezionato
                        const hoursType = hoursTypeSelect.value;
                        let infoMessage = "";
                        
                        if (hoursType === 'standard') {
                            if (data.standardHoursRemaining <= 0) {
                                infoMessage = "ATTENZIONE: La risorsa ha esaurito le ore standard disponibili. Considera di utilizzare ore extra o cambiare risorsa.";
                                document.getElementById('hoursTypeInfo').className = 'alert alert-danger mt-3';
                            } else if (standardUsagePercentage > 90) {
                                infoMessage = "ATTENZIONE: La risorsa ha utilizzato più del 90% delle ore standard disponibili.";
                                document.getElementById('hoursTypeInfo').className = 'alert alert-warning mt-3';
                            } else {
                                infoMessage = `La risorsa ha ancora ${data.standardHoursRemaining.toFixed(2)} ore standard disponibili.`;
                                document.getElementById('hoursTypeInfo').className = 'alert alert-info mt-3';
                            }
                        } else { // hours_type === 'extra'
                            if (data.extraHoursRemaining <= 0) {
                                infoMessage = "ATTENZIONE: La risorsa ha esaurito le ore extra disponibili. Considera di cambiare risorsa.";
                                document.getElementById('hoursTypeInfo').className = 'alert alert-danger mt-3';
                            } else if (extraUsagePercentage > 90) {
                                infoMessage = "ATTENZIONE: La risorsa ha utilizzato più del 90% delle ore extra disponibili.";
                                document.getElementById('hoursTypeInfo').className = 'alert alert-warning mt-3';
                            } else {
                                infoMessage = `La risorsa ha ancora ${data.extraHoursRemaining.toFixed(2)} ore extra disponibili.`;
                                document.getElementById('hoursTypeInfo').className = 'alert alert-info mt-3';
                            }
                        }
                        
                        document.getElementById('hoursTypeInfo').innerHTML = `<i class="fas fa-info-circle"></i> ${infoMessage}`;
                        document.getElementById('hoursTypeInfo').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Errore nel recupero delle informazioni sulla disponibilità:', error);
                    document.getElementById('hoursTypeInfo').textContent = 'Errore nel recupero delle informazioni sulla disponibilità.';
                    document.getElementById('hoursTypeInfo').className = 'alert alert-danger mt-3';
                    document.getElementById('hoursTypeInfo').style.display = 'block';
                });
        }
    });
</script>
@endpush