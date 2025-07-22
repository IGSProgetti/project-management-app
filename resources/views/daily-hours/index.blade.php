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

    <!-- 🆕 STATISTICHE RIEPILOGATIVE UNIFICATE -->
    <div class="row mb-4">
        @php
            // Calcola statistiche unificate (standard + extra)
            $totalStandardCapacity = collect($dailyHoursData)->sum('standard_daily_capacity');
            $totalExtraCapacity = collect($dailyHoursData)->sum('extra_daily_capacity');
            $totalUnifiedCapacity = $totalStandardCapacity + $totalExtraCapacity;
            
            $totalWorked = collect($dailyHoursData)->sum('total_hours_worked');
            $totalStandardRemaining = collect($dailyHoursData)->sum('remaining_standard_hours');
            $totalExtraRemaining = collect($dailyHoursData)->sum('remaining_extra_hours');
            $totalUnifiedRemaining = $totalStandardRemaining + $totalExtraRemaining;
            $totalRemainingValue = collect($dailyHoursData)->sum('remaining_value');
        @endphp
        
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ number_format($totalUnifiedCapacity, 1) }}</h3>
                    <p class="mb-0">Ore Totali Disponibili</p>
                    <small class="opacity-75">
                        {{ number_format($totalStandardCapacity, 1) }}h standard + {{ number_format($totalExtraCapacity, 1) }}h extra
                    </small>
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
                    <h3>{{ number_format($totalUnifiedRemaining, 1) }}</h3>
                    <p class="mb-0">Ore Non Utilizzate</p>
                    <small class="opacity-75">
                        {{ number_format($totalStandardRemaining, 1) }}h std + {{ number_format($totalExtraRemaining, 1) }}h extra
                    </small>
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

    <!-- 🆕 ORE GIORNALIERE CON GESTIONE UNIFICATA STANDARD + EXTRA -->
    <div class="row">
        @foreach($dailyHoursData as $resourceData)
        <div class="col-12 mb-4">
            <div class="card" data-resource-id="{{ $resourceData['id'] }}" 
                 data-standard-rate="{{ $resourceData['standard_hourly_rate'] ?? 50 }}"
                 data-extra-rate="{{ $resourceData['extra_hourly_rate'] ?? 60 }}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-user text-primary"></i> {{ $resourceData['name'] }} 
                                <span class="badge bg-info">{{ $resourceData['role'] }}</span>
                            </h5>
                            
                            <!-- 🆕 NUOVA VISUALIZZAZIONE CAPACITÀ UNIFICATA -->
                            <div class="mt-2">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="me-2">
                                        <strong>Capacità totale:</strong> 
                                        <span class="text-primary">{{ number_format($resourceData['unified_capacity'], 1) }}h</span>
                                    </span>
                                    <div class="capacity-breakdown">
                                        <span class="badge bg-success me-1" title="Ore Standard">
                                            {{ number_format($resourceData['standard_daily_capacity'], 1) }}h std
                                        </span>
                                        <span class="badge bg-warning" title="Ore Extra">
                                            {{ number_format($resourceData['extra_daily_capacity'], 1) }}h extra
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Progress bar per visualizzare l'utilizzo -->
                                <div class="progress mb-2" style="height: 20px;">
                                    @php
                                        $workPercentage = $resourceData['unified_capacity'] > 0 ? 
                                            ($resourceData['total_hours_worked'] / $resourceData['unified_capacity']) * 100 : 0;
                                        $workPercentage = min(100, $workPercentage);
                                    @endphp
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: {{ $workPercentage }}%">
                                        {{ number_format($resourceData['total_hours_worked'], 1) }}h lavorate
                                    </div>
                                </div>
                                
                                <small class="text-muted">
                                    <span class="remaining-unified-hours">{{ number_format($resourceData['unified_remaining_hours'], 1) }}</span>h rimanenti 
                                    (<span class="remaining-value">€{{ number_format($resourceData['remaining_value'], 2) }}</span>)
                                    | Standard: <span class="remaining-standard-hours">{{ number_format($resourceData['remaining_standard_hours'], 1) }}</span>h
                                    | Extra: <span class="remaining-extra-hours">{{ number_format($resourceData['remaining_extra_hours'], 1) }}</span>h
                                </small>
                            </div>
                        </div>
                        
                        <!-- 🆕 PULSANTE TRASFERISCI UNIFICATO -->
                        @if($resourceData['unified_remaining_hours'] > 0)
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning btn-sm transfer-unified-btn" 
                                    data-resource-id="{{ $resourceData['id'] }}"
                                    data-max-unified-hours="{{ $resourceData['unified_remaining_hours'] }}"
                                    data-max-standard-hours="{{ $resourceData['remaining_standard_hours'] }}"
                                    data-max-extra-hours="{{ $resourceData['remaining_extra_hours'] }}">
                                <i class="fas fa-share"></i> Trasferisci 
                                ({{ number_format($resourceData['unified_remaining_hours'], 1) }}h)
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
                                            
                                            <!-- 🆕 BREAKDOWN ORE STANDARD/EXTRA -->
                                            @if(isset($clientData['standard_hours']) || isset($clientData['extra_hours']))
                                            <br><small class="text-muted">
                                                Standard: {{ number_format($clientData['standard_hours'] ?? 0, 1) }}h | 
                                                Extra: {{ number_format($clientData['extra_hours'] ?? 0, 1) }}h
                                            </small>
                                            @endif
                                        </p>
                                        
                                        @if($clientData['total_hours'] > 0)
                                        <div class="btn-group w-100 mb-2">
                                            <button type="button" class="btn btn-success btn-sm redistribute-btn"
                                                    data-resource-id="{{ $resourceData['id'] }}"
                                                    data-client-id="{{ $clientData['id'] }}"
                                                    data-max-hours="{{ $clientData['total_hours'] }}"
                                                    data-standard-hours="{{ $clientData['standard_hours'] ?? 0 }}"
                                                    data-extra-hours="{{ $clientData['extra_hours'] ?? 0 }}">
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
                                                                        @if(isset($activityData['hours_type']))
                                                                            <span class="badge badge-{{ $activityData['hours_type'] === 'standard' ? 'success' : 'warning' }} ms-1">
                                                                                {{ $activityData['hours_type'] }}
                                                                            </span>
                                                                        @endif
                                                                        
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

