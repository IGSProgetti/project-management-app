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
                    <div class="col-md-6 mb-3">
                        <label for="area_id">Area (opzionale)</label>
                        <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                            <option value="">Seleziona un'area</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id', $activity->area_id) == $area->id ? 'selected' : '' }}
                                    data-estimated-minutes="{{ $area->estimated_minutes }}"
                                    data-used-minutes="{{ $area->activities_estimated_minutes }}"
                                    data-remaining-minutes="{{ $area->remaining_estimated_minutes }}">
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="resource_ids">Risorse <small class="text-muted">(Puoi selezionare più risorse)</small></label>
                        <select id="resource_ids" name="resource_ids[]" class="form-select select2-multiple @error('resource_ids') is-invalid @enderror" multiple required>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}" 
                                    {{ (old('resource_ids') && in_array($resource->id, old('resource_ids'))) || 
                                       ($activity->has_multiple_resources && $activity->resources->contains($resource->id)) || 
                                       (!$activity->has_multiple_resources && $activity->resource_id == $resource->id) ? 'selected' : '' }}>
                                    {{ $resource->name }} ({{ $resource->role }})
                                </option>
                            @endforeach
                        </select>
                        @error('resource_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="estimated_minutes">Minuti Stimati</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes', $activity->estimated_minutes) }}" min="1" required>
                        <div id="area-minutes-warning" class="text-danger mt-1" style="display: none;"></div>
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
                    
                    <div class="col-md-4 mb-3">
                        <label for="hours_type">Tipo Ore</label>
                        <select id="hours_type" name="hours_type" class="form-select @error('hours_type') is-invalid @enderror" required>
                            <option value="standard" {{ old('hours_type', $activity->hours_type) == 'standard' ? 'selected' : '' }}>Standard</option>
                            <option value="extra" {{ old('hours_type', $activity->hours_type) == 'extra' ? 'selected' : '' }}>Extra</option>
                        </select>
                        @error('hours_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Sezione per la distribuzione delle risorse -->
                <div id="resource-distribution-section" class="mb-4" style="display: {{ $activity->has_multiple_resources ? 'block' : 'none' }};">
                    <h5 class="mt-3 mb-3">Distribuzione Minuti per Risorsa</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Puoi specificare come distribuire i minuti stimati tra le risorse selezionate. 
                        I minuti non distribuiti verranno allocati automaticamente.
                    </div>
                    
                    <div id="resource-distribution-container" class="row">
                        @if($activity->has_multiple_resources)
                            @foreach($activity->resources as $resource)
                                <div class="col-md-6 mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $resource->name }}</span>
                                        <input type="number" class="form-control resource-minutes" 
                                               name="resource_distribution[{{ $resource->id }}]" 
                                               value="{{ old('resource_distribution.'.$resource->id, $resourceDistribution[$resource->id] ?? $resource->pivot->estimated_minutes) }}" 
                                               min="0" 
                                               max="{{ $activity->estimated_minutes }}" 
                                               data-resource-id="{{ $resource->id }}">
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <strong>Minuti totali:</strong>
                                <span id="total-minutes" class="ms-2">{{ $activity->has_multiple_resources ? $activity->resources->sum('pivot.estimated_minutes') : $activity->estimated_minutes }}</span>
                                <span> / </span>
                                <span id="estimated-minutes">{{ $activity->estimated_minutes }}</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" id="distribute-evenly-btn" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-balance-scale"></i> Distribuisci equamente
                            </button>
                        </div>
                    </div>
                    
                    <div class="progress mt-2">
                        <div id="distribution-progress" class="progress-bar" role="progressbar" 
                             style="width: {{ $activity->has_multiple_resources && $activity->estimated_minutes > 0 ? 
                                    min(100, ($activity->resources->sum('pivot.estimated_minutes') / $activity->estimated_minutes) * 100) : 0 }}%" 
                             aria-valuenow="{{ $activity->has_multiple_resources && $activity->estimated_minutes > 0 ? 
                                    min(100, ($activity->resources->sum('pivot.estimated_minutes') / $activity->estimated_minutes) * 100) : 0 }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div id="area-info" class="alert alert-info" style="display: {{ $activity->area_id ? 'block' : 'none' }};">
                            <!-- Area info content will be populated by JavaScript -->
                        </div>
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
        <h5>Disponibilità Risorse</h5>
    </div>
    <div class="card-body resource-availability-info">
        <div id="resources-availability-container">
            <!-- Verrà popolato via JavaScript -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variabili globali
        let resources = [];
        let selectedResources = [];
        let selectedProject = null;
        let selectedArea = null;
        
        // Elementi DOM
        const projectSelect = document.getElementById('project_id');
        const areaSelect = document.getElementById('area_id');
        const resourceSelect = document.getElementById('resource_ids');
        const hoursTypeSelect = document.getElementById('hours_type');
        const estimatedMinutesInput = document.getElementById('estimated_minutes');
        const actualMinutesInput = document.getElementById('actual_minutes');
        const areaInfo = document.getElementById('area-info');
        const areaMinutesWarning = document.getElementById('area-minutes-warning');
        const resourcesAvailabilityContainer = document.getElementById('resources-availability-container');
        
        // Elementi per la distribuzione delle risorse
        const resourceDistributionSection = document.getElementById('resource-distribution-section');
        const resourceDistributionContainer = document.getElementById('resource-distribution-container');
        const totalMinutesSpan = document.getElementById('total-minutes');
        const estimatedMinutesSpan = document.getElementById('estimated-minutes');
        const distributionProgress = document.getElementById('distribution-progress');
        const distributeEvenlyBtn = document.getElementById('distribute-evenly-btn');
        
        // Inizializza Select2 per la selezione multipla di risorse
        $(resourceSelect).select2({
            theme: 'bootstrap-5',
            placeholder: "Seleziona una o più risorse",
            allowClear: false
        });
        
        // Event listeners
        projectSelect.addEventListener('change', function() {
            const projectId = this.value;
            
            if (projectId) {
                selectedProject = projectId;
                loadAreas(projectId);
                loadResources(projectId);
            } else {
                areaSelect.innerHTML = '<option value="">Seleziona un\'area (opzionale)</option>';
                // Ripristina le risorse originali
                resetResourceOptions();
                selectedArea = null;
                updateAreaInfo();
            }
        });
        
        areaSelect.addEventListener('change', function() {
            const areaId = this.value;
            if (areaId) {
                const selectedOption = this.options[this.selectedIndex];
                selectedArea = {
                    id: areaId,
                    estimatedMinutes: parseInt(selectedOption.dataset.estimatedMinutes) || 0,
                    usedMinutes: parseInt(selectedOption.dataset.usedMinutes) || 0,
                    remainingMinutes: parseInt(selectedOption.dataset.remainingMinutes) || 0
                };
            } else {
                selectedArea = null;
            }
            
            updateAreaInfo();
            validateEstimatedMinutes();
        });
        
        $(resourceSelect).on('change', function() {
            const selectedValues = $(this).val();
            
            if (selectedValues && selectedValues.length > 0) {
                selectedResources = Array.from(resources).filter(r => selectedValues.includes(r.id.toString()));
                
                // Aggiorna la sezione di distribuzione delle risorse
                updateResourceDistribution();
                
                // Mostra la sezione distribuzione solo se ci sono multiple risorse
                resourceDistributionSection.style.display = selectedValues.length > 1 ? 'block' : 'none';
                
                // Aggiorna le informazioni sulla disponibilità delle risorse
                updateResourcesAvailability();
            } else {
                selectedResources = [];
                resourceDistributionSection.style.display = 'none';
                resourcesAvailabilityContainer.innerHTML = '<div class="alert alert-warning">Nessuna risorsa selezionata</div>';
            }
        });
        
        estimatedMinutesInput.addEventListener('input', function() {
            validateEstimatedMinutes();
            updateResourceDistribution();
            updateEstimatedMinutesInDistribution();
        });
        
        distributeEvenlyBtn.addEventListener('click', function() {
            distributeEvenly();
        });
        
        // Aggiunge event listener agli input di distribuzione esistenti
        document.querySelectorAll('.resource-minutes').forEach(input => {
            input.addEventListener('input', function() {
                updateDistributionStats();
            });
        });
        
        // Implementazione delle funzioni omesse per brevità
        
        // Inizializzazione
        if (projectSelect.value) {
            selectedProject = projectSelect.value;
            // Carica le risorse attuali per riferimento
            resources = Array.from(resourceSelect.options).map(option => {
                if (option.value) {
                    return {
                        id: option.value,
                        name: option.text.split('(')[0].trim(),
                        role: option.text.match(/\(([^)]+)\)/)?.[1] || ''
                    };
                }
                return null;
            }).filter(Boolean);
        }
        
        // Seleziona le risorse iniziali dall'attività
        selectedResources = resources.filter(r => {
            return @if($activity->has_multiple_resources) 
                      [{{ $activity->resources->pluck('id')->implode(',') }}].includes(parseInt(r.id))
                   @else 
                      r.id == '{{ $activity->resource_id }}'
                   @endif;
        });
        
        // Trigger change events per inizializzare la UI
        if (areaSelect.value) {
            areaSelect.dispatchEvent(new Event('change'));
        }
        
        $(resourceSelect).trigger('change');
        
        // Aggiorna le statistiche di distribuzione iniziali
        updateDistributionStats();
    });
</script>
@endpush