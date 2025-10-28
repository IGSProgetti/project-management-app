@extends('layouts.app')

@section('title', 'Calendario')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Calendario</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="filterEventType">Tipo Evento</label>
                    <select id="filterEventType" class="form-select">
                        <option value="all">Tutti</option>
                        <option value="projects">Solo Progetti</option>
                        <option value="activities">Solo Attività</option>
                        <option value="tasks">Solo Task</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="filterResource">Risorsa</label>
                    <select id="filterResource" class="form-select">
                        <option value="">Tutte le risorse</option>
                        @foreach($resources as $resource)
                            <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="filterStatus">Stato</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Tutti gli stati</option>
                        <option value="pending">In attesa</option>
                        <option value="in_progress">In corso</option>
                        <option value="completed">Completato</option>
                        <option value="on_hold">In pausa</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button id="applyFilters" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Applica Filtri
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Legenda più compatta e orizzontale -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <!-- Tipi di evento -->
            <div class="d-flex align-items-center me-3">
                <div class="legend-box event-type-project me-1"></div>
                <span class="me-3">Progetto</span>
                
                <div class="legend-box event-type-activity me-1"></div>
                <span class="me-3">Attività</span>
                
                <div class="legend-box event-type-task me-1"></div>
                <span>Task</span>
            </div>
            
            <div class="vr mx-2 d-none d-md-block" style="height: 24px;"></div>
            
            <!-- Stati -->
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <div class="d-flex align-items-center">
                    <span class="status-badge status-pending me-1">
                        <i class="fas fa-clock"></i>
                    </span>
                    <span class="me-3">In attesa</span>
                </div>
                
                <div class="d-flex align-items-center">
                    <span class="status-badge status-in_progress me-1">
                        <i class="fas fa-spinner"></i>
                    </span>
                    <span class="me-3">In corso</span>
                </div>
                
                <div class="d-flex align-items-center">
                    <span class="status-badge status-completed me-1">
                        <i class="fas fa-check"></i>
                    </span>
                    <span class="me-3">Completato</span>
                </div>
                
                <div class="d-flex align-items-center">
                    <span class="status-badge status-on_hold me-1">
                        <i class="fas fa-pause"></i>
                    </span>
                    <span>In pausa</span>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="card">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Modal per dettagli evento -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">Dettagli Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- I dettagli verranno caricati dinamicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <a href="#" class="btn btn-primary" id="eventDetailsLink" target="_blank">Vai ai Dettagli</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal per scegliere Attività o Task -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">
                    <i class="fas fa-calendar-plus"></i> Cosa vuoi creare?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-center mb-3">
                    <strong>Data selezionata:</strong> <span id="selectedDateDisplay"></span>
                </p>
                
                <div class="row g-3">
                    <!-- Pulsante Crea Attività -->
                    <div class="col-md-6">
                        <button type="button" class="btn btn-activity w-100 h-100 py-4" onclick="openActivityForm()">
                            <i class="fas fa-tasks fa-3x mb-3"></i>
                            <h5>Attività</h5>
                            <p class="small mb-0">Crea una nuova attività per un progetto</p>
                        </button>
                    </div>
                    
                    <!-- Pulsante Crea Task -->
                    <div class="col-md-6">
                        <button type="button" class="btn btn-task w-100 h-100 py-4" onclick="openTaskForm()">
                            <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                            <h5>Task</h5>
                            <p class="small mb-0">Crea un nuovo task per un'attività</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal per creare Attività -->
<div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="createActivityModalLabel">
                    <i class="fas fa-tasks"></i> Crea Nuova Attività
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createActivityForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Data scadenza: <strong id="activityDateDisplay"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity_name" class="form-label">Nome Attività *</label>
                        <input type="text" class="form-control" id="activity_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity_project_id" class="form-label">Progetto *</label>
                        <select class="form-select" id="activity_project_id" name="project_id" required>
                            <option value="">Seleziona un progetto...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity_area_id" class="form-label">Area *</label>
                        <select class="form-select" id="activity_area_id" name="area_id" required>
                            <option value="">Prima seleziona un progetto...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity_resource_id" class="form-label">Risorsa</label>
                        <select class="form-select" id="activity_resource_id" name="resource_id">
                            <option value="">Nessuna risorsa assegnata</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="activity_estimated_minutes" class="form-label">Minuti Stimati *</label>
                            <input type="number" class="form-control" id="activity_estimated_minutes" name="estimated_minutes" min="1" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="activity_hour_type" class="form-label">Tipo Ore *</label>
                            <select class="form-select" id="activity_hour_type" name="hour_type" required>
                                <option value="standard">Standard</option>
                                <option value="extra">Extra</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity_description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="activity_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <input type="hidden" id="activity_due_date" name="due_date">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Crea Attività
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per creare Task (COMPLETO come in tasks/create.blade.php) -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createTaskModalLabel">
                    <i class="fas fa-clipboard-list"></i> Crea Nuovo Task
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTaskForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Data scadenza: <strong id="taskDateDisplay"></strong>
                    </div>
                    
                    <!-- Sezione Cliente e Progetto -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-building"></i> Cliente e Progetto
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Selezione Cliente -->
                                <div class="col-md-6 mb-3">
                                    <label for="task_client_select">Cliente</label>
                                    <div class="input-group">
                                        <select id="task_client_select" class="form-select">
                                            <option value="">Seleziona cliente esistente</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" id="task_newClientBtn">
                                            <i class="fas fa-plus"></i> Nuovo
                                        </button>
                                    </div>
                                    
                                    <!-- Form nuovo cliente -->
                                    <div id="task_newClientForm" class="mt-3" style="display: none;">
                                        <div class="border rounded p-3 bg-light">
                                            <h6><i class="fas fa-plus-circle text-success"></i> Crea Nuovo Cliente</h6>
                                            <input type="text" id="task_new_client_name" class="form-control mb-2" placeholder="Nome cliente">
                                            <input type="number" id="task_new_client_budget" class="form-control mb-2" placeholder="Budget" value="10000">
                                            <textarea id="task_new_client_notes" class="form-control mb-2" rows="2" placeholder="Note (opzionale)"></textarea>
                                            <button type="button" class="btn btn-sm btn-success" id="task_saveNewClient">
                                                <i class="fas fa-check"></i> Salva Cliente
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" id="task_cancelNewClient">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Selezione Progetto -->
                                <div class="col-md-6 mb-3">
                                    <label for="task_project_select">Progetto</label>
                                    <div class="input-group">
                                        <select id="task_project_select" class="form-select">
                                            <option value="">Prima seleziona un cliente</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" id="task_newProjectBtn" disabled>
                                            <i class="fas fa-plus"></i> Nuovo
                                        </button>
                                    </div>
                                    
                                    <!-- Form nuovo progetto -->
                                    <div id="task_newProjectForm" class="mt-3" style="display: none;">
                                        <div class="border rounded p-3 bg-light">
                                            <h6><i class="fas fa-plus-circle text-success"></i> Crea Nuovo Progetto</h6>
                                            <input type="text" id="task_new_project_name" class="form-control mb-2" placeholder="Nome progetto">
                                            <textarea id="task_new_project_description" class="form-control mb-2" rows="2" placeholder="Descrizione (opzionale)"></textarea>
                                            <button type="button" class="btn btn-sm btn-success" id="task_saveNewProject">
                                                <i class="fas fa-check"></i> Salva Progetto
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" id="task_cancelNewProject">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selezione Attività -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="task_activity_select">Attività *</label>
                            <div class="input-group">
                                <select id="task_activity_select" name="activity_id" class="form-select" required>
                                    <option value="">Prima seleziona un progetto...</option>
                                </select>
                                <button type="button" class="btn btn-outline-success" id="task_newActivityBtn" disabled>
                                    <i class="fas fa-plus"></i> Nuova Attività
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nome Task -->
                    <div class="mb-3">
                        <label for="task_name" class="form-label">Nome Task *</label>
                        <input type="text" class="form-control" id="task_name" name="name" required>
                    </div>
                    
                    <!-- Minuti Stimati -->
                    <div class="mb-3">
                        <label for="task_estimated_minutes" class="form-label">Minuti Stimati *</label>
                        <input type="number" class="form-control" id="task_estimated_minutes" name="estimated_minutes" min="1" required>
                    </div>
                    
                    <!-- Descrizione -->
                    <div class="mb-3">
                        <label for="task_description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="task_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <input type="hidden" id="task_due_date" name="due_date">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Crea Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<link href="{{ asset('css/calendar-mobile.css') }}" rel='stylesheet' />
