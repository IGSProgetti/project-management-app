@extends('layouts.app')

@section('title', 'Gestione Attività')

@push('styles')
<style>
    .resource-avatars {
        display: flex;
        flex-wrap: nowrap;
    }
    
    .resource-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #007bff;
        color: white;
        font-weight: bold;
        margin-right: -8px;
        border: 2px solid #fff;
        font-size: 12px;
    }
    
    .resource-avatar:nth-child(2) {
        background-color: #28a745;
    }
    
    .resource-avatar:nth-child(3) {
        background-color: #dc3545;
    }
    
    .resource-more {
        background-color: #6c757d;
        cursor: pointer;
    }
</style>
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
                                        @if($activity->resource)
                                            <span class="badge bg-info">{{ $activity->resource->name }}</span>
                                        @endif
                                        
                                        @if($activity->has_multiple_resources && $activity->resources->count() > 0)
                                            <div class="resource-avatars">
                                                @php
                                                    $displayLimit = 3;
                                                    $remainingCount = $activity->resources->count() - $displayLimit;
                                                @endphp
                                                
                                                @foreach($activity->resources->take($displayLimit) as $resource)
                                                    <div class="resource-avatar" 
                                                         data-bs-toggle="tooltip" 
                                                         title="{{ $resource->name }} ({{ $resource->pivot->role ?? $resource->role }})">
                                                        {{ strtoupper(substr($resource->name, 0, 1)) }}
                                                    </div>
                                                @endforeach
                                                
                                                @if($remainingCount > 0)
                                                    <div class="resource-avatar resource-more" 
                                                         data-bs-toggle="tooltip" 
                                                         title="+ {{ $remainingCount }} altre risorse">
                                                        +{{ $remainingCount }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        @if(!$activity->resource && (!$activity->has_multiple_resources || $activity->resources->count() == 0))
                                            <span class="badge bg-warning">Non assegnato</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->status == 'pending')
                                            <span class="badge bg-warning">In attesa</span>
                                        @elseif($activity->status == 'in_progress')
                                            <span class="badge bg-primary">In corso</span>
                                        @elseif($activity->status == 'completed')
                                            <span class="badge bg-success">Completato</span>
                                        @endif
                                    </td>
                                    <td>{{ $activity->estimated_minutes }}</td>
                                    <td>{{ $activity->actual_minutes }}</td>
                                    <td>€{{ number_format($activity->estimated_cost, 2) }}</td>
                                    <td>
                                        @if($activity->hours_type == 'standard')
                                            <span class="badge bg-primary">Standard</span>
                                        @else
                                            <span class="badge bg-success">Extra</span>
                                        @endif
                                    </td>
                                    <td>{{ $activity->due_date ? $activity->due_date->format('d/m/Y') : 'N/D' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-sm btn-info" title="Visualizza">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('activities.edit', $activity->id) }}" class="btn btn-sm btn-warning" title="Modifica">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('activities.destroy', $activity->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questa attività?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Elimina">
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
                <div class="text-center">
                    <p>Nessuna attività disponibile.</p>
                    <a href="{{ route('activities.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crea la prima attività
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip per i badge delle risorse
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Elementi dei filtri
    const filterProject = document.getElementById('filterProject');
    const filterResource = document.getElementById('filterResource');
    const filterStatus = document.getElementById('filterStatus');
    const filterDueDate = document.getElementById('filterDueDate');
    const filterHoursType = document.getElementById('filterHoursType');
    const table = document.getElementById('activitiesTable');
    
    // Funzione per applicare i filtri
    function applyFilters() {
        const projectFilter = filterProject.value;
        const resourceFilter = filterResource.value;
        const statusFilter = filterStatus.value;
        const dueDateFilter = filterDueDate.value;
        const hoursTypeFilter = filterHoursType.value;
        
        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            // Salta la riga se è quella "Nessuna attività disponibile"
            if (row.children.length === 1 && row.children[0].getAttribute('colspan')) {
                return;
            }
            
            const projectMatch = !projectFilter || row.dataset.project === projectFilter;
            const resourceMatch = !resourceFilter || row.dataset.resource === resourceFilter;
            const statusMatch = !statusFilter || row.dataset.status === statusFilter;
            const hoursTypeMatch = !hoursTypeFilter || row.dataset.hoursType === hoursTypeFilter;
            
            // Logica per il filtro delle date di scadenza
            let dueDateMatch = true;
            if (dueDateFilter && row.dataset.dueDate) {
                const dueDate = new Date(row.dataset.dueDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                const weekEnd = new Date(today);
                weekEnd.setDate(weekEnd.getDate() + (7 - weekEnd.getDay()));
                
                if (dueDateFilter === 'today') {
                    dueDateMatch = dueDate.toDateString() === today.toDateString();
                } else if (dueDateFilter === 'tomorrow') {
                    dueDateMatch = dueDate.toDateString() === tomorrow.toDateString();
                } else if (dueDateFilter === 'week') {
                    dueDateMatch = dueDate >= today && dueDate <= weekEnd;
                } else if (dueDateFilter === 'overdue') {
                    dueDateMatch = dueDate < today && row.dataset.status !== 'completed';
                }
            } else if (dueDateFilter) {
                // Se il filtro data è selezionato ma l'attività non ha data di scadenza
                dueDateMatch = false;
            }
            
            // Mostra/nascondi la riga
            if (projectMatch && resourceMatch && statusMatch && dueDateMatch && hoursTypeMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Rimuovi eventuali righe di messaggio precedenti
        const existingEmptyRow = table.querySelector('tbody tr[data-empty-row]');
        if (existingEmptyRow) {
            existingEmptyRow.remove();
        }
        
        // Mostra un messaggio se non ci sono risultati
        if (visibleCount === 0) {
            const tbody = table.querySelector('tbody');
            const emptyRow = document.createElement('tr');
            emptyRow.setAttribute('data-empty-row', 'true');
            emptyRow.innerHTML = '<td colspan="11" class="text-center text-muted">Nessuna attività corrisponde ai filtri selezionati</td>';
            tbody.appendChild(emptyRow);
        }
        
        // Aggiorna il contatore nel titolo della card (opzionale)
        const cardHeader = document.querySelector('.card .card-body');
        if (cardHeader) {
            const existingCounter = cardHeader.querySelector('.results-counter');
            if (existingCounter) {
                existingCounter.remove();
            }
            
            if (visibleCount > 0) {
                const counter = document.createElement('div');
                counter.className = 'results-counter mb-3 text-muted';
                counter.innerHTML = `<small><i class="fas fa-filter"></i> Mostrando ${visibleCount} attività</small>`;
                cardHeader.insertBefore(counter, cardHeader.firstChild);
            }
        }
    }
    
    // Aggiungi event listeners ai filtri
    if (filterProject) filterProject.addEventListener('change', applyFilters);
    if (filterResource) filterResource.addEventListener('change', applyFilters);
    if (filterStatus) filterStatus.addEventListener('change', applyFilters);
    if (filterDueDate) filterDueDate.addEventListener('change', applyFilters);
    if (filterHoursType) filterHoursType.addEventListener('change', applyFilters);
    
    // Applica i filtri inizialmente (nel caso ci siano filtri preimpostati)
    applyFilters();
});
</script>
@endpush