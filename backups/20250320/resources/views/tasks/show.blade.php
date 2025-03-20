@extends('layouts.app')

@section('title', 'Dettagli Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>{{ $task->name ?? 'Dettagli Task' }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('tasks.edit', $task->id ?? 0) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Task</h5>
                </div>
                <div class="card-body">
                    <p><strong>Descrizione:</strong> {{ $task->description ?? 'Nessuna descrizione' }}</p>
                    <p>
                        <strong>Stato:</strong>
                        @if(isset($task) && $task->status == 'pending')
                            <span class="badge bg-warning">In attesa</span>
                        @elseif(isset($task) && $task->status == 'in_progress')
                            <span class="badge bg-primary">In corso</span>
                        @elseif(isset($task) && $task->status == 'completed')
                            <span class="badge bg-success">Completato</span>
                        @else
                            <span class="badge bg-secondary">Sconosciuto</span>
                        @endif
                    </p>
                    <p><strong>Minuti Stimati:</strong> {{ $task->estimated_minutes ?? 0 }}</p>
                    <p><strong>Minuti Effettivi:</strong> {{ $task->actual_minutes ?? 0 }}</p>
                    <p><strong>Progresso:</strong> 
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar {{ $task->is_over_estimated ? 'bg-danger' : '' }}" role="progressbar" 
                                 style="width: {{ $task->progress_percentage }}%" 
                                 aria-valuenow="{{ $task->progress_percentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                        <small>{{ $task->progress_percentage }}%</small>
                    </p>
                    <p><strong>Data Scadenza:</strong> {{ isset($task) && $task->due_date ? $task->due_date->format('d/m/Y') : 'Non specificata' }}</p>
                    <p><strong>Ordine:</strong> {{ $task->order ?? 0 }}</p>
                    
                    @if(isset($task) && $task->is_overdue)
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Questo task è scaduto!
                        </div>
                    @endif
                    
                    @if(isset($task) && $task->is_over_estimated)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> I minuti effettivi hanno superato quelli stimati!
                        </div>
                    @endif
                </div>
            </div>
            
            @if(isset($task) && $task->status != 'completed')
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Azioni</h5>
                    </div>
                    <div class="card-body">
                        <!-- Cronometro per tracciare il tempo di attività -->
                        <div class="timer-container mb-4">
                            <h6>Cronometro</h6>
                            <input type="hidden" id="taskId" value="{{ $task->id }}">
                            
                            <div class="task-timer-display mb-3">
                                <div class="display-box d-flex align-items-center justify-content-center p-3 border rounded bg-light">
                                    <h3 id="taskTimer" class="display-5 m-0">00:00:00</h3>
                                </div>
                            </div>
                            
                            <div class="task-timer-controls mb-3">
                                <div class="btn-group w-100">
                                    <button id="startTimerBtn" class="btn btn-success">
                                        <i class="fas fa-play me-1"></i> Avvia
                                    </button>
                                    <button id="stopTimerBtn" class="btn btn-danger" disabled>
                                        <i class="fas fa-stop me-1"></i> Ferma
                                    </button>
                                    <button id="resetTimerBtn" class="btn btn-secondary">
                                        <i class="fas fa-redo me-1"></i> Azzera
                                    </button>
                                </div>
                                <small class="form-text text-muted mt-2">
                                    <i class="fas fa-info-circle"></i> Usa il cronometro per tracciare il tempo effettivo. I minuti saranno salvati automaticamente quando fermi il cronometro.
                                </small>
                            </div>
                        </div>
                        
                        <hr>

                        <!-- Form per completare un task con minuti effettivi (metodo esistente) -->
                        <h6>Inserimento manuale minuti effettivi</h6>
                        <form action="{{ route('tasks.complete', $task->id ?? 0) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="actual_minutes">Minuti Effettivi</label>
                                    <input type="number" name="actual_minutes" id="actual_minutes" class="form-control" min="0" value="{{ $task->actual_minutes ?? $task->estimated_minutes ?? 0 }}" required>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Segna come completato
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        @if($task->status == 'pending')
                            <form action="{{ route('tasks.start', $task->id ?? 0) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Avvia task
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Dettagli Attività</h5>
                </div>
                <div class="card-body">
                    <p><strong>Attività:</strong> {{ $task->activity->name ?? 'N/D' }}</p>
                    <p><strong>Progetto:</strong> {{ $task->activity->project->name ?? 'N/D' }}</p>
                    <p><strong>Cliente:</strong> {{ $task->activity->project->client->name ?? 'N/D' }}</p>
                    <p><strong>Risorsa Assegnata:</strong> {{ $task->activity->resource->name ?? 'N/D' }}</p>
                    
                    @if(isset($task) && isset($task->activity))
                        <a href="{{ route('activities.show', $task->activity->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> Visualizza Attività
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .task-timer-display .display-box {
        font-size: 2rem;
        font-weight: bold;
        font-family: monospace;
        line-height: 1;
        color: #333;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        transition: background-color 0.3s;
    }
    
    .task-timer-display .display-box:hover {
        background-color: #e9ecef;
    }
    
    .task-timer-controls .btn-group {
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .task-timer-display .display-box {
            font-size: 1.5rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/task-timer.js') }}"></script>
@endpush