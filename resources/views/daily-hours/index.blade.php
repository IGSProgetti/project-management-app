@extends('layouts.app')

@section('title', 'Gestione Ore Giornaliere')

@section('content')
<div class="container-fluid">
    <!-- Header con Export -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-calendar-day"></i> Gestione Ore Giornaliere</h1>
        <button id="exportBtn" class="btn btn-success">
            <i class="fas fa-download"></i> Esporta Dati
        </button>
    </div>

    <!-- 🆕 CARD BUDGET CLIENTI -->
    @if(!empty($clientsBudgetData) && count($clientsBudgetData) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h4><i class="fas fa-chart-line"></i> Budget Clienti e Redistribuzioni</h4>
            <div class="row">
                @foreach($clientsBudgetData as $clientData)
                <div class="col-md-4 mb-3">
                    <div class="card" data-client-id="{{ $clientData['id'] }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>{{ $clientData['name'] }}</strong>
                            <span class="badge badge-{{ $clientData['budget_usage_percentage'] > 80 ? 'danger' : ($clientData['budget_usage_percentage'] > 60 ? 'warning' : 'success') }}">
                                {{ number_format($clientData['budget_usage_percentage'], 1) }}%
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row text-center">
                                <div class="col-6">
                                    <strong>Budget Totale:</strong><br>
                                    <span class="text-primary">€{{ number_format($clientData['budget_total'], 2) }}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Utilizzato:</strong><br>
                                    <span class="text-{{ $clientData['budget_usage_percentage'] > 80 ? 'danger' : 'success' }}">€{{ number_format($clientData['budget_used'], 2) }}</span>
                                </div>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-6">
                                    <strong>Rimanente:</strong><br>
                                    <span class="text-success budget-remaining">€{{ number_format($clientData['budget_remaining'], 2) }}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Redistribuzioni:</strong><br>
                                    <span class="text-info">{{ $clientData['redistributions_count'] }}</span>
                                </div>
                            </div>
                            @if($clientData['hours_transferred_today'] != 0)
                            <div class="mt-2 p-2 bg-light rounded">
                                <small class="text-muted">Oggi trasferite:</small><br>
                                <strong class="transferred-hours">{{ $clientData['hours_transferred_today'] > 0 ? '+' : '' }}{{ number_format($clientData['hours_transferred_today'], 1) }}h</strong>
                                <span class="text-muted">(€{{ number_format($clientData['value_transferred_today'], 2) }})</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Filtri -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtri</h5>
        </div>
        <div class="card-body">
            <form id="filtersForm" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label for="date">Data</label>
                        <input type="date" id="date" name="date" class="form-control" 
                               value="{{ $selectedDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="client_id">Cliente</label>
                        <select id="client_id" name="client_id" class="form-select">
                            <option value="">Tutti i clienti</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" 
                                        {{ $selectedClient == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="project_id">Progetto</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">Tutti i progetti</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" 
                                        {{ $selectedProject == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Filtra
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-times"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiche Riepilogative -->
    <div class="row mb-4">
        @php
            $totalCapacity = collect($dailyHoursData)->sum('daily_hours_capacity');
            $totalWorked = collect($dailyHoursData)->sum('total_hours_worked');
            $totalRemaining = collect($dailyHoursData)->sum('remaining_hours');
            $totalRemainingValue = collect($dailyHoursData)->sum('remaining_value');
        @endphp
        
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ number_format($totalCapacity, 1) }}</h3>
                    <p class="mb-0">Ore Totali Disponibili</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ number_format($totalWorked, 1) }}</h3>
                    <p class="mb-0">Ore Lavorate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ number_format($totalRemaining, 1) }}</h3>
                    <p class="mb-0">Ore Non Utilizzate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3>€{{ number_format($totalRemainingValue, 2) }}</h3>
                    <p class="mb-0">Valore Ore Non Utilizzate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Ore Giornaliere per Risorsa -->
    <div class="row">
        @foreach($dailyHoursData as $resourceData)
        <div class="col-12 mb-4">
            <div class="card" data-resource-id="{{ $resourceData['id'] }}" 
                 data-hourly-rate="{{ $resourceData['hourly_rate'] ?? 50 }}"
                 data-standard-rate="{{ $resourceData['hourly_rate'] ?? 50 }}"
                 data-extra-rate="{{ ($resourceData['hourly_rate'] ?? 50) * 1.2 }}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-user text-primary"></i> {{ $resourceData['name'] }} 
                                <span class="badge bg-info">{{ $resourceData['role'] }}</span>
                            </h5>
                            <small class="text-muted">
                                Capacità: {{ $resourceData['daily_hours_capacity'] }}h/giorno | 
                                Ore Lavorate: {{ number_format($resourceData['total_hours_worked'], 1) }}h |
                                <span class="remaining-hours">{{ number_format($resourceData['remaining_hours'], 1) }}</span>h rimanenti 
                                (<span class="remaining-value">€{{ number_format($resourceData['remaining_value'], 2) }}</span>)
                            </small>
                        </div>
                        
                        @if($resourceData['remaining_hours'] > 0)
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning btn-sm transfer-unified-btn" 
                                    data-resource-id="{{ $resourceData['id'] }}"
                                    data-max-unified-hours="{{ $resourceData['remaining_hours'] }}"
                                    data-max-standard-hours="{{ $resourceData['remaining_hours'] * 0.6 }}"
                                    data-max-extra-hours="{{ $resourceData['remaining_hours'] * 0.4 }}">
                                <i class="fas fa-share"></i> Trasferisci ({{ number_format($resourceData['remaining_hours'], 1) }}h)
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                
                @if(!empty($resourceData['clients']))
                    <div class="card-body">
                        <div class="row">
                            @foreach($resourceData['clients'] as $clientData)
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="text-primary">
                                            <i class="fas fa-building"></i> {{ $clientData['name'] }}
                                        </h6>
                                        <p class="mb-2">
                                            <strong>Totale ore:</strong> {{ number_format($clientData['total_hours'], 1) }}h<br>
                                            <strong>Valore:</strong> €{{ number_format($clientData['total_value'], 2) }}
                                        </p>
                                        
                                        @if($clientData['total_hours'] > 0)
                                        <div class="btn-group w-100 mb-2">
                                            <button type="button" class="btn btn-success btn-sm redistribute-btn"
                                                    data-resource-id="{{ $resourceData['id'] }}"
                                                    data-client-id="{{ $clientData['id'] }}"
                                                    data-max-hours="{{ $clientData['total_hours'] }}"
                                                    data-standard-hours="{{ $clientData['total_hours'] * 0.6 }}"
                                                    data-extra-hours="{{ $clientData['total_hours'] * 0.4 }}">
                                                <i class="fas fa-undo"></i> Rimetti ({{ number_format($clientData['total_hours'], 1) }}h)
                                            </button>
                                        </div>
                                        @endif
                                        
                                        @if(!empty($clientData['projects']))
                                            <div class="projects-details">
                                                @foreach($clientData['projects'] as $projectData)
                                                    <div class="mb-2">
                                                        <strong class="text-info">{{ $projectData['name'] }}</strong>
                                                        <span class="badge bg-secondary">{{ number_format($projectData['total_hours'], 1) }}h</span>
                                                        
                                                        @if(!empty($projectData['activities']))
                                                            <div class="ms-3 mt-1">
                                                                @foreach($projectData['activities'] as $activityData)
                                                                    <div class="small text-muted">
                                                                        • {{ $activityData['name'] }}: {{ number_format($activityData['hours'], 1) }}h
                                                                        @if(!empty($activityData['tasks']))
                                                                            <div class="ms-3">
                                                                                @foreach($activityData['tasks'] as $taskData)
                                                                                    <div class="small">
                                                                                        - {{ $taskData['name'] }}: {{ number_format($taskData['hours'], 1) }}h
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal per Redistribuzione Ore -->
<div class="modal fade" id="redistributeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="redistributeModalTitle">Redistribuisci Ore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Risorsa:</strong> <span id="modalResourceName"></span><br>
                    <strong>Ore disponibili:</strong> <span id="modalAvailableHours"></span>h
                </div>
                
                <form id="redistributeForm">
                    <div class="mb-3">
                        <label for="redistributeClientId">Cliente Destinazione</label>
                        <select id="redistributeClientId" class="form-select" required>
                            <option value="">Seleziona cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="redistributeHours">Ore da Redistribuire</label>
                        <div class="input-group">
                            <input type="number" id="redistributeHours" class="form-control" 
                                   step="0.1" min="0.1" required>
                            <span class="input-group-text">h</span>
                        </div>
                        <div class="form-text">
                            Valore: €<span id="redistributeValue">0.00</span>
                        </div>
                        
                        <!-- 🆕 Pulsanti rapidi per ore comuni -->
                        <div class="mt-2">
                            <small class="text-muted">Rapido:</small><br>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-hours-btn" data-hours="1">1h</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-hours-btn" data-hours="2">2h</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-hours-btn" data-hours="4">4h</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-hours-btn" data-hours="8">8h</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-hours-btn" id="allHoursBtn">Tutte</button>
                        </div>
                    </div>
                    
                    <input type="hidden" id="redistributeResourceId">
                    <input type="hidden" id="redistributeAction">
                    <input type="hidden" id="redistributeMaxHours">
                    <input type="hidden" id="redistributeHourlyRate">
                    <input type="hidden" id="redistributeMaxUnifiedHours">
                    <input type="hidden" id="redistributeMaxStandardHours">
                    <input type="hidden" id="redistributeMaxExtraHours">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="confirmRedistribute">
                    <span id="confirmButtonText">Redistribuisci</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast per notifiche -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="successToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto">Successo</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="successToastBody">
            Operazione completata con successo!
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="errorToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-exclamation-circle text-danger me-2"></i>
            <strong class="me-auto">Errore</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="errorToastBody">
            Si è verificato un errore!
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // 🆕 GESTIONE REDISTRIBUZIONE ORE (sia "Rimetti" che "Trasferisci")
    document.querySelectorAll('.redistribute-btn, .transfer-unified-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const resourceId = this.dataset.resourceId;
            const isTransfer = this.classList.contains('transfer-unified-btn');
            const action = isTransfer ? 'transfer' : 'return';
            const clientId = this.dataset.clientId || '';
            
            console.log('Clicked button:', {resourceId, action, clientId});
            
            // Popola il modal
            document.getElementById('redistributeResourceId').value = resourceId;
            document.getElementById('redistributeAction').value = action;
            
            // Trova dati della risorsa
            const resourceCard = this.closest('.card');
            const resourceName = resourceCard.querySelector('h5 .fas.fa-user').parentElement.textContent.trim();
            const standardRate = parseFloat(resourceCard.dataset.standardRate) || 50;
            const extraRate = parseFloat(resourceCard.dataset.extraRate) || 60;
            
            let maxStandardHours, maxExtraHours, maxUnifiedHours;
            
            if (isTransfer) {
                // Trasferimento: usa ore rimanenti della risorsa
                maxStandardHours = parseFloat(this.dataset.maxStandardHours) || 0;
                maxExtraHours = parseFloat(this.dataset.maxExtraHours) || 0;
                maxUnifiedHours = parseFloat(this.dataset.maxUnifiedHours) || 0;
            } else {
                // Rimetti: usa ore lavorate per il cliente
                maxUnifiedHours = parseFloat(this.dataset.maxHours) || 0;
                maxStandardHours = parseFloat(this.dataset.standardHours) || 0;
                maxExtraHours = parseFloat(this.dataset.extraHours) || 0;
            }
            
            // Popola valori nel modal
            document.getElementById('modalResourceName').textContent = resourceName;
            document.getElementById('modalAvailableHours').textContent = maxUnifiedHours.toFixed(1);
            document.getElementById('redistributeMaxHours').value = maxUnifiedHours;
            document.getElementById('redistributeMaxUnifiedHours').value = maxUnifiedHours;
            document.getElementById('redistributeMaxStandardHours').value = maxStandardHours;
            document.getElementById('redistributeMaxExtraHours').value = maxExtraHours;
            document.getElementById('redistributeHourlyRate').value = standardRate;
            
            // Imposta cliente se è "Rimetti"
            if (!isTransfer && clientId) {
                document.getElementById('redistributeClientId').value = clientId;
                document.getElementById('redistributeModalTitle').textContent = 'Rimetti Ore al Cliente';
                document.getElementById('confirmButtonText').textContent = 'Rimetti al Cliente';
            } else {
                document.getElementById('redistributeClientId').value = '';
                document.getElementById('redistributeModalTitle').textContent = 'Trasferisci Ore a Cliente';
                document.getElementById('confirmButtonText').textContent = 'Trasferisci';
            }
            
            // Reset ore
            document.getElementById('redistributeHours').value = '';
            document.getElementById('redistributeValue').textContent = '0.00';
            
            // Aggiorna pulsante "Tutte"
            document.getElementById('allHoursBtn').dataset.hours = maxUnifiedHours;
            document.getElementById('allHoursBtn').textContent = `Tutte (${maxUnifiedHours.toFixed(1)}h)`;
            
            // Mostra modal
            const modal = new bootstrap.Modal(document.getElementById('redistributeModal'));
            modal.show();
        });
    });
    
    // 🆕 GESTIONE PULSANTI ORE RAPIDE
    document.querySelectorAll('.quick-hours-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const hours = parseFloat(this.dataset.hours);
            const maxHours = parseFloat(document.getElementById('redistributeMaxHours').value);
            const actualHours = Math.min(hours, maxHours);
            
            document.getElementById('redistributeHours').value = actualHours;
            updateRedistributeValue();
        });
    });
    
    // 🆕 AGGIORNA VALORE QUANDO CAMBIANO LE ORE
    document.getElementById('redistributeHours').addEventListener('input', updateRedistributeValue);
    
    // 🆕 CONFERMA REDISTRIBUZIONE
    document.getElementById('confirmRedistribute').addEventListener('click', function() {
        const resourceId = document.getElementById('redistributeResourceId').value;
        const clientId = document.getElementById('redistributeClientId').value;
        const hours = document.getElementById('redistributeHours').value;
        const action = document.getElementById('redistributeAction').value;
        
        if (!clientId || !hours || hours <= 0) {
            showErrorMessage('Compila tutti i campi richiesti');
            return;
        }
        
        const maxHours = parseFloat(document.getElementById('redistributeMaxHours').value);
        if (parseFloat(hours) > maxHours) {
            showErrorMessage(`Non puoi redistribuire più di ${maxHours} ore`);
            return;
        }
        
        redistributeHours(resourceId, clientId, hours, action);
        bootstrap.Modal.getInstance(document.getElementById('redistributeModal')).hide();
    });
    
    // Resto del codice esistente...
    document.getElementById('client_id').addEventListener('change', function() {
        const clientId = this.value;
        const projectSelect = document.getElementById('project_id');
        
        projectSelect.innerHTML = '<option value="">Tutti i progetti</option>';
        
        if (clientId) {
            fetch(`/daily-hours/projects-by-client?client_id=${clientId}`)
                .then(response => response.json())
                .then(projects => {
                    projects.forEach(project => {
                        projectSelect.innerHTML += `<option value="${project.id}">${project.name}</option>`;
                    });
                });
        }
    });
    
    document.getElementById('exportBtn').addEventListener('click', function() {
        const form = document.getElementById('filtersForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        window.open(`/daily-hours/export?${params}`, '_blank');
    });
});

