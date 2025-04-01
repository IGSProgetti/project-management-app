@extends('layouts.app')

@section('title', 'Gestione Task')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Task</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuovo Task
            </a>
            <a href="{{ route('tasks.timetracking') }}" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> Gestione Tempi
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtri</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterActivity">Attività</label>
                        <select id="filterActivity" class="form-select">
                            <option value="">Tutte le attività</option>
                            @foreach($activities ?? [] as $activity)
                                <option value="{{ $activity->id }}">{{ $activity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterStatus">Stato</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tutti gli stati</option>
                            <option value="pending">In attesa</option>
                            <option value="in_progress">In corso</option>
                            <option value="completed">Completato</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterDueDate">Scadenza</label>
                        <select id="filterDueDate" class="form-select">
                            <option value="">Tutte le scadenze</option>
                            <option value="today">Oggi</option>
                            <option value="tomorrow">Domani</option>
                            <option value="week">Questa settimana</option>
                            <option value="overdue">Scaduti</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="filterResource">Risorsa</label>
                        <select id="filterResource" class="form-select">
                            <option value="">Tutte le risorse</option>
                            @foreach($resources ?? [] as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if(isset($tasks) && $tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped" id="tasksTable">
                        <thead>
                            <tr>
                                <th>Risorsa</th> <!-- Nuova colonna per la risorsa -->
                                <th>Nome</th>
                                <th>Cliente</th>
                                <th>Attività</th>
                                <th>Progetto</th>
                                <th>Stato</th>
                                <th>Min. Stimati</th>
                                <th>Min. Effettivi</th>
                                <th>Progresso</th>
                                <th>Scadenza</th>
                                <th>Timer</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr 
                                    data-activity="{{ $task->activity_id }}" 
                                    data-status="{{ $task->status }}"
                                    data-task-id="{{ $task->id }}"
                                    data-resource="{{ $task->resource_id ?? '' }}"
                                >
                                    <td>
                                        @if($task->resource)
                                            <span class="badge bg-info" data-bs-toggle="tooltip" title="{{ $task->resource->role }}">
                                                {{ $task->resource->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Non assegnato</span>
                                        @endif
                                    </td>
                                    <td>{{ $task->name }}</td>
                                    <td>{{ $task->activity->project->client->name ?? 'N/D' }}</td>
                                    <td>{{ $task->activity->name ?? 'N/D' }}</td>
                                    <td>{{ $task->activity->project->name ?? 'N/D' }}</td>
                                    <td>
                                        @if($task->status == 'pending')
                                            <span class="badge bg-warning">In attesa</span>
                                        @elseif($task->status == 'in_progress')
                                            <span class="badge bg-primary">In corso</span>
                                        @elseif($task->status == 'completed')
                                            <span class="badge bg-success">Completato</span>
                                        @endif
                                    </td>
                                    <td>{{ $task->estimated_minutes }}</td>
                                    <td>{{ $task->actual_minutes }}</td>
                                    <td>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar {{ $task->is_over_estimated ? 'bg-danger' : '' }}" role="progressbar" 
                                                 style="width: {{ $task->progress_percentage }}%" 
                                                 aria-valuenow="{{ $task->progress_percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <small>{{ $task->progress_percentage }}%</small>
                                    </td>
                                    <td>{{ $task->due_date ? $task->due_date->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if($task->status != 'completed')
                                            <div class="btn-group task-timer-group" data-task-id="{{ $task->id }}">
                                                <button type="button" class="btn btn-sm btn-success start-timer-btn" 
                                                        data-task-id="{{ $task->id }}" title="Avvia cronometro">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger stop-timer-btn" 
                                                        data-task-id="{{ $task->id }}" title="Ferma cronometro" disabled>
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                                <span class="timer-display ms-2 badge bg-light text-dark">00:00:00</span>
                                            </div>
                                        @else
                                            <span class="badge bg-secondary">Completato</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo task?');">
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
                    Nessun task disponibile. <a href="{{ route('tasks.create') }}">Crea il tuo primo task</a>.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal per Conferma salvataggio tempo -->
<div class="modal fade" id="saveTimerModal" tabindex="-1" aria-labelledby="saveTimerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveTimerModalLabel">Salva tempo cronometrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Vuoi salvare questo tempo come minuti effettivi per il task?</p>
                <p><strong>Tempo cronometrato: </strong><span id="recordedTime">00:00:00</span></p>
                <p><strong>Task: </strong><span id="taskName"></span></p>
                <input type="hidden" id="taskIdForTimer" value="">
                <input type="hidden" id="timerMinutes" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="confirmSaveTimer">Salva tempo</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inizializza tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Filtri
        const filterActivity = document.getElementById('filterActivity');
        const filterStatus = document.getElementById('filterStatus');
        const filterDueDate = document.getElementById('filterDueDate');
        const filterResource = document.getElementById('filterResource');
        const table = document.getElementById('tasksTable');
        
        function applyFilters() {
            const activityFilter = filterActivity.value;
            const statusFilter = filterStatus.value;
            const dueDateFilter = filterDueDate.value;
            const resourceFilter = filterResource.value;
            
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const activityMatch = !activityFilter || row.dataset.activity === activityFilter;
                const statusMatch = !statusFilter || row.dataset.status === statusFilter;
                const resourceMatch = !resourceFilter || row.dataset.resource === resourceFilter;
                
                // Logica per il filtro delle date di scadenza
                let dueDateMatch = true;
                if (dueDateFilter) {
                    const dueDate = new Date(row.cells[9].textContent.trim()); // Assumendo che la colonna della scadenza sia la 9
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    const tomorrow = new Date(today);
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    
                    const weekEnd = new Date(today);
                    weekEnd.setDate(weekEnd.getDate() + (7 - weekEnd.getDay()));
                    
                    if (dueDateFilter === 'today') {
                        dueDateMatch = dueDate.toDateString() === today.toDateString();
                    } else if (dueDateFilter === 'tomorrow') {
                        dueDateMatch = dueDate.toDateString() === tomorrow.toDateString();
                    } else if (dueDateFilter === 'week') {
                        dueDateMatch = dueDate >= today && dueDate <= weekEnd;
                    } else if (dueDateFilter === 'overdue') {
                        dueDateMatch = dueDate < today && row.dataset.status !== 'completed';
                    }
                }
                
                if (activityMatch && statusMatch && dueDateMatch && resourceMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        if (filterActivity) filterActivity.addEventListener('change', applyFilters);
        if (filterStatus) filterStatus.addEventListener('change', applyFilters);
        if (filterDueDate) filterDueDate.addEventListener('change', applyFilters);
        if (filterResource) filterResource.addEventListener('change', applyFilters);
        
        // Timer management
        let timers = {};
        const saveTimerModal = new bootstrap.Modal(document.getElementById('saveTimerModal'));
        
        document.querySelectorAll('.start-timer-btn').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.getAttribute('data-task-id');
                const timerGroup = document.querySelector(`.task-timer-group[data-task-id="${taskId}"]`);
                const timerDisplay = timerGroup.querySelector('.timer-display');
                const stopButton = timerGroup.querySelector('.stop-timer-btn');
                
                // Disabilita tutti gli altri pulsanti di avvio
                document.querySelectorAll('.start-timer-btn').forEach(btn => {
                    btn.disabled = true;
                });
                
                // Abilita il pulsante di stop per questo task
                stopButton.disabled = false;
                
                // Inizializza il timer
                let seconds = 0;
                timers[taskId] = setInterval(() => {
                    seconds++;
                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const secs = seconds % 60;
                    
                    timerDisplay.textContent = 
                        (hours < 10 ? '0' + hours : hours) + ':' +
                        (minutes < 10 ? '0' + minutes : minutes) + ':' +
                        (secs < 10 ? '0' + secs : secs);
                }, 1000);
            });
        });
        
        document.querySelectorAll('.stop-timer-btn').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.getAttribute('data-task-id');
                const timerGroup = document.querySelector(`.task-timer-group[data-task-id="${taskId}"]`);
                const timerDisplay = timerGroup.querySelector('.timer-display');
                
                // Ferma il timer
                clearInterval(timers[taskId]);
                
                // Riabilita tutti i pulsanti di avvio
                document.querySelectorAll('.start-timer-btn').forEach(btn => {
                    btn.disabled = false;
                });
                
                // Disabilita il pulsante di stop
                this.disabled = true;
                
                // Estrai le ore, minuti e secondi dal display
                const timeString = timerDisplay.textContent;
                const [hours, minutes, seconds] = timeString.split(':').map(Number);
                
                // Calcola i minuti totali
                const totalMinutes = hours * 60 + minutes + (seconds >= 30 ? 1 : 0);
                
                // Prepara il modal per la conferma
                document.getElementById('recordedTime').textContent = timeString;
                document.getElementById('taskName').textContent = document.querySelector(`tr[data-task-id="${taskId}"] td:nth-child(2)`).textContent;
                document.getElementById('taskIdForTimer').value = taskId;
                document.getElementById('timerMinutes').value = totalMinutes;
                
                // Mostra il modal
                saveTimerModal.show();
            });
        });
        
        // Gestione della conferma del salvataggio del tempo
        document.getElementById('confirmSaveTimer').addEventListener('click', function() {
            const taskId = document.getElementById('taskIdForTimer').value;
            const minutes = document.getElementById('timerMinutes').value;
            
            // Invia i dati al server
            fetch(`/tasks/${taskId}/update-timer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    actual_minutes: minutes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aggiorna l'interfaccia utente
                    const taskRow = document.querySelector(`tr[data-task-id="${taskId}"]`);
                    taskRow.querySelector('td:nth-child(8)').textContent = minutes;
                    
                    // Aggiorna la percentuale di progresso se necessario
                    const progressBar = taskRow.querySelector('.progress-bar');
                    const progressText = taskRow.querySelector('small');
                    if (progressBar && progressText) {
                        const estimatedMinutes = parseInt(taskRow.querySelector('td:nth-child(7)').textContent);
                        const newPercentage = Math.min(100, Math.round((minutes / estimatedMinutes) * 100));
                        progressBar.style.width = `${newPercentage}%`;
                        progressBar.setAttribute('aria-valuenow', newPercentage);
                        progressText.textContent = `${newPercentage}%`;
                        
                        // Aggiorna la classe se necessario
                        if (minutes > estimatedMinutes) {
                            progressBar.classList.add('bg-danger');
                        } else {
                            progressBar.classList.remove('bg-danger');
                        }
                    }
                    
                    // Resetta il display del timer
                    const timerDisplay = document.querySelector(`.task-timer-group[data-task-id="${taskId}"] .timer-display`);
                    timerDisplay.textContent = '00:00:00';
                    
                    // Notifica l'utente
                    alert('Tempo salvato con successo!');
                } else {
                    alert('Si è verificato un errore durante il salvataggio del tempo.');
                }
                
                saveTimerModal.hide();
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante il salvataggio del tempo.');
                saveTimerModal.hide();
            });
        });
    });
</script>
@endpush