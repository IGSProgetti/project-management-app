@extends('layouts.app')

@section('title', 'Gestione Ore Giornaliere')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-clock"></i> Gestione Ore Giornaliere</h1>
            <p class="text-muted">Controllo delle ore lavorative giornaliere e gestione budget clienti</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-success" id="exportBtn">
                <i class="fas fa-download"></i> Esporta Dati
            </button>
            <button class="btn btn-primary" onclick="window.location.reload()">
                <i class="fas fa-sync"></i> Aggiorna
            </button>
        </div>
    </div>

    <!-- 🆕 NUOVA SEZIONE: Dashboard Budget Clienti -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Riepilogo Budget Clienti - {{ Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($clientsBudgetData as $clientData)
                    <div class="col-md-6 col-lg-4 mb-3" data-client-id="{{ $clientData['id'] }}">
                        <div class="card h-100 border-start border-4 
                            @if($clientData['budget_usage_percentage'] > 90) border-danger
                            @elseif($clientData['budget_usage_percentage'] > 75) border-warning
                            @else border-success
                            @endif">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">{{ $clientData['name'] }}</h6>
                                    <span class="badge 
                                        @if($clientData['budget_usage_percentage'] > 90) bg-danger
                                        @elseif($clientData['budget_usage_percentage'] > 75) bg-warning
                                        @else bg-success
                                        @endif">
                                        {{ number_format($clientData['budget_usage_percentage'], 1) }}%
                                    </span>
                                </div>
                                
                                <div class="row text-sm">
                                    <div class="col-6">
                                        <small class="text-muted">Budget Totale:</small><br>
                                        <strong>€{{ number_format($clientData['budget_total'], 2) }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Utilizzato:</small><br>
                                        <strong class="text-danger">€{{ number_format($clientData['budget_used'], 2) }}</strong>
                                    </div>
                                </div>
                                
                                <div class="row text-sm mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">Rimanente:</small><br>
                                        <strong class="text-success">€{{ number_format($clientData['budget_remaining'], 2) }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Redistribuzioni:</small><br>
                                        <strong>{{ $clientData['redistributions_count'] }}</strong>
                                    </div>
                                </div>
                                
                                @if($clientData['hours_transferred_today'] != 0)
                                    <div class="mt-2 p-2 rounded" style="background-color: #f8f9fa;">
                                        <small class="text-muted">Ore trasferite oggi:</small><br>
                                        <strong class="{{ $clientData['hours_transferred_today'] > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $clientData['hours_transferred_today'] > 0 ? '+' : '' }}{{ number_format($clientData['hours_transferred_today'], 1) }}h
                                            ({{ $clientData['value_transferred_today'] > 0 ? '+' : '' }}€{{ number_format($clientData['value_transferred_today'], 2) }})
                                        </strong>
                                    </div>
                                @endif
                                
                                <!-- Barra di progressione budget -->
                                <div class="progress mt-3" style="height: 6px;">
                                    <div class="progress-bar 
                                        @if($clientData['budget_usage_percentage'] > 90) bg-danger
                                        @elseif($clientData['budget_usage_percentage'] > 75) bg-warning
                                        @else bg-success
                                        @endif" 
                                        role="progressbar" 
                                        style="width: {{ min(100, $clientData['budget_usage_percentage']) }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

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

    <!-- Tabella Principale (resto uguale) -->
    @foreach($dailyHoursData as $resourceData)
        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="fas fa-user"></i> {{ $resourceData['name'] }} 
                            <small class="text-muted">({{ $resourceData['role'] }})</small>
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-info me-2">
                            Capacità: {{ $resourceData['daily_hours_capacity'] }}h
                        </span>
                        <span class="badge bg-success me-2">
                            Lavorate: {{ number_format($resourceData['total_hours_worked'], 1) }}h
                        </span>
                        @if($resourceData['remaining_hours'] > 0)
    <div class="card-body border-bottom bg-light">
        <div class="row">
            <div class="col-md-6">
                <strong>Ore Disponibili per Redistribuzione:</strong><br>
                <span class="text-primary fs-5">{{ number_format($resourceData['remaining_hours'], 1) }} ore</span>
                <small class="text-muted d-block">Valore totale: €{{ number_format($resourceData['remaining_value'], 2) }}</small>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    @if(!empty($resourceData['clients']))
                        @foreach($resourceData['clients'] as $client)
                            <button class="btn btn-sm btn-outline-primary redistribute-btn"
                                    data-resource-id="{{ $resourceData['id'] }}"
                                    data-client-id="{{ $client['id'] }}"
                                    data-max-hours="{{ $resourceData['remaining_hours'] }}"
                                    data-action="return">
                                <i class="fas fa-undo"></i> Rimetti a {{ $client['name'] }}
                            </button>
                        @endforeach
                        <div class="w-100"></div>
                    @endif
                    <button class="btn btn-sm btn-success transfer-btn"
                            data-resource-id="{{ $resourceData['id'] }}"
                            data-max-hours="{{ $resourceData['remaining_hours'] }}">
                        <i class="fas fa-exchange-alt"></i> Trasferisci a Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
                    </div>
                </div>
            </div>
            
            @if($resourceData['remaining_hours'] > 0)
                <div class="card-body border-bottom bg-light">
                    <div class="row">
                        <div class="col-md-8">
                            <strong>Gestione Ore Avanzate:</strong>
                            {{ number_format($resourceData['remaining_hours'], 1) }} ore 
                            (€{{ number_format($resourceData['remaining_value'], 2) }})
                        </div>
                        <div class="col-md-4 text-end">
                            @foreach($resourceData['clients'] as $client)
                                <button class="btn btn-sm btn-outline-primary me-1 redistribute-btn"
                                        data-resource-id="{{ $resourceData['id'] }}"
                                        data-client-id="{{ $client['id'] }}"
                                        data-hours="{{ $resourceData['remaining_hours'] }}"
                                        data-action="return">
                                    <i class="fas fa-undo"></i> Rimetti a {{ $client['name'] }}
                                </button>
                            @endforeach
                            <button class="btn btn-sm btn-outline-secondary transfer-btn"
                                    data-resource-id="{{ $resourceData['id'] }}"
                                    data-hours="{{ $resourceData['remaining_hours'] }}">
                                <i class="fas fa-exchange-alt"></i> Trasferisci
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="card-body">
                @if(empty($resourceData['clients']))
                    <p class="text-muted text-center">Nessuna attività registrata per questa data</p>
                @else
                    <!-- Accordion per ogni cliente -->
                    @foreach($resourceData['clients'] as $clientIndex => $client)
                        <div class="accordion mb-3" id="client-{{ $resourceData['id'] }}-{{ $client['id'] }}">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse-client-{{ $resourceData['id'] }}-{{ $client['id'] }}">
                                        <strong>{{ $client['name'] }}</strong>
                                        <span class="ms-auto me-3">
                                            <span class="badge bg-primary">
                                                {{ number_format($client['total_hours'], 1) }}h
                                            </span>
                                            <span class="badge bg-success">
                                                €{{ number_format($client['total_value'], 2) }}
                                            </span>
                                        </span>
                                    </button>
                                </h2>
                                <div id="collapse-client-{{ $resourceData['id'] }}-{{ $client['id'] }}" 
                                     class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        @foreach($client['projects'] as $project)
                                            <div class="border rounded p-3 mb-3">
                                                <h6>
                                                    <i class="fas fa-project-diagram"></i> {{ $project['name'] }}
                                                    <span class="float-end">
                                                        <span class="badge bg-info">
                                                            {{ number_format($project['total_hours'], 1) }}h
                                                        </span>
                                                        <span class="badge bg-secondary">
                                                            €{{ number_format($project['total_value'], 2) }}
                                                        </span>
                                                    </span>
                                                </h6>
                                                
                                                @foreach($project['activities'] as $activity)
                                                    <div class="ms-3 mt-2 border-start border-2 ps-3">
                                                        <strong>{{ $activity['name'] }}</strong>
                                                        <span class="badge bg-light text-dark ms-2">
                                                            {{ number_format($activity['hours'], 1) }}h
                                                        </span>
                                                        <span class="badge bg-light text-dark">
                                                            €{{ number_format($activity['value'], 2) }}
                                                        </span>
                                                        
                                                        @if(!empty($activity['tasks']))
                                                            <div class="mt-2">
                                                                <small class="text-muted">Task:</small>
                                                                @foreach($activity['tasks'] as $task)
                                                                    <div class="ms-3">
                                                                        <small>
                                                                            • {{ $task['name'] }} 
                                                                            ({{ number_format($task['hours'], 1) }}h - 
                                                                            €{{ number_format($task['value'], 2) }})
                                                                        </small>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach
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

@endsection

// 🔍 DEBUGGING: Aggiungi questo JavaScript di test TEMPORANEAMENTE

// Sostituisci la sezione @section('scripts') con questo per testare:

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // 🆕 GESTIONE REDISTRIBUZIONE ORE (sia "Rimetti" che "Trasferisci")
    document.querySelectorAll('.redistribute-btn, .transfer-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const resourceId = this.dataset.resourceId;
            const maxHours = this.dataset.maxHours;
            const isTransfer = this.classList.contains('transfer-btn');
            const action = isTransfer ? 'transfer' : 'return';
            const clientId = this.dataset.clientId || ''; // Solo per "Rimetti"
            
            console.log('Clicked button:', {resourceId, maxHours, action, clientId});
            
            // Popola il modal
            document.getElementById('redistributeResourceId').value = resourceId;
            document.getElementById('redistributeAction').value = action;
            document.getElementById('redistributeMaxHours').value = maxHours;
            document.getElementById('redistributeHourlyRate').value = 50; // Dovrebbe venire dal dataset
            
            // Trova il nome della risorsa
            const resourceCard = this.closest('.card');
            const resourceName = resourceCard.querySelector('h5 .fas.fa-user').parentElement.textContent.trim();
            document.getElementById('modalResourceName').textContent = resourceName;
            document.getElementById('modalAvailableHours').textContent = maxHours;
            
            // Imposta il cliente se è "Rimetti"
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
            document.getElementById('allHoursBtn').dataset.hours = maxHours;
            document.getElementById('allHoursBtn').textContent = `Tutte (${maxHours}h)`;
            
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
            alert('Compila tutti i campi richiesti');
            return;
        }
        
        const maxHours = parseFloat(document.getElementById('redistributeMaxHours').value);
        if (parseFloat(hours) > maxHours) {
            alert(`Non puoi redistribuire più di ${maxHours} ore`);
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

function redistributeHours(resourceId, clientId, hours, action) {
    console.log('redistributeHours chiamata con:', {resourceId, clientId, hours, action});
    
    const date = document.getElementById('date').value;
    
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
            alert(data.message);
            updateRedistributionCounter(clientId);
            updateClientTransferredHours(clientId, parseFloat(hours), action);
            
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Errore durante l\'operazione');
    });
}

function updateRedistributionCounter(clientId) {
    console.log('updateRedistributionCounter per client:', clientId);
    const clientCards = document.querySelectorAll('[data-client-id="' + clientId + '"]');
    console.log('Cards trovate:', clientCards.length);
    
    if (clientCards.length > 0) {
        const card = clientCards[0];
        const redistributionElement = card.querySelector('.col-6:last-child strong');
        
        if (redistributionElement) {
            const currentCount = parseInt(redistributionElement.textContent) || 0;
            redistributionElement.textContent = currentCount + 1;
            console.log('Contatore aggiornato da', currentCount, 'a', currentCount + 1);
            
            redistributionElement.style.color = '#28a745';
            redistributionElement.style.fontWeight = 'bold';
            redistributionElement.parentElement.style.backgroundColor = '#d4edda';
            redistributionElement.parentElement.style.border = '1px solid #c3e6cb';
            redistributionElement.parentElement.style.borderRadius = '4px';
            redistributionElement.parentElement.style.padding = '2px 5px';
            
            setTimeout(() => {
                redistributionElement.style.color = '';
                redistributionElement.parentElement.style.backgroundColor = '';
                redistributionElement.parentElement.style.border = '';
            }, 2000);
        } else {
            console.log('Element redistributionElement non trovato');
        }
    }
}

function updateClientTransferredHours(clientId, hours, action) {
    console.log('updateClientTransferredHours per client:', clientId, 'hours:', hours);
    // Resto della funzione come prima...
}

function resetFilters() {
    document.getElementById('date').value = '{{ Carbon\Carbon::today()->format('Y-m-d') }}';
    document.getElementById('client_id').value = '';
    document.getElementById('project_id').value = '';
    document.getElementById('filtersForm').submit();
}
</script>
@endsection