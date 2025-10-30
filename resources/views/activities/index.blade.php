@extends('layouts.app')

@section('title', 'Gestione Attività')

@push('styles')
<link href="{{ asset('css/activities-mobile.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Attività</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('activities.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuova Attività
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="filterProject">Progetto</label>
                        <select id="filterProject" class="form-select">
                            <option value="">Tutti i progetti</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="filterResource">Risorsa</label>
                        <select id="filterResource" class="form-select">
                            <option value="">Tutte le risorse</option>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="filterDueDate">Scadenza</label>
                        <select id="filterDueDate" class="form-select">
                            <option value="">Tutte le scadenze</option>
                            <option value="today">Oggi</option>
                            <option value="tomorrow">Domani</option>
                            <option value="week">Questa settimana</option>
                            <option value="overdue">Scadute</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="filterHoursType">Tipo Ore</label>
                        <select id="filterHoursType" class="form-select">
                            <option value="">Tutti i tipi</option>
                            <option value="standard">Ore Standard</option>
                            <option value="extra">Ore Extra</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($activities->count() > 0)
                <!-- VISTA TABELLA (Desktop) -->
                <div class="table-responsive">
                    <table class="table table-striped" id="activitiesTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Progetto</th>
                                <th>Area</th>
                                <th>Risorse</th>
                                <th>Stato</th>
                                <th>Minuti Stimati</th>
                                <th>Minuti Effettivi</th>
                                <th>Costo Stimato</th>
                                <th>Tipo Ore</th>
                                <th>Scadenza</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr 
                                    data-project="{{ $activity->project_id }}" 
                                    data-resource="{{ $activity->resource_id }}" 
                                    data-status="{{ $activity->status }}"
                                    data-hours-type="{{ $activity->hours_type }}"
                                    data-due-date="{{ $activity->due_date ? $activity->due_date->format('Y-m-d') : '' }}"
                                >
                                    <td>{{ $activity->name }}</td>
                                    <td>{{ $activity->project->name }}</td>
                                    <td>{{ $activity->area ? $activity->area->name : 'N/D' }}</td>
                                    <td>
                                        @if($activity->has_multiple_resources && $activity->resources->count() > 0)
                                            @foreach($activity->resources as $resource)
                                                <span class="badge bg-info">{{ $resource->name }}</span>
                                            @endforeach
                                        @elseif($activity->resource)
                                            <span class="badge bg-info">{{ $activity->resource->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">N/D</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $activity->status == 'completed' ? 'success' : ($activity->status == 'in_progress' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst(str_replace('_', ' ', $activity->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $activity->estimated_minutes }}</td>
                                    <td>{{ $activity->actual_minutes }}</td>
                                    <td>€ {{ number_format($activity->estimated_cost, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $activity->hours_type == 'standard' ? 'primary' : 'warning' }}">
                                            {{ ucfirst($activity->hours_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $activity->due_date ? $activity->due_date->format('d/m/Y') : 'N/D' }}</td>
                                    <td>
                                        <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('activities.edit', $activity->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('activities.destroy', $activity->id) }}" method="POST" style="display: inline;">
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
                <div class="activities-mobile-view">
                    <div class="activities-mobile-container">
                        @foreach($activities as $activity)
                            @php
                                $percentage = $activity->estimated_minutes > 0 ? round(($activity->actual_minutes / $activity->estimated_minutes) * 100) : 0;
                                $progressClass = $percentage > 80 ? ($percentage > 100 ? 'low' : 'medium') : 'high';
                                
                                // Calcola stato scadenza
                                $dueDateClass = '';
                                if ($activity->due_date) {
                                    $today = \Carbon\Carbon::today();
                                    $dueDate = \Carbon\Carbon::parse($activity->due_date);
                                    if ($dueDate->lt($today) && $activity->status != 'completed') {
                                        $dueDateClass = 'overdue';
                                    } elseif ($dueDate->isSameDay($today)) {
                                        $dueDateClass = 'today';
                                    } else {
                                        $dueDateClass = 'upcoming';
                                    }
                                }
                            @endphp
                            
                            <div class="activity-card" 
                                 data-project="{{ $activity->project_id }}" 
                                 data-resource="{{ $activity->resource_id }}" 
                                 data-status="{{ $activity->status }}"
                                 data-hours-type="{{ $activity->hours_type }}"
                                 data-due-date="{{ $activity->due_date ? $activity->due_date->format('Y-m-d') : '' }}">
                                
                                <!-- Header -->
                                <div class="activity-card-header">
                                    <h3 class="activity-card-title">{{ $activity->name }}</h3>
                                    <span class="activity-status-badge status-{{ $activity->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $activity->status)) }}
                                    </span>
                                </div>

                                <!-- Informazioni Principali -->
<div class="activity-card-info">
    <!-- CLIENTE -->
    @if($activity->project)
        @if(isset($activity->project->customer))
        <div class="activity-info-row">
            <i class="fas fa-building activity-info-icon"></i>
            <span class="activity-info-label">Cliente:</span>
            <span class="activity-info-value">{{ $activity->project->customer->name }}</span>
        </div>
        @elseif(isset($activity->project->client))
        <div class="activity-info-row">
            <i class="fas fa-building activity-info-icon"></i>
            <span class="activity-info-label">Cliente:</span>
            <span class="activity-info-value">{{ $activity->project->client->name }}</span>
        </div>
        @elseif(isset($activity->project->customer_name))
        <div class="activity-info-row">
            <i class="fas fa-building activity-info-icon"></i>
            <span class="activity-info-label">Cliente:</span>
            <span class="activity-info-value">{{ $activity->project->customer_name }}</span>
        </div>
        @endif
    @endif
    
    <!-- PROGETTO -->
    <div class="activity-info-row">
        <i class="fas fa-project-diagram activity-info-icon"></i>
        <span class="activity-info-label">Progetto:</span>
        <span class="activity-info-value">{{ $activity->project->name }}</span>
    </div>
    
    <!-- AREA -->
    @if($activity->area)
    <div class="activity-info-row">
        <i class="fas fa-layer-group activity-info-icon"></i>
        <span class="activity-info-label">Area:</span>
        <span class="activity-area-badge">
            <i class="fas fa-folder"></i>
            {{ $activity->area->name }}
        </span>
    </div>
    @endif
    
    <!-- TIPO ORE -->
    <div class="activity-info-row">
        <i class="fas fa-clock activity-info-icon"></i>
        <span class="activity-info-label">Tipo Ore:</span>
        <span class="hours-type-badge hours-type-{{ $activity->hours_type }}">
            {{ $activity->hours_type == 'standard' ? '⏱️ Standard' : '⚡ Extra' }}
        </span>
    </div>

                                <!-- Sezione Risorse -->
                                @if($activity->has_multiple_resources && $activity->resources->count() > 0)
                                <div class="activity-resources-section">
                                    <div class="activity-resources-title">
                                        <i class="fas fa-users"></i> Risorse Assegnate
                                    </div>
                                    @foreach($activity->resources as $resource)
                                        @php
                                            $resourceMinutes = $resource->pivot->estimated_minutes;
                                            $resourceHours = round($resourceMinutes / 60, 1);
                                        @endphp
                                        <div class="activity-resource-item">
                                            <div class="activity-resource-name">
                                                <div class="activity-resource-avatar">
                                                    {{ strtoupper(substr($resource->name, 0, 1)) }}
                                                </div>
                                                {{ $resource->name }}
                                            </div>
                                            <div class="activity-resource-hours">
                                                {{ $resourceMinutes }} min ({{ $resourceHours }}h)
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @elseif($activity->resource)
                                <div class="activity-resources-section">
                                    <div class="activity-resources-title">
                                        <i class="fas fa-user"></i> Risorsa Principale
                                    </div>
                                    <div class="activity-resource-item">
                                        <div class="activity-resource-name">
                                            <div class="activity-resource-avatar">
                                                {{ strtoupper(substr($activity->resource->name, 0, 1)) }}
                                            </div>
                                            {{ $activity->resource->name }}
                                        </div>
                                        <div class="activity-resource-hours">
                                            {{ $activity->estimated_minutes }} min
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Metriche Tempo e Costi -->
                                <div class="activity-card-metrics">
                                    <div class="activity-metrics-row">
                                        <span class="activity-metrics-label">⏱️ Tempo Stimato:</span>
                                        <span class="activity-metrics-value">{{ $activity->estimated_minutes }} min</span>
                                    </div>
                                    <div class="activity-metrics-row">
                                        <span class="activity-metrics-label">✅ Tempo Effettivo:</span>
                                        <span class="activity-metrics-value">{{ $activity->actual_minutes }} min</span>
                                    </div>
                                    <div class="activity-metrics-row">
                                        <span class="activity-metrics-label">💰 Differenza:</span>
                                        <span class="activity-metrics-value {{ ($activity->estimated_minutes - $activity->actual_minutes) >= 0 ? 'positive' : 'negative' }}">
                                            {{ $activity->estimated_minutes - $activity->actual_minutes }} min
                                        </span>
                                    </div>
                                </div>

                                <!-- Costo Stimato Highlight -->
                                <div class="activity-cost-highlight">
                                    <div class="activity-cost-label">Costo Stimato</div>
                                    <div class="activity-cost-value">€ {{ number_format($activity->estimated_cost, 2) }}</div>
                                </div>

                                <!-- Barra Progresso -->
                                <div class="activity-progress-section">
                                    <div class="activity-progress-header">
                                        <span class="activity-progress-label">Progresso</span>
                                        <span class="activity-progress-percentage">{{ $percentage }}%</span>
                                    </div>
                                    <div class="activity-progress-bar">
                                        <div class="activity-progress-fill {{ $progressClass }}" style="width: {{ min($percentage, 100) }}%"></div>
                                    </div>
                                </div>

                                <!-- Pulsanti Azione -->
                                <div class="activity-card-actions">
                                    <a href="{{ route('activities.show', $activity->id) }}" class="activity-action-btn btn-view">
                                        <i class="fas fa-eye"></i> Visualizza
                                    </a>
                                    <a href="{{ route('activities.edit', $activity->id) }}" class="activity-action-btn btn-edit">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <form action="{{ route('activities.destroy', $activity->id) }}" method="POST" style="display: inline; flex: 1;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="activity-action-btn btn-delete" style="width: 100%;" onclick="return confirm('Sei sicuro di voler eliminare questa attività?')">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="no-activities-message">
                    <i class="fas fa-clipboard-list"></i>
                    <p>Nessuna attività disponibile</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterProject = document.getElementById('filterProject');
    const filterResource = document.getElementById('filterResource');
    const filterStatus = document.getElementById('filterStatus');
    const filterDueDate = document.getElementById('filterDueDate');
    const filterHoursType = document.getElementById('filterHoursType');
    const table = document.getElementById('activitiesTable');
    
    function applyFilters() {
        const projectFilter = filterProject.value;
        const resourceFilter = filterResource.value;
        const statusFilter = filterStatus.value;
        const dueDateFilter = filterDueDate.value;
        const hoursTypeFilter = filterHoursType.value;
        
        // Filtra righe tabella (desktop)
        const rows = table ? table.querySelectorAll('tbody tr') : [];
        
        // Filtra card (mobile)
        const cards = document.querySelectorAll('.activity-card');
        
        function shouldShow(element) {
            const projectMatch = !projectFilter || element.dataset.project === projectFilter;
            const resourceMatch = !resourceFilter || element.dataset.resource === resourceFilter;
            const statusMatch = !statusFilter || element.dataset.status === statusFilter;
            const hoursTypeMatch = !hoursTypeFilter || element.dataset.hoursType === hoursTypeFilter;
            
            // Logica per il filtro delle date di scadenza
            let dueDateMatch = true;
            if (dueDateFilter && element.dataset.dueDate) {
                const dueDate = new Date(element.dataset.dueDate);
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
            } else if (dueDateFilter) {
                dueDateMatch = false;
            }
            
            return projectMatch && resourceMatch && statusMatch && hoursTypeMatch && dueDateMatch;
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
    if (filterProject) filterProject.addEventListener('change', applyFilters);
    if (filterResource) filterResource.addEventListener('change', applyFilters);
    if (filterStatus) filterStatus.addEventListener('change', applyFilters);
    if (filterDueDate) filterDueDate.addEventListener('change', applyFilters);
    if (filterHoursType) filterHoursType.addEventListener('change', applyFilters);
});
</script>
@endpush