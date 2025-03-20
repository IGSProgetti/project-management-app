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

    <div class="card">
        <div class="card-body">
            @if($clients->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Budget</th>
                                <th>Progetti</th>
                                <th>Budget Utilizzato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $client)
                                <tr>
                                    <td>{{ $client->name }}</td>
                                    <td>{{ number_format($client->budget, 2) }} €</td>
                                    <td>{{ $client->projects->count() }}</td>
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar {{ $client->budget_usage_percentage > 90 ? 'bg-danger' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $client->budget_usage_percentage }}%" 
                                                 aria-valuenow="{{ $client->budget_usage_percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <small>{{ number_format($client->total_budget_used, 2) }} € ({{ $client->budget_usage_percentage }}%)</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('clients.show', $client->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('clients.destroy', $client->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo cliente?');">
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
                    Nessun cliente disponibile. <a href="{{ route('clients.create') }}">Crea il tuo primo cliente</a>.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection