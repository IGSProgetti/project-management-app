@extends('layouts.app')

@section('title', 'Modifica Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Modifica Task</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('tasks.update', $task->id) }}" method="POST" id="taskEditForm">
                @csrf
                @method('PUT')
                
                <!-- Info Task Corrente -->
                <div class="alert alert-info mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Cliente Attuale:</strong> {{ $task->activity->project->client->name }}
                            @if($task->activity->project->client->created_from_tasks)
                                <span class="badge bg-warning ms-1">Da Tasks</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Progetto Attuale:</strong> {{ $task->activity->project->name }}
                            @if($task->activity->project->created_from_tasks)
                                <span class="badge bg-warning ms-1">Da Tasks</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Attività Attuale:</strong> {{ $task->activity->name }}
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Selezione/Creazione Cliente e Progetto -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-building"></i> Cliente e Progetto
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Selezione Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_select">Cliente</label>
                                <div class="input-group">
                                    <select id="client_select" name="client_id" class="form-select">
                                        <option value="">Seleziona cliente esistente</option>
                                        @foreach($clients ?? [] as $client)
                                            <option value="{{ $client->id }}" 
                                                    data-created-from-tasks="{{ $client->created_from_tasks ? 1 : 0 }}"
                                                    {{ $task->activity->project->client_id == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                                @if($client->created_from_tasks)
                                                    <span class="badge bg-warning ms-2">Creato da Tasks</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="newClientBtn">
                                        <i class="fas fa-plus"></i> Nuovo
                                    </button>
                                </div>
                                
                                <!-- Form per nuovo cliente (nascosto) -->
                                <div id="newClientForm" class="mt-3" style="display: none;">
                                    <div class="border rounded p-3 bg-light">
                                        <h6><i class="fas fa-plus-circle text-primary"></i> Crea Nuovo Cliente</h6>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <input type="text" 
                                                       id="new_client_name" 
                                                       name="new_client_name" 
                                                       class="form-control" 
                                                       placeholder="Nome cliente">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" 
                                                       id="new_client_budget" 
                                                       name="new_client_budget" 
                                                       class="form-control" 
                                                       placeholder="Budget stimato" 
                                                       value="10000" 
                                                       step="0.01" 
                                                       min="0">
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <textarea id="new_client_notes" 
                                                          name="new_client_notes" 
                                                          class="form-control" 
                                                          rows="2" 
                                                          placeholder="Note sul cliente (opzionale)"></textarea>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-success" id="saveNewClient">
                                                <i class="fas fa-check"></i> Salva Cliente
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" id="cancelNewClient">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selezione Progetto -->
                            <div class="col-md-6 mb-3">
                                <label for="project_select">Progetto</label>
                                <div class="input-group">
                                    <select id="project_select" name="project_id" class="form-select">
                                        <option value="">Caricamento progetti...</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="newProjectBtn">
                                        <i class="fas fa-plus"></i> Nuovo
                                    </button>
                                </div>
                                
                                <!-- Form per nuovo progetto (nascosto) -->
                                <div id="newProjectForm" class="mt-3" style="display: none;">
                                    <div class="border rounded p-3 bg-light">
                                        <h6><i class="fas fa-plus-circle text-primary"></i> Crea Nuovo Progetto</h6>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <input type="text" 
                                                       id="new_project_name" 
                                                       name="new_project_name" 
                                                       class="form-control" 
                                                       placeholder="Nome progetto">
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <textarea id="new_project_description" 
                                                          name="new_project_description" 
                                                          class="form-control" 
                                                          rows="2" 
                                                          placeholder="Descrizione progetto (opzionale)"></textarea>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-success" id="saveNewProject">
                                                <i class="fas fa-check"></i> Salva Progetto
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" id="cancelNewProject">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selezione Attività (si popola dopo aver selezionato il progetto) -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="activity_id">Attività</label>
                        <div class="input-group">
                            <select id="activity_id" name="activity_id" class="form-select @error('activity_id') is-invalid @enderror" required>
                                @foreach($activities as $activity)
                                    <option value="{{ $activity->id }}" 
                                            {{ old('activity_id', $task->activity_id) == $activity->id ? 'selected' : '' }}
                                            data-project-id="{{ $activity->project_id }}">
                                        {{ $activity->name }} ({{ $activity->project->name ?? 'N/D' }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="newActivityBtn">
                                <i class="fas fa-plus"></i> Nuova
                            </button>
                        </div>
                        @error('activity_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Task</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $task->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="resource_id">Risorsa Assegnata</label>
                        <select id="resource_id" name="resource_id" class="form-select @error('resource_id') is-invalid @enderror">
                            <option value="">Seleziona una risorsa</option>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}" {{ old('resource_id', $task->resource_id) == $resource->id ? 'selected' : '' }}>
                                    {{ $resource->name }} ({{ $resource->role }})
                                </option>
                            @endforeach
                        </select>
                        @error('resource_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="estimated_minutes">Minuti Stimati</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes', $task->estimated_minutes) }}" min="1" required>
                        @error('estimated_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="due_date">Data Scadenza</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
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
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description">Descrizione</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Aggiorna Task
                        </button>
                        <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annulla
                        </a>
                        
                        @if($task->activity->project->created_from_tasks || $task->activity->project->client->created_from_tasks)
                            <div class="mt-3">
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Nota:</strong> Questo task è associato a elementi creati automaticamente. 
                                    Considera di consolidare il 
                                    @if($task->activity->project->client->created_from_tasks)
                                        <a href="{{ route('clients.show', $task->activity->project->client->id) }}">cliente</a>
                                    @endif
                                    @if($task->activity->project->created_from_tasks)
                                        e/o il <a href="{{ route('projects.show', $task->activity->project->id) }}">progetto</a>
                                    @endif
                                    per completare la configurazione.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.getElementById('client_select');
    const projectSelect = document.getElementById('project_select');
    const activitySelect = document.getElementById('activity_id');
    const newClientBtn = document.getElementById('newClientBtn');
    const newProjectBtn = document.getElementById('newProjectBtn');
    const newActivityBtn = document.getElementById('newActivityBtn');
    const newClientForm = document.getElementById('newClientForm');
    const newProjectForm = document.getElementById('newProjectForm');
    
    // Carica inizialmente i progetti del cliente selezionato
    const initialClientId = clientSelect.value;
    if (initialClientId) {
        loadProjects(initialClientId, {{ $task->activity->project->id }});
    }
    
    // Gestione nuovo cliente
    newClientBtn.addEventListener('click', function() {
        newClientForm.style.display = newClientForm.style.display === 'none' ? 'block' : 'none';
        if (newClientForm.style.display === 'block') {
            document.getElementById('new_client_name').focus();
        }
    });

    document.getElementById('cancelNewClient').addEventListener('click', function() {
        newClientForm.style.display = 'none';
        // Reset form
        document.getElementById('new_client_name').value = '';
        document.getElementById('new_client_budget').value = '10000';
        document.getElementById('new_client_notes').value = '';
    });

    document.getElementById('saveNewClient').addEventListener('click', function() {
        const clientName = document.getElementById('new_client_name').value.trim();
        const clientBudget = document.getElementById('new_client_budget').value;
        const clientNotes = document.getElementById('new_client_notes').value;

        if (!clientName) {
            alert('Il nome del cliente è obbligatorio');
            return;
        }

        // Crea il cliente via AJAX
        fetch('/tasks/create-client', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                name: clientName,
                budget: clientBudget,
                notes: clientNotes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Aggiungi il nuovo cliente alla select
                const option = document.createElement('option');
                option.value = data.client.id;
                option.textContent = data.client.name + ' [Creato da Tasks]';
                option.setAttribute('data-created-from-tasks', '1');
                option.selected = true;
                
                clientSelect.appendChild(option);
                
                // Nascondi e reset il form
                newClientForm.style.display = 'none';
                document.getElementById('new_client_name').value = '';
                document.getElementById('new_client_budget').value = '10000';
                document.getElementById('new_client_notes').value = '';
                
                // Abilita il pulsante nuovo progetto e carica i progetti
                newProjectBtn.disabled = false;
                loadProjects(data.client.id);
            } else {
                alert('Errore nella creazione del cliente: ' + (data.message || 'Errore sconosciuto'));
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore nella comunicazione con il server');
        });
    });

    // Gestione cambio cliente
    clientSelect.addEventListener('change', function() {
        const clientId = this.value;
        newProjectBtn.disabled = !clientId;
        
        if (clientId) {
            loadProjects(clientId);
        } else {
            projectSelect.innerHTML = '<option value="">Prima seleziona un cliente</option>';
            filterActivitiesByProject();
        }
    });

    // Gestione nuovo progetto
    newProjectBtn.addEventListener('click', function() {
        newProjectForm.style.display = newProjectForm.style.display === 'none' ? 'block' : 'none';
        if (newProjectForm.style.display === 'block') {
            document.getElementById('new_project_name').focus();
        }
    });

    document.getElementById('cancelNewProject').addEventListener('click', function() {
        newProjectForm.style.display = 'none';
        document.getElementById('new_project_name').value = '';
        document.getElementById('new_project_description').value = '';
    });

    document.getElementById('saveNewProject').addEventListener('click', function() {
        const projectName = document.getElementById('new_project_name').value.trim();
        const projectDescription = document.getElementById('new_project_description').value;
        const clientId = clientSelect.value;

        if (!projectName) {
            alert('Il nome del progetto è obbligatorio');
            return;
        }

        if (!clientId) {
            alert('Seleziona prima un cliente');
            return;
        }

        // Crea il progetto via AJAX
        fetch('/tasks/create-project', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                name: projectName,
                description: projectDescription,
                client_id: clientId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Aggiungi il nuovo progetto alla select
                const option = document.createElement('option');
                option.value = data.project.id;
                option.textContent = data.project.name + ' [Creato da Tasks]';
                option.selected = true;
                
                projectSelect.appendChild(option);
                
                // Nascondi e reset il form
                newProjectForm.style.display = 'none';
                document.getElementById('new_project_name').value = '';
                document.getElementById('new_project_description').value = '';
                
                // Filtra le attività per il nuovo progetto
                filterActivitiesByProject();
            } else {
                alert('Errore nella creazione del progetto: ' + (data.message || 'Errore sconosciuto'));
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore nella comunicazione con il server');
        });
    });

    // Gestione cambio progetto
    projectSelect.addEventListener('change', function() {
        filterActivitiesByProject();
    });

    // Funzione per caricare i progetti
    function loadProjects(clientId, selectedProjectId = null) {
        projectSelect.innerHTML = '<option value="">Caricamento...</option>';
        
        fetch(`/api/projects-by-client/${clientId}`)
            .then(response => response.json())
            .then(data => {
                projectSelect.innerHTML = '<option value="">Seleziona un progetto</option>';
                
                data.projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.name;
                    if (project.created_from_tasks) {
                        option.textContent += ' [Creato da Tasks]';
                    }
                    
                    // Seleziona il progetto se è quello attuale del task
                    if (selectedProjectId && project.id == selectedProjectId) {
                        option.selected = true;
                    }
                    
                    projectSelect.appendChild(option);
                });
                
                // Filtra le attività dopo aver caricato i progetti
                filterActivitiesByProject();
            })
            .catch(error => {
                console.error('Errore nel caricamento progetti:', error);
                projectSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
            });
    }

    // Funzione per filtrare le attività per progetto
    function filterActivitiesByProject() {
        const selectedProjectId = projectSelect.value;
        const activityOptions = activitySelect.querySelectorAll('option');
        
        activityOptions.forEach(option => {
            if (option.value === '') {
                // Mantieni sempre l'opzione vuota
                option.style.display = '';
                return;
            }
            
            const projectId = option.getAttribute('data-project-id');
            
            if (!selectedProjectId || projectId === selectedProjectId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
                
                // Se l'opzione nascosta era selezionata, deselezionala
                if (option.selected) {
                    option.selected = false;
                    activitySelect.value = '';
                }
            }
        });
    }
});
</script>
@endpush