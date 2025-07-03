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
                            <input type="text" id="filterName" class="form-control" placeholder="Filtra per nome task...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="mb-2">
                            <label>&nbsp;</label>
                            <div class="btn-group d-block">
                                <button type="button" id="applyFiltersBtn" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Applica
                                </button>
                                <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Dashboard con statistiche -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Statistiche Generali</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-light h-100 dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Minuti Stimati</h6>
                            <h3 id="totalEstimatedMinutes">{{ $totalStats['estimatedMinutes'] }}</h3>
                            <p class="text-muted">Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100 dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Minuti Effettivi</h6>
                            <h3 id="totalActualMinutes">{{ $totalStats['actualMinutes'] }}</h3>
                            <p class="text-muted">Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100 dashboard-card">
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
                    <div class="card bg-light h-100 dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Bonus Totale</h6>
                            <h3 id="totalBonus" class="text-success">{{ number_format($totalStats['bonus'], 2) }} €</h3>
                            <p class="text-muted">5% sulle ore effettive (tariffa progetto)</p>
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
                                    
                                    // Calcola la tariffa oraria del progetto utilizzando il sistema a step
                                    $hourlyRate = 0;
                                    
                                    // Verifica le condizioni base
                                    if ($task->activity && $task->activity->project && $task->activity->resource) {
                                        $project = $task->activity->project;
                                        $resource = $task->activity->resource;
                                        $hoursType = $task->activity->hours_type;
                                        
                                        // Determina la tariffa base della risorsa
                                        $baseRate = 0;
                                        if ($hoursType == 'standard') {
                                            $baseRate = $resource->selling_price;
                                        } else {
                                            $baseRate = $resource->extra_selling_price ?: ($resource->selling_price * 1.2);
                                        }
                                        
                                        // Calcola la tariffa oraria del progetto utilizzando il sistema a step
                                        $hourlyRate = $project->calculateAdjustedRate($baseRate);
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
                                    <td>{{ $task->activity && $task->activity->project && $task->activity->project->client ? $task->activity->project->client->name : 'N/A' }}</td>
                                    <td>{{ $task->activity ? $task->activity->name : 'N/A' }}</td>
                                    <td>{{ $task->activity && $task->activity->project ? $task->activity->project->name : 'N/A' }}</td>
                                    <td>{{ $task->activity && $task->activity->resource ? $task->activity->resource->name : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $task->status == 'completed' ? 'success' : 
                                            ($task->status == 'in_progress' ? 'warning' : 'secondary') 
                                        }}">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $task->estimated_minutes }}</td>
                                    <td>{{ $task->actual_minutes }}</td>
                                    <td class="{{ $balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $balance }}</td>
                                    <td class="text-success">{{ number_format($bonus, 2) }} €</td>
                                    <td>
                                        <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline-primary" title="Visualizza">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-outline-warning" title="Modifica">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info text-center">
                    <h4>Nessun task trovato</h4>
                    <p>Non ci sono task da visualizzare al momento.</p>
                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crea il primo task
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Grafici -->
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
/* Stili per le card dashboard */
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

/* Stili per filtri con selezione multipla */
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

/* Responsive */
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
    
    #tasksTable {
        font-size: 0.8rem;
    }
    
    #tasksTable th,
    #tasksTable td {
        padding: 0.3rem;
    }
    
    .btn-sm {
        padding: 0.2rem 0.4rem;
    }
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
        
        // Reset delle attività
        filterActivities.empty();
        filterActivities.append('<option></option>'); // Opzione vuota per placeholder
        
        // Se non ci sono progetti selezionati, mostra tutte le attività
        if (selectedProjectIds.length === 0) {
            @foreach($activities ?? [] as $activity)
                filterActivities.append(new Option("{{ $activity->name }}", "{{ $activity->id }}"));
            @endforeach
        } else {
            // Mostra solo le attività dei progetti selezionati
            @foreach($activities ?? [] as $activity)
                if (selectedProjectIds.includes("{{ $activity->project_id }}")) {
                    filterActivities.append(new Option("{{ $activity->name }}", "{{ $activity->id }}"));
                }
            @endforeach
        }
        
        // Aggiorna Select2
        filterActivities.trigger('change');
    });
    
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
    
    // Event listeners
    applyFiltersBtn.addEventListener('click', applyFilters);
    resetFiltersBtn.addEventListener('click', resetFilters);
    filterName.addEventListener('input', applyFilters);
    
    // Event listeners per Select2
    filterProjects.on('change', applyFilters);
    filterClients.on('change', applyFilters);
    filterActivities.on('change', applyFilters);
    filterResources.on('change', applyFilters);
    
    // Grafici
    let resourceEfficiencyChart = null;
    let bonusDistributionChart = null;
    
    function updateCharts() {
        // Raccogli dati per i grafici
        const resources = {};
        const bonusData = {};
        
        const visibleRows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none');
        
        visibleRows.forEach(row => {
            const resourceName = row.cells[4].textContent; // Colonna Risorsa
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
            
            // Aggregazione bonus
            if (!bonusData[resourceName]) {
                bonusData[resourceName] = 0;
            }
            bonusData[resourceName] += bonus;
        });
        
        // Grafico efficienza per risorsa
        const resourceNames = Object.keys(resources);
        const efficiencyData = resourceNames.map(name => {
            const data = resources[name];
            return data.estimatedMinutes > 0 ? (data.actualMinutes / data.estimatedMinutes) * 100 : 0;
        });
        
        // Distruggi il grafico esistente se presente
        if (resourceEfficiencyChart) {
            resourceEfficiencyChart.destroy();
        }
        
        const ctx1 = document.getElementById('resourceEfficiencyChart').getContext('2d');
        resourceEfficiencyChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: resourceNames,
                datasets: [{
                    label: 'Efficienza (%)',
                    data: efficiencyData,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Efficienza (%)'
                        }
                    }
                }
            }
        });
        
        // Grafico distribuzione bonus
        const bonusNames = Object.keys(bonusData);
        const bonusValues = Object.values(bonusData);
        
        // Distruggi il grafico esistente se presente
        if (bonusDistributionChart) {
            bonusDistributionChart.destroy();
        }
        
        const ctx2 = document.getElementById('bonusDistributionChart').getContext('2d');
        bonusDistributionChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: bonusNames,
                datasets: [{
                    label: 'Bonus (€)',
                    data: bonusValues,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 205, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // Inizializza i grafici al caricamento della pagina
    updateCharts();
    
    // Applica filtri iniziali
    applyFilters();
});
</script>
@endpush