<!-- 🆕 MODAL REDISTRIBUZIONE CON GESTIONE UNIFICATA -->
<div class="modal fade" id="redistributeUnifiedModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="redistributeModalTitle">Redistribuisci Ore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Risorsa:</strong> <span id="modalResourceName"></span><br>
                    <strong>Ore disponibili:</strong> 
                    <span id="modalAvailableHours"></span>h totali
                    (<span id="modalStandardHours"></span>h standard + <span id="modalExtraHours"></span>h extra)
                </div>
                
                <!-- 🆕 VISUALIZZAZIONE CAPACITÀ DETTAGLIATA -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-success">Ore Standard Disponibili</h6>
                                <h4 class="text-success" id="availableStandardHours">0h</h4>
                                <small class="text-muted">Tariffa: €<span id="standardRate">50</span>/h</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-warning">Ore Extra Disponibili</h6>
                                <h4 class="text-warning" id="availableExtraHours">0h</h4>
                                <small class="text-muted">Tariffa: €<span id="extraRate">60</span>/h</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form id="redistributeUnifiedForm">
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
                        <label for="redistributeUnifiedHours">Ore da Redistribuire</label>
                        <div class="input-group">
                            <input type="number" id="redistributeUnifiedHours" class="form-control" 
                                   step="0.1" min="0.1" required>
                            <span class="input-group-text">h</span>
                        </div>
                        
                        <!-- 🆕 BREAKDOWN AUTOMATICO STANDARD/EXTRA -->
                        <div class="mt-2 p-2 bg-light rounded" id="hoursBreakdown">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-success">Standard: <span id="breakdownStandard">0</span>h</small><br>
                                    <small class="text-muted">Valore: €<span id="breakdownStandardValue">0.00</span></small>
                                </div>
                                <div class="col-6">
                                    <small class="text-warning">Extra: <span id="breakdownExtra">0</span>h</small><br>
                                    <small class="text-muted">Valore: €<span id="breakdownExtraValue">0.00</span></small>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <strong>Valore Totale: €<span id="redistributeUnifiedValue">0.00</span></strong>
                            </div>
                        </div>
                        
                        <!-- 🆕 PULSANTI RAPIDI MIGLIORATI -->
                        <div class="mt-2">
                            <small class="text-muted">Rapido:</small><br>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-unified-hours-btn" data-hours="1">1h</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-unified-hours-btn" data-hours="2">2h</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-unified-hours-btn" data-hours="4">4h</button>
                            <button type="button" class="btn btn-sm btn-outline-success quick-unified-hours-btn" data-type="standard">Solo Standard</button>
                            <button type="button" class="btn btn-sm btn-outline-warning quick-unified-hours-btn" data-type="extra">Solo Extra</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-unified-hours-btn" id="allUnifiedHoursBtn">Tutte</button>
                        </div>
                    </div>
                    
                    <input type="hidden" id="redistributeResourceId">
                    <input type="hidden" id="redistributeAction">
                    <input type="hidden" id="redistributeMaxUnifiedHours">
                    <input type="hidden" id="redistributeMaxStandardHours">
                    <input type="hidden" id="redistributeMaxExtraHours">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="confirmUnifiedRedistribute">
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
    console.log('DOM loaded with unified hours management');
    
    // 🆕 GESTIONE REDISTRIBUZIONE ORE UNIFICATA
    document.querySelectorAll('.redistribute-btn, .transfer-unified-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const resourceId = this.dataset.resourceId;
            const isTransfer = this.classList.contains('transfer-unified-btn');
            const action = isTransfer ? 'transfer' : 'return';
            const clientId = this.dataset.clientId || '';
            
            console.log('Clicked unified button:', {resourceId, action, clientId});
            
            // Popola il modal con dati unificati
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
            
            // Popola il modal
            document.getElementById('modalResourceName').textContent = resourceName;
            document.getElementById('modalAvailableHours').textContent = maxUnifiedHours.toFixed(1);
            document.getElementById('modalStandardHours').textContent = maxStandardHours.toFixed(1);
            document.getElementById('modalExtraHours').textContent = maxExtraHours.toFixed(1);
            
            document.getElementById('availableStandardHours').textContent = maxStandardHours.toFixed(1) + 'h';
            document.getElementById('availableExtraHours').textContent = maxExtraHours.toFixed(1) + 'h';
            document.getElementById('standardRate').textContent = standardRate;
            document.getElementById('extraRate').textContent = extraRate;
            
            document.getElementById('redistributeMaxUnifiedHours').value = maxUnifiedHours;
            document.getElementById('redistributeMaxStandardHours').value = maxStandardHours;
            document.getElementById('redistributeMaxExtraHours').value = maxExtraHours;
            
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
            
            // Reset form
            document.getElementById('redistributeUnifiedHours').value = '';
            updateUnifiedBreakdown();
            
            // Aggiorna pulsante "Tutte"
            document.getElementById('allUnifiedHoursBtn').dataset.hours = maxUnifiedHours;
            document.getElementById('allUnifiedHoursBtn').textContent = `Tutte (${maxUnifiedHours.toFixed(1)}h)`;
            
            // Mostra modal
            const modal = new bootstrap.Modal(document.getElementById('redistributeUnifiedModal'));
            modal.show();
        });
    });
    
    // 🆕 GESTIONE PULSANTI ORE RAPIDE UNIFICATI
    document.querySelectorAll('.quick-unified-hours-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const maxStandardHours = parseFloat(document.getElementById('redistributeMaxStandardHours').value) || 0;
            const maxExtraHours = parseFloat(document.getElementById('redistributeMaxExtraHours').value) || 0;
            const maxUnifiedHours = parseFloat(document.getElementById('redistributeMaxUnifiedHours').value) || 0;
            
            let hours = 0;
            
            if (this.dataset.hours) {
                // Ore fisse
                hours = Math.min(parseFloat(this.dataset.hours), maxUnifiedHours);
            } else if (this.dataset.type === 'standard') {
                // Solo ore standard
                hours = maxStandardHours;
            } else if (this.dataset.type === 'extra') {
                // Solo ore extra
                hours = maxExtraHours;
            } else if (this.id === 'allUnifiedHoursBtn') {
                // Tutte le ore disponibili
                hours = maxUnifiedHours;
            }
            
            document.getElementById('redistributeUnifiedHours').value = hours;
            updateUnifiedBreakdown();
        });
    });
    
    // 🆕 AGGIORNA BREAKDOWN QUANDO CAMBIANO LE ORE
    document.getElementById('redistributeUnifiedHours').addEventListener('input', updateUnifiedBreakdown);
    
    // 🆕 CONFERMA REDISTRIBUZIONE UNIFICATA
    document.getElementById('confirmUnifiedRedistribute').addEventListener('click', function() {
        const resourceId = document.getElementById('redistributeResourceId').value;
        const clientId = document.getElementById('redistributeClientId').value;
        const unifiedHours = document.getElementById('redistributeUnifiedHours').value;
        const action = document.getElementById('redistributeAction').value;
        
        if (!clientId || !unifiedHours || unifiedHours <= 0) {
            showErrorMessage('Compila tutti i campi richiesti');
            return;
        }
        
        const maxUnifiedHours = parseFloat(document.getElementById('redistributeMaxUnifiedHours').value);
        if (parseFloat(unifiedHours) > maxUnifiedHours) {
            showErrorMessage(`Non puoi redistribuire più di ${maxUnifiedHours} ore`);
            return;
        }
        
        // Calcola breakdown standard/extra
        const breakdown = calculateHoursBreakdown(parseFloat(unifiedHours));
        
        redistributeUnifiedHours(resourceId, clientId, unifiedHours, breakdown, action);
        bootstrap.Modal.getInstance(document.getElementById('redistributeUnifiedModal')).hide();
    });
    
    // Gestione filtri esistente
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

