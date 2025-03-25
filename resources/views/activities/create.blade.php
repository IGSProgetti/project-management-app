@extends('layouts.app')

@section('title', 'Nuova Attività')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuova Attività</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('activities.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="project_id">Progetto</label>
                        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                            <option value="">Seleziona un progetto</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" data-hours-type="{{ $project->default_hours_type }}" {{ old('project_id', $selectedProjectId ?? '') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }} ({{ $project->client->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Attività</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="area_id">Area</label>
                        <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                            <option value="">Seleziona un'area (opzionale)</option>
                            @if(isset($areas) && $areas->count() > 0)
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id', $selectedArea->id ?? '') == $area->id ? 'selected' : '' }}
                                        data-estimated-minutes="{{ $area->estimated_minutes }}"
                                        data-used-minutes="{{ $area->activities_estimated_minutes }}"
                                        data-remaining-minutes="{{ $area->remaining_estimated_minutes }}">
                                        {{ $area->name }} (Minuti rimanenti: {{ $area->remaining_estimated_minutes }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('area_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="resource_id">Risorsa</label>
                        <select id="resource_id" name="resource_id" class="form-select @error('resource_id') is-invalid @enderror" required>
                            <option value="">Seleziona una risorsa</option>
                        </select>
                        @error('resource_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="estimated_minutes">Minuti Stimati</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes') }}" min="1" required>
                        <div id="area-minutes-warning" class="text-danger mt-1" style="display: none;"></div>
                        @error('estimated_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="due_date">Data Scadenza</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="hours_type">Tipo Ore</label>
                        <select id="hours_type" name="hours_type" class="form-select @error('hours_type') is-invalid @enderror" required>
                            <option value="standard" {{ old('hours_type') == 'standard' ? 'selected' : '' }}>Standard</option>
                            <option value="extra" {{ old('hours_type') == 'extra' ? 'selected' : '' }}>Extra</option>
                        </select>
                        @error('hours_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div id="area-info" class="alert alert-info" style="display: none;">
                            <h6>Informazioni Area</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Minuti Totali:</strong> <span id="area-total-minutes">0</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Minuti Utilizzati:</strong> <span id="area-used-minutes">0</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Minuti Rimanenti:</strong> <span id="area-remaining-minutes">0</span>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 10px;">
                                <div id="area-minutes-progress" class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="alert alert-info resource-info" style="display: none;">
                            <div class="resource-standard-hours mb-2">
                                <strong>Ore Standard Disponibili:</strong> <span id="standardHoursAvailable">0</span>
                                <div class="progress" style="height: 10px;">
                                    <div id="standardHoursProgress" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="resource-extra-hours">
                                <strong>Ore Extra Disponibili:</strong> <span id="extraHoursAvailable">0</span>
                                <div class="progress" style="height: 10px;">
                                    <div id="extraHoursProgress" class="progress-bar bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div id="estimatedCostPreview" class="alert alert-success" style="display: none;">
                            <strong>Costo Stimato (Previsione):</strong> <span id="estimatedCostValue">0.00</span> €
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salva Attività</button>
                        <a href="{{ route('activities.index') }}" class="btn btn-secondary">Annulla</a>
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
        // Variabili globali
        let resources = [];
        let selectedResource = null;
        let selectedProject = null;
        let selectedArea = null;
        
        // Elementi DOM
        const projectSelect = document.getElementById('project_id');
        const areaSelect = document.getElementById('area_id');
        const resourceSelect = document.getElementById('resource_id');
        const hoursTypeSelect = document.getElementById('hours_type');
        const estimatedMinutesInput = document.getElementById('estimated_minutes');
        const resourceInfo = document.querySelector('.resource-info');
        const estimatedCostPreview = document.getElementById('estimatedCostPreview');
        const estimatedCostValue = document.getElementById('estimatedCostValue');
        const areaInfo = document.getElementById('area-info');
        const areaTotalMinutes = document.getElementById('area-total-minutes');
        const areaUsedMinutes = document.getElementById('area-used-minutes');
        const areaRemainingMinutes = document.getElementById('area-remaining-minutes');
        const areaMinutesProgress = document.getElementById('area-minutes-progress');
        const areaMinutesWarning = document.getElementById('area-minutes-warning');
        
        // Event listeners
        projectSelect.addEventListener('change', function() {
            const projectId = this.value;
            const selectedOption = this.options[this.selectedIndex];
            const defaultHoursType = selectedOption.dataset.hoursType || 'standard';
            
            // Imposta il tipo di ore predefinito del progetto
            hoursTypeSelect.value = defaultHoursType;
            
            if (projectId) {
                selectedProject = projectId;
                loadAreas(projectId);
                loadResources(projectId);
            } else {
                areaSelect.innerHTML = '<option value="">Seleziona un\'area (opzionale)</option>';
                resourceSelect.innerHTML = '<option value="">Seleziona una risorsa</option>';
                resourceInfo.style.display = 'none';
                estimatedCostPreview.style.display = 'none';
                areaInfo.style.display = 'none';
                selectedArea = null;
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
                updateAreaInfo();
            } else {
                selectedArea = null;
                areaInfo.style.display = 'none';
                areaMinutesWarning.style.display = 'none';
            }
            
            validateEstimatedMinutes();
        });
        
        resourceSelect.addEventListener('change', function() {
            const resourceId = this.value;
            if (resourceId && resources.length > 0) {
                selectedResource = resources.find(r => r.id == resourceId);
                updateResourceInfo();
                updateEstimatedCost();
            } else {
                resourceInfo.style.display = 'none';
                estimatedCostPreview.style.display = 'none';
                selectedResource = null;
            }
        });
        
        hoursTypeSelect.addEventListener('change', function() {
            if (selectedResource) {
                updateResourceInfo();
                updateEstimatedCost();
            }
        });
        
        estimatedMinutesInput.addEventListener('input', function() {
            updateEstimatedCost();
            validateEstimatedMinutes();
        });
        
        // Carica le aree per il progetto selezionato
        function loadAreas(projectId) {
            fetch(`/areas/by-project/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let options = '<option value="">Seleziona un\'area (opzionale)</option>';
                        data.areas.forEach(area => {
                            // Calcoliamo i minuti rimanenti
                            const estimatedMinutes = area.estimated_minutes || 0;
                            const usedMinutes = area.activities_estimated_minutes || 0;
                            const remainingMinutes = Math.max(0, estimatedMinutes - usedMinutes);
                            
                            options += `<option value="${area.id}" 
                                       data-estimated-minutes="${estimatedMinutes}" 
                                       data-used-minutes="${usedMinutes}" 
                                       data-remaining-minutes="${remainingMinutes}">
                                       ${area.name} (Minuti rimanenti: ${remainingMinutes})
                                   </option>`;
                        });
                        areaSelect.innerHTML = options;
                        
                        // Se c'è un'area selezionata precedentemente, proviamo a ri-selezionarla
                        if (selectedArea) {
                            const option = areaSelect.querySelector(`option[value="${selectedArea.id}"]`);
                            if (option) {
                                option.selected = true;
                                areaSelect.dispatchEvent(new Event('change'));
                            }
                        }
                    }
                })
                .catch(error => console.error('Errore nel caricamento delle aree:', error));
        }
        
        // Carica le risorse per il progetto selezionato
        function loadResources(projectId) {
            fetch(`/api/resources-by-project/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resources = data.resources;
                        let options = '<option value="">Seleziona una risorsa</option>';
                        resources.forEach(resource => {
                            options += `<option value="${resource.id}">${resource.name} (${resource.role})</option>`;
                        });
                        resourceSelect.innerHTML = options;
                        
                        // Se c'è una risorsa selezionata precedentemente, proviamo a ri-selezionarla
                        if (selectedResource) {
                            const option = resourceSelect.querySelector(`option[value="${selectedResource.id}"]`);
                            if (option) {
                                option.selected = true;
                                resourceSelect.dispatchEvent(new Event('change'));
                            }
                        }
                    }
                })
                .catch(error => console.error('Errore nel caricamento delle risorse:', error));
        }
        
        // Aggiorna le informazioni dell'area selezionata
        function updateAreaInfo() {
            if (!selectedArea) {
                areaInfo.style.display = 'none';
                return;
            }
            
            areaTotalMinutes.textContent = selectedArea.estimatedMinutes;
            areaUsedMinutes.textContent = selectedArea.usedMinutes;
            areaRemainingMinutes.textContent = selectedArea.remainingMinutes;
            
            // Calcola la percentuale di utilizzo
            const usagePercentage = selectedArea.estimatedMinutes > 0 ? 
                Math.min(100, (selectedArea.usedMinutes / selectedArea.estimatedMinutes) * 100) : 0;
            
            areaMinutesProgress.style.width = `${usagePercentage}%`;
            areaMinutesProgress.setAttribute('aria-valuenow', usagePercentage);
            
            // Cambia il colore della barra in base alla percentuale
            if (usagePercentage > 90) {
                areaMinutesProgress.classList.remove('bg-info', 'bg-warning');
                areaMinutesProgress.classList.add('bg-danger');
            } else if (usagePercentage > 70) {
                areaMinutesProgress.classList.remove('bg-info', 'bg-danger');
                areaMinutesProgress.classList.add('bg-warning');
            } else {
                areaMinutesProgress.classList.remove('bg-warning', 'bg-danger');
                areaMinutesProgress.classList.add('bg-info');
            }
            
            areaInfo.style.display = 'block';
        }
        
        // Verifica che i minuti stimati non superino quelli disponibili nell'area
        function validateEstimatedMinutes() {
            const estimatedMinutes = parseInt(estimatedMinutesInput.value) || 0;
            
            if (selectedArea && estimatedMinutes > 0) {
                if (estimatedMinutes > selectedArea.remainingMinutes) {
                    areaMinutesWarning.textContent = `Attenzione: I minuti stimati (${estimatedMinutes}) superano i minuti disponibili nell'area (${selectedArea.remainingMinutes})`;
                    areaMinutesWarning.style.display = 'block';
                    // Non disabilitiamo il pulsante di invio, ma avvisiamo l'utente
                } else {
                    areaMinutesWarning.style.display = 'none';
                }
            } else {
                areaMinutesWarning.style.display = 'none';
            }
        }
        
        // Aggiorna le informazioni della risorsa selezionata
        function updateResourceInfo() {
            if (!selectedResource) return;
            
            const hoursType = hoursTypeSelect.value;
            const standardHoursAvailable = document.getElementById('standardHoursAvailable');
            const extraHoursAvailable = document.getElementById('extraHoursAvailable');
            const standardHoursProgress = document.getElementById('standardHoursProgress');
            const extraHoursProgress = document.getElementById('extraHoursProgress');
            
            // Calcola le ore disponibili e utilizzate
            const standardHours = selectedResource.standard_hours_per_year || 0;
            const extraHours = selectedResource.extra_hours_per_year || 0;
            const standardHoursUsed = selectedResource.total_standard_estimated_hours || 0;
            const extraHoursUsed = selectedResource.total_extra_estimated_hours || 0;
            
            // Calcola le ore rimanenti
            const standardHoursRemaining = Math.max(0, standardHours - standardHoursUsed);
            const extraHoursRemaining = Math.max(0, extraHours - extraHoursUsed);
            
            // Calcola le percentuali di utilizzo
            const standardUsagePercentage = standardHours > 0 ? 
                Math.min(100, (standardHoursUsed / standardHours) * 100) : 0;
            const extraUsagePercentage = extraHours > 0 ? 
                Math.min(100, (extraHoursUsed / extraHours) * 100) : 0;
            
            // Aggiorna i valori nell'interfaccia
            standardHoursAvailable.textContent = `${standardHoursRemaining.toFixed(2)} / ${standardHours.toFixed(2)}`;
            extraHoursAvailable.textContent = `${extraHoursRemaining.toFixed(2)} / ${extraHours.toFixed(2)}`;
            
            standardHoursProgress.style.width = `${standardUsagePercentage}%`;
            standardHoursProgress.setAttribute('aria-valuenow', standardUsagePercentage);
            
            extraHoursProgress.style.width = `${extraUsagePercentage}%`;
            extraHoursProgress.setAttribute('aria-valuenow', extraUsagePercentage);
            
            // Evidenzia il tipo di ore selezionato
            if (hoursType === 'standard') {
                standardHoursProgress.classList.add('bg-success');
                standardHoursProgress.classList.remove('bg-primary');
                extraHoursProgress.classList.remove('bg-success');
            } else {
                standardHoursProgress.classList.remove('bg-success');
                extraHoursProgress.classList.add('bg-success');
                extraHoursProgress.classList.remove('bg-warning');
            }
            
            resourceInfo.style.display = 'block';
        }
        
        // Calcola e aggiorna il costo stimato
        function updateEstimatedCost() {
            if (!selectedResource || !selectedProject) return;
            
            const hoursType = hoursTypeSelect.value;
            const estimatedMinutes = parseFloat(estimatedMinutesInput.value) || 0;
            
            if (estimatedMinutes <= 0) {
                estimatedCostPreview.style.display = 'none';
                return;
            }
            
            // Trova la tariffa oraria corretta per questa risorsa nel progetto
            let hourlyRate = 0;
            if (hoursType === 'standard') {
                // Cerca la tariffa standard nella pivot della relazione progetto-risorsa
                const projectResource = selectedResource.pivot && 
                                       selectedResource.pivot.hours_type === 'standard' ? 
                                       selectedResource : null;
                
                if (projectResource) {
                    hourlyRate = projectResource.pivot.adjusted_rate;
                } else {
                    // Se non trovata, usa la tariffa base
                    hourlyRate = selectedResource.selling_price;
                }
            } else {
                // Cerca la tariffa extra nella pivot
                const projectResource = selectedResource.pivot && 
                                       selectedResource.pivot.hours_type === 'extra' ? 
                                       selectedResource : null;
                
                if (projectResource) {
                    hourlyRate = projectResource.pivot.adjusted_rate;
                } else {
                    // Se non trovata, usa la tariffa extra o quella standard come fallback
                    hourlyRate = selectedResource.extra_selling_price || selectedResource.selling_price;
                }
            }
            
            // Calcola il costo stimato (converti minuti in ore)
            const estimatedCost = (estimatedMinutes / 60) * hourlyRate;
            
            // Aggiorna l'interfaccia
            estimatedCostValue.textContent = estimatedCost.toFixed(2);
            estimatedCostPreview.style.display = 'block';
        }
        
        // Inizializzazione
        if (projectSelect.value) {
            projectSelect.dispatchEvent(new Event('change'));
        }
        
        // Se un'area è già selezionata, mostra le sue informazioni
        if (areaSelect.value) {
            areaSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush