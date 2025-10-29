@extends('layouts.app')

@section('title', 'Gestione Task')

@push('styles')
<link href="{{ asset('css/tasks-mobile.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Task</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Task
            </a>
            <a href="{{ route('tasks.timetracking') }}" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> Gestione Tempi
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterActivity">Attività</label>
                        <select id="filterActivity" class="form-select">
                            <option value="">Tutte le attività</option>
                            @foreach($activities ?? [] as $activity)
                                <option value="{{ $activity->id }}">{{ $activity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterStatus">Stato</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tutti gli stati</option>
                            <option value="pending">In attesa</option>
                            <option value="in_progress">In corso</option>
                            <option value="completed">Completato</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterDueDate">Scadenza</label>
                        <select id="filterDueDate" class="form-select">
                            <option value="">Tutte le scadenze</option>
                            <option value="today">Oggi</option>
                            <option value="tomorrow">Domani</option>
                            <option value="week">Questa settimana</option>
                            <option value="overdue">Scaduti</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterResource">Risorsa</label>
                        <select id="filterResource" class="form-select">
                            <option value="">Tutte le risorse</option>
                            @foreach($resources ?? [] as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if(isset($tasks) && $tasks->count() > 0)
                <!-- VISTA TABELLA (Desktop) -->
                <div class="table-responsive">
                    <table class="table table-striped" id="tasksTable">
                        <thead>
                            <tr>
                                <th>Risorsa</th>
                                <th>Nome</th>
                                <th>Cliente</th>
                                <th>Attività</th>
                                <th>Progetto</th>
                                <th>Stato</th>
                                <th>Min. Stimati</th>
                                <th>Min. Effettivi</th>
                                <th>Progresso</th>
                                <th>Scadenza</th>
                                <th>Timer</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr 
                                    data-activity="{{ $task->activity_id }}" 
                                    data-status="{{ $task->status }}"
                                    data-task-id="{{ $task->id }}"
                                    data-resource="{{ $task->resource_id ?? '' }}"
                                >
                                    <td>
                                        @if($task->resource)
                                            <span class="badge bg-info" data-bs-toggle="tooltip" title="{{ $task->resource->role }}">
                                                {{ $task->resource->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Non assegnato</span>
                                        @endif
                                    </td>
                                    <td>{{ $task->name }}</td>
                                    <td>{{ $task->activity->project->client->name ?? 'N/D' }}</td>
                                    <td>{{ $task->activity->name ?? 'N/D' }}</td>
                                    <td>{{ $task->activity->project->name ?? 'N/D' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in_progress' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $task->estimated_minutes }}</td>
                                    <td>{{ $task->actual_minutes }}</td>
                                    <td>
                                        @php
                                            $percentage = $task->estimated_minutes > 0 ? round(($task->actual_minutes / $task->estimated_minutes) * 100) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $percentage > 100 ? 'danger' : ($percentage > 80 ? 'warning' : 'success') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($percentage, 100) }}%"
                                                 aria-valuenow="{{ $percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $percentage }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $task->due_date ? $task->due_date->format('d/m/Y') : 'N/D' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-success timer-btn" data-task-id="{{ $task->id }}">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">
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
                <div class="tasks-mobile-view">
                    <div class="tasks-mobile-container">
                        @foreach($tasks as $task)
                            @php
                                $percentage = $task->estimated_minutes > 0 ? round(($task->actual_minutes / $task->estimated_minutes) * 100) : 0;
                                $progressClass = $percentage > 80 ? ($percentage > 100 ? 'low' : 'medium') : 'high';
                                
                                // Calcola stato scadenza
                                $dueDateClass = '';
                                if ($task->due_date) {
                                    $today = \Carbon\Carbon::today();
                                    $dueDate = \Carbon\Carbon::parse($task->due_date);
                                    if ($dueDate->lt($today) && $task->status != 'completed') {
                                        $dueDateClass = 'overdue';
                                    } elseif ($dueDate->isSameDay($today)) {
                                        $dueDateClass = 'today';
                                    } else {
                                        $dueDateClass = 'upcoming';
                                    }
                                }
                            @endphp
                            
                            <div class="task-card" 
                                 data-activity="{{ $task->activity_id }}" 
                                 data-status="{{ $task->status }}"
                                 data-task-id="{{ $task->id }}"
                                 data-resource="{{ $task->resource_id ?? '' }}">
                                
                                <!-- Header -->
                                <div class="task-card-header">
                                    <h3 class="task-card-title">{{ $task->name }}</h3>
                                    <span class="task-status-badge status-{{ $task->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>

                                <!-- Informazioni Principali -->
                                <div class="task-card-info">
                                    <div class="task-info-row">
                                        <i class="fas fa-user task-info-icon"></i>
                                        <span class="task-info-label">Risorsa:</span>
                                        @if($task->resource)
                                            <span class="task-resource-badge">
                                                <i class="fas fa-user-circle"></i>
                                                {{ $task->resource->name }}
                                            </span>
                                        @else
                                            <span class="task-resource-badge unassigned">
                                                Non assegnato
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="task-info-row">
                                        <i class="fas fa-building task-info-icon"></i>
                                        <span class="task-info-label">Cliente:</span>
                                        <span class="task-info-value">{{ $task->activity->project->client->name ?? 'N/D' }}</span>
                                    </div>
                                    
                                    <div class="task-info-row">
                                        <i class="fas fa-folder task-info-icon"></i>
                                        <span class="task-info-label">Progetto:</span>
                                        <span class="task-info-value">{{ $task->activity->project->name ?? 'N/D' }}</span>
                                    </div>
                                    
                                    <div class="task-info-row">
                                        <i class="fas fa-tasks task-info-icon"></i>
                                        <span class="task-info-label">Attività:</span>
                                        <span class="task-info-value">{{ $task->activity->name ?? 'N/D' }}</span>
                                    </div>
                                    
                                    @if($task->due_date)
                                    <div class="task-info-row">
                                        <i class="fas fa-calendar task-info-icon"></i>
                                        <span class="task-info-label">Scadenza:</span>
                                        <span class="task-due-date {{ $dueDateClass }}">
                                            <i class="fas fa-clock"></i>
                                            {{ $task->due_date->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    @endif
                                </div>

                                <!-- Tempo e Progresso -->
                                <div class="task-card-time">
                                    <div class="task-time-row">
                                        <span class="task-time-label">⏱️ Tempo Stimato:</span>
                                        <span class="task-time-value">{{ $task->estimated_minutes }} min</span>
                                    </div>
                                    <div class="task-time-row">
                                        <span class="task-time-label">✅ Tempo Effettivo:</span>
                                        <span class="task-time-value">{{ $task->actual_minutes }} min</span>
                                    </div>
                                    <div class="task-time-row">
                                        <span class="task-time-label">💰 Tesoretto:</span>
                                        <span class="task-time-value" style="color: {{ ($task->estimated_minutes - $task->actual_minutes) >= 0 ? '#4CAF50' : '#f44336' }}">
                                            {{ $task->estimated_minutes - $task->actual_minutes }} min
                                        </span>
                                    </div>
                                    
                                    <!-- Barra Progresso -->
                                    <div class="task-progress-bar">
                                        <div class="task-progress-fill {{ $progressClass }}" style="width: {{ min($percentage, 100) }}%"></div>
                                    </div>
                                    <div class="task-progress-text">{{ $percentage }}% completato</div>
                                </div>

                                <!-- Pulsanti Azione -->
                                <div class="task-card-actions">
                                    <button class="task-action-btn btn-timer timer-btn" data-task-id="{{ $task->id }}">
                                        <i class="fas fa-play"></i> Timer
                                    </button>
                                    <a href="{{ route('tasks.edit', $task->id) }}" class="task-action-btn btn-edit">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="display: inline; flex: 1;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="task-action-btn btn-delete" style="width: 100%;" onclick="return confirm('Sei sicuro di voler eliminare questo task?')">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="no-tasks-message">
                    <i class="fas fa-inbox"></i>
                    <p>Nessun task disponibile</p>
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
    
    // Filtri
    const filterActivity = document.getElementById('filterActivity');
    const filterStatus = document.getElementById('filterStatus');
    const filterDueDate = document.getElementById('filterDueDate');
    const filterResource = document.getElementById('filterResource');
    const table = document.getElementById('tasksTable');
    
    function applyFilters() {
        const activityFilter = filterActivity.value;
        const statusFilter = filterStatus.value;
        const dueDateFilter = filterDueDate.value;
        const resourceFilter = filterResource.value;
        
        // Filtra righe tabella (desktop)
        const rows = table ? table.querySelectorAll('tbody tr') : [];
        
        // Filtra card (mobile)
        const cards = document.querySelectorAll('.task-card');
        
        // Funzione comune per controllare i filtri
        function shouldShow(element) {
            const activityMatch = !activityFilter || element.dataset.activity === activityFilter;
            const statusMatch = !statusFilter || element.dataset.status === statusFilter;
            const resourceMatch = !resourceFilter || element.dataset.resource === resourceFilter;
            
            // Logica per il filtro delle date di scadenza
            let dueDateMatch = true;
            if (dueDateFilter) {
                const dueDateCell = element.querySelector('.task-due-date') || element.querySelector('td:nth-child(10)');
                if (dueDateCell) {
                    const dueDateText = dueDateCell.textContent.trim();
                    if (dueDateText !== 'N/D') {
                        const dueDate = parseDueDate(dueDateText);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        const tomorrow = new Date(today);
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        
                        const weekEnd = new Date(today);
                        weekEnd.setDate(weekEnd.getDate() + 7);
                        
                        switch(dueDateFilter) {
                            case 'today':
                                dueDateMatch = dueDate.toDateString() === today.toDateString();
                                break;
                            case 'tomorrow':
                                dueDateMatch = dueDate.toDateString() === tomorrow.toDateString();
                                break;
                            case 'week':
                                dueDateMatch = dueDate >= today && dueDate <= weekEnd;
                                break;
                            case 'overdue':
                                dueDateMatch = dueDate < today;
                                break;
                        }
                    } else {
                        dueDateMatch = false;
                    }
                }
            }
            
            return activityMatch && statusMatch && resourceMatch && dueDateMatch;
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
    
    function parseDueDate(dateString) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
            return new Date(parts[2], parts[1] - 1, parts[0]);
        }
        return new Date();
    }
    
    // Event listeners per i filtri
    if (filterActivity) filterActivity.addEventListener('change', applyFilters);
    if (filterStatus) filterStatus.addEventListener('change', applyFilters);
    if (filterDueDate) filterDueDate.addEventListener('change', applyFilters);
    if (filterResource) filterResource.addEventListener('change', applyFilters);
    
    // Gestione Timer (esempio base)
    const timerButtons = document.querySelectorAll('.timer-btn');
    timerButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            alert('Timer per task ID: ' + taskId + '\n(Funzionalità da implementare)');
            // Qui puoi aggiungere la logica per il timer
        });
    });
});
</script>
@endpush