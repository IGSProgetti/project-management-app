@extends('layouts.app')

@section('title', 'Gestione Clienti')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Clienti</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Cliente
            </a>
        </div>
    </div>

    <!-- Filtri -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterCreatedFrom">Origine</label>
                        <select id="filterCreatedFrom" class="form-select">
                            <option value="">Tutti i clienti</option>
                            <option value="normal">Creati normalmente</option>
                            <option value="tasks">Creati da Tasks</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterBudget">Budget</label>
                        <select id="filterBudget" class="form-select">
                            <option value="">Tutti i budget</option>
                            <option value="low">Meno di 5.000€</option>
                            <option value="medium">5.000€ - 20.000€</option>
                            <option value="high">Oltre 20.000€</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="searchClient">Cerca</label>
                        <input type="text" id="searchClient" class="form-control" placeholder="Nome cliente...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Elenco Clienti</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="clientsTable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Budget</th>
                            <th>Budget Utilizzato</th>
                            <th>Progetti</th>
                            <th>Origine</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                        <tr data-created-from="{{ $client->created_from_tasks ? 'tasks' : 'normal' }}" 
                            data-budget="{{ $client->budget }}" 
                            data-name="{{ strtolower($client->name) }}">
                            <td>
                                <strong>{{ $client->name }}</strong>
                                @if($client->created_from_tasks)
                                    <br><small class="text-muted">Creato il {{ $client->tasks_created_at->format('d/m/Y H:i') }}</small>
                                @endif
                            </td>
                            <td>{{ number_format($client->budget, 2) }} €</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill me-2">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar {{ $client->budget_usage_percentage > 90 ? 'bg-danger' : ($client->budget_usage_percentage > 70 ? 'bg-warning' : 'bg-success') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $client->budget_usage_percentage }}%"></div>
                                        </div>
                                    </div>
                                    <small>{{ number_format($client->total_budget_used, 2) }} €</small>
                                </div>
                                <small class="text-muted">{{ $client->budget_usage_percentage }}% utilizzato</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $client->projects_count }}</span>
                                @if($client->projects_count > 0)
                                    <br><small class="text-muted">
                                        <a href="{{ route('projects.index') }}?client={{ $client->id }}">Vedi progetti</a>
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($client->created_from_tasks)
                                    <span class="badge bg-warning">
                                        <i class="fas fa-tasks"></i> Da Tasks
                                    </span>
                                    <br><small class="text-warning">Da consolidare</small>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-user-plus"></i> Standard
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($client->created_from_tasks)
                                    <span class="badge bg-warning">Provvisorio</span>
                                @else
                                    @if($client->budget_usage_percentage > 90)
                                        <span class="badge bg-danger">Budget Critico</span>
                                    @elseif($client->budget_usage_percentage > 70)
                                        <span class="badge bg-warning">Budget Attenzione</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('clients.show', $client->id) }}" class="btn btn-outline-info" title="Visualizza">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-outline-warning" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($client->created_from_tasks)
                                        <button type="button" class="btn btn-outline-success consolidate-btn" 
                                                data-client-id="{{ $client->id }}" 
                                                data-client-name="{{ $client->name }}"
                                                title="Consolida cliente">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    @endif
                                    @if($client->projects_count == 0)
                                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Sei sicuro di voler eliminare questo cliente?')"
                                                    title="Elimina">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<!-- Modal per consolidare cliente -->
<div class="modal fade" id="consolidateClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Consolida Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="consolidateClientForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Consolidamento Cliente</strong><br>
                        Stai per consolidare un cliente creato automaticamente dai tasks. 
                        Questo lo renderà un cliente "ufficiale" del sistema.
                    </div>
                    
                    <div class="mb-3">
                        <label for="consolidate_budget">Budget Definitivo (€)</label>
                        <input type="number" name="budget" id="consolidate_budget" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="consolidate_notes">Note</label>
                        <textarea name="notes" id="consolidate_notes" class="form-control" rows="3" 
                                  placeholder="Aggiungi informazioni dettagliate sul cliente..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Consolida Cliente
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
    // Filtri
    const filterCreatedFrom = document.getElementById('filterCreatedFrom');
    const filterBudget = document.getElementById('filterBudget');
    const searchClient = document.getElementById('searchClient');
    const tableRows = document.querySelectorAll('#clientsTable tbody tr');

    function applyFilters() {
        const createdFromFilter = filterCreatedFrom.value;
        const budgetFilter = filterBudget.value;
        const searchTerm = searchClient.value.toLowerCase();

        tableRows.forEach(row => {
            let show = true;

            // Filtro origine
            if (createdFromFilter && row.dataset.createdFrom !== createdFromFilter) {
                show = false;
            }

            // Filtro budget
            if (budgetFilter && show) {
                const budget = parseFloat(row.dataset.budget);
                switch(budgetFilter) {
                    case 'low':
                        if (budget >= 5000) show = false;
                        break;
                    case 'medium':
                        if (budget < 5000 || budget > 20000) show = false;
                        break;
                    case 'high':
                        if (budget <= 20000) show = false;
                        break;
                }
            }

            // Filtro ricerca
            if (searchTerm && show) {
                if (!row.dataset.name.includes(searchTerm)) {
                    show = false;
                }
            }

            row.style.display = show ? '' : 'none';
        });
    }

    filterCreatedFrom.addEventListener('change', applyFilters);
    filterBudget.addEventListener('change', applyFilters);
    searchClient.addEventListener('input', applyFilters);

    // Gestione consolidamento cliente
    const consolidateButtons = document.querySelectorAll('.consolidate-btn');
    const consolidateModal = new bootstrap.Modal(document.getElementById('consolidateClientModal'));
    const consolidateForm = document.getElementById('consolidateClientForm');

    consolidateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const clientId = this.dataset.clientId;
            const clientName = this.dataset.clientName;
            
            document.querySelector('#consolidateClientModal .modal-title').textContent = `Consolida Cliente: ${clientName}`;
            consolidateForm.action = `/clients/${clientId}/consolidate`;
            
            consolidateModal.show();
        });
    });
});
</script>
@endpush