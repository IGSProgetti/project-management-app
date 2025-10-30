@extends('layouts.app')

@section('title', 'Gestione Clienti')

@push('styles')
<link href="{{ asset('css/clients-mobile.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header con titolo e pulsanti -->
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

    <!-- Card filtri (nascosta su mobile) -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <!-- Filtri qui (da implementare) -->
            <form method="GET" action="{{ route('clients.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Cerca cliente..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Cerca
                        </button>
                        <a href="{{ route('clients.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DESKTOP VIEW: Tabella (nascosta su mobile) -->
    <div class="card table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nome Cliente</th>
                    <th>Data Creazione</th>
                    <th>Budget Totale</th>
                    <th>Budget Utilizzato</th>
                    <th>Percentuale</th>
                    <th>N° Progetti</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    @php
                        $budgetUsed = $client->projects->sum('budget_used') ?? 0;
                        $budgetTotal = $client->budget_total ?? 0;
                        $budgetPercentage = $budgetTotal > 0 ? round(($budgetUsed / $budgetTotal) * 100, 1) : 0;
                        $projectsCount = $client->projects->count() ?? 0;
                        
                        // Determina la classe CSS per il badge
                        $badgeClass = 'success';
                        if ($budgetPercentage >= 90) {
                            $badgeClass = 'danger';
                        } elseif ($budgetPercentage >= 75) {
                            $badgeClass = 'warning';
                        }
                    @endphp
                    <tr>
                        <td><strong>{{ $client->name }}</strong></td>
                        <td>{{ $client->created_at->format('d/m/Y') }}</td>
                        <td>€{{ number_format($budgetTotal, 2, ',', '.') }}</td>
                        <td>€{{ number_format($budgetUsed, 2, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-{{ $badgeClass }}">
                                {{ $budgetPercentage }}%
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                {{ $projectsCount }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('projects.index') }}?client={{ $client->id }}" 
                               class="btn btn-sm btn-info" 
                               title="Vedi Progetti">
                                <i class="fas fa-folder"></i>
                            </a>
                            <a href="{{ route('clients.edit', $client->id) }}" 
                               class="btn btn-sm btn-warning" 
                               title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('clients.destroy', $client->id) }}" 
                                  method="POST" 
                                  style="display: inline-block;"
                                  onsubmit="return confirm('Sei sicuro di voler eliminare questo cliente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessun cliente trovato</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- MOBILE VIEW: Card compatte (nascosta su desktop) -->
    <div class="clients-mobile-view" style="display: none;">
        <div class="clients-mobile-container">
            @forelse($clients as $client)
                @php
                    $budgetUsed = $client->projects->sum('budget_used') ?? 0;
                    $budgetTotal = $client->budget_total ?? 0;
                    $budgetPercentage = $budgetTotal > 0 ? round(($budgetUsed / $budgetTotal) * 100, 1) : 0;
                    $projectsCount = $client->projects->count() ?? 0;
                    
                    // Determina le classi CSS per colori
                    $percentageClass = '';
                    $progressClass = '';
                    if ($budgetPercentage >= 90) {
                        $percentageClass = 'danger';
                        $progressClass = 'danger';
                    } elseif ($budgetPercentage >= 75) {
                        $percentageClass = 'warning';
                        $progressClass = 'warning';
                    }
                @endphp
                
                <div class="client-card">
                    <!-- Header -->
                    <div class="client-card-header">
                        <h3 class="client-card-title">{{ $client->name }}</h3>
                        <span class="client-created-date">
                            {{ $client->created_at->format('d/m/Y') }}
                        </span>
                    </div>

                    <!-- Informazioni -->
                    <div class="client-card-info">
                        <div class="client-info-row">
                            <i class="fas fa-euro-sign client-info-icon"></i>
                            <span class="client-info-label">Budget Totale:</span>
                            <span class="client-info-value budget-total">
                                €{{ number_format($budgetTotal, 2, ',', '.') }}
                            </span>
                        </div>
                        
                        <div class="client-info-row">
                            <i class="fas fa-chart-line client-info-icon"></i>
                            <span class="client-info-label">Budget Utilizzato:</span>
                            <span class="client-info-value budget-used {{ $budgetPercentage >= 90 ? 'budget-warning' : '' }}">
                                €{{ number_format($budgetUsed, 2, ',', '.') }}
                            </span>
                        </div>
                        
                        <div class="client-info-row">
                            <i class="fas fa-folder client-info-icon"></i>
                            <span class="client-info-label">Numero Progetti:</span>
                            <span class="client-info-value project-count">
                                {{ $projectsCount }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar Budget -->
                    <div class="client-budget-section">
                        <div class="client-budget-header">
                            <span class="client-budget-label">Utilizzo Budget</span>
                            <span class="client-budget-percentage {{ $percentageClass }}">
                                {{ $budgetPercentage }}%
                            </span>
                        </div>
                        <div class="client-progress-bar">
                            <div class="client-progress-fill {{ $progressClass }}" 
                                 style="width: {{ min($budgetPercentage, 100) }}%">
                            </div>
                        </div>
                        <div class="client-budget-amounts">
                            <span>€{{ number_format($budgetUsed, 0, ',', '.') }}</span>
                            <span>€{{ number_format($budgetTotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Pulsanti azione -->
                    <div class="client-card-actions">
                        <a href="{{ route('projects.index') }}?client={{ $client->id }}" 
                           class="client-action-btn btn-projects">
                            <i class="fas fa-folder"></i> Progetti ({{ $projectsCount }})
                        </a>
                        <a href="{{ route('clients.edit', $client->id) }}" 
                           class="client-action-btn btn-edit">
                            <i class="fas fa-edit"></i> Modifica
                        </a>
                    </div>
                </div>
            @empty
                <div class="no-clients-message">
                    <i class="fas fa-inbox"></i>
                    <p>Nessun cliente trovato</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Paginazione -->
    @if(method_exists($clients, 'links'))
        <div class="mt-4">
            {{ $clients->links() }}
        </div>
    @endif
</div>
@endsection