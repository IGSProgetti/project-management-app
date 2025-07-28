@extends('layouts.app')

@section('title', 'Manager Dashboard')

@section('content')
<div class="manager-dashboard">
    <!-- 📊 STATISTICHE GENERALI -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <i class="fas fa-building fa-2x text-primary mb-2"></i>
                    <h4>{{ $stats['clients'] }}</h4>
                    <small>Clienti</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <i class="fas fa-project-diagram fa-2x text-info mb-2"></i>
                    <h4>{{ $stats['projects'] }}</h4>
                    <small>Progetti</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-success mb-2"></i>
                    <h4>{{ $stats['resources'] }}</h4>
                    <small>Risorse</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                    <h4>{{ $stats['activities'] }}</h4>
                    <small>Attività</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <i class="fas fa-clipboard-list fa-2x text-danger mb-2"></i>
                    <h4>{{ $stats['tasks'] }}</h4>
                    <small>Task</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card text-center">
                <div class="card-body">
                    <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                    <h4>{{ number_format($financialData['total_balance'], 0) }}€</h4>
                    <small>Tesoretto</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 💰 RIEPILOGO FINANZIARIO -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-primary">Budget Totale</h6>
                    <h4>{{ number_format($financialData['total_budget'], 2) }}€</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-info">Costo Stimato</h6>
                    <h4>{{ number_format($financialData['total_estimated_cost'], 2) }}€</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-warning">Costo Effettivo</h6>
                    <h4>{{ number_format($financialData['total_actual_cost'], 2) }}€</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-success">Margine</h6>
                    <h4 class="{{ $financialData['total_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($financialData['total_balance'], 2) }}€
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- 📋 TABS PRINCIPALI -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="managerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="timetracking-tab" data-bs-toggle="tab" data-bs-target="#timetracking" type="button" role="tab">
                        <i class="fas fa-stopwatch"></i> Time Tracking
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab">
                        <i class="fas fa-users"></i> Risorse
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">
                        <i class="fas fa-building"></i> Clienti
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="daily-hours-tab" data-bs-toggle="tab" data-bs-target="#daily-hours" type="button" role="tab">
                        <i class="fas fa-calendar-day"></i> Ore Giornaliere
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hours-tab" data-bs-toggle="tab" data-bs-target="#hours" type="button" role="tab">
                        <i class="fas fa-clock"></i> Gestione Orario
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="managerTabsContent">
                
                <!-- ⏰ TIME TRACKING TAB -->
                <div class="tab-pane fade show active" id="timetracking" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ $timeTrackingData['stats']['estimatedMinutes'] }}</h5>
                                <small>Minuti Stimati</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ $timeTrackingData['stats']['actualMinutes'] }}</h5>
                                <small>Minuti Effettivi</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5 class="{{ $timeTrackingData['stats']['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $timeTrackingData['stats']['balance'] }}
                                </h5>
                                <small>Consuntivo</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5 class="text-success">{{ number_format($timeTrackingData['stats']['bonus'], 2) }}€</h5>
                                <small>Bonus Totale</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Ultimi Task ({{ $timeTrackingData['total_tasks'] }} totali)</h6>
                        <a href="{{ route('tasks.timetracking') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Vista Completa
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Cliente</th>
                                    <th>Progetto</th>
                                    <th>Stato</th>
                                    <th>Min. Stimati</th>
                                    <th>Min. Effettivi</th>
                                    <th>Consuntivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeTrackingData['tasks'] as $task)
                                <tr>
                                    <td>{{ $task->name }}</td>
                                    <td>{{ $task->activity->project->client->name ?? '-' }}</td>
                                    <td>{{ $task->activity->project->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in_progress' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $task->estimated_minutes }}</td>
                                    <td>{{ $task->actual_minutes }}</td>
                                    <td class="{{ ($task->estimated_minutes - $task->actual_minutes) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $task->estimated_minutes - $task->actual_minutes }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 👥 RESOURCES TAB -->
                <div class="tab-pane fade" id="resources" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ $resourcesData['resources']->count() }}</h5>
                                <small>Risorse Attive</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ number_format($resourcesData['total_cost'], 2) }}€</h5>
                                <small>Costo Totale/Ora</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ number_format($resourcesData['total_selling'], 2) }}€</h5>
                                <small>Vendita Totale/Ora</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Risorse</h6>
                        <a href="{{ route('resources.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Gestione Completa
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Ruolo</th>
                                    <th>Attività</th>
                                    <th>Task</th>
                                    <th>Costo/Ora</th>
                                    <th>Vendita/Ora</th>
                                    <th>Margine</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resourcesData['resources'] as $resource)
                                <tr>
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ $resource->role }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $resource->activities_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ $resource->tasks_count }}</span>
                                    </td>
                                    <td>{{ number_format($resource->cost_per_hour ?? 0, 2) }}€</td>
                                    <td>{{ number_format($resource->selling_price ?? 0, 2) }}€</td>
                                    <td class="{{ ($resource->selling_price - $resource->cost_per_hour) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format(($resource->selling_price ?? 0) - ($resource->cost_per_hour ?? 0), 2) }}€
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 🏢 CLIENTS TAB -->
                <div class="tab-pane fade" id="clients" role="tabpanel">
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Clienti ({{ $clientsData['clients']->count() }} totali, {{ $clientsData['active_clients'] }} attivi)</h6>
                        <a href="{{ route('clients.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Gestione Completa
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Progetti</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientsData['clients']->take(10) as $client)
                                <tr>
                                    <td>
                                        <strong>{{ $client->name }}</strong><br>
                                        <small class="text-muted">{{ $client->company ?? '-' }}</small>
                                    </td>
                                    <td>{{ $client->email }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $client->projects_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $client->projects_count > 0 ? 'success' : 'secondary' }}">
                                            {{ $client->projects_count > 0 ? 'Attivo' : 'Inattivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 📅 DAILY HOURS TAB -->
                <div class="tab-pane fade" id="daily-hours" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ $dailyHoursData['today_hours'] }}</h5>
                                <small>Ore Oggi</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ $dailyHoursData['week_hours'] }}</h5>
                                <small>Ore Questa Settimana</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Ultime Registrazioni</h6>
                        <a href="{{ route('daily-hours.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Vista Completa
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Risorsa</th>
                                    <th>Ore</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyHoursData['recent_entries'] as $entry)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</td>
                                    <td>{{ $entry->resource_name }}</td>
                                    <td><span class="badge bg-primary">{{ $entry->hours }}h</span></td>
                                    <td>{{ $entry->notes ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 🕐 HOURS TAB - DATI REALI -->
                <div class="tab-pane fade" id="hours" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ number_format($hoursData['total_capacity_all'], 1) }}</h5>
                                <small>Capacità Totale (ore)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ number_format($hoursData['total_assigned_all'], 1) }}</h5>
                                <small>Ore Assegnate</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5 class="text-success">{{ number_format($hoursData['total_available_all'], 1) }}</h5>
                                <small>Ore Disponibili</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h5>{{ $hoursData['total_capacity_all'] > 0 ? number_format(($hoursData['total_assigned_all'] / $hoursData['total_capacity_all']) * 100, 1) : 0 }}%</h5>
                                <small>Utilizzo Medio</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h6>Riepilogo Capacità e Ore per Risorsa</h6>
                        <a href="{{ route('hours.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt"></i> Gestione Completa
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Risorsa</th>
                                    <th>Ruolo</th>
                                    <th>Capacità Annuale</th>
                                    <th>Ore Assegnate</th>
                                    <th>Ore Disponibili</th>
                                    <th>Ore Mese</th>
                                    <th>Utilizzo %</th>
                                    <th>Tariffa/h</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hoursData['resources_hours'] as $resourceHour)
                                <tr>
                                    <td>
                                        <strong>{{ $resourceHour['resource']->name }}</strong>
                                        <br><small class="text-muted">{{ $resourceHour['standard_capacity'] }}h std @if($resourceHour['extra_capacity'] > 0)+ {{ $resourceHour['extra_capacity'] }}h extra @endif</small>
                                    </td>
                                    <td>{{ $resourceHour['resource']->role }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ number_format($resourceHour['total_capacity_hours'], 0) }}h</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ number_format($resourceHour['total_assigned_hours'], 1) }}h</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $resourceHour['available_hours'] > 0 ? 'success' : 'danger' }}">
                                            {{ number_format($resourceHour['available_hours'], 1) }}h
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ number_format($resourceHour['monthly_hours'], 1) }}h</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">{{ $resourceHour['utilization_percentage'] }}%</span>
                                            <div class="progress" style="width: 60px; height: 8px;">
                                                <div class="progress-bar bg-{{ $resourceHour['utilization_percentage'] > 90 ? 'danger' : ($resourceHour['utilization_percentage'] > 70 ? 'warning' : 'success') }}" 
                                                     style="width: {{ min(100, $resourceHour['utilization_percentage']) }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($resourceHour['resource']->selling_price ?: 0, 0) }}€</strong>
                                        @if($resourceHour['resource']->extra_selling_price)
                                            <br><small class="text-muted">{{ number_format($resourceHour['resource']->extra_selling_price, 0) }}€ extra</small>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.stats-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.manager-dashboard .nav-tabs .nav-link {
    color: #666;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 10px 20px;
}

.manager-dashboard .nav-tabs .nav-link:hover,
.manager-dashboard .nav-tabs .nav-link.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: none;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.9rem;
}

.bg-light.rounded {
    border: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .manager-dashboard .nav-tabs {
        flex-wrap: wrap;
    }
    
    .manager-dashboard .nav-tabs .nav-link {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dei dati ogni 5 minuti
    setInterval(function() {
        location.reload();
    }, 300000); // 5 minuti

    // Gestione click rapido sui tab
    const tabs = document.querySelectorAll('#managerTabs button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Salva il tab attivo nel localStorage
            localStorage.setItem('activeManagerTab', this.id);
        });
    });

    // Ripristina l'ultimo tab attivo
    const activeTab = localStorage.getItem('activeManagerTab');
    if (activeTab) {
        const tabButton = document.getElementById(activeTab);
        if (tabButton) {
            new bootstrap.Tab(tabButton).show();
        }
    }

    // Tooltip per le statistiche
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
@endsection