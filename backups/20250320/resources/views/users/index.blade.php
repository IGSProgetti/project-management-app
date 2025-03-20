@extends('layouts.app')

@section('title', 'Gestione Utenti')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Utenti</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Utente
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Ruolo</th>
                                <th>Risorsa Associata</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->isAdmin())
                                            <span class="badge bg-danger">Amministratore</span>
                                        @else
                                            <span class="badge bg-info">{{ $user->getRole() }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->resource)
                                            <a href="{{ route('resources.show', $user->resource->id) }}">
                                                {{ $user->resource->name }} ({{ $user->resource->role }})
                                            </a>
                                        @else
                                            <span class="text-muted">Nessuna risorsa associata</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
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
                    Nessun utente disponibile. <a href="{{ route('users.create') }}">Crea il tuo primo utente</a>.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection