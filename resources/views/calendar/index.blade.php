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
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            buttonText: {
                today: 'Oggi',
                month: 'Mese',
                week: 'Settimana',
                list: 'Lista'
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
</script>
@endpush