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
                <div class="col-md-3">
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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterStatus">Stato</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tutti gli stati</option>
                            <option value="pending">In attesa</option>
                            <option value="in_progress">In corso</option>
                            <option value="completed">Completata</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterHoursType">Tipo di Ore</label>
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
                                >
                                    <td>{{ $activity->name }}</td>
                                    <td>{{ $activity->project->name }}</td>
                                    <td>{{ $activity->area ? $activity->area->name : '-' }}</td>
                                    <td>
                                        @if($activity->has_multiple_resources && $activity->resources->count() > 0)
                                            <div class="resource-avatars">
                                                @foreach($activity->resources as $index => $resource)
                                                    @if($index < 3)
                                                        <span class="resource-avatar" 
                                                              data-bs-toggle="tooltip" 
                                                              title="{{ $resource->name }} ({{ $resource->role }})">
                                                            {{ substr($resource->name, 0, 1) }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                                
                                                @if($activity->resources->count() > 3)
                                                    <span class="resource-avatar resource-more" 
                                                          data-bs-toggle="popover" 
                                                          data-bs-placement="top" 
                                                          data-bs-html="true"
                                                          data-bs-content="
                                                              @foreach($activity->resources as $index => $resource)
                                                                  @if($index >= 3)
                                                                      <div>{{ $resource->name }} ({{ $resource->role }})</div>
                                                                  @endif
                                                              @endforeach
                                                          ">
                                                        +{{ $activity->resources->count() - 3 }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            {{ $activity->resource ? $activity->resource->name : 'N/D' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->status == 'pending')
                                            <span class="badge bg-warning">In attesa</span>
                                        @elseif($activity->status == 'in_progress')
                                            <span class="badge bg-primary">In corso</span>
                                        @elseif($activity->status == 'completed')
                                            <span class="badge bg-success">Completata</span>
                                        @endif
                                    </td>
                                    <td>{{ $activity->estimated_minutes }}</td>
                                    <td>{{ $activity->actual_minutes }}</td>
                                    <td>{{ number_format($activity->estimated_cost, 2) }} €</td>
                                    <td>
                                        @if($activity->hours_type == 'standard')
                                            <span class="badge bg-primary">Standard</span>
                                        @else
                                            <span class="badge bg-warning">Extra</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('activities.edit', $activity->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('activities.destroy', $activity->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questa attività?');">
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
                    Nessuna attività disponibile. <a href="{{ route('activities.create') }}">Crea la tua prima attività</a>.
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
        
        // Inizializza i popover
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                trigger: 'click hover',
                container: 'body'
            });
        });
        
        // Filtri
        const filterProject = document.getElementById('filterProject');
        const filterResource = document.getElementById('filterResource');
        const filterStatus = document.getElementById('filterStatus');
        const filterHoursType = document.getElementById('filterHoursType');
        const table = document.getElementById('activitiesTable');
        
        if (filterProject && filterResource && filterStatus && filterHoursType && table) {
            const rows = table.querySelectorAll('tbody tr');
            
            function applyFilters() {
                const projectFilter = filterProject.value;
                const resourceFilter = filterResource.value;
                const statusFilter = filterStatus.value;
                const hoursTypeFilter = filterHoursType.value;
                
                rows.forEach(row => {
                    const projectMatch = !projectFilter || row.dataset.project === projectFilter;
                    const resourceMatch = !resourceFilter || row.dataset.resource === resourceFilter;
                    const statusMatch = !statusFilter || row.dataset.status === statusFilter;
                    const hoursTypeMatch = !hoursTypeFilter || row.dataset.hoursType === hoursTypeFilter;
                    
                    if (projectMatch && resourceMatch && statusMatch && hoursTypeMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            filterProject.addEventListener('change', applyFilters);
            filterResource.addEventListener('change', applyFilters);
            filterStatus.addEventListener('change', applyFilters);
            filterHoursType.addEventListener('change', applyFilters);
        }
    });
</script>
@endpush