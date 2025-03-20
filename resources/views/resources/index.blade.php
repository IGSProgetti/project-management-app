@extends('layouts.app')

@section('title', 'Gestione Risorse')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Risorse</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('resources.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuova Risorsa
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($resources->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Ruolo</th>
                                <th>Prezzo di costo</th>
                                <th>Prezzo di vendita</th>
                                <th>Ore disponibili</th>
                                <th>Ore allocate</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resources as $resource)
                                <tr>
                                    <td>{{ $resource->name }}</td>
                                    <td>{{ $resource->role }}</td>
                                    <td>{{ number_format($resource->cost_price, 2) }} €/h</td>
                                    <td>{{ number_format($resource->selling_price, 2) }} €/h</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>Standard: {{ number_format($resource->standard_hours_per_year, 2) }}</span>
                                            <span>Extra: {{ number_format($resource->extra_hours_per_year, 2) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div class="mb-2">
                                                <strong>Standard:</strong>
                                                <div class="d-flex justify-content-between">
                                                    <small>Stimate: {{ number_format($resource->total_standard_estimated_hours, 2) }}</small>
                                                    <small>Effettive: {{ number_format($resource->total_standard_actual_hours, 2) }}</small>
                                                </div>
                                                
                                                @php
                                                    $standardEstimatedUsagePercentage = min(100, $resource->standard_hours_per_year > 0 ? ($resource->total_standard_estimated_hours / $resource->standard_hours_per_year) * 100 : 0);
                                                @endphp
                                                
                                                <div class="progress mt-1" style="height: 6px;">
                                                    <div class="progress-bar {{ $standardEstimatedUsagePercentage > 90 ? 'bg-danger' : 'bg-success' }}" 
                                                        role="progressbar" 
                                                        style="width: {{ $standardEstimatedUsagePercentage }}%" 
                                                        aria-valuenow="{{ $standardEstimatedUsagePercentage }}" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100"
                                                        title="Utilizzo stimato: {{ number_format($standardEstimatedUsagePercentage, 1) }}%">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <strong>Extra:</strong>
                                                <div class="d-flex justify-content-between">
                                                    <small>Stimate: {{ number_format($resource->total_extra_estimated_hours, 2) }}</small>
                                                    <small>Effettive: {{ number_format($resource->total_extra_actual_hours, 2) }}</small>
                                                </div>
                                                
                                                @php
                                                    $extraEstimatedUsagePercentage = min(100, $resource->extra_hours_per_year > 0 ? ($resource->total_extra_estimated_hours / $resource->extra_hours_per_year) * 100 : 0);
                                                @endphp
                                                
                                                <div class="progress mt-1" style="height: 6px;">
                                                    <div class="progress-bar {{ $extraEstimatedUsagePercentage > 90 ? 'bg-danger' : 'bg-warning' }}" 
                                                        role="progressbar" 
                                                        style="width: {{ $extraEstimatedUsagePercentage }}%" 
                                                        aria-valuenow="{{ $extraEstimatedUsagePercentage }}" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100"
                                                        title="Utilizzo stimato: {{ number_format($extraEstimatedUsagePercentage, 1) }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('resources.show', $resource->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('resources.edit', $resource->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('resources.destroy', $resource->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questa risorsa?');">
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
                    Nessuna risorsa disponibile. <a href="{{ route('resources.create') }}">Crea la tua prima risorsa</a>.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection