/**
 * Script per la gestione della Task Board in stile Trello
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementi DOM principali
    const boardContainer = document.querySelector('.kanban-board');
    const filterProjectEl = document.getElementById('filterProject');
    const filterActivityEl = document.getElementById('filterActivity');
    const applyFiltersBtn = document.getElementById('applyFilters');
    
    // Dati di stato
    let allTasks = [];
    let currentProjectId = '';
    let currentActivityId = '';
    let selectedTaskId = '';
    
    // Inizializzazione
    initTaskBoard();
    
    // Funzione principale di inizializzazione
    async function initTaskBoard() {
        setupEventListeners();
        await loadTasks();
        
        // Abilita il sortable per il drag-and-drop tra colonne
        setupSortable();
    }
    
    // Carica i task dal server
    async function loadTasks() {
        try {
            allTasks = await TaskBoardApi.getTasks(currentProjectId, currentActivityId);
            renderTaskBoard();
        } catch (error) {
            console.error('Errore nel caricamento dei task:', error);
            showNotification('Errore nel caricamento dei task', 'error');
            
            // Per scopi di test, usa dati di esempio se la chiamata API fallisce
            generateSampleTasks();
            renderTaskBoard();
        }
    }
    
    // Configurazione degli event listeners
    function setupEventListeners() {
        // Gestione filtri
        if (filterProjectEl) {
            filterProjectEl.addEventListener('change', function() {
                currentProjectId = this.value;
                updateActivityOptions(currentProjectId);
            });
        }
        
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function() {
                currentActivityId = filterActivityEl ? filterActivityEl.value : '';
                loadTasks();
            });
        }
        
        // Event listener per click sulle task card
        document.querySelectorAll('.kanban-tasks').forEach(column => {
            column.addEventListener('click', handleTaskClick);
        });
        
        // Modal event listeners
        const saveStatusBtn = document.getElementById('saveTaskStatusBtn');
        if (saveStatusBtn) {
            saveStatusBtn.addEventListener('click', handleStatusChange);
        }
    }
    
    // Gestisce il click su una task card
    function handleTaskClick(e) {
        const taskCard = e.target.closest('.task-card');
        if (taskCard) {
            selectedTaskId = taskCard.dataset.taskId;
            showTaskDetails(selectedTaskId);
        }
    }
    
    // Gestisce il cambiamento di stato dal modale
    function handleStatusChange() {
        const modal = document.getElementById('taskDetailsModal');
        const selectedOption = modal.querySelector('.task-status-option.active');
        
        if (selectedOption && selectedTaskId) {
            const newStatus = selectedOption.dataset.status;
            updateTaskStatus(selectedTaskId, newStatus)
                .then(() => {
                    // Chiudi il modal e ricarica la board
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    bsModal.hide();
                })
                .catch(error => {
                    showNotification('Errore nell\'aggiornamento dello stato', 'error');
                });
        }
    }
    
    // Aggiorna le opzioni del filtro attività in base al progetto selezionato
    function updateActivityOptions(projectId) {
        if (!filterActivityEl) return;
        
        // Resetta il select delle attività
        filterActivityEl.innerHTML = '<option value="">Tutte le attività</option>';
        
        if (!projectId) return;
        
        // Raggruppa le attività per progetto
        const activityByProject = {};
        
        allTasks.forEach(task => {
            if (task.activity && task.activity.project_id == projectId) {
                activityByProject[task.activity_id] = task.activity.name;
            }
        });
        
        // Aggiungi le opzioni
        Object.entries(activityByProject).forEach(([id, name]) => {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = name;
            filterActivityEl.appendChild(option);
        });
    }
    
    // Genera i dati di esempio per il testing
    function generateSampleTasks() {
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
    
    // Aggiorna lo stato di un task
    async function updateTaskStatus(taskId, newStatus) {
        try {
            const result = await TaskBoardApi.updateTaskStatus(taskId, newStatus);
            
            // Aggiorna il task localmente
            const taskIndex = allTasks.findIndex(t => t.id == taskId);
            if (taskIndex !== -1) {
                allTasks[taskIndex].status = newStatus;
            }
            
            // Aggiorna la UI
            renderTaskBoard();
            showNotification('Stato aggiornato con successo');
            
            return result;
        } catch (error) {
            console.error('Errore nell\'aggiornamento dello stato:', error);
            throw error;
        }
    }
    
    // Mostra i dettagli del task in un modal
    function showTaskDetails(taskId) {
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
        
        // Prepara il contenuto del modal
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
        
        // Mostra il modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    // Renderizza la board con tutti i task
    function renderTaskBoard() {
        // Raggruppa i task per stato
        const tasksByStatus = {
            pending: allTasks.filter(task => task.status === 'pending'),
            in_progress: allTasks.filter(task => task.status === 'in_progress'),
            completed: allTasks.filter(task => task.status === 'completed')
        };
        
        // Aggiorna i contatori
        document.getElementById('pending-count').textContent = tasksByStatus.pending.length;
        document.getElementById('progress-count').textContent = tasksByStatus.in_progress.length;
        document.getElementById('completed-count').textContent = tasksByStatus.completed.length;
        
        // Ordina i task per il campo order
        for (const status in tasksByStatus) {
            tasksByStatus[status].sort((a, b) => a.order - b.order);
        }
        
        // Aggiorna le colonne
        for (const status in tasksByStatus) {
            updateColumn(status, tasksByStatus[status]);
        }
    }
    
    // Aggiorna una singola colonna con i suoi task
    function updateColumn(status, tasks) {
        const column = document.querySelector(`.kanban-tasks[data-status="${status}"]`);
        if (!column) return;
        
        // Svuota la colonna
        column.innerHTML = '';
        
        if (tasks.length === 0) {
            // Se non ci sono task per questo stato, mostra un messaggio
            column.innerHTML = `
                <div class="empty-column-message text-center p-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>Nessun task in questo stato</p>
                </div>
            `;
            return;
        }
        
        // Crea una card per ogni task
        tasks.forEach(task => {
            column.appendChild(createTaskCard(task));
        });
    }
    
    // Crea una card per un task
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
        
        return card;
    }
    
    // Ottiene le iniziali da un nome
    function getInitials(name) {
        if (!name || name === 'NA') return 'NA';
        return name.split(' ').map(part => part.charAt(0)).join('').substring(0, 2).toUpperCase();
    }
    
    // Configura la libreria Sortable.js per il drag-and-drop
    function setupSortable() {
        // Verifica se Sortable.js è disponibile
        if (typeof Sortable === 'undefined') {
            console.error('Sortable.js non è disponibile. Assicurati di includere la libreria.');
            showNotification('Funzionalità di drag-and-drop limitata', 'warning');
            return;
        }
        
        // Crea un Sortable per ogni colonna
        document.querySelectorAll('.kanban-tasks').forEach(column => {
            Sortable.create(column, {
                group: 'tasks',
                animation: 150,
                ghostClass: 'task-dragging',
                handle: '.drag-handle',
                onEnd: function(evt) {
                    const taskId = evt.item.dataset.taskId;
                    const newStatus = evt.to.dataset.status;
                    const oldStatus = evt.from.dataset.status;
                    
                    // Se lo stato è cambiato, aggiornalo sul server
                    if (newStatus !== oldStatus) {
                        updateTaskStatus(taskId, newStatus)
                            .catch(error => {
                                // In caso di errore, ripristina la UI
                                renderTaskBoard();
                            });
                    }
                    
                    // Riordina i task nella colonna di destinazione
                    const taskIds = Array.from(evt.to.querySelectorAll('.task-card'))
                        .map(card => card.dataset.taskId);
                    
                    // Aggiorna l'ordine sul server
                    TaskBoardApi.reorderTasks(taskIds, newStatus)
                        .catch(error => {
                            console.error('Errore nel riordinamento dei task:', error);
                            // In caso di errore, ripristina la UI
                            renderTaskBoard();
                        });
                }
            });
        });
    }
    
    // Mostra una notifica all'utente
    function showNotification(message, type = 'success') {
        // Crea elemento di notifica
        const notificationContainer = document.createElement('div');
        notificationContainer.className = `notification notification-${type}`;
        notificationContainer.textContent = message;
        
        document.body.appendChild(notificationContainer);
        
        // Rimuovi la notifica dopo 3 secondi
        setTimeout(() => {
            notificationContainer.remove();
        }, 3000);
    }
});