// 🆕 FUNZIONE: Calcola breakdown automatico standard/extra
function calculateHoursBreakdown(totalHours) {
    const maxStandardHours = parseFloat(document.getElementById('redistributeMaxStandardHours').value) || 0;
    const maxExtraHours = parseFloat(document.getElementById('redistributeMaxExtraHours').value) || 0;
    
    let standardHours = 0;
    let extraHours = 0;
    
    if (totalHours <= maxStandardHours) {
        // Se le ore richieste sono <= ore standard disponibili, usa solo standard
        standardHours = totalHours;
        extraHours = 0;
    } else {
        // Usa tutte le ore standard disponibili + il resto come extra
        standardHours = maxStandardHours;
        extraHours = Math.min(totalHours - maxStandardHours, maxExtraHours);
    }
    
    return {
        standard: standardHours,
        extra: extraHours,
        total: standardHours + extraHours
    };
}

// 🆕 FUNZIONE: Aggiorna visualizzazione breakdown
function updateUnifiedBreakdown() {
    const totalHours = parseFloat(document.getElementById('redistributeUnifiedHours').value) || 0;
    const standardRate = parseFloat(document.getElementById('standardRate').textContent) || 50;
    const extraRate = parseFloat(document.getElementById('extraRate').textContent) || 60;
    
    const breakdown = calculateHoursBreakdown(totalHours);
    
    // Aggiorna visualizzazione breakdown
    document.getElementById('breakdownStandard').textContent = breakdown.standard.toFixed(1);
    document.getElementById('breakdownExtra').textContent = breakdown.extra.toFixed(1);
    
    const standardValue = breakdown.standard * standardRate;
    const extraValue = breakdown.extra * extraRate;
    const totalValue = standardValue + extraValue;
    
    document.getElementById('breakdownStandardValue').textContent = standardValue.toFixed(2);
    document.getElementById('breakdownExtraValue').textContent = extraValue.toFixed(2);
    document.getElementById('redistributeUnifiedValue').textContent = totalValue.toFixed(2);
    
    // Evidenzia se ci sono ore extra
    const hoursBreakdown = document.getElementById('hoursBreakdown');
    if (breakdown.extra > 0) {
        hoursBreakdown.classList.add('border-warning');
        hoursBreakdown.classList.remove('border-success');
    } else {
        hoursBreakdown.classList.add('border-success');
        hoursBreakdown.classList.remove('border-warning');
    }
}

