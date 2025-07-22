@extends('layouts.app')

@section('title', 'Gestione Progetti')

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
                            data-created-from="{{ $project->created_from_tasks ? 'tasks' : 'normal' }}">
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
                                         style="width: {{ $project->progress_percentage }}%"></div>
                                </div>
                                <small class="text-muted">{{ $project->progress_percentage }}%</small>
                            </td>
                            <td>
                                {{ number_format($project->total_cost, 2) }} €
                                @if($project->activities_count > 0)
                                    <br><small class="text-muted">{{ $project->activities_count }} attività</small>
                                @endif
                            </td>
                            <td>
                                @if($project->created_from_tasks)
                                    <span class="badge bg-warning">
                                        <i class="fas fa-tasks"></i> Da Tasks
                                    </span>
                                    <br><small class="text-warning">Da consolidare</small>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-project-diagram"></i> Standard
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-info" title="Visualizza">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-warning" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    @if($project->created_from_tasks)
                                        <!-- Dropdown per azioni specifiche progetti da tasks -->
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cogs"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <button class="dropdown-item consolidate-project-btn" 
                                                            data-project-id="{{ $project->id }}" 
                                                            data-project-name="{{ $project->name }}">
                                                        <i class="fas fa-check-circle text-success"></i> Consolida Progetto
                                                    </button>
                                                </li>
                                                @if($project->activities_count > 0)
                                                <li>
                                                    <button class="dropdown-item reassign-tasks-btn" 
                                                            data-project-id="{{ $project->id }}" 
                                                            data-project-name="{{ $project->name }}">
                                                        <i class="fas fa-exchange-alt text-info"></i> Riassegna Tasks
                                                    </button>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" 
                                                                onclick="return confirm('Eliminare questo progetto? Tutti i tasks verranno eliminati!')">
                                                            <i class="fas fa-trash"></i> Elimina Progetto
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @else
                                        <!-- Azioni standard per progetti normali -->
                                        @if($project->activities_count == 0)
                                            <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" 
                                                        onclick="return confirm('Sei sicuro di voler eliminare questo progetto?')"
                                                        title="Elimina">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal per consolidare progetto -->
<div class="modal fade" id="consolidateProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Consolida Progetto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="consolidateProjectForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Consolidamento Progetto</strong><br>
                        Stai per consolidare un progetto creato automaticamente dai tasks. 
                        Completa le informazioni mancanti per renderlo un progetto ufficiale.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="consolidate_description">Descrizione Dettagliata</label>
                            <textarea name="description" id="consolidate_description" class="form-control" rows="3" 
                                      placeholder="Descrizione completa del progetto..."></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="consolidate_start_date">Data Inizio</label>
                            <input type="date" name="start_date" id="consolidate_start_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="consolidate_end_date">Data Fine Prevista</label>
                            <input type="date" name="end_date" id="consolidate_end_date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="consolidate_hours_type">Tipo Ore Standard</label>
                            <select name="default_hours_type" id="consolidate_hours_type" class="form-select">
                                <option value="standard">Standard</option>
                                <option value="extra">Extra</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Step di Costo Attivi</label>
                            <div class="form-check-container">
                                @php
                                $costSteps = [
                                    1 => 'Costo struttura (25%)',
                                    2 => 'Utile gestore azienda (12.5%)',
                                    3 => 'Utile IGS (12.5%)',
                                    4 => 'Compenso professionista (20%)',
                                    5 => 'Bonus professionista (5%)',
                                    6 => 'Gestore società (3%)',
                                    7 => 'Chi porta il lavoro (8%)',
                                    8 => 'Network IGS (14%)'
                                ];
                                @endphp
                                @foreach($costSteps as $step => $label)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="cost_steps[]" value="{{ $step }}" id="step{{ $step }}" checked>
                                    <label class="form-check-label" for="step{{ $step }}">{{ $step }}</label>
                                </div>
                                @endforeach
                            </div>
                            <small class="text-muted">Deseleziona gli step non applicabili a questo progetto</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Consolida Progetto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per riassegnare tasks -->
