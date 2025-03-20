@extends('layouts.app')

@section('title', 'Modifica Progetto')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Modifica Progetto</h1>
        </div>
    </div>

    <form action="{{ route('projects.update', $project->id) }}" method="POST" id="projectForm">
        @csrf
        @method('PUT')
        <div class="card mb-4">
            <div class="card-header">
                <h5>Dettagli del Progetto</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Progetto</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $project->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="client_id">Cliente</label>
                        <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                            <option value="">Seleziona un cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} (Budget: {{ number_format($client->budget, 2) }} €)
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description">Descrizione</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $project->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date">Data Inizio</label>
                        <input type="date" id="start_date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $project->start_date ? $project->start_date->format('Y-m-d') : '') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="end_date">Data Fine</label>
                        <input type="date" id="end_date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $project->end_date ? $project->end_date->format('Y-m-d') : '') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="status">Stato</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="pending" {{ old('status', $project->status) == 'pending' ? 'selected' : '' }}>In attesa</option>
                            <option value="in_progress" {{ old('status', $project->status) == 'in_progress' ? 'selected' : '' }}>In corso</option>
                            <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>Completato</option>
                            <option value="on_hold" {{ old('status', $project->status) == 'on_hold' ? 'selected' : '' }}>In pausa</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Tipo Ore Predefinito per Attività</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="default_hours_type" id="defaultHoursTypeStandard" value="standard" {{ old('default_hours_type', $project->default_hours_type) == 'standard' ? 'checked' : '' }}>
                            <label class="form-check-label" for="defaultHoursTypeStandard">
                                Standard - Scala dalle ore lavorative standard delle risorse
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="default_hours_type" id="defaultHoursTypeExtra" value="extra" {{ old('default_hours_type', $project->default_hours_type) == 'extra' ? 'checked' : '' }}>
                            <label class="form-check-label" for="defaultHoursTypeExtra">
                                Extra - Scala dalle ore extra delle risorse
                            </label>
                        </div>
                        @error('default_hours_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Step di Costo</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @php
                        $costSteps = old('cost_steps', $project->cost_steps ?? [1, 2, 3, 4, 5, 6, 7, 8]);
                        if (!is_array($costSteps)) {
                            $costSteps = json_decode($costSteps) ?? [1, 2, 3, 4, 5, 6, 7, 8];
                        }
                    @endphp
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step1" name="cost_steps[]" value="1" {{ in_array(1, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step1">Costo struttura (25%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step2" name="cost_steps[]" value="2" {{ in_array(2, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step2">Utile gestore azienda (12.5%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step3" name="cost_steps[]" value="3" {{ in_array(3, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step3">Utile IGS (12.5%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step4" name="cost_steps[]" value="4" {{ in_array(4, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step4">Compenso professionista (20%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step5" name="cost_steps[]" value="5" {{ in_array(5, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step5">Bonus professionista (5%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step6" name="cost_steps[]" value="6" {{ in_array(6, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step6">Gestore società (3%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step7" name="cost_steps[]" value="7" {{ in_array(7, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step7">Chi porta il lavoro (8%)</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="step8" name="cost_steps[]" value="8" {{ in_array(8, $costSteps) ? 'checked' : '' }}>
                            <label class="form-check-label" for="step8">Network IGS (14%)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Risorse</h5>
            </div>
            <div class="card-body">
                <div id="resourcesList" class="mb-4">
                    @foreach($resources as $resource)
                    @php
                        $projectResource = $project->resources->firstWhere('id', $resource->id);
                        $isSelected = $projectResource !== null;
                        $standardHours = $isSelected ? ($projectResource->pivot->hours_type == 'standard' ? $projectResource->pivot->hours : 0) : 0;
                        $extraHours = $isSelected ? ($projectResource->pivot->hours_type == 'extra' ? $projectResource->pivot->hours : 0) : 0;
                    @endphp
                    <div class="card resource-item mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input resource-checkbox" 
                                               id="resource{{ $resource->id }}" 
                                               name="resources[]" 
                                               value="{{ $resource->id }}"
                                               {{ $isSelected ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resource{{ $resource->id }}">
                                            <strong>{{ $resource->name }}</strong> - {{ $resource->role }}
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <p class="mb-1">Prezzo di Costo: {{ number_format($resource->cost_price, 2) }} €/h</p>
                                        <p class="mb-1">Prezzo di Vendita: {{ number_format($resource->selling_price, 2) }} €/h</p>
                                        <p class="mb-1">Ore Standard Disponibili: {{ number_format($resource->standard_hours_per_year, 2) }}</p>
                                        <p class="mb-1">Ore Extra Disponibili: {{ number_format($resource->extra_hours_per_year, 2) }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 resource-hours" style="display: {{ $isSelected ? 'block' : 'none' }};">
                                    <div class="form-group mb-3">
                                        <label>Ore Standard:</label>
                                        <input type="number" 
                                               name="resource_standard_hours[{{ $resource->id }}]" 
                                               class="form-control resource-standard-hours-input" 
                                               min="0" 
                                               value="{{ $standardHours }}" 
                                               step="0.5">
                                    </div>
                                    <div class="form-group">
                                        <label>Ore Extra:</label>
                                        <input type="number" 
                                               name="resource_extra_hours[{{ $resource->id }}]" 
                                               class="form-control resource-extra-hours-input" 
                                               min="0" 
                                               value="{{ $extraHours }}" 
                                               step="0.5">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div id="costSummary" class="card mb-4">
                    <div class="card-header">
                        <h5>Riepilogo Costi</h5>
                    </div>
                    <div class="card-body">
                        <div id="resourceCostDetails">
                            <!-- Dettagli costi risorse -->
                        </div>
                        <div class="mt-3">
                            <h6>Totale Costo Progetto: <span id="totalProjectCost">{{ number_format($project->total_cost, 2) }}</span> €</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 mb-4">
                <button type="button" id="calculateCostsBtn" class="btn btn-info me-2">
                    <i class="fas fa-calculator"></i> Calcola Costi
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Aggiorna Progetto
                </button>
                <a href="{{ route('projects.show', $project->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle visualizzazione ore risorse
        const resourceCheckboxes = document.querySelectorAll('.resource-checkbox');
        resourceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const resourceItem = this.closest('.resource-item');
                const hoursContainer = resourceItem.querySelector('.resource-hours');
                
                if (this.checked) {
                    hoursContainer.style.display = 'block';
                } else {
                    hoursContainer.style.display = 'none';
                    const standardHoursInput = hoursContainer.querySelector('.resource-standard-hours-input');
                    const extraHoursInput = hoursContainer.querySelector('.resource-extra-hours-input');
                    standardHoursInput.value = 0;
                    extraHoursInput.value = 0;
                }
            });
        });

        // Calcolo costi
        document.getElementById('calculateCostsBtn').addEventListener('click', function() {
            calculateProjectCosts();
        });

        // Inizializza con i dati esistenti
        calculateProjectCosts();

        // Calcola costi progetto
        function calculateProjectCosts() {
            const costSteps = Array.from(document.querySelectorAll('input[name="cost_steps[]"]:checked')).map(cb => parseInt(cb.value));
            const selectedResources = Array.from(document.querySelectorAll('.resource-checkbox:checked')).map(cb => cb.value);
            const standardHours = {};
            const extraHours = {};
            
            selectedResources.forEach(resourceId => {
                const standardHoursInput = document.querySelector(`input[name="resource_standard_hours[${resourceId}]"]`);
                const extraHoursInput = document.querySelector(`input[name="resource_extra_hours[${resourceId}]"]`);
                standardHours[resourceId] = parseFloat(standardHoursInput.value) || 0;
                extraHours[resourceId] = parseFloat(extraHoursInput.value) || 0;
            });
            
            // AJAX request
            fetch('{{ route("projects.calculate-costs") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    resource_ids: selectedResources,
                    standard_hours: standardHours,
                    extra_hours: extraHours,
                    cost_steps: costSteps
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCostSummary(data);
                } else {
                    alert('Errore nel calcolo dei costi: ' + JSON.stringify(data.errors));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella richiesta. Verifica i dati inseriti.');
            });
        }

        // Visualizza riepilogo costi
        function displayCostSummary(data) {
            const resourceCostDetails = document.getElementById('resourceCostDetails');
            const totalProjectCost = document.getElementById('totalProjectCost');
            
            let detailsHtml = '';
            data.summary.forEach(item => {
                const standardHoursText = item.standard_hours > 0 ? 
                    `${item.standard_hours}h standard x ${item.standard_adjusted_rate.toFixed(2)}€/h = ${(item.standard_hours * item.standard_adjusted_rate).toFixed(2)}€<br>` : '';
                const extraHoursText = item.extra_hours > 0 ? 
                    `${item.extra_hours}h extra x ${item.extra_adjusted_rate.toFixed(2)}€/h = ${(item.extra_hours * item.extra_adjusted_rate).toFixed(2)}€` : '';
                
                detailsHtml += `
                    <div class="resource-cost-item mb-3 p-2 border-bottom">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>${item.name}</strong> (${item.role})
                            </div>
                            <div class="col-md-6 text-end">
                                ${standardHoursText}
                                ${extraHoursText}
                                <strong>Totale: ${item.total_cost.toFixed(2)}€</strong>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            resourceCostDetails.innerHTML = detailsHtml || '<p>Nessuna risorsa selezionata o ore specificate.</p>';
            totalProjectCost.textContent = data.total_cost.toFixed(2);
        }
    });
</script>
@endpush