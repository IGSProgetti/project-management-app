@extends('layouts.app')

@section('title', 'Gestione Tempi Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Gestione Tempi Task</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Task
            </a>
            <a href="{{ route('tasks.timetracking') }}" class="btn btn-secondary">
                <i class="fas fa-clock"></i> Gestione Tempi
            </a>
        </div>
    </div>

    <!-- Filtri -->
    <div class="card mb-4">
    <div class="card-header">
        <h5>Filtri</h5>
    </div>
    <div class="card-body">
        <form id="filterForm" class="mb-3">
            <div class="row">
                <div class="col">
                    <div class="mb-2">
                        <label for="filterProjects">Progetti</label>
                        <select id="filterProjects" class="form-select select2-dropdown" multiple>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-2">
                        <label for="filterClients">Clienti</label>
                        <select id="filterClients" class="form-select select2-dropdown" multiple>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-2">
                        <label for="filterActivities">Attività</label>
                        <select id="filterActivities" class="form-select select2-dropdown" multiple>
                            @foreach($activities ?? [] as $activity)
                                <option value="{{ $activity->id }}">{{ $activity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-2">
                        <label for="filterResources">Risorse</label>
                        <select id="filterResources" class="form-select select2-dropdown" multiple>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-2">
                        <label for="filterName">Nome Task</label>
                        <input type="text" id="filterName" class="form-control" placeholder="Cerca...">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="mb-2 d-flex align-items-end h-100">
                        <div class="btn-group">
                            <button type="button" id="applyFiltersBtn" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtra
                            </button>
                            <button type="button" id="resetFiltersBtn" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

    <!-- Statistiche riassuntive -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Dashboard Riassuntiva</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Minuti Stimati</h6>
                            <h3 id="totalEstimatedMinutes">{{ $totalStats['estimatedMinutes'] }}</h3>
                            <p class="text-muted">Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Minuti Effettivi</h6>
                            <h3 id="totalActualMinutes">{{ $totalStats['actualMinutes'] }}</h3>
                            <p class="text-muted">Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Consuntivo</h6>
                            <h3 id="totalBalance" class="{{ $totalStats['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $totalStats['balance'] }}
                            </h3>
                            <p class="text-muted">Differenza</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Bonus Totale</h6>
                            <h3 id="totalBonus" class="text-success">{{ number_format($totalStats['bonus'], 2) }} €</h3>
                            <p class="text-muted">5% sulle ore effettive</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella dei task -->
    <div class="card mb-4">
        <div class="card-body">
            @if($tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped" id="tasksTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cliente</th>
                                <th>Attività</th>
                                <th>Progetto</th>
                                <th>Risorsa</th>
                                <th>Stato</th>
                                <th>Min. Stimati</th>
                                <th>Min. Effettivi</th>
                                <th>Consuntivo</th>
                                <th>Bonus</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                @php
                                    $balance = $task->estimated_minutes - $task->actual_minutes;
                                    
                                    // Inizializza la tariffa oraria a zero
                                    $hourlyRate = 0;
                                    
                                    // Verifica le condizioni base
                                    if ($task->activity && $task->activity->resource) {
                                        // Determina quale tariffa usare in base al tipo di ore
                                        if ($task->activity->hours_type == 'standard') {
                                            // Usa tariffa standard
                                            $hourlyRate = $task->activity->resource->selling_price;
                                        } else {
                                            // Usa tariffa extra se disponibile, altrimenti standard
                                            if (!empty($task->activity->resource->extra_selling_price)) {
                                                $hourlyRate = $task->activity->resource->extra_selling_price;
                                            } else {
                                                $hourlyRate = $task->activity->resource->selling_price;
                                            }
                                        }
                                    }
                                    
                                    // Calcolo del bonus
                                    $bonus = 0;
                                    if ($balance >= 0 && $task->actual_minutes > 0) {
                                        $bonus = ($task->actual_minutes / 60) * $hourlyRate * 0.05;
                                    }
                                @endphp
                                <tr 
                                    data-client="{{ $task->activity && $task->activity->project ? $task->activity->project->client_id : '' }}"
                                    data-project="{{ $task->activity ? $task->activity->project_id : '' }}"
                                    data-activity="{{ $task->activity ? $task->activity->id : '' }}"
                                    data-resource="{{ $task->activity ? $task->activity->resource_id : '' }}"
                                    data-task-name="{{ strtolower($task->name) }}"
                                >
                                    <td>{{ $task->name }}</td>
                                    <td>{{ $task->activity && $task->activity->project && $task->activity->project->client ? $task->activity->project->client->name : 'N/D' }}</td>
                                    <td>{{ $task->activity ? $task->activity->name : 'N/D' }}</td>
                                    <td>{{ $task->activity && $task->activity->project ? $task->activity->project->name : 'N/D' }}</td>
                                    <td>{{ $task->activity && $task->activity->resource ? $task->activity->resource->name : 'N/D' }}</td>
                                    <td>
                                        @if($task->status == 'pending')
                                            <span class="badge bg-warning">In attesa</span>
                                        @elseif($task->status == 'in_progress')
                                            <span class="badge bg-primary">In corso</span>
                                        @elseif($task->status == 'completed')
                                            <span class="badge bg-success">Completato</span>
                                        @endif
                                    </td>
                                    <td>{{ $task->estimated_minutes }}</td>
                                    <td>{{ $task->actual_minutes }}</td>
                                    <td class="{{ $balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $balance }}</td>
                                    <td class="{{ $bonus > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($bonus, 2) }} €
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo task?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    Nessun task disponibile. <a href="{{ route('tasks.create') }}">Crea il tuo primo task</a>.
                </div>
            @endif
        </div>
    </div>

    <!-- Grafici - spostati dopo la tabella dei task -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6>Efficienza per Risorsa</h6>
                </div>
                <div class="card-body">
                    <canvas id="resourceEfficiencyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6>Distribuzione Bonus</h6>
                </div>
                <div class="card-body">
                    <canvas id="bonusDistributionChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .card-body .card {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .card-body .card h3 {
        font-size: 2rem;
        font-weight: 600;
    }
    
    .table thead th {
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .text-success {
        font-weight: 600;
    }
    
    .text-danger {
        font-weight: 600;
    }
    
    .dashboard-card {
        transition: all 0.3s ease;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
   /* Stili per filtri con selezione multipla in linea */
.card-body {
    padding: 1rem;
}

/* Stili personalizzati per Select2 */
.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
    display: flex;
    flex-wrap: wrap;
    padding-left: 8px;
    margin: 0;
    list-style: none;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
    background-color: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 0.2rem;
    padding: 0.2rem 0.6rem;
    margin-right: 5px;
    margin-top: 3px;
    font-size: 0.875rem;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff;
    margin-right: 5px;
    font-weight: 700;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fff;
    opacity: 0.7;
}

.select2-dropdown {
    border-color: #ced4da;
    border-radius: 0.25rem;
}

.select2-results__option--highlighted {
    background-color: #0d6efd !important;
    color: white !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

/* Assicura che le colonne abbiano la stessa altezza */
.row .col, .row .col-auto {
    display: flex;
    flex-direction: column;
}

.row .col .mb-2, .row .col-auto .mb-2 {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.row .col .mb-2 .form-select, 
.row .col .mb-2 .form-control, 
.row .col-auto .mb-2 .btn-group {
    flex-grow: 1;
}

/* Stile per i pulsanti dei filtri */
.btn-group {
    height: 38px;
    margin-top: auto;
}

/* Responsive - quando lo schermo diventa piccolo, i filtri andranno a capo */
@media (max-width: 1200px) {
    .row .col, .row .col-auto {
        width: 50%;
        margin-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .row .col, .row .col-auto {
        width: 100%;
    }
}

/* Stili per la dashboard e le card del filtro */
.card-body .card {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card-body .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.card-body .card h3 {
    font-size: 2rem;
    font-weight: 600;
}

/* Stili per i risultati filtrati */
.table thead th {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Per mostrare chiaramente quando un filtro è attivo */
.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Miglioramenti per Select2 dropdown */
.select2-container {
    width: 100% !important;
}

.select2-container--bootstrap-5 .select2-selection--multiple {
    min-height: 38px;
}

/* Fix placeholder */
.select2-container--bootstrap-5 .select2-selection--multiple .select2-search__field::placeholder {
    color: #6c757d;
    font-size: 0.875rem;
}

</style>
@endpush

@push('scripts')
<!-- Select2 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Inizializzazione pagina...");
    
    // Inizializza Select2 per tutte le dropdown multiple
    $('.select2-dropdown').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Seleziona...",
        allowClear: true,
        closeOnSelect: false
    });
    
    // Elementi DOM per i filtri
    const filterProjects = $('#filterProjects');
    const filterClients = $('#filterClients');
    const filterActivities = $('#filterActivities');
    const filterResources = $('#filterResources');
    const filterName = document.getElementById('filterName');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const table = document.getElementById('tasksTable');
    
    // Popolamento dinamico delle attività in base ai progetti selezionati
    filterProjects.on('change', function() {
        const selectedProjectIds = $(this).val();
        console.log("Progetti selezionati:", selectedProjectIds);
        
        // Reset del filtro attività se non ci sono progetti selezionati
        if (!selectedProjectIds || selectedProjectIds.length === 0) {
            console.log("Nessun progetto selezionato, carico tutte le attività");
            loadAllActivities();
            return;
        }
        
        // Carica attività per i progetti selezionati
        loadActivitiesForProjects(selectedProjectIds);
    });
    
    // Funzione per caricare tutte le attività
    function loadAllActivities() {
        console.log("Caricamento di tutte le attività...");
        // Mostra un indicatore di caricamento
        filterActivities.empty().append(new Option('Caricamento attività...', ''));
        filterActivities.prop('disabled', true);
        
        fetch('/api/activities')
            .then(response => {
                console.log("Risposta ricevuta:", response.status);
                if (!response.ok) {
                    throw new Error(`Errore HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Dati attività ricevuti:", data);
                if (data.success) {
                    updateActivitiesDropdown(data.activities);
                } else {
                    console.error("La risposta non contiene 'success: true'");
                    updateActivitiesDropdown([]);
                }
            })
            .catch(error => {
                console.error('Errore nel caricamento delle attività:', error);
                updateActivitiesDropdown([]);
            })
            .finally(() => {
                // Riabilita il dropdown
                filterActivities.prop('disabled', false);
            });
    }
    
    // Funzione per caricare le attività dei progetti selezionati
    function loadActivitiesForProjects(projectIds) {
        console.log("Caricamento attività per progetti:", projectIds);
        // Mantieni le attività selezionate correntemente
        const selectedActivityIds = filterActivities.val() || [];
        
        // Mostra un indicatore di caricamento nel dropdown
        filterActivities.empty().append(new Option('Caricamento attività...', ''));
        filterActivities.prop('disabled', true);
        
        // Processa ogni progetto separatamente e poi combina i risultati
        const promises = projectIds.map(projectId =>
            fetch(`/api/activities/by-project/${projectId}`)
                .then(response => {
                    console.log(`Risposta per il progetto ${projectId}:`, response.status);
                    if (!response.ok) {
                        throw new Error(`Errore HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(`Dati attività per il progetto ${projectId}:`, data);
                    if (data.success) {
                        return data.activities;
                    }
                    return [];
                })
                .catch(error => {
                    console.error(`Errore nel caricamento delle attività per il progetto ${projectId}:`, error);
                    return [];
                })
        );
        
        // Combina tutti i risultati quando tutte le richieste sono completate
        Promise.all(promises)
            .then(activitiesArrays => {
                // Unisci tutti gli array di attività
                const uniqueActivitiesMap = {};
                activitiesArrays.flat().forEach(activity => {
                    uniqueActivitiesMap[activity.id] = activity;
                });
                
                const uniqueActivities = Object.values(uniqueActivitiesMap);
                console.log("Attività uniche:", uniqueActivities.length);
                
                // Aggiorna il dropdown delle attività mantenendo le selezioni
                updateActivitiesDropdown(uniqueActivities, selectedActivityIds);
            })
            .finally(() => {
                // Riabilita il dropdown
                filterActivities.prop('disabled', false);
            });
    }
    
    // Funzione per aggiornare il dropdown delle attività
    function updateActivitiesDropdown(activities, selectedIds = []) {
        console.log("Aggiornamento dropdown attività con", activities ? activities.length : 0, "attività");
        
        // Se non sono stati forniti selectedIds, prendi le attuali selezioni
        if (!selectedIds || selectedIds.length === 0) {
            selectedIds = filterActivities.val() || [];
        }
        
        // Svuota il dropdown e aggiungi l'opzione predefinita
        filterActivities.empty().append(new Option('Tutte le attività', ''));
        
        // Controlla se abbiamo attività da aggiungere
        if (!activities || activities.length === 0) {
            console.log("Nessuna attività da aggiungere al dropdown");
            filterActivities.trigger('change');
            return;
        }
        
        console.log("Aggiunta di", activities.length, "attività al dropdown");
        
        // Ordina le attività per nome per una migliore usabilità
        activities.sort((a, b) => a.name.localeCompare(b.name));
        
        // Riempilo con le nuove attività
        activities.forEach(activity => {
            // Assicurati che activity.id sia una stringa quando fai il confronto
            const activityId = String(activity.id);
            const isSelected = selectedIds.includes(activityId);
            
            console.log(`Aggiunta attività: ${activity.name} (id: ${activityId}), selezionata: ${isSelected}`);
            
            // Crea una nuova opzione
            const option = new Option(
                activity.name, 
                activityId, 
                false,  // non selezionata per default
                isSelected  // selezionata se era nella lista precedente
            );
            
            filterActivities.append(option);
        });
        
        // Aggiorna Select2
        filterActivities.trigger('change');
    }
    
    // Applica filtri
    function applyFilters() {
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        
        // Ottieni i valori selezionati da ogni filtro multiplo
        const selectedProjectIds = filterProjects.val() || [];
        const selectedClientIds = filterClients.val() || [];
        const selectedActivityIds = filterActivities.val() || [];
        const selectedResourceIds = filterResources.val() || [];
        const taskName = filterName.value.toLowerCase();
        
        let totalEstimatedMinutes = 0;
        let totalActualMinutes = 0;
        let totalBalance = 0;
        let totalBonus = 0;
        
        rows.forEach(row => {
            const rowProjectId = row.dataset.project;
            const rowClientId = row.dataset.client;
            const rowActivityId = row.dataset.activity;
            const rowResourceId = row.dataset.resource;
            const rowTaskName = row.dataset.taskName;
            
            // Match se il filtro è vuoto o il valore è incluso nell'array dei valori selezionati
            const projectMatch = selectedProjectIds.length === 0 || selectedProjectIds.includes(rowProjectId);
            const clientMatch = selectedClientIds.length === 0 || selectedClientIds.includes(rowClientId);
            const activityMatch = selectedActivityIds.length === 0 || selectedActivityIds.includes(rowActivityId);
            const resourceMatch = selectedResourceIds.length === 0 || selectedResourceIds.includes(rowResourceId);
            const nameMatch = !taskName || rowTaskName.includes(taskName);
            
            const isVisible = projectMatch && clientMatch && activityMatch && resourceMatch && nameMatch;
            
            if (isVisible) {
                row.style.display = '';
                
                // Aggiungi valori ai totali
                const estimatedMinutes = parseInt(row.cells[6].textContent) || 0;
                const actualMinutes = parseInt(row.cells[7].textContent) || 0;
                const balance = parseInt(row.cells[8].textContent) || 0;
                const bonus = parseFloat(row.cells[9].textContent.replace('€', '').trim()) || 0;
                
                totalEstimatedMinutes += estimatedMinutes;
                totalActualMinutes += actualMinutes;
                totalBalance += balance;
                totalBonus += bonus;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Aggiorna i contatori nella dashboard
        document.getElementById('totalEstimatedMinutes').textContent = totalEstimatedMinutes;
        document.getElementById('totalActualMinutes').textContent = totalActualMinutes;
        
        const totalBalanceElement = document.getElementById('totalBalance');
        totalBalanceElement.textContent = totalBalance;
        totalBalanceElement.className = totalBalance >= 0 ? 'text-success' : 'text-danger';
        
        document.getElementById('totalBonus').textContent = totalBonus.toFixed(2) + ' €';
        
        // Aggiorna i grafici con i dati filtrati
        updateCharts();
    }
    
    // Reset filtri
    function resetFilters() {
        // Deseleziona tutte le opzioni in ogni select multipla usando l'API di Select2
        filterProjects.val(null).trigger('change');
        filterClients.val(null).trigger('change');
        filterActivities.val(null).trigger('change');
        filterResources.val(null).trigger('change');
        
        // Resetta il campo di ricerca
        filterName.value = '';
        
        // Applica i filtri resettati
        applyFilters();
    }
    
    // Grafici
    function updateCharts() {
        // Raccogli dati per i grafici
        const resources = {};
        const bonusData = {};
        
        const visibleRows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none');
        
        visibleRows.forEach(row => {
            const resourceName = row.cells[4].textContent; // Colonna Risorsa (quinta colonna)
            const estimatedMinutes = parseInt(row.cells[6].textContent) || 0;
            const actualMinutes = parseInt(row.cells[7].textContent) || 0;
            const bonus = parseFloat(row.cells[9].textContent.replace('€', '').trim()) || 0;
            
            // Aggregazione per risorsa
            if (!resources[resourceName]) {
                resources[resourceName] = {
                    estimatedMinutes: 0,
                    actualMinutes: 0
                };
            }
            
            resources[resourceName].estimatedMinutes += estimatedMinutes;
            resources[resourceName].actualMinutes += actualMinutes;
            
            // Aggregazione per bonus
            if (bonus > 0) {
                if (!bonusData[resourceName]) {
                    bonusData[resourceName] = 0;
                }
                bonusData[resourceName] += bonus;
            }
        });
        
        // Aggiorna grafico efficienza
        updateEfficiencyChart(resources);
        
        // Aggiorna grafico bonus
        updateBonusChart(bonusData);
    }
    
    function updateEfficiencyChart(resourcesData) {
        const ctx = document.getElementById('resourceEfficiencyChart').getContext('2d');
        
        // Distruggi il grafico esistente se presente
        if (window.efficiencyChart) {
            window.efficiencyChart.destroy();
        }
        
        // Prepara i dati
        const labels = Object.keys(resourcesData);
        const estimatedData = labels.map(label => resourcesData[label].estimatedMinutes);
        const actualData = labels.map(label => resourcesData[label].actualMinutes);
        
        // Crea il nuovo grafico
        window.efficiencyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Minuti Stimati',
                        data: estimatedData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Minuti Effettivi',
                        data: actualData,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    function updateBonusChart(bonusData) {
        const ctx = document.getElementById('bonusDistributionChart').getContext('2d');
        
        // Distruggi il grafico esistente se presente
        if (window.bonusChart) {
            window.bonusChart.destroy();
        }
        
        // Prepara i dati
        const labels = Object.keys(bonusData);
        const bonusValues = labels.map(label => bonusData[label]);
        
        // Crea colori consistenti ma diversi per ogni risorsa
        const backgroundColors = labels.map((label, index) => {
            // Genera colori in modo deterministico basandosi sul nome
            const hue = (index * 137.5) % 360; // Distribuzione uniforme di colori
            return `hsla(${hue}, 70%, 60%, 0.7)`;
        });
        
        // Crea il nuovo grafico
        window.bonusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: bonusValues,
                        backgroundColor: backgroundColors,
                        hoverOffset: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw.toFixed(2)} €`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Event listeners
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }
    
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }
    
    // Event listener per il cambio delle selezioni in Select2
    $('.select2-dropdown').on('select2:select select2:unselect', function (e) {
        // Aggiungi una classe per mostrare visivamente che un filtro è attivo
        if ($(this).val() && $(this).val().length > 0) {
            $(this).next('.select2-container').addClass('has-selection');
        } else {
            $(this).next('.select2-container').removeClass('has-selection');
        }
    });
    
    // Applica i filtri anche quando si digita nel campo di ricerca (dopo un breve ritardo)
    let searchTimeout;
    $('#filterName').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    });
    
    // Inizializza i grafici
    updateCharts();
    
    // Carica le attività all'avvio della pagina
    console.log("Caricamento iniziale delle attività...");
    loadAllActivities();
});
</script>
@endpush