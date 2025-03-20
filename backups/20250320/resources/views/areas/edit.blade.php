@extends('layouts.app')

@section('title', 'Modifica Area')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Modifica Area</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('areas.update', $area->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Area</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $area->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="project_id">Progetto</label>
                        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                            <option value="">Seleziona un progetto</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $area->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }} ({{ $project->client->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description">Descrizione</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $area->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Aggiorna Area</button>
                        <a href="{{ route('areas.show', $area->id) }}" class="btn btn-secondary">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection