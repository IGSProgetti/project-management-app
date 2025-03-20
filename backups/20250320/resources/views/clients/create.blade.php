@extends('layouts.app')

@section('title', 'Nuovo Cliente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuovo Cliente</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('clients.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Cliente</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="budget">Budget (€)</label>
                        <input type="number" id="budget" name="budget" class="form-control @error('budget') is-invalid @enderror" value="{{ old('budget') }}" min="0" step="0.01" required>
                        @error('budget')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="notes">Note</label>
                        <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salva Cliente</button>
                        <a href="{{ route('clients.index') }}" class="btn btn-secondary">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection