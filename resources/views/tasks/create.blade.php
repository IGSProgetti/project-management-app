@extends('layouts.app')

@section('title', 'Nuovo Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuovo Task</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('tasks.store') }}" method="POST" id="taskForm">
                @csrf
                
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
                                            <option value="{{ $client->id }}" data-created-from-tasks="{{ $client->created_from_tasks ? 1 : 0 }}">
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
                                        <option value="">Prima seleziona un cliente</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="newProjectBtn" disabled>
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
                                <option value="">Prima seleziona un progetto</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="newActivityBtn" disabled>
                                <i class="fas fa-plus"></i> Nuova
                            </button>
                        </div>
                        @error('activity_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Task</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
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
                                <option value="{{ $resource->id }}" {{ old('resource_id', Auth::user()->resource_id ?? '') == $resource->id ? 'selected' : '' }}>
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
                        <input type="number" id="estimated_minutes" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes') }}" min="1" required>
                        @error('estimated_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="due_date">Data Scadenza</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status">Stato</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>In attesa</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In corso</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completato</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description">Descrizione</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salva Task
                        </button>
                        <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annulla
                        </a>
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
            activitySelect.innerHTML = '<option value="">Prima seleziona un progetto</option>';
            newActivityBtn.disabled = true;
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
                
                // Abilita il pulsante nuova attività e carica le attività
                newActivityBtn.disabled = false;
                loadActivities(data.project.id);
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
        const projectId = this.value;
        newActivityBtn.disabled = !projectId;
        
        if (projectId) {
            loadActivities(projectId);
        } else {
            activitySelect.innerHTML = '<option value="">Prima seleziona un progetto</option>';
        }
    });

    // Funzione per caricare i progetti
    function loadProjects(clientId) {
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
                    projectSelect.appendChild(option);
                });
                
                activitySelect.innerHTML = '<option value="">Prima seleziona un progetto</option>';
                newActivityBtn.disabled = true;
            })
            .catch(error => {
                console.error('Errore nel caricamento progetti:', error);
                projectSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
            });
    }

    // Funzione per caricare le attività
    function loadActivities(projectId) {
        activitySelect.innerHTML = '<option value="">Caricamento...</option>';
        
        fetch(`/api/activities-by-project/${projectId}`)
            .then(response => response.json())
            .then(data => {
                activitySelect.innerHTML = '<option value="">Seleziona un\'attività</option>';
                
                data.activities.forEach(activity => {
                    const option = document.createElement('option');
                    option.value = activity.id;
                    option.textContent = activity.name;
                    activitySelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Errore nel caricamento attività:', error);
                activitySelect.innerHTML = '<option value="">Errore nel caricamento</option>';
            });
    }
});
</script>
@endpush