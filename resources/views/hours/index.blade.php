@extends('layouts.app')

@section('title', 'Gestione Orario')

@section('content')

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary" id="refreshData">
                    <i class="fas fa-sync"></i> Aggiorna Dati
                </button>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download"></i> Esporta
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item export-btn" href="#" data-type="resources">Esporta Risorse</a></li>
                        <li><a class="dropdown-item export-btn" href="#" data-type="clients">Esporta per Cliente</a></li>
                        <li><a class="dropdown-item export-btn" href="#" data-type="projects">Esporta per Progetto</a></li>
                        <li><a class="dropdown-item export-btn" href="#" data-type="activities">Esporta per Attività</a></li>
                        <li><a class="dropdown-item export-btn" href="#" data-type="tasks">Esporta per Task</a></li>
                    </ul>
                </div>
            </div>
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
                    <div class="col-md-3 mb-3">
                        <label for="client_ids">Clienti</label>
                        <select id="client_ids" name="client_ids[]" class="form-select select2-multiple" multiple>
                            <option value="">Tutti i clienti</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="project_ids">Progetti</label>
                        <select id="project_ids" name="project_ids[]" class="form-select select2-multiple" multiple>
                            <option value="">Tutti i progetti</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="resource_ids">Risorse</label>
                        <select id="resource_ids" name="resource_ids[]" class="form-select select2-multiple" multiple>
                            <option value="">Tutte le risorse</option>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="button" id="applyFilters" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Applica Filtri
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabella Risorse -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Panoramica Risorse</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="resourcesTable">
                    <thead>
                        <tr>
                            <th>Risorsa</th>
                            <th>Ruolo</th>
                            <th>Ore Standard/Anno</th>
                            <th>Ore Standard Rimanenti</th>
                            <th>Ore Extra/Anno</th>
                            <th>Ore Extra Rimanenti</th>
                            <th>Ore Stimate</th>
                            <th>Ore Effettive</th>
                            <th>Tesoretto</th>
                            <th>Utilizzo Ore</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dati popolati dinamicamente da JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sezione Dettagli Risorsa (si apre al click su una risorsa) -->
    <div class="card mb-4" id="resourceDetail" style="display: none;">
        <div class="card-header d-flex justify-content-between">
            <h5>Dettagli Risorsa: <span id="detailResourceName"></span></h5>
            <button type="button" class="btn-close" id="closeResourceDetail"></button>
        </div>
        <div class="card-body">
            <!-- Tabs per navigare tra dettagli client/progetto/attività/task -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="client-tab" data-bs-toggle="tab" data-bs-target="#client-view" type="button" role="tab">Per Cliente</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="project-tab" data-bs-toggle="tab" data-bs-target="#project-view" type="button" role="tab">Per Progetto</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-view" type="button" role="tab">Per Attività</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="task-tab" data-bs-toggle="tab" data-bs-target="#task-view" type="button" role="tab">Per Task</button>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Vista per Cliente -->
                <div class="tab-pane fade show active" id="client-view" role="tabpanel">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm" id="resourceClientTable">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Ore Stimate</th>
                                    <th>Ore Effettive</th>
                                    <th>Tesoretto</th>
                                    <th>% Completamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dati popolati dinamicamente da JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <canvas id="clientChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Vista per Progetto -->
                <div class="tab-pane fade" id="project-view" role="tabpanel">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm" id="resourceProjectTable">
                            <thead>
                                <tr>
                                    <th>Progetto</th>
                                    <th>Cliente</th>
                                    <th>Ore Stimate</th>
                                    <th>Ore Effettive</th>
                                    <th>Tesoretto</th>
                                    <th>% Completamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dati popolati dinamicamente da JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <canvas id="projectChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Vista per Attività -->
                <div class="tab-pane fade" id="activity-view" role="tabpanel">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm" id="resourceActivityTable">
                            <thead>
                                <tr>
                                    <th>Attività</th>
                                    <th>Progetto</th>
                                    <th>Cliente</th>
                                    <th>Tipo Ore</th>
                                    <th>Stato</th>
                                    <th>Ore Stimate</th>
                                    <th>Ore Effettive</th>
                                    <th>Tesoretto</th>
                                    <th>Task</th>  <!-- Colonna per vedere i task -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dati popolati dinamicamente da JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Sezione per visualizzare il dettaglio delle task -->
                    <div id="taskDetail" class="mt-4" style="display: none;">
                        <h5 class="mb-3">Dettaglio Task: <span id="activityDetailName"></span></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="taskDetailTable">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Stato</th>
                                        <th>Ore Stimate</th>
                                        <th>Ore Effettive</th>
                                        <th>Tesoretto</th>
                                        <th>Completamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dati popolati dinamicamente da JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Vista per Task (Nuovo) -->
                <div class="tab-pane fade" id="task-view" role="tabpanel">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm" id="resourceTaskTable">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Attività</th>
                                    <th>Progetto</th>
                                    <th>Cliente</th>
                                    <th>Tipo Ore</th>
                                    <th>Stato</th>
                                    <th>Ore Stimate</th>
                                    <th>Ore Effettive</th>
                                    <th>Tesoretto</th>
                                    <th>% Completamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dati popolati dinamicamente da JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <canvas id="taskChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiche Globali - SPOSTATE ALLA FINE -->
    <div class="card mb-4" id="globalStats">
        <div class="card-header">
            <h5>Statistiche Globali</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Ore Stimate Totali</h6>
                            <h3 id="totalEstimatedHours">0</h3>
                            <p class="text-muted">Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Ore Effettive Totali</h6>
                            <h3 id="totalActualHours">0</h3>
                            <p class="text-muted">Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Tesoretto Totale</h6>
                            <h3 id="totalTreasureHours" class="treasure-value">0</h3>
                            <p class="text-muted">Differenza</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h6 class="card-title">Efficienza</h6>
                            <h3 id="efficiency">0%</h3>
                            <p class="text-muted">Rendimento</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grafico di riepilogo -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <canvas id="globalStatsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
  /* Ottimizzazione delle dimensioni per la tabella principale */