// 🆕 FUNZIONE PRINCIPALE: Redistribuzione ore unificate
function redistributeUnifiedHours(resourceId, clientId, totalHours, breakdown, action) {
    console.log('redistributeUnifiedHours chiamata con:', {
        resourceId, clientId, totalHours, breakdown, action
    });
    
    const date = document.getElementById('date').value;
    
    // Disabilita il pulsante
    const confirmBtn = document.getElementById('confirmUnifiedRedistribute');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Elaborando...';
    
    fetch('/daily-hours/redistribute-unified', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            resource_id: resourceId,
            client_id: clientId,
            total_hours: totalHours,
            standard_hours: breakdown.standard,
            extra_hours: breakdown.extra,
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
            showSuccessMessage(data.message);
            
            // 🆕 AGGIORNA DINAMICAMENTE I DATI UNIFICATI
            updateResourceUnifiedHours(resourceId, breakdown);
            updateClientBudgetData(clientId, totalHours, action);
            updateRedistributionCounter(clientId);
            
            // Reset del form
            document.getElementById('redistributeUnifiedHours').value = '';
            updateUnifiedBreakdown();
            
        } else {
            showErrorMessage('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Errore durante l\'operazione');
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

// 🆕 AGGIORNAMENTO DINAMICO ORE UNIFICATE
function updateResourceUnifiedHours(resourceId, breakdown) {
    const resourceCard = document.querySelector(`[data-resource-id="${resourceId}"]`);
    if (resourceCard) {
        // Aggiorna ore rimanenti standard
        const remainingStandardElement = resourceCard.querySelector('.remaining-standard-hours');
        if (remainingStandardElement) {
            const currentStandard = parseFloat(remainingStandardElement.textContent) || 0;
            const newStandard = Math.max(0, currentStandard - breakdown.standard);
            remainingStandardElement.textContent = newStandard.toFixed(1);
        }
        
        // Aggiorna ore rimanenti extra
        const remainingExtraElement = resourceCard.querySelector('.remaining-extra-hours');
        if (remainingExtraElement) {
            const currentExtra = parseFloat(remainingExtraElement.textContent) || 0;
            const newExtra = Math.max(0, currentExtra - breakdown.extra);
            remainingExtraElement.textContent = newExtra.toFixed(1);
        }
        
        // Aggiorna ore rimanenti unificate
        const remainingUnifiedElement = resourceCard.querySelector('.remaining-unified-hours');
        if (remainingUnifiedElement) {
            const currentUnified = parseFloat(remainingUnifiedElement.textContent) || 0;
            const newUnified = Math.max(0, currentUnified - breakdown.total);
            remainingUnifiedElement.textContent = newUnified.toFixed(1);
        }
        
        // Aggiorna valore rimanente
        const remainingValueElement = resourceCard.querySelector('.remaining-value');
        if (remainingValueElement) {
            const standardRate = parseFloat(resourceCard.dataset.standardRate) || 50;
            const extraRate = parseFloat(resourceCard.dataset.extraRate) || 60;
            
            const newStandard = parseFloat(remainingStandardElement?.textContent) || 0;
            const newExtra = parseFloat(remainingExtraElement?.textContent) || 0;
            const newValue = (newStandard * standardRate) + (newExtra * extraRate);
            
            remainingValueElement.textContent = '€' + newValue.toFixed(2);
        }
        
        // Aggiorna progress bar
        updateResourceProgressBar(resourceCard);
        
        // Evidenzia le modifiche
        highlightChanges(resourceCard, ['.remaining-standard-hours', '.remaining-extra-hours', '.remaining-unified-hours', '.remaining-value']);
        
        // Aggiorna pulsante trasferisci
        const transferBtn = resourceCard.querySelector('.transfer-unified-btn');
        if (transferBtn) {
            const newUnified = parseFloat(remainingUnifiedElement?.textContent) || 0;
            if (newUnified > 0) {
                transferBtn.innerHTML = `<i class="fas fa-share"></i> Trasferisci (${newUnified.toFixed(1)}h)`;
                transferBtn.dataset.maxUnifiedHours = newUnified;
            } else {
                transferBtn.style.display = 'none';
            }
        }
    }
}

// 🆕 AGGIORNA PROGRESS BAR RISORSA
function updateResourceProgressBar(resourceCard) {
    const progressBar = resourceCard.querySelector('.progress-bar');
    if (progressBar) {
        // Ricalcola la percentuale di utilizzo
        // Questa è una versione semplificata - potresti voler implementare una logica più sofisticata
        const workedHours = parseFloat(progressBar.textContent.replace('h lavorate', '')) || 0;
        // Per ora manteniamo il valore esistente, ma potresti aggiornarlo dinamicamente
    }
}

// 🆕 EVIDENZIA MODIFICHE
function highlightChanges(container, selectors) {
    selectors.forEach(selector => {
        const element = container.querySelector(selector);
        if (element) {
            element.style.backgroundColor = '#d4edda';
            element.style.fontWeight = 'bold';
            element.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                element.style.backgroundColor = '';
                element.style.fontWeight = '';
            }, 2000);
        }
    });
}

// Funzioni esistenti (aggiornate)
function updateClientBudgetData(clientId, hours, action) {
    const clientCard = document.querySelector(`[data-client-id="${clientId}"]`);
    if (clientCard) {
        const budgetRemainingElement = clientCard.querySelector('.budget-remaining');
        
        if (budgetRemainingElement) {
            // Calcola il valore usando le tariffe appropriate
            // Per semplicità, usa un valore medio - in produzione dovresti calcolare il valore esatto
            const averageRate = 55; // Media tra standard e extra
            const transferValue = parseFloat(hours) * averageRate;
            
            const currentRemaining = parseFloat(budgetRemainingElement.textContent.replace('€', '').replace(',', '')) || 0;
            const newRemaining = currentRemaining + transferValue;
            
            budgetRemainingElement.textContent = '€' + newRemaining.toFixed(2);
            
            highlightChanges(clientCard, ['.budget-remaining']);
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
                
                highlightChanges(clientCard, ['.text-info']);
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

// 🆕 GESTIONE ERRORI GLOBALI E INIZIALIZZAZIONE
window.addEventListener('error', function(event) {
    console.error('Errore JavaScript:', event.error);
    showErrorMessage('Si è verificato un errore imprevisto. Ricarica la pagina se il problema persiste.');
});

// 🆕 FUNZIONI DI UTILITÀ
function formatHours(hours) {
    return parseFloat(hours).toFixed(1) + 'h';
}

function formatCurrency(value) {
    return '€' + parseFloat(value).toFixed(2);
}

// 🆕 TASTI RAPIDI
document.addEventListener('keydown', function(event) {
    // Ctrl/Cmd + R per ricaricare i dati
    if ((event.ctrlKey || event.metaKey) && event.key === 'r') {
        event.preventDefault();
        location.reload();
    }
    
    // Esc per chiudere modali
    if (event.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) modal.hide();
        }
    }
});

// 🆕 FUNZIONE DI DEBUG
function debugUnifiedHours() {
    console.log('=== DEBUG UNIFIED HOURS ===');
    console.log('Resources cards:', document.querySelectorAll('[data-resource-id]').length);
    console.log('Client cards:', document.querySelectorAll('[data-client-id]').length);
    console.log('Transfer unified buttons:', document.querySelectorAll('.transfer-unified-btn').length);
    console.log('Redistribute buttons:', document.querySelectorAll('.redistribute-btn').length);
    
    // Log capacità di ogni risorsa
    document.querySelectorAll('[data-resource-id]').forEach(card => {
        const resourceId = card.dataset.resourceId;
        const standardHours = card.querySelector('.remaining-standard-hours')?.textContent || '0';
        const extraHours = card.querySelector('.remaining-extra-hours')?.textContent || '0';
        const unifiedHours = card.querySelector('.remaining-unified-hours')?.textContent || '0';
        
        console.log(`Resource ${resourceId}: ${standardHours} std + ${extraHours} extra = ${unifiedHours} unified`);
    });
    
    console.log('============================');
}

// Esponi funzioni per testing
window.debugUnifiedHours = debugUnifiedHours;
window.calculateHoursBreakdown = calculateHoursBreakdown;
</script>
@endsection