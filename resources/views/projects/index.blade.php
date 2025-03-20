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

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterClient">Cliente</label>
                        <select id="filterClient" class="form-select">
                            <option value="">Tutti i clienti</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterStatus">Stato</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tutti gli stati</option>
                            <option value="pending">In attesa</option>
                            <option value="in_progress">In corso</option>
                            <option value="completed">Completato</option>
                            <option value="on_hold">In pausa</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterSearch">Ricerca</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Nome progetto...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($projects->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped" id="projectsTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cliente</th>
                                <th>Stato</th>
                                <th>Costo</th>
                                <th>Progresso</th>
                                <th>Data Inizio</th>
                                <th>Data Fine</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                <tr data-client="{{ $project->client_id }}" data-status="{{ $project->status }}">
                                    <td>{{ $project->name }}</td>
                                    <td>{{ $project->client->name }}</td>
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
                                    <td>{{ number_format($project->total_cost, 2) }} €</td>
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $project->progress_percentage }}%" aria-valuenow="{{ $project->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small>{{ $project->progress_percentage }}%</small>
                                    </td>
                                    <td>{{ $project->start_date ? $project->start_date->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $project->end_date ? $project->end_date->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto?');">
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
                    Nessun progetto disponibile. <a href="{{ route('projects.create') }}">Crea il tuo primo progetto</a>.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterClient = document.getElementById('filterClient');
        const filterStatus = document.getElementById('filterStatus');
        const filterSearch = document.getElementById('filterSearch');
        const table = document.getElementById('projectsTable');
        
        if (filterClient && filterStatus && filterSearch && table) {
            const rows = table.querySelectorAll('tbody tr');
            
            function applyFilters() {
                const clientFilter = filterClient.value;
                const statusFilter = filterStatus.value;
                const searchFilter = filterSearch.value.toLowerCase();
                
                rows.forEach(row => {
                    const clientMatch = !clientFilter || row.dataset.client === clientFilter;
                    const statusMatch = !statusFilter || row.dataset.status === statusFilter;
                    const nameCell = row.cells[0];
                    const searchMatch = !searchFilter || nameCell.textContent.toLowerCase().includes(searchFilter);
                    
                    if (clientMatch && statusMatch && searchMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            filterClient.addEventListener('change', applyFilters);
            filterStatus.addEventListener('change', applyFilters);
            filterSearch.addEventListener('input', applyFilters);
        }
    });
</script>
@endpush