<style>
    #calendar {
        height: 70vh;
    }
    
    .fc-event {
        cursor: pointer;
    }
    
    .event-detail-row {
        margin-bottom: 10px;
    }
    
    .event-detail-label {
        font-weight: bold;
    }
    
    /* Stili per la legenda */
    .legend-box {
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }
    
    .event-type-project {
        background-color: #6f42c1; /* Viola per i progetti */
        border-left: 4px solid #6f42c1;
    }
    
    .event-type-activity {
        background-color: #fd7e14; /* Arancione per le attività */
        border-left: 4px solid #fd7e14;
    }
    
    .event-type-task {
        background-color: #20c997; /* Verde acqua per i task */
        border-left: 4px solid #20c997;
    }
    
    /* Badge per gli stati */
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        color: white;
        font-size: 0.8rem;
    }
    
    .status-pending {
        background-color: #ffc107;
    }
    
    .status-in_progress {
        background-color: #0d6efd;
    }
    
    .status-completed {
        background-color: #198754;
    }
    
    .status-on_hold {
        background-color: #6c757d;
    }
    
    /* Stili per gli eventi con dettagli */
    .fc-event-title {
        font-weight: bold;
        margin-bottom: 2px;
    }
    
    .event-details-preview {
        font-size: 0.8em;
        white-space: normal !important;
        overflow: visible !important;
        margin-top: 4px;
    }
    
    .event-icon {
        margin-right: 3px;
    }
    
    /* Stili per le diverse tipologie di eventi */
    .event-project {
        border-left: 4px solid #6f42c1 !important;
    }
    
    .event-activity {
        border-left: 4px solid #fd7e14 !important;
    }
    
    .event-task {
        border-left: 4px solid #20c997 !important;
    }