// 🆕 FUNZIONE: Aggiorna valore redistribuzione
function updateRedistributeValue() {
    const hours = parseFloat(document.getElementById('redistributeHours').value) || 0;
    const hourlyRate = parseFloat(document.getElementById('redistributeHourlyRate').value) || 50;
    const value = hours * hourlyRate;
    
    document.getElementById('redistributeValue').textContent = value.toFixed(2);
}

// 🔥 FUNZIONE PRINCIPALE CORRETTA - SENZA RELOAD
function redistributeHours(resourceId, clientId, hours, action) {
    console.log('redistributeHours chiamata con:', {resourceId, clientId, hours, action});
    
    const date = document.getElementById('date').value;
    
    // Disabilita il pulsante per evitare doppi click
    const confirmBtn = document.getElementById('confirmRedistribute');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Elaborando...';
    
    fetch('/daily-hours/redistribute', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            resource_id: resourceId,
            client_id: clientId,
            hours: hours,
            action: action,
            date: date
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Mostra messaggio di successo
            showSuccessMessage(data.message);
            
            // 🔥 AGGIORNA DINAMICAMENTE I DATI SENZA RELOAD
            updateResourceRemainingHours(resourceId, hours);
            updateClientBudgetData(clientId, hours, action);
            updateRedistributionCounter(clientId);
            
            // Reset del form del modal
            document.getElementById('redistributeHours').value = '';
            document.getElementById('redistributeValue').textContent = '0.00';
            
        } else {
            showErrorMessage('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Errore durante l\'operazione');
    })
    .finally(() => {
        // Riabilita il pulsante
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

// 🆕 FUNZIONI PER AGGIORNAMENTO DINAMICO
function updateResourceRemainingHours(resourceId, transferredHours) {
    const resourceCard = document.querySelector(`[data-resource-id="${resourceId}"]`);
    if (resourceCard) {
        const remainingHoursElement = resourceCard.querySelector('.remaining-hours');
        const remainingValueElement = resourceCard.querySelector('.remaining-value');
        
        if (remainingHoursElement && remainingValueElement) {
            const currentHours = parseFloat(remainingHoursElement.textContent) || 0;
            const newHours = Math.max(0, currentHours - parseFloat(transferredHours));
            
            remainingHoursElement.textContent = newHours.toFixed(1);
            
            // Calcola nuovo valore
            const hourlyRate = parseFloat(resourceCard.dataset.hourlyRate) || 50;
            const newValue = newHours * hourlyRate;
            remainingValueElement.textContent = '€' + newValue.toFixed(2);
            
            // Evidenzia la modifica
            remainingHoursElement.style.backgroundColor = '#d4edda';
            remainingValueElement.style.backgroundColor = '#d4edda';
            
            setTimeout(() => {
                remainingHoursElement.style.backgroundColor = '';
                remainingValueElement.style.backgroundColor = '';
            }, 2000);
            
            // Aggiorna o nascondi il pulsante "Trasferisci"
            const transferBtn = resourceCard.querySelector('.transfer-unified-btn');
            if (transferBtn) {
                if (newHours > 0) {
                    transferBtn.innerHTML = `<i class="fas fa-share"></i> Trasferisci (${newHours.toFixed(1)}h)`;
                    transferBtn.dataset.maxUnifiedHours = newHours;
                } else {
                    transferBtn.style.display = 'none';
                }
            }
        }
    }
}

function updateClientBudgetData(clientId, hours, action) {
    const clientCard = document.querySelector(`[data-client-id="${clientId}"]`);
    if (clientCard) {
        const budgetRemainingElement = clientCard.querySelector('.budget-remaining');
        
        if (budgetRemainingElement) {
            const hourlyRate = 50; // Valore da prendere dinamicamente se disponibile
            const transferValue = parseFloat(hours) * hourlyRate;
            
            const currentRemaining = parseFloat(budgetRemainingElement.textContent.replace('€', '').replace(',', '')) || 0;
            const newRemaining = currentRemaining + transferValue;
            
            budgetRemainingElement.textContent = '€' + newRemaining.toFixed(2);
            
            // Evidenzia la modifica
            budgetRemainingElement.style.backgroundColor = '#d4edda';
            budgetRemainingElement.style.fontWeight = 'bold';
            
            setTimeout(() => {
                budgetRemainingElement.style.backgroundColor = '';
                budgetRemainingElement.style.fontWeight = '';
            }, 3000);
        }
        
        // Aggiorna le ore trasferite oggi
        const transferredHoursElement = clientCard.querySelector('.transferred-hours');
        if (transferredHoursElement) {
            const currentTransferred = parseFloat(transferredHoursElement.textContent.replace('+', '').replace('h', '')) || 0;
            const newTransferred = currentTransferred + parseFloat(hours);
            transferredHoursElement.textContent = '+' + newTransferred.toFixed(1) + 'h';
        }
    }
}

function updateRedistributionCounter(clientId) {
    const clientCard = document.querySelector(`[data-client-id="${clientId}"]`);
    
    if (clientCard) {
        const redistributionElements = clientCard.querySelectorAll('.text-info');
        
        redistributionElements.forEach(element => {
            if (element.parentElement.textContent.includes('Redistribuzioni:')) {
                const currentCount = parseInt(element.textContent) || 0;
                element.textContent = currentCount + 1;
                
                element.style.color = '#28a745';
                element.style.fontWeight = 'bold';
                element.style.backgroundColor = '#d4edda';
                element.style.borderRadius = '4px';
                element.style.padding = '2px 5px';
                
                setTimeout(() => {
                    element.style.color = '';
                    element.style.fontWeight = '';
                    element.style.backgroundColor = '';
                    element.style.borderRadius = '';
                    element.style.padding = '';
                }, 2000);
            }
        });
    }
}

// 🆕 FUNZIONI PER TOAST NOTIFICATIONS
function showSuccessMessage(message) {
    const toastElement = document.getElementById('successToast');
    const toastBody = document.getElementById('successToastBody');
    
    toastBody.textContent = message;
    
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
}

function showErrorMessage(message) {
    const toastElement = document.getElementById('errorToast');
    const toastBody = document.getElementById('errorToastBody');
    
    toastBody.textContent = message;
    
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
}

// 🆕 FUNZIONE PER RESET FILTRI
function resetFilters() {
    document.getElementById('date').value = '{{ Carbon\Carbon::today()->format('Y-m-d') }}';
    document.getElementById('client_id').value = '';
    document.getElementById('project_id').value = '';
    document.getElementById('filtersForm').submit();
}

// 🆕 GESTIONE ERRORI GLOBALI
window.addEventListener('error', function(event) {
    console.error('Errore JavaScript:', event.error);
    showErrorMessage('Si è verificato un errore imprevisto. Ricarica la pagina se il problema persiste.');
});

// 🆕 FUNZIONE DI DEBUG
function debugInfo() {
    console.log('=== DEBUG INFO ===');
    console.log('Resources cards:', document.querySelectorAll('[data-resource-id]').length);
    console.log('Client cards:', document.querySelectorAll('[data-client-id]').length);
    console.log('Transfer buttons:', document.querySelectorAll('.transfer-unified-btn').length);
    console.log('Redistribute buttons:', document.querySelectorAll('.redistribute-btn').length);
    console.log('==================');
}

window.debugInfo = debugInfo;
</script>
@endsection