#resourcesTable {
  font-size: 0.85rem;
  width: 100%;
  table-layout: fixed;
}

#resourcesTable th, #resourcesTable td {
  padding: 0.5rem 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Definizione delle larghezze delle colonne per evitare espansione eccessiva */
#resourcesTable th:nth-child(1), #resourcesTable td:nth-child(1) { width: 10%; } /* Risorsa */
#resourcesTable th:nth-child(2), #resourcesTable td:nth-child(2) { width: 8%; } /* Ruolo */
#resourcesTable th:nth-child(3), #resourcesTable td:nth-child(3) { width: 7%; } /* Ore Standard */
#resourcesTable th:nth-child(4), #resourcesTable td:nth-child(4) { width: 7%; } /* Ore Standard Rimanenti */
#resourcesTable th:nth-child(5), #resourcesTable td:nth-child(5) { width: 7%; } /* Ore Extra */
#resourcesTable th:nth-child(6), #resourcesTable td:nth-child(6) { width: 7%; } /* Ore Extra Rimanenti */
#resourcesTable th:nth-child(7), #resourcesTable td:nth-child(7) { width: 7%; } /* Ore Stimate */
#resourcesTable th:nth-child(8), #resourcesTable td:nth-child(8) { width: 7%; } /* Ore Effettive */
#resourcesTable th:nth-child(9), #resourcesTable td:nth-child(9) { width: 7%; } /* Tesoretto */
#resourcesTable th:nth-child(10), #resourcesTable td:nth-child(10) { width: 25%; } /* Utilizzo Ore */
#resourcesTable th:nth-child(11), #resourcesTable td:nth-child(11) { width: 8%; } /* Azioni */

/* Stile per i progress bar più compatti */
.progress {
  height: 6px;
  margin-bottom: 4px;
}

.small {
  font-size: 0.7rem;
  margin-bottom: 2px;
}

/* Ottimizzazione delle tabelle nei dettagli */
#resourceDetail .table {
  font-size: 0.8rem;
  width: 100%;
  table-layout: fixed;
}

#resourceDetail .table th, #resourceDetail .table td {
  padding: 0.4rem 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ottimizzazione per il layout generale */
.card {
  margin-bottom: 1rem;
  padding: 0.5rem;
}

.card-header {
  padding: 0.5rem;
}

.card-body {
  padding: 0.5rem;
}

/* Migliora la responsività generale */
@media (max-width: 1400px) {
  .container-fluid {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
  
  .btn {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
  }
  
  .col-md-3 {
    padding-left: 5px;
    padding-right: 5px;
  }
}

/* Rimuove il padding laterale che potrebbe causare scrolling */
@media (max-width: 992px) {
  .container-fluid {
    padding-left: 0.2rem;
    padding-right: 0.2rem;
  }
}

/* Assicura che le statistiche globali siano visualizzate correttamente */
#globalStats .card-body h3 {
  font-size: 1.2rem;
}

#globalStats .card-body .card {
  padding: 0.25rem;
}

/* Ridimensiona i grafici per adattarli meglio */
#globalStatsChart, #clientChart, #projectChart, #taskChart {
  max-height: 200px;
  height: 200px;
}

/* Stili per il tesoretto e le ore rimanenti */
.treasure-positive {
    color: #28a745;
    font-weight: bold;
}

.treasure-negative {
    color: #dc3545;
    font-weight: bold;
}

.hours-positive {
    color: #28a745;
}

.hours-negative {
    color: #dc3545;
    font-weight: bold;
}

/* Evidenziazione per task in ritardo o sovrastimati */
.table-danger {
    background-color: #f8d7da;
}

.table-warning {
    background-color: #fff3cd;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // FUNZIONI DI FORMATTAZIONE TEMPO - INIZIO
    /**
     * Converte minuti in formato "Xh Ymin"
     * @param {number} minutes - Minuti totali
     * @return {string} - Tempo formattato come "Xh Ymin"
     */
    function formatTimeHoursMinutes(minutes) {
        if (minutes === null || minutes === undefined || isNaN(minutes)) {
            return "0h 0min";
        }
        
        // Arrotonda i minuti se sono un numero con decimali
        minutes = Math.round(minutes);
        
        // Calcola ore e minuti
        const hours = Math.floor(Math.abs(minutes) / 60);
        const mins = Math.abs(minutes) % 60;
        
        // Gestisci valori negativi
        const sign = minutes < 0 ? "-" : "";
        
        // Formatta l'output
        return `${sign}${hours}h ${mins}min`;
    }
    
    /**
     * Converte ore decimali in formato "Xh Ymin"
     * @param {number} hours - Ore in formato decimale
     * @return {string} - Tempo formattato come "Xh Ymin"
     */
    function formatDecimalHoursToHoursMinutes(hours) {
        if (hours === null || hours === undefined || isNaN(hours)) {
            return "0h 0min";
        }
        
        // Converti ore in minuti
        const totalMinutes = Math.round(hours * 60);
        
        // Utilizza la funzione esistente
        return formatTimeHoursMinutes(totalMinutes);
    }
    
    /**
     * Formatta tutti i valori orari nella tabella e nei contatori
     */
    function formatAllTimeDisplays() {
        // Formatta i valori nella tabella risorse
        const tableRows = document.querySelectorAll('#resourcesTable tbody tr');
        tableRows.forEach(row => {
            // Le colonne 3-9 contengono valori orari
            for (let i = 2; i <= 8; i++) {
                const cell = row.cells[i];
                if (cell) {
                    const value = parseFloat(cell.textContent);
                    if (!isNaN(value)) {
                        // Salva il valore originale per uso futuro
                        cell.setAttribute('data-hours', value);
                        // Formatta e aggiorna il testo
                        cell.textContent = formatDecimalHoursToHoursMinutes(value);
                    }
                }
            }
        });
        
        // Formatta i contatori globali
        const totalElements = [
            { id: 'totalEstimatedHours', selector: '#totalEstimatedHours' },
            { id: 'totalActualHours', selector: '#totalActualHours' },
            { id: 'totalTreasureHours', selector: '#totalTreasureHours' }
        ];
        
        totalElements.forEach(item => {
            const element = document.querySelector(item.selector);
            if (element) {
                const value = parseFloat(element.textContent);
                if (!isNaN(value)) {
                    element.setAttribute('data-hours', value);
                    element.textContent = formatDecimalHoursToHoursMinutes(value);
                }
            }
        });
    }
    // FUNZIONI DI FORMATTAZIONE TEMPO - FINE

    // Inizializza Select2 per i filtri multipli
    $('.select2-multiple').select2({
        theme: 'bootstrap-5',
        placeholder: "Seleziona...",
        allowClear: true
    });
    
    // Global variables
    let resourcesData = {!! json_encode($resourcesData) !!};
    let currentResourceId = null;
    let globalStatsChart = null;
    let clientChart = null;
    let projectChart = null;
    let taskChart = null; // Nuovo grafico per task
    let taskDetails = []; // Array per memorizzare i dettagli dei task
    
    // Inizializza la dashboard
    initializeDashboard();
    
    // Event Listeners
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('refreshData').addEventListener('click', refreshData);
    document.getElementById('closeResourceDetail').addEventListener('click', hideResourceDetail);
    
    // Event listeners per i pulsanti di esportazione
    document.querySelectorAll('.export-btn').forEach(button => {
        button.addEventListener('click', handleExport);
    });
    
    // Event listener per il tab tasks
    document.getElementById('task-tab').addEventListener('click', function() {
        loadTaskDetails();
    });
    
    /**
     * Carica i dettagli dei task per la risorsa selezionata
     */
    function loadTaskDetails() {
        if (!currentResourceId) return;
        
        // Prepara i filtri
        const clientIds = $('#client_ids').val() || [];
        const projectIds = $('#project_ids').val() || [];
        
        // Costruisci correttamente la query string
        const queryParams = new URLSearchParams();
        
        // Aggiungi i filtri per client_ids
        if (clientIds.length > 0) {
            clientIds.forEach(id => queryParams.append('client_ids[]', id));
        }
        
        // Aggiungi i filtri per project_ids
        if (projectIds.length > 0) {
            projectIds.forEach(id => queryParams.append('project_ids[]', id));
        }
        
        // Aggiungi un parametro per indicare che vogliamo task unici - CORREZIONE QUI
        queryParams.append('unique_tasks', '1');
        
        // Mostra un indicatore di caricamento
        const tableBody = document.querySelector('#resourceTaskTable tbody');
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center">Caricamento dati in corso...</td></tr>';
        
        // Chiamata AJAX corretta con la nuova gestione dei parametri
        fetch(`/resource/${currentResourceId}/tasks?${queryParams.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                taskDetails = data.taskDetails;
                populateTaskTable(taskDetails);
                updateTaskChart(taskDetails);
            } else {
                console.error('Risposta ricevuta con success=false:', data);
                tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Errore durante il caricamento dei task.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Errore durante il caricamento dei task:', error);
            tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Errore durante il caricamento dei task. Dettagli in console.</td></tr>';
        });
    }
    
    /**
     * Popola la tabella dei task
     */
    function populateTaskTable(taskDetails) {
        const tableBody = document.querySelector('#resourceTaskTable tbody');
        tableBody.innerHTML = '';
        
        if (!taskDetails || taskDetails.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="10" class="text-center">Nessun task disponibile</td>';
            tableBody.appendChild(row);
            return;
        }
        
        taskDetails.forEach(task => {
            const row = document.createElement('tr');
            // Determina la classe CSS per il tesoretto (positivo = verde, negativo = rosso)
            const treasureClass = task.treasure_hours >= 0 ? 'treasure-positive' : 'treasure-negative';
            
            // Aggiungi classe per task in ritardo o sovrastimati
            if (task.is_overdue) {
                row.classList.add('table-danger');
            } else if (task.is_over_estimated) {
                row.classList.add('table-warning');
            }
            
            // Formatta i valori delle ore in formato "Xh Ymin"
            const estimatedHoursFormatted = formatDecimalHoursToHoursMinutes(task.estimated_hours);
            const actualHoursFormatted = formatDecimalHoursToHoursMinutes(task.actual_hours);
            const treasureHoursFormatted = formatDecimalHoursToHoursMinutes(task.treasure_hours);
            
            row.innerHTML = `
                <td>${task.name}</td>
                <td>${task.activity_name}</td>
                <td>${task.project_name}</td>
                <td>${task.client_name}</td>
                <td><span class="badge ${task.hours_type === 'standard' ? 'bg-info' : 'bg-warning'}">${task.hours_type_label}</span></td>
                <td><span class="badge ${getStatusBadgeClass(task.status)}">${task.status_label}</span></td>
                <td data-hours="${task.estimated_hours}">${estimatedHoursFormatted}</td>
                <td data-hours="${task.actual_hours}">${actualHoursFormatted}</td>
                <td class="${treasureClass}" data-hours="${task.treasure_hours}">${treasureHoursFormatted}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar ${getCompletionBarClass(task.completion_percentage, task.status)}" 
                             role="progressbar" style="width: ${task.completion_percentage}%">
                            ${task.completion_percentage.toFixed(0)}%
                        </div>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    /**
     * Ottiene la classe per il badge di stato
     */
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'pending': return 'bg-warning';
            case 'in_progress': return 'bg-primary';
            case 'completed': return 'bg-success';
            default: return 'bg-secondary';
        }
    }
    
    /**
     * Ottiene la classe per la barra di completamento
     */
    function getCompletionBarClass(percentage, status) {
        if (status === 'completed') return 'bg-success';
        if (percentage >= 100) return 'bg-danger';
        if (percentage >= 75) return 'bg-warning';
        return 'bg-primary';
    }
    
    /**
     * Aggiorna il grafico dei task
     */
    function updateTaskChart(taskDetails) {
        if (!taskChart) {
            // Inizializza il grafico se non esiste
            const taskCtx = document.getElementById('taskChart').getContext('2d');
            taskChart = new Chart(taskCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Ore Stimate',
                            data: [],
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Ore Effettive',
                            data: [],
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Tesoretto',
                            data: [],
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
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
                    },
                    // Aggiungi tooltip personalizzato per mostrare ore in formato "Xh Ymin"
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                                }
                            }
                        }
                    }
                }
            });
        }
        
        if (!taskDetails || taskDetails.length === 0) {
            taskChart.data.labels = [];
            taskChart.data.datasets[0].data = [];
            taskChart.data.datasets[1].data = [];
            taskChart.data.datasets[2].data = [];
            taskChart.update();
            return;
        }
        
        // Limita il numero di task mostrati nel grafico per leggibilità
        const maxTasksInChart = 10;
        const tasksToShow = taskDetails.length > maxTasksInChart 
            ? taskDetails.slice(0, maxTasksInChart) 
            : taskDetails;
        
        const labels = tasksToShow.map(task => task.name);
        const estimatedData = tasksToShow.map(task => task.estimated_hours);
        const actualData = tasksToShow.map(task => task.actual_hours);
        const treasureData = tasksToShow.map(task => task.treasure_hours);
        
        taskChart.data.labels = labels;
        taskChart.data.datasets[0].data = estimatedData;
        taskChart.data.datasets[1].data = actualData;
        taskChart.data.datasets[2].data = treasureData;
        
        taskChart.update();
    }
    
    /**
     * Carica i task di un'attività specifica
     */
    function loadActivityTasks(activityId, activityName) {
        if (!currentResourceId) return;
        
        // Aggiorna il nome dell'attività nel dettaglio
        document.getElementById('activityDetailName').textContent = activityName;
        
        // Prepara i filtri
        const clientIds = $('#client_ids').val() || [];
        const projectIds = $('#project_ids').val() || [];
        
        // Costruisci correttamente la query string
        const queryParams = new URLSearchParams();
        
        // Aggiungi l'activity_id
        queryParams.append('activity_id', activityId);
        
        // Aggiungi i filtri per client_ids
        if (clientIds.length > 0) {
            clientIds.forEach(id => queryParams.append('client_ids[]', id));
        }
        
        // Aggiungi i filtri per project_ids
        if (projectIds.length > 0) {
            projectIds.forEach(id => queryParams.append('project_ids[]', id));
        }
        
        // Aggiungi un parametro per indicare che vogliamo task unici - CORREZIONE QUI
        queryParams.append('unique_tasks', '1');
        
        // Mostra un indicatore di caricamento nel contenitore dei dettagli task
        const tableBody = document.querySelector('#taskDetailTable tbody');
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Caricamento dettagli task in corso...</td></tr>';
        
        // Mostra la sezione dei dettagli task prima di avviare la richiesta
        document.getElementById('taskDetail').style.display = 'block';
        
        // Chiamata AJAX corretta
        fetch(`/resource/${currentResourceId}/tasks?${queryParams.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateTaskDetailTable(data.taskDetails);
                // Scorri alla visualizzazione dettagli task
                document.getElementById('taskDetail').scrollIntoView({ behavior: 'smooth' });
            } else {
                console.error('Risposta ricevuta con success=false:', data);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Errore durante il caricamento dei task.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Errore durante il caricamento dei task:', error);
            // Mostra un messaggio di errore
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Errore durante il caricamento dei task. Dettagli in console.</td></tr>';
        });
    }
    
    /**
     * Popola la tabella dei dettagli task
     */
    function populateTaskDetailTable(taskDetails) {
        const tableBody = document.querySelector('#taskDetailTable tbody');
        tableBody.innerHTML = '';
        
        if (!taskDetails || taskDetails.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center">Nessun task disponibile per questa attività</td>';
            tableBody.appendChild(row);
            return;
        }
        
        taskDetails.forEach(task => {
            const row = document.createElement('tr');
            const treasureClass = task.treasure_hours >= 0 ? 'treasure-positive' : 'treasure-negative';
            
            // Aggiungi classe per task in ritardo o sovrastimati
            if (task.is_overdue) {
                row.classList.add('table-danger');
            } else if (task.is_over_estimated) {
                row.classList.add('table-warning');
            }
            
            // Formatta i valori delle ore in formato "Xh Ymin"
            const estimatedHoursFormatted = formatDecimalHoursToHoursMinutes(task.estimated_hours);
            const actualHoursFormatted = formatDecimalHoursToHoursMinutes(task.actual_hours);
            const treasureHoursFormatted = formatDecimalHoursToHoursMinutes(task.treasure_hours);
            
            row.innerHTML = `
                <td>${task.name}</td>
                <td><span class="badge ${getStatusBadgeClass(task.status)}">${task.status_label}</span></td>
                <td data-hours="${task.estimated_hours}">${estimatedHoursFormatted}</td>
                <td data-hours="${task.actual_hours}">${actualHoursFormatted}</td>
                <td class="${treasureClass}" data-hours="${task.treasure_hours}">${treasureHoursFormatted}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar ${getCompletionBarClass(task.completion_percentage, task.status)}" 
                             role="progressbar" style="width: ${task.completion_percentage}%">
                            ${task.completion_percentage.toFixed(0)}%
                        </div>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    /**
     * Aggiorna il grafico per cliente
     */
    function updateClientChart(resourceData) {
        try {
            console.log('Dati client chart:', resourceData.by_client);
            
            if (!clientChart) {
                console.error('Client chart non inizializzato');
                return;
            }
            
            if (!resourceData.by_client || !Array.isArray(resourceData.by_client) || resourceData.by_client.length === 0) {
                console.warn('Nessun dato cliente disponibile');
                clientChart.data.labels = [];
                clientChart.data.datasets[0].data = [];
                clientChart.data.datasets[1].data = [];
                clientChart.data.datasets[2].data = [];
                clientChart.update();
                return;
            }
            
            const labels = resourceData.by_client.map(client => client.name || 'N/D');
            const estimatedData = resourceData.by_client.map(client => client.estimated_hours || 0);
            const actualData = resourceData.by_client.map(client => client.actual_hours || 0);
            const treasureData = resourceData.by_client.map(client => client.treasure_hours || 0);
            
            clientChart.data.labels = labels;
            clientChart.data.datasets[0].data = estimatedData;
            clientChart.data.datasets[1].data = actualData;
            clientChart.data.datasets[2].data = treasureData;
            
            // Aggiorna le opzioni per mostrare tooltip con ore in formato "Xh Ymin"
            clientChart.options.plugins = clientChart.options.plugins || {};
            clientChart.options.plugins.tooltip = clientChart.options.plugins.tooltip || {};
            clientChart.options.plugins.tooltip.callbacks = {
                label: function(context) {
                    const label = context.dataset.label || '';
                    const value = context.raw || 0;
                    return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                }
            };
            
            clientChart.update();
        } catch (error) {
            console.error('Errore in updateClientChart:', error);
        }
    }
    
    /**
     * Aggiorna il grafico per progetto
     */
    function updateProjectChart(resourceData) {
        try {
            console.log('Dati project chart:', resourceData.by_project);
            
            if (!projectChart) {
                console.error('Project chart non inizializzato');
                return;
            }
            
            if (!resourceData.by_project || !Array.isArray(resourceData.by_project) || resourceData.by_project.length === 0) {
                console.warn('Nessun dato progetto disponibile');
                projectChart.data.labels = [];
                projectChart.data.datasets[0].data = [];
                projectChart.data.datasets[1].data = [];
                projectChart.data.datasets[2].data = [];
                projectChart.update();
                return;
            }
            
            const labels = resourceData.by_project.map(project => project.name || 'N/D');
            const estimatedData = resourceData.by_project.map(project => project.estimated_hours || 0);
            const actualData = resourceData.by_project.map(project => project.actual_hours || 0);
            const treasureData = resourceData.by_project.map(project => project.treasure_hours || 0);
            
            projectChart.data.labels = labels;
            projectChart.data.datasets[0].data = estimatedData;
            projectChart.data.datasets[1].data = actualData;
            projectChart.data.datasets[2].data = treasureData;
            
            // Aggiorna le opzioni per mostrare tooltip con ore in formato "Xh Ymin"
            projectChart.options.plugins = projectChart.options.plugins || {};
            projectChart.options.plugins.tooltip = projectChart.options.plugins.tooltip || {};
            projectChart.options.plugins.tooltip.callbacks = {
                label: function(context) {
                    const label = context.dataset.label || '';
                    const value = context.raw || 0;
                    return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                }
            };
            
            projectChart.update();
        } catch (error) {
            console.error('Errore in updateProjectChart:', error);
        }
    }
    
    /**
     * Applica i filtri selezionati
     */
    function applyFilters() {
        const formData = new FormData(document.getElementById('filterForm'));
        const clientIds = formData.getAll('client_ids[]');
        const projectIds = formData.getAll('project_ids[]');
        const resourceIds = formData.getAll('resource_ids[]');
        
        // Costruisci i parametri di query
        const queryParams = new URLSearchParams();
        
        // Aggiungi i filtri in modo sicuro
        if (clientIds.length > 0) {
            clientIds.forEach(id => {
                if (id) queryParams.append('client_ids[]', id);
            });
        }
        
        if (projectIds.length > 0) {
            projectIds.forEach(id => {
                if (id) queryParams.append('project_ids[]', id);
            });
        }
        
        if (resourceIds.length > 0) {
            resourceIds.forEach(id => {
                if (id) queryParams.append('resource_ids[]', id);
            });
        }
        
        // Mostra un indicatore di caricamento nella tabella risorse
        const tableBody = document.querySelector('#resourcesTable tbody');
        tableBody.innerHTML = '<tr><td colspan="11" class="text-center">Caricamento dati in corso...</td></tr>';
        
        // Invia la richiesta AJAX
        fetch(`/hours/filter?${queryParams.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Errore HTTP! stato: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Assicurati che resourcesData sia un array
                resourcesData = Array.isArray(data.resourcesData) ? data.resourcesData : [];
                
                // Aggiorna la dashboard
                updateGlobalStats();
                populateResourcesTable();
                
                // Applica la formattazione ore e minuti
                formatAllTimeDisplays();
                
                // Se una risorsa è attualmente selezionata, aggiorna la visualizzazione dettagliata
                if (currentResourceId) {
                    const resourceData = resourcesData.find(r => r.id == currentResourceId);
                    if (resourceData) {
                        // Assicurati che i dati siano corretti prima di passarli
                        if (resourceData.by_client && Array.isArray(resourceData.by_client)) {
                            populateClientTable(resourceData);
                        }
                        if (resourceData.by_project && Array.isArray(resourceData.by_project)) {
                            populateProjectTable(resourceData);
                        }
                        populateActivityTable(resourceData);
                        updateClientChart(resourceData);
                        updateProjectChart(resourceData);
                        
                        // Se il tab task è attivo, ricarica i dati dei task
                        if (document.getElementById('task-tab').classList.contains('active')) {
                            loadTaskDetails();
                        }
                    } else {
                        hideResourceDetail();
                    }
                }
            } else {
                console.error('Errore durante l\'applicazione dei filtri:', data);
                const tableBody = document.querySelector('#resourcesTable tbody');
                tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Errore durante l\'applicazione dei filtri.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Errore durante l\'applicazione dei filtri:', error);
            const tableBody = document.querySelector('#resourcesTable tbody');
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Errore durante l\'applicazione dei filtri. Dettagli in console.</td></tr>';
        });
    }
    
    /**
     * Resetta i filtri
     */
    function resetFilters() {
        // Resetta i select con Select2
        $('.select2-multiple').val(null).trigger('change');
        
        // Applica i filtri resettati
        applyFilters();
    }
    
    /**
     * Aggiorna i dati
     */
    function refreshData() {
        window.location.reload();
    }
    
    /**
     * Inizializza la dashboard con i dati forniti
     */
    function initializeDashboard() {
        updateGlobalStats();
        populateResourcesTable();
        initializeCharts();
        
        // Formatta tutti i valori orari
        formatAllTimeDisplays();
    }
    
    /**
     * Aggiorna le statistiche globali
     */
    function updateGlobalStats() {
        let totalEstimated = 0;
        let totalActual = 0;
        let totalTreasure = 0;
        
        resourcesData.forEach(resource => {
            totalEstimated += parseFloat(resource.total_estimated_hours);
            totalActual += parseFloat(resource.total_actual_hours);
            totalTreasure += parseFloat(resource.total_treasure_hours);
        });
        
        // Calcola l'efficienza (percentuale di ore effettive rispetto alle stimate)
        const efficiency = totalEstimated > 0 ? (totalActual / totalEstimated) * 100 : 0;
        
        // Aggiorna il DOM
        document.getElementById('totalEstimatedHours').textContent = totalEstimated.toFixed(2);
        document.getElementById('totalEstimatedHours').setAttribute('data-hours', totalEstimated);
        
        document.getElementById('totalActualHours').textContent = totalActual.toFixed(2);
        document.getElementById('totalActualHours').setAttribute('data-hours', totalActual);
        
        const treasureElement = document.getElementById('totalTreasureHours');
        treasureElement.textContent = totalTreasure.toFixed(2);
        treasureElement.setAttribute('data-hours', totalTreasure);
        treasureElement.className = totalTreasure >= 0 ? 'treasure-positive' : 'treasure-negative';
        
        document.getElementById('efficiency').textContent = efficiency.toFixed(2) + '%';
        
        // Aggiorna il grafico globale
        updateGlobalStatsChart(totalEstimated, totalActual, totalTreasure);
    }
    
    /**
     * Popola la tabella delle risorse
     */
    function populateResourcesTable() {
        const tableBody = document.querySelector('#resourcesTable tbody');
        tableBody.innerHTML = '';
        
        if (!resourcesData || resourcesData.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="11" class="text-center">Nessun dato disponibile</td>';
            tableBody.appendChild(row);
            return;
        }
        
        resourcesData.forEach(resource => {
            const row = document.createElement('tr');
            
            // Determina le classi CSS per il tesoretto e le ore rimanenti
            const treasureClass = resource.total_treasure_hours >= 0 ? 'text-success' : 'text-danger';
            const stdRemainingClass = resource.remaining_standard_hours >= 0 ? 'text-success' : 'text-danger';
            const extraRemainingClass = resource.remaining_extra_hours >= 0 ? 'text-success' : 'text-danger';
            
            // Salva i valori originali come attributi data per uso futuro
            const standardHours = resource.standard_hours_per_year.toFixed(2);
            const remainingStdHours = resource.remaining_standard_hours.toFixed(2);
            const extraHours = resource.extra_hours_per_year.toFixed(2);
            const remainingExtraHours = resource.remaining_extra_hours.toFixed(2);
            const estimatedHours = resource.total_estimated_hours.toFixed(2);
            const actualHours = resource.total_actual_hours.toFixed(2);
            const treasureHours = resource.total_treasure_hours.toFixed(2);
            
            row.innerHTML = `
                <td>${resource.name}</td>
                <td>${resource.role}</td>
                <td data-hours="${standardHours}">${standardHours}</td>
                <td class="${stdRemainingClass} fw-bold" data-hours="${remainingStdHours}">${remainingStdHours}</td>
                <td data-hours="${extraHours}">${extraHours}</td>
                <td class="${extraRemainingClass} fw-bold" data-hours="${remainingExtraHours}">${remainingExtraHours}</td>
                <td data-hours="${estimatedHours}">${estimatedHours}</td>
                <td data-hours="${actualHours}">${actualHours}</td>
                <td class="${treasureClass} fw-bold" data-hours="${treasureHours}">${treasureHours}</td>
                <td>
                    <div class="small">Standard: ${resource.standard_hours_usage}%</div>
                    <div class="progress mb-2">
                        <div class="progress-bar ${resource.standard_hours_usage > 95 ? 'bg-danger' : 'bg-primary'}" 
                            role="progressbar" style="width: ${Math.min(100, resource.standard_hours_usage)}%"></div>
                    </div>
                    <div class="small">Extra: ${resource.extra_hours_usage}%</div>
                    <div class="progress">
                        <div class="progress-bar ${resource.extra_hours_usage > 95 ? 'bg-danger' : 'bg-success'}" 
                            role="progressbar" style="width: ${Math.min(100, resource.extra_hours_usage)}%"></div>
                    </div>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary btn-detail" data-resource-id="${resource.id}">
                        <i class="fas fa-chart-pie"></i> Dettagli
                    </button>
                </td>
            `;
            
            // Aggiungi l'event listener per il pulsante dettagli
            const detailButton = row.querySelector('.btn-detail');
            detailButton.addEventListener('click', function() {
                const resourceId = this.getAttribute('data-resource-id');
                showResourceDetail(resourceId);
            });
            
            tableBody.appendChild(row);
        });
    }
    
    /**
     * Inizializza i grafici
     */
    function initializeCharts() {
        // Grafico globale
        const globalCtx = document.getElementById('globalStatsChart').getContext('2d');
        globalStatsChart = new Chart(globalCtx, {
            type: 'bar',
            data: {
                labels: ['Ore Stimate', 'Ore Effettive', 'Tesoretto'],
                datasets: [{
                    label: 'Panoramica Globale Ore',
                    data: [0, 0, 0],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                // Aggiungi un tooltip personalizzato
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                            }
                        }
                    }
                }
            }
        });
        
        // Grafico clienti (verrà popolato al click su una risorsa)
        const clientCtx = document.getElementById('clientChart').getContext('2d');
        clientChart = new Chart(clientCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Ore Stimate',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ore Effettive',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Tesoretto',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                            }
                        }
                    }
                }
            }
        });
        
        // Grafico progetti (verrà popolato al click su una risorsa)
        const projectCtx = document.getElementById('projectChart').getContext('2d');
        projectChart = new Chart(projectCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Ore Stimate',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ore Effettive',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Tesoretto',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                            }
                        }
                    }
                }
            }
        });
        
        // Grafico task (inizializzato nella funzione updateTaskChart)
        const taskCtx = document.getElementById('taskChart').getContext('2d');
        taskChart = new Chart(taskCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Ore Stimate',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ore Effettive',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Tesoretto',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                return label + ': ' + formatDecimalHoursToHoursMinutes(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Aggiorna il grafico delle statistiche globali
     */
    function updateGlobalStatsChart(estimated, actual, treasure) {
        if (!globalStatsChart) return;
        
        globalStatsChart.data.datasets[0].data = [estimated, actual, treasure];
        globalStatsChart.update();
    }
    
    /**
     * Mostra i dettagli della risorsa selezionata
     */
    function showResourceDetail(resourceId) {
        // Log dettagliato per diagnosticare il problema
        console.log('Tutti i dati delle risorse:', resourcesData);
        console.log('ID risorsa selezionata:', resourceId);
        
        // Trova la risorsa corrispondente
        const resourceData = resourcesData.find(r => r.id == resourceId);
        
        if (!resourceData) {
            console.error('Nessuna risorsa trovata con ID:', resourceId);
            alert('Impossibile caricare i dettagli della risorsa.');
            return;
        }
        
        // Log dettagliato della risorsa selezionata
        console.log('Dati risorsa selezionata:', resourceData);
        console.log('Chiavi nella risorsa selezionata:', Object.keys(resourceData));
        
        // Controlli aggiuntivi sui dati
        console.log('by_client:', resourceData.by_client);
        console.log('by_project:', resourceData.by_project);
        console.log('by_activity:', resourceData.by_activity);
        
        // Imposta l'ID della risorsa corrente
        currentResourceId = resourceId;
        
        // Aggiorna il titolo
        document.getElementById('detailResourceName').textContent = resourceData.name;
        
        // Funzione di popolazione sicura
        function safePopulate(populateFunction, dataKey) {
            try {
                // Forza la conversione in array se necessario
                const data = Array.isArray(resourceData[dataKey]) 
                    ? resourceData[dataKey] 
                    : (resourceData[dataKey] ? [resourceData[dataKey]] : []);
                
                console.log(`Dati per ${dataKey}:`, data);
                
                if (data.length > 0) {
                    populateFunction({ [dataKey]: data });
                } else {
                    console.warn(`Nessun dato disponibile per ${dataKey}`);
                    // Pulisci la tabella
                    const tableBody = document.querySelector(`#resource${dataKey.charAt(0).toUpperCase() + dataKey.slice(1)}Table tbody`);
                    if (tableBody) {
                        tableBody.innerHTML = `<tr><td colspan="10" class="text-center">Nessun dato disponibile</td></tr>`;
                    }
                }
            } catch (error) {
                console.error(`Errore durante la popolazione di ${dataKey}:`, error);
            }
        }
        
        // Popola le tabelle
        safePopulate(populateClientTable, 'by_client');
        safePopulate(populateProjectTable, 'by_project');
        safePopulate(populateActivityTable, 'by_activity');
        
        // Aggiorna i grafici
        try {
            updateClientChart(resourceData);
            updateProjectChart(resourceData);
        } catch (error) {
            console.error('Errore durante l\'aggiornamento dei grafici:', error);
        }
        
        // Mostra il pannello dei dettagli
        document.getElementById('resourceDetail').style.display = 'block';
        
        // Scorri alla visualizzazione dettagli
        document.getElementById('resourceDetail').scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Popola la tabella per cliente
     */
    function populateClientTable(resourceData) {
        const tableBody = document.querySelector('#resourceClientTable tbody');
        tableBody.innerHTML = '';
        
        // Log per debug
        console.log('Dati client per tabella:', resourceData.by_client);
        
        if (!resourceData.by_client || !Array.isArray(resourceData.by_client) || resourceData.by_client.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="5" class="text-center">Nessun dato disponibile per i clienti</td>';
            tableBody.appendChild(row);
            return;
        }
        
        resourceData.by_client.forEach(client => {
            const row = document.createElement('tr');
            const completionPercentage = client.estimated_hours > 0 ? 
                (client.actual_hours / client.estimated_hours) * 100 : 0;
            
            const treasureClass = client.treasure_hours >= 0 ? 'treasure-positive' : 'treasure-negative';
            
            // Formatta i valori delle ore in formato "Xh Ymin"
            const estimatedHoursFormatted = formatDecimalHoursToHoursMinutes(client.estimated_hours || 0);
            const actualHoursFormatted = formatDecimalHoursToHoursMinutes(client.actual_hours || 0);
            const treasureHoursFormatted = formatDecimalHoursToHoursMinutes(client.treasure_hours || 0);
            
            row.innerHTML = `
                <td>${client.name || 'N/D'}</td>
                <td data-hours="${client.estimated_hours || 0}">${estimatedHoursFormatted}</td>
                <td data-hours="${client.actual_hours || 0}">${actualHoursFormatted}</td>
                <td class="${treasureClass}" data-hours="${client.treasure_hours || 0}">${treasureHoursFormatted}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: ${Math.min(100, completionPercentage)}%">
                            ${completionPercentage.toFixed(0)}%
                        </div>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }

    /**
     * Popola la tabella per progetto
     */
    function populateProjectTable(resourceData) {
        const tableBody = document.querySelector('#resourceProjectTable tbody');
        tableBody.innerHTML = '';
        
        console.log('Dati progetti per tabella:', resourceData.by_project);
        
        if (!resourceData.by_project || resourceData.by_project.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center">Nessun dato disponibile per i progetti</td>';
            tableBody.appendChild(row);
            return;
        }
        
        resourceData.by_project.forEach(project => {
            const row = document.createElement('tr');
            const completionPercentage = project.estimated_hours > 0 ? 
                (project.actual_hours / project.estimated_hours) * 100 : 0;
            
            const treasureClass = project.treasure_hours >= 0 ? 'treasure-positive' : 'treasure-negative';
            
            // Formatta i valori delle ore in formato "Xh Ymin"
            const estimatedHoursFormatted = formatDecimalHoursToHoursMinutes(project.estimated_hours || 0);
            const actualHoursFormatted = formatDecimalHoursToHoursMinutes(project.actual_hours || 0);
            const treasureHoursFormatted = formatDecimalHoursToHoursMinutes(project.treasure_hours || 0);
            
            row.innerHTML = `
                <td>${project.name || 'N/D'}</td>
                <td>${project.client_name || 'N/D'}</td>
                <td data-hours="${project.estimated_hours || 0}">${estimatedHoursFormatted}</td>
                <td data-hours="${project.actual_hours || 0}">${actualHoursFormatted}</td>
                <td class="${treasureClass}" data-hours="${project.treasure_hours || 0}">${treasureHoursFormatted}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: ${Math.min(100, completionPercentage)}%">
                            ${completionPercentage.toFixed(0)}%
                        </div>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }

    /**
     * Popola la tabella per attività
     */
    function populateActivityTable(resourceData) {
        const tableBody = document.querySelector('#resourceActivityTable tbody');
        tableBody.innerHTML = '';
        
        if (!resourceData.by_activity || resourceData.by_activity.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="9" class="text-center">Nessun dato disponibile</td>';
            tableBody.appendChild(row);
            return;
        }
        
        resourceData.by_activity.forEach(activity => {
            const row = document.createElement('tr');
            const treasureClass = activity.treasure_hours >= 0 ? 'treasure-positive' : 'treasure-negative';
            
            // Determina il badge per lo stato
            let statusBadge = '';
            switch (activity.status) {
                case 'pending':
                    statusBadge = '<span class="badge bg-warning">In attesa</span>';
                    break;
                case 'in_progress':
                    statusBadge = '<span class="badge bg-primary">In corso</span>';
                    break;
                case 'completed':
                    statusBadge = '<span class="badge bg-success">Completato</span>';
                    break;
                default:
                    statusBadge = '<span class="badge bg-secondary">N/D</span>';
            }
            
            // Determina il badge per il tipo di ore
            let hoursTypeBadge = '';
            switch (activity.hours_type) {
                case 'standard':
                    hoursTypeBadge = '<span class="badge bg-info">Standard</span>';
                    break;
                case 'extra':
                    hoursTypeBadge = '<span class="badge bg-warning">Extra</span>';
                    break;
                default:
                    hoursTypeBadge = '<span class="badge bg-secondary">N/D</span>';
            }
            
            // Formatta i valori delle ore in formato "Xh Ymin"
            const estimatedHoursFormatted = formatDecimalHoursToHoursMinutes(activity.estimated_hours);
            const actualHoursFormatted = formatDecimalHoursToHoursMinutes(activity.actual_hours);
            const treasureHoursFormatted = formatDecimalHoursToHoursMinutes(activity.treasure_hours);
            
            row.innerHTML = `
                <td>${activity.name}</td>
                <td>${activity.project_name}</td>
                <td>${activity.client_name}</td>
                <td>${hoursTypeBadge}</td>
                <td>${statusBadge}</td>
                <td data-hours="${activity.estimated_hours}">${estimatedHoursFormatted}</td>
                <td data-hours="${activity.actual_hours}">${actualHoursFormatted}</td>
                <td class="${treasureClass}" data-hours="${activity.treasure_hours}">${treasureHoursFormatted}</td>
                <td>
                    <button class="btn btn-sm btn-info view-tasks" data-activity-id="${activity.id}">
                        <i class="fas fa-tasks"></i> Vedi Task
                    </button>
                </td>
            `;
            
            // Aggiungi l'event listener per il pulsante "Vedi Task"
            const viewTasksButton = row.querySelector('.view-tasks');
            viewTasksButton.addEventListener('click', function() {
                const activityId = this.getAttribute('data-activity-id');
                loadActivityTasks(activityId, activity.name);
            });
            
            tableBody.appendChild(row);
        });
    }
    
    /**
     * Nasconde i dettagli della risorsa
     */
    function hideResourceDetail() {
        document.getElementById('resourceDetail').style.display = 'none';
        document.getElementById('taskDetail').style.display = 'none'; // Nasconde anche il dettaglio task
        currentResourceId = null;
    }
    
    /**
     * Gestisce l'esportazione dei dati
     */
    function handleExport(e) {
        e.preventDefault();
        
        // Ottieni il tipo di esportazione
        const exportType = this.getAttribute('data-type');
        
        // Crea un form temporaneo per l'invio dei dati
        const tempForm = document.createElement('form');
        tempForm.method = 'GET';
        tempForm.action = '{{ route("hours.export") }}';
        tempForm.style.display = 'none';
        
        // Aggiungi il tipo di esportazione
        const exportTypeInput = document.createElement('input');
        exportTypeInput.type = 'hidden';
        exportTypeInput.name = 'export_type';
        exportTypeInput.value = exportType;
        tempForm.appendChild(exportTypeInput);
        
        // Aggiungi i filtri correnti
        const clientIds = $('#client_ids').val() || [];
        const projectIds = $('#project_ids').val() || [];
        const resourceIds = $('#resource_ids').val() || [];
        
        // Aggiungi un parametro per indicare che vogliamo dati unici - CORREZIONE QUI
        const uniqueInput = document.createElement('input');
        uniqueInput.type = 'hidden';
        uniqueInput.name = 'unique_tasks';
        uniqueInput.value = '1';
        tempForm.appendChild(uniqueInput);
        
        clientIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'client_ids[]';
            input.value = id;
            tempForm.appendChild(input);
        });
        
        projectIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'project_ids[]';
            input.value = id;
            tempForm.appendChild(input);
        });
        
        resourceIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'resource_ids[]';
            input.value = id;
            tempForm.appendChild(input);
        });
        
        // Aggiungi il form al corpo del documento e invialo
        document.body.appendChild(tempForm);
        tempForm.submit();
        
        // Rimuovi il form dopo l'invio
        document.body.removeChild(tempForm);
    }
});
</script>
@endpush