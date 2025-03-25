@extends('layouts.app')

@section('title', 'Gestione Aree')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Aree</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('areas.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuova Area
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
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
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="filterSearch">Ricerca</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Nome area...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($areas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped" id="areasTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Progetto</th>
                                <th>Attività</th>
                                <th>Minuti</th>
                                <th>Progresso</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($areas as $area)
                                <tr data-project="{{ $area->project_id }}">
                                    <td>{{ $area->name }}</td>
                                    <td>{{ $area->project->name }}</td>
                                    <td>{{ $area->activities->count() }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>Stimati: {{ $area->estimated_minutes ?? 0 }}</span>
                                            <span>Effettivi: {{ $area->actual_minutes ?? 0 }}</span>
                                            <span>Rimanenti: {{ isset($area->estimated_minutes) ? max(0, $area->estimated_minutes - ($area->activities->sum('estimated_minutes') ?? 0)) : 0 }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $progressPercentage = 0;
                                            if (isset($area->estimated_minutes) && $area->estimated_minutes > 0) {
                                                $progressPercentage = min(100, round((($area->actual_minutes ?? 0) / $area->estimated_minutes) * 100));
                                            }
                                            $isOverEstimated = (isset($area->actual_minutes) && isset($area->estimated_minutes)) ? $area->actual_minutes > $area->estimated_minutes : false;
                                        @endphp
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar {{ $isOverEstimated ? 'bg-danger' : 'bg-success' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $progressPercentage }}%" 
                                                 aria-valuenow="{{ $progressPercentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <small>{{ $progressPercentage }}%</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('areas.show', $area->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('areas.edit', $area->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('areas.destroy', $area->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questa area?');">
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
                    Nessuna area disponibile. <a href="{{ route('areas.create') }}">Crea la tua prima area</a>.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filtri
        const filterProject = document.getElementById('filterProject');
        const filterSearch = document.getElementById('filterSearch');
        const table = document.getElementById('areasTable');
        
        if (filterProject && filterSearch && table) {
            const rows = table.querySelectorAll('tbody tr');
            
            function applyFilters() {
                const projectFilter = filterProject.value;
                const searchFilter = filterSearch.value.toLowerCase();
                
                rows.forEach(row => {
                    const projectMatch = !projectFilter || row.dataset.project === projectFilter;
                    const nameCell = row.cells[0];
                    const searchMatch = !searchFilter || nameCell.textContent.toLowerCase().includes(searchFilter);
                    
                    if (projectMatch && searchMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            filterProject.addEventListener('change', applyFilters);
            filterSearch.addEventListener('input', applyFilters);
        }
    });
</script>
@endpush