/* Stili per il modale di creazione */
.btn-activity {
    background: linear-gradient(135deg, #fd7e14 0%, #ff9a3c 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
    text-align: center;
}

.btn-activity:hover {
    background: linear-gradient(135deg, #ff9a3c 0%, #fd7e14 100%);
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(253, 126, 20, 0.4);
    color: white;
}

.btn-task {
    background: linear-gradient(135deg, #20c997 0%, #4dd4ac 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
    text-align: center;
}

.btn-task:hover {
    background: linear-gradient(135deg, #4dd4ac 0%, #20c997 100%);
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(32, 201, 151, 0.4);
    color: white;
}

/* Stile bottom sheet per mobile */
@media (max-width: 768px) {
    #createEventModal .modal-dialog {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0;
        max-width: 100%;
    }
    
    #createEventModal .modal-content {
        border-radius: 20px 20px 0 0;
        border: none;
    }
    
    #createEventModal .modal-body {
        padding: 20px;
    }
}

</style>
@endpush

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementi DOM per i filtri
        const filterEventType = document.getElementById('filterEventType');
        const filterResource = document.getElementById('filterResource');
        const filterStatus = document.getElementById('filterStatus');
        const applyFiltersBtn = document.getElementById('applyFilters');
        
        // Elemento DOM calendario
        const calendarEl = document.getElementById('calendar');
        
        // Funzione per ottenere il nome del tipo di evento
        function getEventTypeName(type) {
            switch(type) {
                case 'project': return 'Progetto';
                case 'activity': return 'Attività';
                case 'task': return 'Task';
                default: return type;
            }
        }
        
        // Funzione per ottenere il nome dello stato
        function getStatusName(status) {
            switch(status) {
                case 'pending': return 'In attesa';
                case 'in_progress': return 'In corso';
                case 'completed': return 'Completato';
                case 'on_hold': return 'In pausa';
                default: return status;
            }
        }
        
        // Funzione per ottenere l'icona dello stato
        function getStatusIcon(status) {
            switch(status) {
                case 'pending': return '<i class="fas fa-clock text-warning event-icon"></i>';
                case 'in_progress': return '<i class="fas fa-spinner text-primary event-icon"></i>';
                case 'completed': return '<i class="fas fa-check text-success event-icon"></i>';
                case 'on_hold': return '<i class="fas fa-pause text-secondary event-icon"></i>';
                default: return '';
            }
        }
        
        // Funzione per ottenere l'icona del tipo di evento
        function getEventTypeIcon(type) {
            switch(type) {
                case 'project': return '<i class="fas fa-project-diagram text-purple event-icon"></i>';
                case 'activity': return '<i class="fas fa-tasks text-orange event-icon"></i>';
                case 'task': return '<i class="fas fa-clipboard-list text-teal event-icon"></i>';
                default: return '';
            }
        }
        
        // Inizializza il calendario
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'it',
    
    // NUOVO: Configurazione mobile
    aspectRatio: window.innerWidth <= 768 ? 1.0 : 1.35,
    contentHeight: 'auto',
    fixedWeekCount: false,
    
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listMonth'
        },
    
    // NUOVO: Gestione click sul giorno
    dateClick: function(info) {
        showCreateEventMenu(info.dateStr);
    },
    
    events: function(info, successCallback, failureCallback) {
        
    },
            events: function(info, successCallback, failureCallback) {
                // Costruisci parametri della query
                const params = new URLSearchParams();
                params.append('event_type', filterEventType.value);
                
                if (filterResource.value) {
                    params.append('resource_id', filterResource.value);
                }
                
                if (filterStatus.value) {
                    params.append('status', filterStatus.value);
                }
                
                // Richiedi eventi tramite API
                fetch('/api/calendar/events?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        // Modifica gli eventi per aggiungere informazioni di visualizzazione
                        const enhancedEvents = data.map(event => {
                            const props = event.extendedProps;
                            let resourceInfo = '';
                            
                            if (props.type === 'project') {
                                resourceInfo = props.resources ? `<div><i class="fas fa-users text-muted event-icon"></i> ${truncateText(props.resources, 20)}</div>` : '';
                            } else {
                                resourceInfo = props.resource ? `<div><i class="fas fa-user text-muted event-icon"></i> ${props.resource}</div>` : '';
                            }
                            
                            // Crea HTML per la descrizione dell'evento
                            const htmlTitle = `
                                <div class="fc-event-title">${event.title}</div>
                                <div class="event-details-preview">
                                    ${getEventTypeIcon(props.type)} ${getEventTypeName(props.type)}
                                    ${getStatusIcon(props.status)}
                                    ${resourceInfo}
                                </div>
                            `;
                            
                            // Aggiungi classi per lo stile in base al tipo
                            const eventClasses = [];
                            
                            if (props.type === 'project') {
                                eventClasses.push('event-project');
                            } else if (props.type === 'activity') {
                                eventClasses.push('event-activity');
                            } else if (props.type === 'task') {
                                eventClasses.push('event-task');
                            }
                            
                            return {
                                ...event,
                                title: htmlTitle,
                                classNames: eventClasses,
                                // Override il colore se necessario
                                backgroundColor: getEventTypeColor(props.type)
                            };
                        });
                        
                        successCallback(enhancedEvents);
                    })
                    .catch(error => {
                        console.error('Error fetching events:', error);
                        failureCallback(error);
                    });
            },
            eventClick: function(info) {
                showEventDetails(info.event);
            },
            eventContent: function(arg) {
                return { html: arg.event.title };
            }
        });
        
        // Funzione per troncare testo lungo
        function truncateText(text, maxLength) {
            if (!text) return '';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        }
        
        // Funzione per ottenere il colore del tipo di evento
        function getEventTypeColor(type) {
            switch(type) {
                case 'project': return 'rgba(111, 66, 193, 0.85)'; // Viola
                case 'activity': return 'rgba(253, 126, 20, 0.85)'; // Arancione
                case 'task': return 'rgba(32, 201, 151, 0.85)'; // Verde acqua
                default: return null; // Usa il colore dello stato
            }
        }
        
        // Rendering iniziale del calendario
        calendar.render();
        
        // Listener per il pulsante di applica filtri
        applyFiltersBtn.addEventListener('click', function() {
            calendar.refetchEvents();
        });
        
        // Funzione per mostrare i dettagli dell'evento in un modal
        function showEventDetails(event) {
            const eventData = event._def;
            if (!eventData || !eventData.extendedProps) return;
            
            const props = eventData.extendedProps;
            const modalTitle = document.getElementById('eventDetailsModalLabel');
            const modalContent = document.getElementById('eventDetailsContent');
            const eventLink = document.getElementById('eventDetailsLink');
            
            // Estrai il titolo originale (senza HTML)
            const titleMatch = eventData.title.match(/<div class="fc-event-title">(.*?)<\/div>/);
            const originalTitle = titleMatch ? titleMatch[1] : eventData.title;
            
            // Imposta il titolo del modal
            modalTitle.textContent = originalTitle;
            
            // Prepara il contenuto del modal in base al tipo di evento
            let content = `
                <div class="event-detail-row">
                    <div class="event-detail-label">Tipo:</div>
                    <div>${getEventTypeName(props.type)}</div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Data Scadenza:</div>
                    <div>${new Date(event.start).toLocaleDateString('it-IT')}</div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Stato:</div>
                    <div>
                        <span class="event-status status-${props.status}">
                            ${getStatusName(props.status)}
                        </span>
                    </div>
                </div>
            `;
            
            // Aggiungi dettagli specifici in base al tipo di evento
            if (props.type === 'project') {
                content += `
                    <div class="event-detail-row">
                        <div class="event-detail-label">Cliente:</div>
                        <div>${props.client}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Risorse:</div>
                        <div>${props.resources}</div>
                    </div>
                `;
                
                if (props.description) {
                    content += `
                        <div class="event-detail-row">
                            <div class="event-detail-label">Descrizione:</div>
                            <div>${props.description}</div>
                        </div>
                    `;
                }
            } else {
                content += `
                    <div class="event-detail-row">
                        <div class="event-detail-label">Progetto:</div>
                        <div>${props.project}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Risorsa:</div>
                        <div>${props.resource}</div>
                    </div>
                `;
                
                if (props.type === 'task') {
                    content += `
                        <div class="event-detail-row">
                            <div class="event-detail-label">Attività:</div>
                            <div>${props.activity}</div>
                        </div>
                    `;
                }
            }
            
            // Imposta il contenuto del modal
            modalContent.innerHTML = content;
            
            // Imposta il link per i dettagli completi
            eventLink.href = props.url;
            
            // Mostra il modal
            const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            modal.show();
        }
    });

    // Variabile globale per memorizzare la data selezionata
        let selectedDate = '';
        
        // Funzione per mostrare il menu di creazione
        function showCreateEventMenu(dateStr) {
            selectedDate = dateStr;
            
            // Formatta la data in italiano
            const date = new Date(dateStr);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = date.toLocaleDateString('it-IT', options);
            
            // Mostra la data nel modale
            document.getElementById('selectedDateDisplay').textContent = formattedDate;
            
            // Apri il modale
            const modal = new bootstrap.Modal(document.getElementById('createEventModal'));
            modal.show();
        }
        
        // Funzione per aprire il form delle attività
        window.openActivityForm = function() {
            // Chiudi il modale di scelta
            const choiceModal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
            choiceModal.hide();
            
            // Formatta la data
            const date = new Date(selectedDate);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = date.toLocaleDateString('it-IT', options);
            
            // Imposta la data nel form
            document.getElementById('activity_due_date').value = selectedDate;
            document.getElementById('activityDateDisplay').textContent = formattedDate;
            
            // Reset form
            document.getElementById('createActivityForm').reset();
            document.getElementById('activity_due_date').value = selectedDate;
            
            // Carica i progetti
            loadProjectsForActivity();
            
            // Carica le risorse
            loadResourcesForActivity();
            
            // Apri il modale dell'attività
            const activityModal = new bootstrap.Modal(document.getElementById('createActivityModal'));
            activityModal.show();
        }
        
        // ============================================
        // FORM TASK COMPLETO
        // ============================================
        
        // Funzione per aprire il form dei task
        window.openTaskForm = function() {
            // Chiudi il modale di scelta
            const choiceModal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
            choiceModal.hide();
            
            // Formatta la data
            const date = new Date(selectedDate);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = date.toLocaleDateString('it-IT', options);
            
            // Imposta la data nel form
            document.getElementById('task_due_date').value = selectedDate;
            document.getElementById('taskDateDisplay').textContent = formattedDate;
            
            // Reset form
            document.getElementById('createTaskForm').reset();
            document.getElementById('task_due_date').value = selectedDate;
            
            // Reset delle select
            document.getElementById('task_project_select').innerHTML = '<option value="">Prima seleziona un cliente</option>';
            document.getElementById('task_activity_select').innerHTML = '<option value="">Prima seleziona un progetto...</option>';
            
            // Carica i clienti
            loadClientsForTask();

            // Attendi che il modale sia nel DOM, poi aggiungi il listener per "Nuova Attività"
            setTimeout(function() {
                const taskNewActivityBtn = document.getElementById('task_newActivityBtn');
                if (taskNewActivityBtn && !taskNewActivityBtn.hasAttribute('data-listener-added')) {
                    taskNewActivityBtn.setAttribute('data-listener-added', 'true');
                    
                    taskNewActivityBtn.addEventListener('click', function() {
                        const projectId = document.getElementById('task_project_select').value;
                        
                        if (!projectId) {
                            alert('Seleziona prima un progetto');
                            return;
                        }
                        
                        const activityName = prompt('Inserisci il nome della nuova attività:');
                        
                        if (!activityName || activityName.trim() === '') {
                            return;
                        }
                        
                        const estimatedMinutes = prompt('Inserisci i minuti stimati per questa attività:', '60');
                        
                        if (!estimatedMinutes || isNaN(estimatedMinutes)) {
                            alert('Devi inserire un numero valido di minuti');
                            return;
                        }
                        
                        // Crea l'attività via AJAX
                        fetch('/tasks/create-activity', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                name: activityName.trim(),
                                project_id: projectId,
                                estimated_minutes: parseInt(estimatedMinutes),
                                hours_type: 'standard'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const activitySelect = document.getElementById('task_activity_select');
                                const option = document.createElement('option');
                                option.value = data.activity.id;
                                option.textContent = data.activity.name + ' [Creata ora]';
                                option.selected = true;
                                activitySelect.appendChild(option);
                                
                                alert('Attività creata con successo!');
                            } else {
                                alert('Errore nella creazione dell\'attività: ' + (data.message || 'Errore sconosciuto'));
                            }
                        })
                        .catch(error => {
                            console.error('Errore:', error);
                            alert('Errore nella comunicazione con il server');
                        });
                    });
                }
            }, 100);
            
            // Apri il modale del task
            const taskModal = new bootstrap.Modal(document.getElementById('createTaskModal'));
            taskModal.show();
        }
        
        // Carica i clienti per il form task
        function loadClientsForTask() {
            fetch('/api/clients')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('task_client_select');
                    select.innerHTML = '<option value="">Seleziona cliente esistente</option>';
                    
                    data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.id;
                        option.textContent = client.name;
                        if (client.created_from_tasks) {
                            option.textContent += ' [Creato da Tasks]';
                        }
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Errore nel caricamento dei clienti:', error);
                    alert('Errore nel caricamento dei clienti');
                });
        }
        
        // Gestione pulsante nuovo cliente (TASK)
        document.getElementById('task_newClientBtn').addEventListener('click', function() {
            const form = document.getElementById('task_newClientForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                document.getElementById('task_new_client_name').focus();
            }
        });
        
        // Annulla nuovo cliente (TASK)
        document.getElementById('task_cancelNewClient').addEventListener('click', function() {
            document.getElementById('task_newClientForm').style.display = 'none';
            document.getElementById('task_new_client_name').value = '';
            document.getElementById('task_new_client_budget').value = '10000';
            document.getElementById('task_new_client_notes').value = '';
        });
        
        // Salva nuovo cliente (TASK)
        document.getElementById('task_saveNewClient').addEventListener('click', function() {
            const clientName = document.getElementById('task_new_client_name').value.trim();
            const clientBudget = document.getElementById('task_new_client_budget').value;
            const clientNotes = document.getElementById('task_new_client_notes').value;

            if (!clientName) {
                alert('Il nome del cliente è obbligatorio');
                return;
            }

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
                    const select = document.getElementById('task_client_select');
                    const option = document.createElement('option');
                    option.value = data.client.id;
                    option.textContent = data.client.name + ' [Creato da Tasks]';
                    option.selected = true;
                    select.appendChild(option);
                    
                    document.getElementById('task_newClientForm').style.display = 'none';
                    document.getElementById('task_new_client_name').value = '';
                    document.getElementById('task_new_client_budget').value = '10000';
                    document.getElementById('task_new_client_notes').value = '';
                    
                    document.getElementById('task_newProjectBtn').disabled = false;
                    loadProjectsForTaskByClient(data.client.id);
                } else {
                    alert('Errore nella creazione del cliente: ' + (data.message || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella comunicazione con il server');
            });
        });
        
        // Cambio cliente (TASK)
        document.getElementById('task_client_select').addEventListener('change', function() {
            const clientId = this.value;
            const newProjectBtn = document.getElementById('task_newProjectBtn');
            const projectSelect = document.getElementById('task_project_select');
            const activitySelect = document.getElementById('task_activity_select');
            
            newProjectBtn.disabled = !clientId;
            
            if (clientId) {
                loadProjectsForTaskByClient(clientId);
            } else {
                projectSelect.innerHTML = '<option value="">Prima seleziona un cliente</option>';
                activitySelect.innerHTML = '<option value="">Prima seleziona un progetto...</option>';
                document.getElementById('task_newActivityBtn').disabled = true;
            }
        });
        
        // Carica progetti per cliente (TASK)
        function loadProjectsForTaskByClient(clientId) {
            const projectSelect = document.getElementById('task_project_select');
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
                })
                .catch(error => {
                    console.error('Errore:', error);
                    projectSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                });
        }
        
        // Gestione pulsante nuovo progetto (TASK)
        document.getElementById('task_newProjectBtn').addEventListener('click', function() {
            const form = document.getElementById('task_newProjectForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                document.getElementById('task_new_project_name').focus();
            }
        });
        
        // Annulla nuovo progetto (TASK)
        document.getElementById('task_cancelNewProject').addEventListener('click', function() {
            document.getElementById('task_newProjectForm').style.display = 'none';
            document.getElementById('task_new_project_name').value = '';
            document.getElementById('task_new_project_description').value = '';
        });
        
        // Salva nuovo progetto (TASK)
        document.getElementById('task_saveNewProject').addEventListener('click', function() {
            const projectName = document.getElementById('task_new_project_name').value.trim();
            const projectDescription = document.getElementById('task_new_project_description').value;
            const clientId = document.getElementById('task_client_select').value;

            if (!projectName) {
                alert('Il nome del progetto è obbligatorio');
                return;
            }

            if (!clientId) {
                alert('Seleziona prima un cliente');
                return;
            }

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
                    const projectSelect = document.getElementById('task_project_select');
                    const option = document.createElement('option');
                    option.value = data.project.id;
                    option.textContent = data.project.name + ' [Creato da Tasks]';
                    option.selected = true;
                    projectSelect.appendChild(option);
                    
                    document.getElementById('task_newProjectForm').style.display = 'none';
                    document.getElementById('task_new_project_name').value = '';
                    document.getElementById('task_new_project_description').value = '';
                    
                    document.getElementById('task_newActivityBtn').disabled = false;
                    loadActivitiesForTaskByProject(data.project.id);
                } else {
                    alert('Errore nella creazione del progetto: ' + (data.message || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella comunicazione con il server');
            });
        });
        
        // Cambio progetto (TASK)
        document.getElementById('task_project_select').addEventListener('change', function() {
            const projectId = this.value;
            document.getElementById('task_newActivityBtn').disabled = !projectId;
            
            if (projectId) {
                loadActivitiesForTaskByProject(projectId);
            } else {
                document.getElementById('task_activity_select').innerHTML = '<option value="">Prima seleziona un progetto...</option>';
            }
        });
        
        // Carica attività per progetto (TASK)
        function loadActivitiesForTaskByProject(projectId) {
            const activitySelect = document.getElementById('task_activity_select');
            activitySelect.innerHTML = '<option value="">Caricamento...</option>';
            
            fetch(`/api/activities-by-project/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    activitySelect.innerHTML = '<option value="">Seleziona un\'attività</option>';
                    
                    if (data.activities && data.activities.length > 0) {
                        data.activities.forEach(activity => {
                            const option = document.createElement('option');
                            option.value = activity.id;
                            option.textContent = activity.name;
                            activitySelect.appendChild(option);
                        });
                    } else {
                        activitySelect.innerHTML = '<option value="">Nessuna attività disponibile</option>';
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    activitySelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                });
        }
        
        // Gestione submit form task
        document.getElementById('createTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('task_name').value,
                activity_id: document.getElementById('task_activity_select').value,
                estimated_minutes: document.getElementById('task_estimated_minutes').value,
                due_date: document.getElementById('task_due_date').value,
                description: document.getElementById('task_description').value,
                status: 'pending'
            };
            
            fetch('/tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Chiudi il modale
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createTaskModal'));
                    modal.hide();
                    
                    // Ricarica gli eventi del calendario
                    calendar.refetchEvents();
                    
                    // Mostra messaggio di successo
                    alert('Task creato con successo!');
                } else {
                    alert('Errore nella creazione del task: ' + (data.message || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella comunicazione con il server');
            });
        });
        
        // ============================================
        // FUNZIONI PER CARICARE I DATI NELLE SELECT
        // ============================================
        
        // Carica progetti per il form attività
        function loadProjectsForActivity() {
            fetch('/api/projects')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('activity_project_id');
                    select.innerHTML = '<option value="">Seleziona un progetto...</option>';
                    
                    data.forEach(project => {
                        const option = document.createElement('option');
                        option.value = project.id;
                        option.textContent = project.name + ' (' + project.client_name + ')';
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Errore nel caricamento dei progetti:', error);
                    alert('Errore nel caricamento dei progetti');
                });
        }
        
        // Carica risorse per il form attività
        function loadResourcesForActivity() {
            fetch('/api/resources')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('activity_resource_id');
                    select.innerHTML = '<option value="">Nessuna risorsa assegnata</option>';
                    
                    data.forEach(resource => {
                        const option = document.createElement('option');
                        option.value = resource.id;
                        option.textContent = resource.name + ' (' + resource.role + ')';
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Errore nel caricamento delle risorse:', error);
                });
        }
        
        // Carica progetti per il form task
        function loadProjectsForTask() {
            fetch('/api/projects')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('task_project_id');
                    select.innerHTML = '<option value="">Seleziona un progetto...</option>';
                    
                    data.forEach(project => {
                        const option = document.createElement('option');
                        option.value = project.id;
                        option.textContent = project.name + ' (' + project.client_name + ')';
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Errore nel caricamento dei progetti:', error);
                    alert('Errore nel caricamento dei progetti');
                });
        }
        
        // Quando si seleziona un progetto nel form attività, carica le aree
        document.getElementById('activity_project_id').addEventListener('change', function() {
            const projectId = this.value;
            const areaSelect = document.getElementById('activity_area_id');
            
            if (!projectId) {
                areaSelect.innerHTML = '<option value="">Prima seleziona un progetto...</option>';
                return;
            }
            
            areaSelect.innerHTML = '<option value="">Caricamento...</option>';
            
            fetch(`/api/areas-by-project/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    areaSelect.innerHTML = '<option value="">Seleziona un\'area...</option>';
                    
                    if (data.areas && data.areas.length > 0) {
                        data.areas.forEach(area => {
                            const option = document.createElement('option');
                            option.value = area.id;
                            option.textContent = area.name;
                            areaSelect.appendChild(option);
                        });
                    } else {
                        areaSelect.innerHTML = '<option value="">Nessuna area disponibile</option>';
                    }
                })
                .catch(error => {
                    console.error('Errore nel caricamento delle aree:', error);
                    areaSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                });
        });
        
        // Quando si seleziona un progetto nel form task, carica le attività
        document.getElementById('task_project_id').addEventListener('change', function() {
            const projectId = this.value;
            const activitySelect = document.getElementById('task_activity_id');
            
            if (!projectId) {
                activitySelect.innerHTML = '<option value="">Prima seleziona un progetto...</option>';
                return;
            }
            
            activitySelect.innerHTML = '<option value="">Caricamento...</option>';
            
            fetch(`/api/activities-by-project/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    activitySelect.innerHTML = '<option value="">Seleziona un\'attività...</option>';
                    
                    if (data.activities && data.activities.length > 0) {
                        data.activities.forEach(activity => {
                            const option = document.createElement('option');
                            option.value = activity.id;
                            option.textContent = activity.name;
                            activitySelect.appendChild(option);
                        });
                    } else {
                        activitySelect.innerHTML = '<option value="">Nessuna attività disponibile</option>';
                    }
                })
                .catch(error => {
                    console.error('Errore nel caricamento delle attività:', error);
                    activitySelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                });
        });
</script>
@endpush