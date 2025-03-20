@extends('layouts.app')

@section('title', 'Modifica Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1>Modifica Task</h1>
        </div>
    </div>

    <form action="{{ route('tasks.update', $task->id) }}" method="POST" id="editTaskForm">
        @csrf
        @method('PUT')
        
        <div class="card mb-5">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Task</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $task->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="activity_id">Attività</label>
                        <select id="activity_id" name="activity_id" class="form-select @error('activity_id') is-invalid @enderror" required>
                            <option value="">Seleziona un'attività</option>
                            @foreach($activities as $activity)
                                <option value="{{ $activity->id }}" {{ old('activity_id', $task->activity_id) == $activity->id ? 'selected' : '' }}>
                                    {{ $activity->name }} ({{ $activity->project->name ?? 'N/D' }})
                                </option>
                            @endforeach
                        </select>
                        @error('activity_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description">Descrizione</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="estimated_minutes">Minuti Stimati</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" min="1" value="{{ old('estimated_minutes', $task->estimated_minutes) }}" required>
                        @error('estimated_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="actual_minutes">Minuti Effettivi</label>
                        <input type="number" id="actual_minutes" name="actual_minutes" class="form-control @error('actual_minutes') is-invalid @enderror" min="0" value="{{ old('actual_minutes', $task->actual_minutes) }}">
                        <small class="form-text text-muted">Inserire solo quando il task è completato.</small>
                        @error('actual_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="status">Stato</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>In attesa</option>
                            <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In corso</option>
                            <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completato</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="due_date">Data Scadenza</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="order">Ordine</label>
                        <input type="number" id="order" name="order" class="form-control @error('order') is-invalid @enderror" value="{{ old('order', $task->order) }}" min="0">
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="fixed-bottom bg-white py-2 border-top" style="z-index: 1000;">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-end">
                <button type="submit" form="editTaskForm" class="btn btn-primary">Salva Modifiche</button>
                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-secondary">Annulla</a>
            </div>
        </div>
    </div>
</div>
@endsection