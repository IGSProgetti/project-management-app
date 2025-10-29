@extends('layouts.app')

@section('title', 'Gestione Progetti')

@push('styles')
<link href="{{ asset('css/projects-mobile.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Progetti</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Progetto
            </a>
        </div>
    </div>

    <!-- Alert per progetti da consolidare -->
    @if($projects->where('created_from_tasks', true)->count() > 0)
    <div class="alert alert-warning mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Attenzione!</strong> 
                Ci sono {{ $projects->where('created_from_tasks', true)->count() }} progetti creati automaticamente dai tasks che necessitano di essere consolidati o riorganizzati.
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="showOnlyTasksCreated()">
                    <i class="fas fa-filter"></i> Mostra solo progetti da tasks
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Filtri -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('projects.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="filterClient">Cliente</label>
                            <select name="client" id="filterClient" class="form-select">
                                <option value="">Tutti i clienti</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ request('client') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                        @if($client->created_from_tasks)
                                            [Da Tasks]
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="filterStatus">Stato</label>
                            <select name="status" id="filterStatus" class="form-select">
                                <option value="">Tutti gli stati</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In attesa</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In corso</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completato</option>
                                <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>In pausa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="filterCreatedFrom">Origine</label>
                            <select name="created_from" id="filterCreatedFrom" class="form-select">
                                <option value="">Tutte le origini</option>
                                <option value="normal" {{ request('created_from') == 'normal' ? 'selected' : '' }}>Creati normalmente</option>
                                <option value="tasks" {{ request('created_from') == 'tasks' ? 'selected' : '' }}>Creati da Tasks</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label>&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-filter"></i> Applica Filtri
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Elenco Progetti</h5>
        </div>
        <div class="card-body">
            @if(isset($projects) && $projects->count() > 0)
                <!-- VISTA TABELLA (Desktop) -->
                <div class="table-responsive">
                    <table class="table table-striped" id="projectsTable">
                        <thead>
                            <tr>
                                <th>Nome Progetto</th>
                                <th>Cliente</th>
                                <th>Stato</th>
                                <th>Progresso</th>
                                <th>Costo Totale</th>
                                <th>Origine</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                            <tr class="{{ $project->created_from_tasks ? 'table-warning' : '' }}" 
                                data-created-from="{{ $project->created_from_tasks ? 'tasks' : 'normal' }}"
                                data-client="{{ $project->client_id }}"
                                data-status="{{ $project->status }}">
                                <td>
                                    <strong>{{ $project->name }}</strong>
                                    @if($project->created_from_tasks)
                                        <br><small class="text-muted">Creato il {{ $project->tasks_created_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                    @if($project->description)
                                        <br><small class="text-muted">{{ Str::limit($project->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('clients.show', $project->client->id) }}">
                                        {{ $project->client->name }}
                                    </a>
                                    @if($project->client->created_from_tasks)
                                        <br><span class="badge bg-warning badge-sm">Cliente da Tasks</span>
                                    @endif
                                </td>
                                <td>
                                    @if($project->status == 'pending')
                                        <span class="badge bg-warning">In attesa</span>
                                    @elseif($project->status == 'in_progress')
                                        <span class="badge bg-primary">In corso</span>
                                    @elseif($project->status == 'completed')
                                        <span class="badge bg-success">Completato</span>
                                    @elseif($project->status == 'on_hold')
                                        <span class="badge bg-secondary">In pausa</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress mb-1" style="height: 8px;">
                                        <div class="progress-bar {{ $project->progress_percentage > 90 ? 'bg-success' : ($project->progress_percentage > 50 ? 'bg-info' : 'bg-warning') }}" 
                                             role="progressbar" 
                                             style="width: {{ $project->progress_percentage }}%"
                                             aria-valuenow="{{ $project->progress_percentage }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small>{{ $project->progress_percentage }}%</small>
                                </td>
                                <td>{{ number_format($project->total_cost, 2) }} €</td>
                                <td>
                                    @if($project->created_from_tasks)
                                        <span class="badge bg-warning">Da Tasks</span>
                                    @else
                                        <span class="badge bg-info">Normale</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('projects.destroy', $project->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo progetto?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- VISTA CARD (Mobile) -->
                <div class="projects-mobile-view">
                    <div class="projects-mobile-container">
                        @foreach($projects as $project)
                            <div class="project-card {{ $project->created_from_tasks ? 'created-from-tasks' : '' }}"
                                 data-created-from="{{ $project->created_from_tasks ? 'tasks' : 'normal' }}"
                                 data-client="{{ $project->client_id }}"
                                 data-status="{{ $project->status }}">
                                
                                <!-- Warning Badge per progetti da tasks -->
                                @if($project->created_from_tasks)
                                <div class="project-warning-badge">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Creato da Tasks il {{ $project->tasks_created_at->format('d/m/Y H:i') }}
                                </div>
                                @endif

                                <!-- Header -->
                                <div class="project-card-header">
                                    <h3 class="project-card-title">{{ $project->name }}</h3>
                                    <span class="project-status-badge status-{{ $project->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </div>

                                <!-- Descrizione -->
                                @if($project->description)
                                <div class="project-description">
                                    {{ $project->description }}
                                </div>
                                @else
                                <div class="project-description empty">
                                    Nessuna descrizione disponibile
                                </div>
                                @endif

                                <!-- Informazioni Cliente -->
                                <div class="project-card-info">
                                    <div class="project-info-row">
                                        <i class="fas fa-building project-info-icon"></i>
                                        <span class="project-info-label">Cliente:</span>
                                        <span class="project-client-badge {{ $project->client->created_from_tasks ? 'from-tasks' : '' }}">
                                            {{ $project->client->name }}
                                            @if($project->client->created_from_tasks)
                                                <i class="fas fa-exclamation-circle"></i>
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <!-- Date Progetto -->
                                @if($project->start_date || $project->end_date)
                                <div class="project-dates">
                                    @if($project->start_date)
                                    <div class="project-date-item">
                                        <div class="project-date-label">Inizio</div>
                                        <div class="project-date-value">{{ $project->start_date->format('d/m/Y') }}</div>
                                    </div>
                                    @endif
                                    @if($project->end_date)
                                    <div class="project-date-item">
                                        <div class="project-date-label">Fine</div>
                                        <div class="project-date-value">{{ $project->end_date->format('d/m/Y') }}</div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- Budget e Progresso -->
                                <div class="project-card-budget">
                                    <div class="project-budget-row">
                                        <span class="project-budget-label">💰 Costo Totale:</span>
                                        <span class="project-budget-value">{{ number_format($project->total_cost, 2) }} €</span>
                                    </div>
                                    
                                    <!-- Barra Progresso -->
                                    <div class="project-progress-bar">
                                        @php
                                            $progressClass = 'high';
                                            if ($project->progress_percentage < 50) {
                                                $progressClass = 'low';
                                            } elseif ($project->progress_percentage < 90) {
                                                $progressClass = 'medium';
                                            }
                                        @endphp
                                        <div class="project-progress-fill {{ $progressClass }}" style="width: {{ $project->progress_percentage }}%"></div>
                                    </div>
                                    <div class="project-progress-text">{{ $project->progress_percentage }}% completato</div>
                                </div>

                                <!-- Stats Inline -->
                                <div class="project-stats-inline">
                                    <div class="stat-item">
                                        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                                        <div class="stat-value">{{ $project->activities->count() }}</div>
                                        <div class="stat-label">Attività</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                                        <div class="stat-value">{{ $project->resources->count() }}</div>
                                        <div class="stat-label">Risorse</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                                        <div class="stat-value">{{ $project->activities->where('status', 'completed')->count() }}</div>
                                        <div class="stat-label">Completate</div>
                                    </div>
                                </div>

                                <!-- Pulsanti Azione -->
                                <div class="project-card-actions">
                                    <a href="{{ route('projects.show', $project->id) }}" class="project-action-btn btn-view">
                                        <i class="fas fa-eye"></i> Visualizza
                                    </a>
                                    <a href="{{ route('projects.edit', $project->id) }}" class="project-action-btn btn-edit">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <form action="{{ route('projects.destroy', $project->id) }}" method="POST" style="display: inline; flex: 1;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="project-action-btn btn-delete" style="width: 100%;" onclick="return confirm('Sei sicuro di voler eliminare questo progetto?')">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="no-projects-message">
                    <i class="fas fa-folder-open"></i>
                    <p>Nessun progetto disponibile</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Gestione filtri
    const filterClient = document.getElementById('filterClient');
    const filterStatus = document.getElementById('filterStatus');
    const filterCreatedFrom = document.getElementById('filterCreatedFrom');
    const table = document.getElementById('projectsTable');
    
    // Applica filtri lato client (per le card mobile)
    function applyClientFilters() {
        const clientFilter = filterClient ? filterClient.value : '';
        const statusFilter = filterStatus ? filterStatus.value : '';
        const createdFromFilter = filterCreatedFrom ? filterCreatedFrom.value : '';
        
        // Filtra righe tabella (desktop)
        const rows = table ? table.querySelectorAll('tbody tr') : [];
        
        // Filtra card (mobile)
        const cards = document.querySelectorAll('.project-card');
        
        // Funzione comune per controllare i filtri
        function shouldShow(element) {
            const clientMatch = !clientFilter || element.dataset.client === clientFilter;
            const statusMatch = !statusFilter || element.dataset.status === statusFilter;
            
            let createdFromMatch = true;
            if (createdFromFilter) {
                const isFromTasks = element.dataset.createdFrom === 'tasks';
                createdFromMatch = (createdFromFilter === 'tasks' && isFromTasks) || 
                                   (createdFromFilter === 'normal' && !isFromTasks);
            }
            
            return clientMatch && statusMatch && createdFromMatch;
        }
        
        // Applica filtri alle righe della tabella
        rows.forEach(row => {
            row.style.display = shouldShow(row) ? '' : 'none';
        });
        
        // Applica filtri alle card
        cards.forEach(card => {
            card.style.display = shouldShow(card) ? 'block' : 'none';
        });
    }
    
    // Event listeners per i filtri
    if (filterClient) filterClient.addEventListener('change', applyClientFilters);
    if (filterStatus) filterStatus.addEventListener('change', applyClientFilters);
    if (filterCreatedFrom) filterCreatedFrom.addEventListener('change', applyClientFilters);
    
    // Funzione per mostrare solo progetti da tasks (richiamata dal pulsante nell'alert)
    window.showOnlyTasksCreated = function() {
        if (filterCreatedFrom) {
            filterCreatedFrom.value = 'tasks';
            applyClientFilters();
        }
    };
});
</script>
@endpush