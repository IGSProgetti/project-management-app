@extends('layouts.app')

@section('title', 'Dettagli Attività')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $activity->name }}</h1>
            <p class="text-muted">
                Progetto: <a href="{{ route('projects.show', $activity->project_id) }}">{{ $activity->project->name }}</a>
                @if($activity->area)
                 | Area: <a href="{{ route('areas.show', $activity->area_id) }}">{{ $activity->area->name }}</a>
                @endif
            </p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('tasks.create') }}?activity_id={{ $activity->id }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Nuovo Task
            </a>
            <a href="{{ route('activities.edit', $activity->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('activities.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Riepilogo minuti task -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Riepilogo Minuti</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <h6>Minuti stimati task</h6>
                                <h3>{{ $activity->tasks->sum('estimated_minutes') }}</h3>
                                <small>su {{ $activity->estimated_minutes }} dell'attività</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <h6>Minuti effettivi task</h6>
                                <h3>{{ $activity->tasks->sum('actual_minutes') }}</h3>
                                <small>{{ $activity->tasks->sum('estimated_minutes') > 0 ? number_format(($activity->tasks->sum('actual_minutes') / $activity->tasks->sum('estimated_minutes')) * 100, 0) : 0 }}% del tempo stimato</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Attività</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Stato:</strong>
                        @if($activity->status == 'pending')
                            <span class="badge bg-warning">In attesa</span>
                        @elseif($activity->status == 'in_progress')
                            <span class="badge bg-primary">In corso</span>
                        @elseif($activity->status == 'completed')
                            <span class="badge bg-success">Completata</span>
                        @endif
                    </p>
                    <p><strong>Risorsa Assegnata:</strong> {{ $activity->resource->name }} ({{ $activity->resource->role }})</p>
                    <p>
                        <strong>Tipo di Ore:</strong>
                        @if($activity->hours_type == 'standard')
                            <span class="badge bg-primary">Standard</span>
                        @else
                            <span class="badge bg-warning">Extra</span>
                        @endif
                    </p>
                    <p><strong>Minuti Preventivati:</strong> {{ $activity->estimated_minutes }}</p>
                    <p><strong>Minuti Effettivi:</strong> {{ $activity->actual_minutes ?? 0 }}</p>
                    <p><strong>Costo Preventivato:</strong> {{ number_format($activity->estimated_cost, 2) }} €</p>
                    <p><strong>Costo Effettivo:</strong> {{ number_format($activity->actual_cost, 2) }} €</p>
                    <p><strong>Data Scadenza:</strong> {{ $activity->due_date ? $activity->due_date->format('d/m/Y') : 'Non specificata' }}</p>
                    
                    @if($activity->is_overdue)
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-circle"></i> Attività scaduta!
                        </div>
                    @endif
                    
                    @if($activity->is_over_estimated)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> I minuti effettivi superano quelli preventivati!
                        </div>
                    @endif

                    <h6 class="mt-4">Progresso</h6>
                    <div class="progress mb-2" style="height: 15px;">
                        <div class="progress-bar {{ $activity->progress_percentage > 90 ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $activity->progress_percentage }}%" aria-valuenow="{{ $activity->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $activity->progress_percentage }}%</div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Azioni</h5>
                </div>
                <div class="card-body">
                    @if($activity->status == 'pending')
                        <form action="{{ route('activities.updateStatus', $activity->id) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-play"></i> Avvia Attività
                            </button>
                        </form>
                    @endif
                    
                    @if($activity->status != 'completed')
                        <form action="{{ route('activities.updateStatus', $activity->id) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check"></i> Completa Attività
                            </button>
                        </form>
                    @endif
                    
                    <form action="{{ route('activities.destroy', $activity->id) }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Sei sicuro di voler eliminare questa attività?');">
                            <i class="fas fa-trash"></i> Elimina Attività
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Disponibilità Risorsa</h5>
                </div>
                <div class="card-body">
                    <h6>{{ $activity->resource->name }}</h6>
                    
                    @if($activity->hours_type == 'standard')
                        <p><strong>Ore Standard/Anno:</strong> {{ number_format($activity->resource->standard_hours_per_year, 2) }}</p>
                        <p><strong>Ore Standard Utilizzate:</strong> {{ number_format($activity->resource->total_standard_estimated_hours, 2) }}</p>
                        <p><strong>Ore Standard Residue:</strong> {{ number_format($activity->resource->remaining_standard_estimated_hours, 2) }}</p>
                        
                        <div class="progress mb-3" style="height: 10px;">
                            @php
                                $standardUsagePercentage = min(100, $activity->resource->standard_hours_per_year > 0 ? 
                                    ($activity->resource->total_standard_estimated_hours / $activity->resource->standard_hours_per_year) * 100 : 0);
                            @endphp
                            <div class="progress-bar {{ $standardUsagePercentage > 90 ? 'bg-danger' : 'bg-success' }}" 
                                role="progressbar" 
                                style="width: {{ $standardUsagePercentage }}%" 
                                aria-valuenow="{{ $standardUsagePercentage }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($standardUsagePercentage, 1) }}%
                            </div>
                        </div>
                    @else
                        <p><strong>Ore Extra/Anno:</strong> {{ number_format($activity->resource->extra_hours_per_year, 2) }}</p>
                        <p><strong>Ore Extra Utilizzate:</strong> {{ number_format($activity->resource->total_extra_estimated_hours, 2) }}</p>
                        <p><strong>Ore Extra Residue:</strong> {{ number_format($activity->resource->remaining_extra_estimated_hours, 2) }}</p>
                        
                        <div class="progress mb-3" style="height: 10px;">
                            @php
                                $extraUsagePercentage = min(100, $activity->resource->extra_hours_per_year > 0 ? 
                                    ($activity->resource->total_extra_estimated_hours / $activity->resource->extra_hours_per_year) * 100 : 0);
                            @endphp
                            <div class="progress-bar {{ $extraUsagePercentage > 90 ? 'bg-danger' : 'bg-warning' }}" 
                                role="progressbar" 
                                style="width: {{ $extraUsagePercentage }}%" 
                                aria-valuenow="{{ $extraUsagePercentage }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                {{ number_format($extraUsagePercentage, 1) }}%
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Task</h5>
                    <a href="{{ route('tasks.create') }}?activity_id={{ $activity->id }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuovo Task
                    </a>
                </div>
                <div class="card-body">
                    @if($activity->tasks->count() > 0)
                        <div id="taskList" class="task-list mb-4">
                            @foreach($activity->tasks->sortBy('order') as $task)
                                <div class="task-item {{ $task->status }} {{ $task->is_over_estimated ? 'over-estimated' : '' }}" data-task-id="{{ $task->id }}">
                                    <div class="task-header d-flex justify-content-between align-items-center">
                                        <div class="task-status">
                                            @if($task->status == 'pending')
                                                <span class="badge bg-warning">In attesa</span>
                                            @elseif($task->status == 'in_progress')
                                                <span class="badge bg-primary">In corso</span>
                                            @elseif($task->status == 'completed')
                                                <span class="badge bg-success">Completato</span>
                                            @endif
                                        </div>
                                        <div class="task-actions">
                                            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($task->status != 'completed')
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeTaskModal{{ $task->id }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                
                                                <!-- Modal per il completamento task -->
                                                <div class="modal fade" id="completeTaskModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Completa Task: {{ $task->name }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('tasks.complete', $task->id) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="actual_minutes_{{ $task->id }}">Minuti Effettivi</label>
                                                                        <input type="number" class="form-control" id="actual_minutes_{{ $task->id }}" 
                                                                            name="actual_minutes" min="0" value="{{ $task->estimated_minutes }}" required>
                                                                        <div class="form-text">Inserisci il tempo effettivamente impiegato per completare questo task.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                                    <button type="submit" class="btn btn-success">Completa Task</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="task-content">
                                        <h5>{{ $task->name }}</h5>
                                        @if($task->description)
                                            <p>{{ Str::limit($task->description, 100) }}</p>
                                        @endif
                                        
                                        <div class="task-details row">
                                            <div class="col-md-6">
                                                <small><strong>Minuti stimati:</strong> {{ $task->estimated_minutes }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                <small><strong>Minuti effettivi:</strong> {{ $task->actual_minutes }}</small>
                                            </div>
                                        </div>
                                        
                                        <div class="progress mt-2 mb-2" style="height: 8px;">
                                            <div class="progress-bar {{ $task->is_over_estimated ? 'bg-danger' : 'bg-primary' }}" role="progressbar" 
                                                 style="width: {{ $task->progress_percentage }}%" 
                                                 aria-valuenow="{{ $task->progress_percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        
                                        @if($task->due_date)
                                            <div class="task-due-date mt-2">
                                                <small>
                                                    <i class="fas fa-calendar"></i> 
                                                    Scadenza: {{ $task->due_date->format('d/m/Y') }}
                                                    @if($task->is_overdue)
                                                        <span class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i> Scaduto
                                                        </span>
                                                    @endif
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            Nessun task definito per questa attività. <a href="{{ route('tasks.create') }}?activity_id={{ $activity->id }}">Crea un nuovo task</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .task-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .task-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid #2196F3;
    }
    
    .task-item.completed {
        border-left-color: #4CAF50;
        background: #f1f8e9;
    }
    
    .task-item.pending {
        border-left-color: #FFC107;
    }
    
    .task-item.over-estimated {
        border-right: 4px solid #dc3545;
    }
    
    .task-header {
        margin-bottom: 10px;
    }
    
    .task-content h5 {
        margin-bottom: 8px;
    }
    
    .task-content p {
        color: #666;
        margin-bottom: 10px;
    }
    
    .task-due-date {
        color: #777;
    }
    
    .task-details {
        margin-top: 8px;
        color: #555;
    }
</style>
@endpush