<div class="modal fade" id="reassignTasksModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Riassegna Tasks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reassignTasksForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exchange-alt"></i>
                        <strong>Riassegnazione Tasks</strong><br>
                        Seleziona i tasks da spostare e il progetto di destinazione. 
                        I tasks verranno spostati in attività equivalenti o nuove.
                    </div>
                    
                    <div class="mb-3">
                        <label for="target_project_id">Progetto di Destinazione</label>
                        <select name="target_project_id" id="target_project_id" class="form-select" required>
                            <option value="">Seleziona progetto di destinazione</option>
                            @foreach($projects->where('created_from_tasks', false) as $targetProject)
                                <option value="{{ $targetProject->id }}">{{ $targetProject->name }} ({{ $targetProject->client->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div id="tasksListContainer">
                        <!-- I tasks verranno caricati dinamicamente via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-exchange-alt"></i> Riassegna Tasks Selezionati
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione consolidamento progetto
    const consolidateProjectButtons = document.querySelectorAll('.consolidate-project-btn');
    const consolidateProjectModal = new bootstrap.Modal(document.getElementById('consolidateProjectModal'));
    const consolidateProjectForm = document.getElementById('consolidateProjectForm');

    consolidateProjectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const projectName = this.dataset.projectName;
            
            document.querySelector('#consolidateProjectModal .modal-title').textContent = `Consolida Progetto: ${projectName}`;
            consolidateProjectForm.action = `/projects/${projectId}/consolidate`;
            
            consolidateProjectModal.show();
        });
    });

    // Gestione riassegnazione tasks
    const reassignTasksButtons = document.querySelectorAll('.reassign-tasks-btn');
    const reassignTasksModal = new bootstrap.Modal(document.getElementById('reassignTasksModal'));
    const reassignTasksForm = document.getElementById('reassignTasksForm');

    reassignTasksButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const projectName = this.dataset.projectName;
            
            document.querySelector('#reassignTasksModal .modal-title').textContent = `Riassegna Tasks da: ${projectName}`;
            reassignTasksForm.action = `/projects/${projectId}/reassign-tasks`;
            
            // Carica i tasks del progetto
            loadProjectTasks(projectId);
            
            reassignTasksModal.show();
        });
    });

    // Funzione per caricare i tasks di un progetto
    function loadProjectTasks(projectId) {
        const container = document.getElementById('tasksListContainer');
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Caricamento tasks...</div>';
        
        fetch(`/api/project-tasks/${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tasks.length > 0) {
                    let html = '<h6>Seleziona i tasks da riassegnare:</h6>';
                    html += '<div class="form-check mb-2">';
                    html += '<input class="form-check-input" type="checkbox" id="selectAllTasks">';
                    html += '<label class="form-check-label" for="selectAllTasks"><strong>Seleziona tutti</strong></label>';
                    html += '</div><hr>';
                    
                    data.tasks.forEach(task => {
                        html += '<div class="form-check mb-2">';
                        html += `<input class="form-check-input task-checkbox" type="checkbox" name="task_ids[]" value="${task.id}" id="task_${task.id}">`;
                        html += `<label class="form-check-label" for="task_${task.id}">`;
                        html += `<strong>${task.name}</strong><br>`;
                        html += `<small class="text-muted">Attività: ${task.activity_name} | Stato: ${task.status} | ${task.estimated_minutes} min</small>`;
                        html += '</label>';
                        html += '</div>';
                    });
                    
                    container.innerHTML = html;
                    
                    // Gestione "seleziona tutti"
                    document.getElementById('selectAllTasks').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.task-checkbox');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                    
                } else {
                    container.innerHTML = '<div class="alert alert-info">Nessun task trovato per questo progetto.</div>';
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                container.innerHTML = '<div class="alert alert-danger">Errore nel caricamento dei tasks.</div>';
            });
    }

    // Funzione per mostrare solo progetti creati da tasks
    window.showOnlyTasksCreated = function() {
        document.getElementById('filterCreatedFrom').value = 'tasks';
        document.getElementById('filterForm').submit();
    };
});
</script>
@endpush