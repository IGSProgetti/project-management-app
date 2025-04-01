@extends('layouts.app')

@section('title', 'Dettagli Utente')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Dettagli Utente: {{ $user->name }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Torna all'elenco
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informazioni Utente</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">Nome</th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th>Ruolo</th>
                            <td>
                                @if($user->is_admin)
                                    <span class="badge bg-danger">Amministratore</span>
                                @else
                                    <span class="badge bg-info">Utente Risorsa</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Registrato il</th>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Ultimo aggiornamento</th>
                            <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Risorsa Associata</h5>
                </div>
                <div class="card-body">
                    @if($user->resource)
                        <div class="text-center mb-3">
                            <div class="avatar-circle">
                                <span class="avatar-text">{{ substr($user->resource->name, 0, 2) }}</span>
                            </div>
                        </div>
                        
                        <h5 class="text-center mb-3">{{ $user->resource->name }}</h5>
                        
                        <table class="table table-bordered">
                            <tr>
                                <th>Ruolo</th>
                                <td>{{ $user->resource->role }}</td>
                            </tr>
                            <tr>
                                <th>Stato</th>
                                <td>
                                    @if($user->resource->is_active)
                                        <span class="badge bg-success">Attivo</span>
                                    @else
                                        <span class="badge bg-warning">Inattivo</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('resources.show', $user->resource->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Vedi Dettagli Risorsa
                            </a>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="avatar-circle bg-secondary">
                                <span class="avatar-text">NA</span>
                            </div>
                            <p class="mt-3 text-muted">Nessuna risorsa associata</p>
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
    .avatar-circle {
        width: 100px;
        height: 100px;
        background-color: #0d6efd;
        text-align: center;
        border-radius: 50%;
        -webkit-border-radius: 50%;
        -moz-border-radius: 50%;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-text {
        font-size: 40px;
        color: #fff;
        line-height: 1;
        font-weight: bold;
        text-transform: uppercase;
    }
</style>
@endpush