@extends('layouts.app')

@section('title', 'Nuova Attività')

@push('styles')
<!-- Aggiungi Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

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
                        <label for="resource_ids">Risorse <small class="text-muted">(Puoi selezionare più risorse)</small></label>
                        <select id="resource_ids" name="resource_ids[]" class="form-select select2-multiple @error('resource_ids') is-invalid @enderror" multiple required>
                            <option value="">Seleziona almeno una risorsa</option>
                        </select>
                        @error('resource_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('resource_ids.*')
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
                
                <!-- Sezione per la distribuzione delle risorse -->
                <div id="resource-distribution-section" class="mb-4" style="display: none;">
                    <h5 class="mt-3 mb-3">Distribuzione Minuti per Risorsa</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Puoi specificare come distribuire i minuti stimati tra le risorse selezionate. 
                        I minuti non distribuiti verranno allocati automaticamente.
                    </div>
                    
                    <div id="resource-distribution-container" class="row">
                        <!-- Verrà popolato dinamicamente -->
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <strong>Minuti totali:</strong>
                                <span id="total-minutes" class="ms-2">0</span>
                                <span> / </span>
                                <span id="estimated-minutes">0</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" id="distribute-evenly-btn" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-balance-scale"></i> Distribuisci equamente
                            </button>
                        </div>
                    </div>
                    
                    <div class="progress mt-2">
                        <div id="distribution-progress" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
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
<!-- Aggiungi Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
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
        const resourceInfo = document.querySelector('.resource-info');
        const estimatedCostPreview = document.getElementById('estimatedCostPreview');
        const estimatedCostValue = document.getElementById('estimatedCostValue');
        const areaInfo = document.getElementById('area-info');
        const areaTotalMinutes = document.getElementById('area-total-minutes');
        const areaUsedMinutes = document.getElementById('area-used-minutes');
        const areaRemainingMinutes = document.getElementById('area-remaining-minutes');
        const areaMinutesProgress = document.getElementById('area-minutes-progress');
        const areaMinutesWarning = document.getElementById('area-minutes-warning');
        
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
            allowClear: true
        });
        
        // Event listeners
        if (projectSelect) {
            projectSelect.addEventListener('change', function() {
                const projectId = this.value;
                const selectedOption = this.options[this.selectedIndex];
                const defaultHoursType = selectedOption.dataset.hoursType || 'standard';
                
                // Imposta il tipo di ore predefinito del progetto
                if (hoursTypeSelect) {
                    hoursTypeSelect.value = defaultHoursType;
                }
                
                if (projectId) {
                    selectedProject = projectId;
                    loadAreas(projectId);
                    loadResources(projectId);
                } else {
                    if (areaSelect) {
                        areaSelect.innerHTML = '<option value="">Seleziona un\'area (opzionale)</option>';
                    }
                    if (resourceSelect) {
                        // Reset select2
                        $(resourceSelect).empty().trigger('change');
                    }
                    if (resourceInfo) resourceInfo.style.display = 'none';
                    if (estimatedCostPreview) estimatedCostPreview.style.display = 'none';
                    if (areaInfo) areaInfo.style.display = 'none';
                    if (resourceDistributionSection) resourceDistributionSection.style.display = 'none';
                    selectedArea = null;
                }
            });
        }
        
        // Gestione del cambio area
        if (areaSelect) {
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
                    if (areaInfo) areaInfo.style.display = 'none';
                    if (areaMinutesWarning) areaMinutesWarning.style.display = 'none';
                }
                
                validateEstimatedMinutes();
            });
        }
        
        // Gestione del cambio di risorse
        $(resourceSelect).on('change', function() {
            const selectedValues = $(this).val();
            
            if (selectedValues && selectedValues.length > 0 && resources.length > 0) {
                selectedResources = resources.filter(r => selectedValues.includes(r.id.toString()));
                updateResourceDistribution();
                updateResourceInfo();
                updateEstimatedCost();
                
                // Mostra la sezione distribuzione solo se ci sono multiple risorse
                if (resourceDistributionSection) {
                    resourceDistributionSection.style.display = selectedValues.length > 1 ? 'block' : 'none';
                }
            } else {
                selectedResources = [];
                if (resourceInfo) resourceInfo.style.display = 'none';
                if (estimatedCostPreview) estimatedCostPreview.style.display = 'none';
                if (resourceDistributionSection) resourceDistributionSection.style.display = 'none';
            }
        });
        
        // Altri listener
        if (hoursTypeSelect) {
            hoursTypeSelect.addEventListener('change', function() {
                if (selectedResources.length > 0) {
                    updateResourceInfo();
                    updateEstimatedCost();
                }
            });
        }
        
        if (estimatedMinutesInput) {
            estimatedMinutesInput.addEventListener('input', function() {
                updateEstimatedCost();
                validateEstimatedMinutes();
                updateResourceDistribution();
            });
        }
        
        if (distributeEvenlyBtn) {
            distributeEvenlyBtn.addEventListener('click', function() {
                distributeEvenly();
            });
        }
        
        // Carica le aree per il progetto selezionato
        function loadAreas(projectId) {
            fetch(`/areas/by-project/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && areaSelect) {
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
                        
                        let options = [];
                        resources.forEach(resource => {
                            options.push({
                                id: resource.id,
                                text: `${resource.name} (${resource.role})`
                            });
                        });
                        
                        // Aggiorna il select2 con le nuove opzioni
                        $(resourceSelect).empty().select2({
                            data: options,
                            theme: 'bootstrap-5',
                            placeholder: "Seleziona una o più risorse",
                            allowClear: true
                        });
                        
                        // Trigger change event
                        $(resourceSelect).trigger('change');
                    }
                })
                .catch(error => {
                    console.error('Errore nel caricamento delle risorse:', error);
                    // Mostra un messaggio di errore nel DOM
                    if (resourceSelect) {
                        $(resourceSelect).empty().append(new Option('Errore nel caricamento delle risorse', '')).trigger('change');
                    }
                });
        }
        
        // Aggiorna le informazioni dell'area selezionata
        function updateAreaInfo() {
            if (!selectedArea || !areaInfo) {
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
            if (!estimatedMinutesInput || !areaMinutesWarning) {
                return;
            }
            
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
        
        // Aggiorna la sezione di distribuzione delle risorse
        function updateResourceDistribution() {
            if (!resourceDistributionSection || !resourceDistributionContainer || selectedResources.length <= 1) {
                return;
            }
            
            resourceDistributionSection.style.display = 'block';
            resourceDistributionContainer.innerHTML = '';
            
            const estimatedMinutes = parseInt(estimatedMinutesInput.value) || 0;
            estimatedMinutesSpan.textContent = estimatedMinutes;
            
            selectedResources.forEach(resource => {
                const resourceId = resource.id;
                const resourceName = resource.name;
                
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-6 mb-3';
                
                const inputGroup = document.createElement('div');
                inputGroup.className = 'input-group';
                
                const inputGroupText = document.createElement('span');
                inputGroupText.className = 'input-group-text';
                inputGroupText.textContent = resourceName;
                
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control resource-minutes';
                input.name = `resource_distribution[${resourceId}]`;
                input.min = 0;
                input.max = estimatedMinutes;
                input.value = 0;
                input.dataset.resourceId = resourceId;
                
                // Event listener per l'input
                input.addEventListener('input', function() {
                    updateDistributionStats();
                });
                
                inputGroup.appendChild(inputGroupText);
                inputGroup.appendChild(input);
                
                colDiv.appendChild(inputGroup);
                resourceDistributionContainer.appendChild(colDiv);
            });
            
            // Inizializza la distribuzione equa
            distributeEvenly();
        }
        
        // Distribuisce equamente i minuti tra le risorse
        function distributeEvenly() {
            if (!resourceDistributionContainer || selectedResources.length <= 1) return;
            
            const estimatedMinutes = parseInt(estimatedMinutesInput.value) || 0;
            const equalShare = Math.floor(estimatedMinutes / selectedResources.length);
            let extraMinute = estimatedMinutes % selectedResources.length;
            
            const inputs = resourceDistributionContainer.querySelectorAll('.resource-minutes');
            
            inputs.forEach(input => {
                input.value = equalShare;
                
                // Distribuisci gli eventuali minuti extra (arrotondamento)
                if (extraMinute > 0) {
                    input.value = parseInt(input.value) + 1;
                    extraMinute--;
                }
            });
            
            updateDistributionStats();
        }
        
        // Aggiorna le statistiche di distribuzione
        function updateDistributionStats() {
            if (!resourceDistributionContainer || !distributionProgress) return;
            
            const inputs = resourceDistributionContainer.querySelectorAll('.resource-minutes');
            const estimatedMinutes = parseInt(estimatedMinutesInput.value) || 0;
            
            let totalDistributed = 0;
            
            inputs.forEach(input => {
                totalDistributed += parseInt(input.value) || 0;
            });
            
            totalMinutesSpan.textContent = totalDistributed;
            
            // Aggiorna la barra di progresso
            const percentage = estimatedMinutes > 0 ? (totalDistributed / estimatedMinutes) * 100 : 0;
            distributionProgress.style.width = `${Math.min(100, percentage)}%`;
            distributionProgress.setAttribute('aria-valuenow', percentage);
            
            // Colora la barra di progresso
            if (totalDistributed > estimatedMinutes) {
                distributionProgress.className = 'progress-bar bg-danger';
            } else if (totalDistributed === estimatedMinutes) {
                distributionProgress.className = 'progress-bar bg-success';
            } else {
                distributionProgress.className = 'progress-bar bg-primary';
            }
        }
        
        // Aggiorna le informazioni delle risorse selezionate
        function updateResourceInfo() {
            if (!resourceInfo || selectedResources.length === 0) {
                return;
            }
            
            const hoursType = hoursTypeSelect.value;
            const standardHoursAvailable = document.getElementById('standardHoursAvailable');
            const extraHoursAvailable = document.getElementById('extraHoursAvailable');
            const standardHoursProgress = document.getElementById('standardHoursProgress');
            const extraHoursProgress = document.getElementById('extraHoursProgress');
            
            // Calcola le ore totali disponibili e utilizzate per tutte le risorse
            let totalStandardHours = 0;
            let totalExtraHours = 0;
            let totalStandardHoursUsed = 0;
            let totalExtraHoursUsed = 0;
            
            selectedResources.forEach(resource => {
                totalStandardHours += resource.standard_hours_per_year || 0;
                totalExtraHours += resource.extra_hours_per_year || 0;
                totalStandardHoursUsed += resource.total_standard_estimated_hours || 0;
                totalExtraHoursUsed += resource.total_extra_estimated_hours || 0;
            });
            
            // Calcola le ore rimanenti
            const standardHoursRemaining = Math.max(0, totalStandardHours - totalStandardHoursUsed);
            const extraHoursRemaining = Math.max(0, totalExtraHours - totalExtraHoursUsed);
            
            // Calcola le percentuali di utilizzo
            const standardUsagePercentage = totalStandardHours > 0 ? 
                Math.min(100, (totalStandardHoursUsed / totalStandardHours) * 100) : 0;
            const extraUsagePercentage = totalExtraHours > 0 ? 
                Math.min(100, (totalExtraHoursUsed / totalExtraHours) * 100) : 0;
            
            // Aggiorna i valori nell'interfaccia
            standardHoursAvailable.textContent = `${standardHoursRemaining.toFixed(2)} / ${totalStandardHours.toFixed(2)}`;
            extraHoursAvailable.textContent = `${extraHoursRemaining.toFixed(2)} / ${totalExtraHours.toFixed(2)}`;
            
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
    if (selectedResources.length === 0 || !selectedProject) return;
    
    const hoursType = hoursTypeSelect.value;
    const estimatedMinutes = parseFloat(estimatedMinutesInput.value) || 0;
    
    if (estimatedMinutes <= 0) {
        estimatedCostPreview.style.display = 'none';
        return;
    }
            
            // Se stiamo utilizzando multiple risorse, calcola il costo basato sulla distribuzione
            let totalCost = 0;
            
            if (selectedResources.length === 1) {
                // Calcolo semplice per una singola risorsa
                const resource = selectedResources[0];
                
                // Trova la tariffa oraria corretta per questa risorsa nel progetto
                let hourlyRate = 0;
                if (hoursType === 'standard') {
                    // Cerca la tariffa standard nella pivot della relazione progetto-risorsa
                    const projectResource = resource.pivot && 
                                           resource.pivot.hours_type === 'standard' ? 
                                           resource : null;
                    
                    if (projectResource) {
                        hourlyRate = projectResource.pivot.adjusted_rate;
                    } else {
                        // Se non trovata, usa la tariffa base
                        hourlyRate = resource.selling_price;
                    }
                } else {
                    // Cerca la tariffa extra nella pivot
                    const projectResource = resource.pivot && 
                                           resource.pivot.hours_type === 'extra' ? 
                                           resource : null;
                    
                    if (projectResource) {
                        hourlyRate = projectResource.pivot.adjusted_rate;
                    } else {
                        // Se non trovata, usa la tariffa extra o quella standard come fallback
                        hourlyRate = resource.extra_selling_price || resource.selling_price;
                    }
                }
                
                // Calcola il costo stimato (converti minuti in ore)
                totalCost = (estimatedMinutes / 60) * hourlyRate;
            } else if (selectedResources.length > 1 && resourceDistributionContainer) {
                // Calcolo per multiple risorse con distribuzione
                const inputs = resourceDistributionContainer.querySelectorAll('.resource-minutes');
                
                inputs.forEach(input => {
                    const resourceId = input.dataset.resourceId;
                    const resourceMinutes = parseInt(input.value) || 0;
                    
                    if (resourceMinutes > 0) {
                        const resource = selectedResources.find(r => r.id == resourceId);
                        
                        if (resource) {
                            // Determina la tariffa
                            let hourlyRate = 0;
                            if (hoursType === 'standard') {
                                hourlyRate = resource.selling_price;
                            } else {
                                hourlyRate = resource.extra_selling_price || resource.selling_price;
                            }
                            
                            // Aggiungi al costo totale
                            totalCost += (resourceMinutes / 60) * hourlyRate;
                        }
                    }
                });
            }
            
            // Aggiorna l'interfaccia
            if (estimatedCostValue && estimatedCostPreview) {
                estimatedCostValue.textContent = totalCost.toFixed(2);
                estimatedCostPreview.style.display = 'block';
            }
        }
        
        // Inizializzazione
        if (projectSelect && projectSelect.value) {
            projectSelect.dispatchEvent(new Event('change'));
        }
        
        // Se un'area è già selezionata, mostra le sue informazioni
        if (areaSelect && areaSelect.value) {
            areaSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
