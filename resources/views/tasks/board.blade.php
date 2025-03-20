@extends('layouts.app')

@section('title', 'Task Board')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Task Board</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Task
            </a>
            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> Vista Lista
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterProject">Progetto</label>
                        <select id="filterProject" class="form-select">
                            <option value="">Tutti i progetti</option>
                            @foreach($projects ?? [] as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="filterActivity">Attività</label>
                        <select id="filterActivity" class="form-select">
                            <option value="">Tutte le attività</option>
                            <!-- Verrà popolato via JS in base al progetto selezionato -->
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>&nbsp;</label>
                        <button id="applyFilters" class="btn btn-primary form-control">
                            <i class="fas fa-filter"></i> Applica Filtri
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kanban-container">
        <div class="kanban-board">
            <!-- Colonna "In attesa" -->
            <div class="kanban-column" id="pending-tasks">
                <div class="kanban-column-header">
                    <h5>In attesa</h5>
                    <span class="task-counter" id="pending-count">0</span>
                </div>
                <div class="kanban-tasks" data-status="pending">
                    <!-- I task verranno caricati dinamicamente -->
                </div>
            </div>

            <!-- Colonna "In corso" -->
            <div class="kanban-column" id="in-progress-tasks">
                <div class="kanban-column-header">
                    <h5>In corso</h5>
                    <span class="task-counter" id="progress-count">0</span>
                </div>
                <div class="kanban-tasks" data-status="in_progress">
                    <!-- I task verranno caricati dinamicamente -->
                </div>
            </div>

            <!-- Colonna "Completati" -->
            <div class="kanban-column" id="completed-tasks">
                <div class="kanban-column-header">
                    <h5>Completati</h5>
                    <span class="task-counter" id="completed-count">0</span>
                </div>
                <div class="kanban-tasks" data-status="completed">
                    <!-- I task verranno caricati dinamicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskDetailsModalLabel">Dettagli Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="taskDetailsContent">
                    <!-- Contenuto caricato dinamicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <a href="#" class="btn btn-warning" id="editTaskBtn">Modifica</a>
                    <button type="button" class="btn btn-primary" id="saveTaskStatusBtn">Salva Stato</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .kanban-container {
        overflow-x: auto;
        padding-bottom: 20px;
    }

    .kanban-board {
        display: flex;
        gap: 20px;
        min-height: calc(100vh - 350px);
    }

    .kanban-column {
        min-width: 300px;
        width: 33%;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }

    .kanban-column-header {
        padding: 15px;
        background-color: #eaecf0;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
    }

    .kanban-column-header h5 {
        margin: 0;
    }

    .task-counter {
        background-color: rgba(0, 0, 0, 0.1);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    .kanban-tasks {
        padding: 15px;
        flex-grow: 1;
        overflow-y: auto;
        min-height: 100px;
    }

    .task-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        border-left: 5px solid #ccc;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .task-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
    }

    .task-card.high-priority {
        border-left-color: #dc3545;
    }

    .task-card.medium-priority {
        border-left-color: #ffc107;
    }

    .task-card.low-priority {
        border-left-color: #28a745;
    }

    .task-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .task-card-title {
        font-weight: 600;
        margin: 0;
    }

    .task-card-date {
        font-size: 0.85rem;
        color: #666;
    }

    .task-card-activity {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 10px;
    }

    .task-card-resource {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .resource-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
    }

    /* Stili per drag and drop */
    .task-dragging {
        opacity: 0.5;
    }

    .kanban-tasks.drag-over {
        background-color: rgba(33, 150, 243, 0.1);
    }
    
    /* Badge per lo stato del task */
    .task-status {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-in-progress {
        background-color: #cce5ff;
        color: #004085;
    }
    
    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }
    
    /* Stili per modali */
    #taskDetailsContent {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .task-details-section {
        margin-bottom: 20px;
    }
    
    .task-details-section h6 {
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
    
    .task-description {
        white-space: pre-line;
        margin-bottom: 15px;
    }
    
    .task-status-options {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .task-status-option {
        cursor: pointer;
        padding: 8px 15px;
        border-radius: 4px;
        border: 1px solid #eee;
        transition: all 0.2s;
    }
    
    .task-status-option.active {
        border-color: #2196F3;
        background-color: #e3f2fd;
    }
    
    .task-status-option:hover:not(.active) {
        background-color: #f8f9fa;
    }
    
    /* Aggiungi icona di trascinamento per indicare che i task sono trascinabili */
    .drag-handle {
        cursor: move;
        color: #aaa;
        margin-right: 8px;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variabili globali
    let allTasks = [];
    let filteredTasks = [];
    let currentProjectId = '';
    let currentActivityId = '';
    let selectedTaskId = '';
    
    // Carica tutti i task dal server
    loadAllTasks();
    
    // Gestione dei filtri per progetti e attività
    const filterProject = document.getElementById('filterProject');
    const filterActivity = document.getElementById('filterActivity');
    const applyFiltersBtn = document.getElementById('applyFilters');
    
    // Event listeners
    filterProject.addEventListener('change', function() {
        currentProjectId = this.value;
        // Aggiorna il dropdown delle attività in base al progetto selezionato
        updateActivityFilter(currentProjectId);
    });
    
    applyFiltersBtn.addEventListener('click', function() {
        // Applica i filtri selezionati
        filterTasks();
    });
    
    // Gestione della visualizzazione dei dettagli del task
    document.querySelectorAll('.kanban-tasks').forEach(column => {
        column.addEventListener('click', function(e) {
            const taskCard = e.target.closest('.task-card');
            if (taskCard) {
                const taskId = taskCard.dataset.taskId;
                showTaskDetails(taskId);
            }
        });
    });
    
    // Implementazione del drag and drop per i task
    setupDragAndDrop();
    
    // Funzioni
    function loadAllTasks() {
        // Simulazione di una chiamata AJAX - in produzione usare fetch o axios
        fetch('/api/tasks')
            .then(response => response.json())
            .then(data => {
                allTasks = data.tasks || [];
                // Prima applicazione dei filtri (nessun filtro attivo)
                filterTasks();
            })
            .catch(error => {
                console.error('Errore nel caricamento dei task:', error);
                // Per scopi di test, generiamo alcuni task di esempio se la chiamata fallisce
                generateSampleTasks();
                filterTasks();
            });
    }
    
    function generateSampleTasks() {
        // Task di esempio per testing
        allTasks = [
            {
                id: 1,
                name: 'Progettare la Homepage',
                activity_id: 1,
                activity: { name: 'UI Design', project_id: 1, project: { name: 'Restyling Sito Web' }, resource: { name: 'Marco Rossi' } },
                description: 'Creare una bozza iniziale della homepage con tutti gli elementi richiesti dal cliente',
                status: 'pending',
                due_date: '2025-04-30',
                order: 1
            },
            {
                id: 2,
                name: 'Sviluppare componenti React',
                activity_id: 2,
                activity: { name: 'Frontend Development', project_id: 1, project: { name: 'Restyling Sito Web' }, resource: { name: 'Giulia Bianchi' } },
                description: 'Implementare i componenti React per la nuova sezione prodotti',
                status: 'in_progress',
                due_date: '2025-04-20',
                order: 1
            },
            {
                id: 3,
                name: 'Ottimizzare database',
                activity_id: 3,
                activity: { name: 'Backend Optimization', project_id: 1, project: { name: 'Restyling Sito Web' }, resource: { name: 'Luca Verdi' } },
                description: 'Ottimizzare le query e migliorare le performance del database',
                status: 'completed',
                due_date: '2025-04-15',
                order: 1
            },
            {
                id: 4,
                name: 'Implementare API REST',
                activity_id: 3,
                activity: { name: 'Backend Optimization', project_id: 1, project: { name: 'Restyling Sito Web' }, resource: { name: 'Luca Verdi' } },
                description: 'Sviluppare le API REST per il nuovo sistema di autenticazione',
                status: 'in_progress',
                due_date: '2025-04-25',
                order: 2
            },
            {
                id: 5,
                name: 'Test di sicurezza',
                activity_id: 4,
                activity: { name: 'Security Testing', project_id: 2, project: { name: 'Sistema di Autenticazione' }, resource: { name: 'Alessandra Neri' } },
                description: 'Eseguire test di penetrazione e verifica della sicurezza',
                status: 'pending',
                due_date: '2025-05-10',
                order: 1
            }
        ];
    }
    
    function updateActivityFilter(projectId) {
        // Svuota il dropdown delle attività
        filterActivity.innerHTML = '<option value="">Tutte le attività</option>';
        
        if (!projectId) return;
        
        // Raggruppa le attività per ID progetto
        const projectActivities = allTasks.reduce((acc, task) => {
            if (task.activity && task.activity.project_id == projectId) {
                if (!acc.find(a => a.id === task.activity_id)) {
                    acc.push({
                        id: task.activity_id,
                        name: task.activity.name
                    });
                }
            }
            return acc;
        }, []);
        
        // Popola il dropdown con le attività filtrate
        projectActivities.forEach(activity => {
            const option = document.createElement('option');
            option.value = activity.id;
            option.textContent = activity.name;
            filterActivity.appendChild(option);
        });
    }
    
    function filterTasks() {
        currentProjectId = filterProject.value;
        currentActivityId = filterActivity.value;
        
        // Applica i filtri
        filteredTasks = allTasks.filter(task => {
            if (currentProjectId && task.activity && task.activity.project_id != currentProjectId) {
                return false;
            }
            if (currentActivityId && task.activity_id != currentActivityId) {
                return false;
            }
            return true;
        });
        
        // Aggiorna la vista Kanban
        updateKanbanBoard();
    }
    
    function updateKanbanBoard() {
        // Pulisci tutte le colonne
        document.querySelectorAll('.kanban-tasks').forEach(column => {
            column.innerHTML = '';
        });
        
        // Raggruppare i task per stato
        const tasksByStatus = {
            pending: filteredTasks.filter(task => task.status === 'pending'),
            in_progress: filteredTasks.filter(task => task.status === 'in_progress'),
            completed: filteredTasks.filter(task => task.status === 'completed')
        };
        
        // Aggiorna i contatori
        document.getElementById('pending-count').textContent = tasksByStatus.pending.length;
        document.getElementById('progress-count').textContent = tasksByStatus.in_progress.length;
        document.getElementById('completed-count').textContent = tasksByStatus.completed.length;
        
        // Ordina i task per il campo order
        for (const status in tasksByStatus) {
            tasksByStatus[status].sort((a, b) => a.order - b.order);
        }
        
        // Popola le colonne con i task filtrati e ordinati
        for (const status in tasksByStatus) {
            const column = document.querySelector(`.kanban-tasks[data-status="${status}"]`);
            
            if (tasksByStatus[status].length === 0) {
                // Se non ci sono task per questo stato, mostra un messaggio
                column.innerHTML = `
                    <div class="empty-column-message text-center p-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Nessun task in questo stato</p>
                    </div>
                `;
                continue;
            }
            
            // Crea una card per ogni task
            tasksByStatus[status].forEach(task => {
                column.appendChild(createTaskCard(task));
            });
        }
    }
    
    function createTaskCard(task) {
        const card = document.createElement('div');
        card.className = 'task-card';
        card.dataset.taskId = task.id;
        card.setAttribute('draggable', 'true');
        
        // Determina la priorità (in base alla data di scadenza)
        const today = new Date();
        const dueDate = task.due_date ? new Date(task.due_date) : null;
        let priorityClass = '';
        
        if (dueDate) {
            const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
            if (daysDiff < 0) {
                priorityClass = 'high-priority';
            } else if (daysDiff < 7) {
                priorityClass = 'medium-priority';
            } else {
                priorityClass = 'low-priority';
            }
        }
        
        if (priorityClass) {
            card.classList.add(priorityClass);
        }
        
        // Formatta la data di scadenza
        const formattedDate = dueDate ? dueDate.toLocaleDateString('it-IT') : 'N/D';
        
        // Genera il markup della card
        card.innerHTML = `
            <div class="task-card-header">
                <h6 class="task-card-title">
                    <i class="fas fa-grip-vertical drag-handle"></i>
                    ${task.name}
                </h6>
                <div class="task-card-date">
                    <i class="far fa-calendar-alt"></i> ${formattedDate}
                </div>
            </div>
            <div class="task-card-activity">
                <i class="fas fa-tasks"></i> ${task.activity?.name || 'N/D'} • 
                <small><i class="fas fa-project-diagram"></i> ${task.activity?.project?.name || 'N/D'}</small>
            </div>
            <div class="task-card-resource">
                <div class="resource-avatar">
                    ${getInitials(task.activity?.resource?.name || 'NA')}
                </div>
                <div>
                    ${task.activity?.resource?.name || 'Non assegnato'}
                </div>
            </div>
        `;
        
        // Aggiungi event listeners per il drag and drop
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        
        return card;
    }
    
    function getInitials(name) {
        if (!name || name === 'NA') return 'NA';
        return name.split(' ').map(part => part.charAt(0)).join('').substring(0, 2).toUpperCase();
    }
    
    function showTaskDetails(taskId) {
        selectedTaskId = taskId;
        const task = allTasks.find(t => t.id == taskId);
        
        if (!task) {
            console.error('Task non trovato:', taskId);
            return;
        }
        
        const modal = document.getElementById('taskDetailsModal');
        const modalContent = document.getElementById('taskDetailsContent');
        const modalTitle = document.getElementById('taskDetailsModalLabel');
        const editBtn = document.getElementById('editTaskBtn');
        
        // Imposta il titolo del modal
        modalTitle.textContent = task.name;
        
        // Imposta l'URL per il pulsante di modifica
        editBtn.href = `/tasks/${task.id}/edit`;
        
        // Formatta la data di scadenza
        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('it-IT') : 'Non specificata';
        
        // Contenuto del modal
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <div class="task-details-section">
                        <h6>Descrizione</h6>
                        <div class="task-description">
                            ${task.description || 'Nessuna descrizione disponibile.'}
                        </div>
                    </div>
                    
                    <div class="task-details-section">
                        <h6>Stato</h6>
                        <div class="task-status-options">
                            <div class="task-status-option ${task.status === 'pending' ? 'active' : ''}" data-status="pending">
                                <i class="fas fa-clock"></i> In attesa
                            </div>
                            <div class="task-status-option ${task.status === 'in_progress' ? 'active' : ''}" data-status="in_progress">
                                <i class="fas fa-spinner"></i> In corso
                            </div>
                            <div class="task-status-option ${task.status === 'completed' ? 'active' : ''}" data-status="completed">
                                <i class="fas fa-check"></i> Completato
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="task-details-section">
                        <h6>Informazioni</h6>
                        <p><strong>Attività:</strong> ${task.activity?.name || 'N/D'}</p>
                        <p><strong>Progetto:</strong> ${task.activity?.project?.name || 'N/D'}</p>
                        <p><strong>Risorsa:</strong> ${task.activity?.resource?.name || 'Non assegnato'}</p>
                        <p><strong>Data Scadenza:</strong> ${dueDate}</p>
                    </div>
                </div>
            </div>
        `;
        
        // Aggiungi event listener per le opzioni di stato
        modalContent.querySelectorAll('.task-status-option').forEach(option => {
            option.addEventListener('click', function() {
                // Rimuovi la classe 'active' da tutte le opzioni
                modalContent.querySelectorAll('.task-status-option').forEach(opt => opt.classList.remove('active'));
                // Aggiungi la classe 'active' all'opzione selezionata
                this.classList.add('active');
            });
        });
        
        // Gestisci il click sul pulsante "Salva Stato"
        const saveStatusBtn = document.getElementById('saveTaskStatusBtn');
        saveStatusBtn.onclick = function() {
            const selectedStatus = modalContent.querySelector('.task-status-option.active').dataset.status;
            updateTaskStatus(taskId, selectedStatus);
        };
        
        // Mostra il modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    function updateTaskStatus(taskId, newStatus) {
        // Aggiorna lo stato del task localmente
        const taskIndex = allTasks.findIndex(t => t.id == taskId);
        if (taskIndex !== -1) {
            allTasks[taskIndex].status = newStatus;
            
            // In produzione, invia la modifica al server
            fetch(`/api/tasks/${taskId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Status updated successfully:', data);
                // Aggiorna la UI
                filterTasks();
                // Chiudi il modal
                bootstrap.Modal.getInstance(document.getElementById('taskDetailsModal')).hide();
            })
            .catch(error => {
                console.error('Error updating task status:', error);
                // Visualizza un messaggio di errore
                alert('Si è verificato un errore durante l\'aggiornamento dello stato del task. Riprova più tardi.');
            });
        }
    }
    
    function setupDragAndDrop() {
        const columns = document.querySelectorAll('.kanban-tasks');
        
        // Eventi per le colonne
        columns.forEach(column => {
            column.addEventListener('dragover', handleDragOver);
            column.addEventListener('dragenter', handleDragEnter);
            column.addEventListener('dragleave', handleDragLeave);
            column.addEventListener('drop', handleDrop);
        });
    }
    
    function handleDragStart(e) {
        this.classList.add('task-dragging');
        e.dataTransfer.setData('text/plain', this.dataset.taskId);
        e.dataTransfer.effectAllowed = 'move';
    }
    
    function handleDragEnd(e) {
        this.classList.remove('task-dragging');
    }
    
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }
    
    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }
    
    function handleDragLeave() {
        this.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const taskId = e.dataTransfer.getData('text/plain');
        const newStatus = this.dataset.status;
        
        // Aggiorna lo stato del task
        updateTaskStatus(taskId, newStatus);
    }
});
</script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/task-board-api.js') }}"></script>
<script src="{{ asset('js/task-board.js') }}"></script>
@endpush