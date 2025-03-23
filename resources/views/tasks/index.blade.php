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
                <div class="col-md-4">
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
                <div class="col-md-4">
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
                <div class="col-md-4">
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
                                >
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

@push('styles')
<style>
    .timer-display {
        display: inline-block;
        min-width: 60px;
        font-family: monospace;
    }
    
    .task-timer-group {
        white-space: nowrap;
    }
    
    .timer-active {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
        100% {
            opacity: 1;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filtri
        const filterActivity = document.getElementById('filterActivity');
        const filterStatus = document.getElementById('filterStatus');
        const filterDueDate = document.getElementById('filterDueDate');
        const table = document.getElementById('tasksTable');
        
        if (filterActivity && filterStatus && filterDueDate && table) {
            const rows = table.querySelectorAll('tbody tr');
            
            function applyFilters() {
                const activityFilter = filterActivity.value;
                const statusFilter = filterStatus.value;
                const dueDateFilter = filterDueDate.value;
                
                rows.forEach(row => {
                    const activityMatch = !activityFilter || row.dataset.activity === activityFilter;
                    const statusMatch = !statusFilter || row.dataset.status === statusFilter;
                    
                    // Gestione filtro data di scadenza
                    let dueDateMatch = true;
                    if (dueDateFilter) {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        const tomorrow = new Date(today);
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        
                        const nextWeek = new Date(today);
                        nextWeek.setDate(nextWeek.getDate() + 7);
                        
                        const dueDateCell = row.cells[8].textContent.trim();
                        
                        if (dueDateCell !== '-') {
                            const parts = dueDateCell.split('/');
                            const dueDate = new Date(parts[2], parts[1] - 1, parts[0]);
                            dueDate.setHours(0, 0, 0, 0);
                            
                            if (dueDateFilter === 'today') {
                                dueDateMatch = dueDate.getTime() === today.getTime();
                            } else if (dueDateFilter === 'tomorrow') {
                                dueDateMatch = dueDate.getTime() === tomorrow.getTime();
                            } else if (dueDateFilter === 'week') {
                                dueDateMatch = dueDate >= today && dueDate <= nextWeek;
                            } else if (dueDateFilter === 'overdue') {
                                dueDateMatch = dueDate < today;
                            }
                        } else {
                            dueDateMatch = false;
                        }
                    }
                    
                    if (activityMatch && statusMatch && dueDateMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            filterActivity.addEventListener('change', applyFilters);
            filterStatus.addEventListener('change', applyFilters);
            filterDueDate.addEventListener('change', applyFilters);
        }

        // Gestione dei timer
        const timers = {};
        const saveTimerModal = new bootstrap.Modal(document.getElementById('saveTimerModal'));

        // Funzione per avviare un cronometro
        function startTimer(taskId) {
            if (timers[taskId]) {
                return; // Timer già in esecuzione
            }

            const timerGroup = document.querySelector(`.task-timer-group[data-task-id="${taskId}"]`);
            const startBtn = timerGroup.querySelector('.start-timer-btn');
            const stopBtn = timerGroup.querySelector('.stop-timer-btn');
            const timerDisplay = timerGroup.querySelector('.timer-display');

            // Disabilita il pulsante di avvio e abilita quello di arresto
            startBtn.disabled = true;
            stopBtn.disabled = false;

            // Stato iniziale del timer
            timers[taskId] = {
                startTime: Date.now(),
                elapsedTime: 0,
                intervalId: null,
                display: timerDisplay
            };

            // Aggiunge una classe per indicare che il timer è attivo
            timerDisplay.classList.add('timer-active');

            // Imposta un intervallo per aggiornare il timer ogni secondo
            timers[taskId].intervalId = setInterval(() => {
                updateTimerDisplay(taskId);
            }, 1000);

            // Salva lo stato del timer in localStorage
            saveTimerState(taskId);
        }

        // Funzione per fermare un cronometro
        function stopTimer(taskId) {
            if (!timers[taskId]) {
                return; // Nessun timer in esecuzione
            }

            const timerGroup = document.querySelector(`.task-timer-group[data-task-id="${taskId}"]`);
            const startBtn = timerGroup.querySelector('.start-timer-btn');
            const stopBtn = timerGroup.querySelector('.stop-timer-btn');
            const timerDisplay = timerGroup.querySelector('.timer-display');

            // Ferma l'intervallo
            clearInterval(timers[taskId].intervalId);

            // Calcola il tempo totale trascorso
            const currentTime = Date.now();
            const elapsedTime = currentTime - timers[taskId].startTime + timers[taskId].elapsedTime;
            timers[taskId].elapsedTime = elapsedTime;

            // Rimuove la classe che indica che il timer è attivo
            timerDisplay.classList.remove('timer-active');

            // Riabilita il pulsante di avvio e disabilita quello di arresto
            startBtn.disabled = false;
            stopBtn.disabled = true;

            // Salva lo stato del timer in localStorage
            saveTimerState(taskId);

            // Chiedi all'utente se vuole salvare il tempo
            showSaveTimerModal(taskId, elapsedTime);
        }

        // Funzione per aggiornare il display del timer
        function updateTimerDisplay(taskId) {
            if (!timers[taskId]) {
                return;
            }

            const currentTime = Date.now();
            const elapsedTime = currentTime - timers[taskId].startTime + timers[taskId].elapsedTime;
            
            // Calcola ore, minuti e secondi
            const totalSeconds = Math.floor(elapsedTime / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            // Formatta il tempo
            const timeString = [
                hours.toString().padStart(2, '0'),
                minutes.toString().padStart(2, '0'),
                seconds.toString().padStart(2, '0')
            ].join(':');
            
            // Aggiorna il display
            timers[taskId].display.textContent = timeString;
        }

        // Funzione per salvare lo stato dei timer in localStorage
        function saveTimerState(taskId) {
            if (!timers[taskId]) {
                return;
            }

            const timerState = {
                taskId: taskId,
                startTime: timers[taskId].startTime,
                elapsedTime: timers[taskId].elapsedTime,
                isRunning: timers[taskId].intervalId !== null,
                lastUpdated: Date.now()
            };

            localStorage.setItem(`taskTimer_${taskId}`, JSON.stringify(timerState));
        }

        // Funzione per caricare lo stato dei timer da localStorage
        function loadTimerStates() {
            document.querySelectorAll('.task-timer-group').forEach(timerGroup => {
                const taskId = timerGroup.dataset.taskId;
                const savedState = localStorage.getItem(`taskTimer_${taskId}`);
                
                if (savedState) {
                    try {
                        const state = JSON.parse(savedState);
                        const timerDisplay = timerGroup.querySelector('.timer-display');
                        const startBtn = timerGroup.querySelector('.start-timer-btn');
                        const stopBtn = timerGroup.querySelector('.stop-timer-btn');
                        
                        // Crea un oggetto timer
                        timers[taskId] = {
                            startTime: state.startTime,
                            elapsedTime: state.elapsedTime,
                            intervalId: null,
                            display: timerDisplay
                        };

                        // Se il timer era in esecuzione
                        if (state.isRunning) {
                            // Calcola il tempo trascorso mentre la pagina era chiusa
                            const timePassed = Date.now() - state.lastUpdated;
                            timers[taskId].elapsedTime += timePassed;
                            
                            // Riavvia il timer
                            timers[taskId].startTime = Date.now();
                            timers[taskId].intervalId = setInterval(() => {
                                updateTimerDisplay(taskId);
                            }, 1000);
                            
                            // Aggiorna l'interfaccia
                            startBtn.disabled = true;
                            stopBtn.disabled = false;
                            timerDisplay.classList.add('timer-active');
                        }
                        
                        // Aggiorna il display
                        updateTimerDisplay(taskId);
                    } catch (e) {
                        console.error('Errore nel caricamento dello stato del timer', e);
                    }
                }
            });
        }

        // Funzione per mostrare il modal di conferma per salvare il tempo
        function showSaveTimerModal(taskId, elapsedTime) {
            const taskRow = document.querySelector(`tr[data-task-id="${taskId}"]`);
            const taskName = taskRow.cells[0].textContent;
            const totalMinutes = Math.floor(elapsedTime / 60000);
            
            // Calcola il formato del tempo
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
            
            // Aggiorna il modal
            document.getElementById('taskName').textContent = taskName;
            document.getElementById('recordedTime').textContent = formattedTime;
            document.getElementById('taskIdForTimer').value = taskId;
            document.getElementById('timerMinutes').value = totalMinutes;
            
            // Mostra il modal
            saveTimerModal.show();
        }

        // Funzione per salvare il tempo nel database
        function saveTimerTime(taskId, minutes) {
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
                    if (taskRow) {
                        const actualMinutesCell = taskRow.cells[6]; // Colonna dei minuti effettivi
                        actualMinutesCell.textContent = minutes;
                    }
                    
                    // Mostra un messaggio di successo
                    alert('Tempo salvato con successo!');
                    
                    // Resetta il timer
                    resetTimer(taskId);
                } else {
                    alert('Errore durante il salvataggio del tempo: ' + (data.message || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Errore durante il salvataggio del tempo:', error);
                alert('Errore durante la comunicazione con il server.');
            });
        }

        // Funzione per resettare un timer
        function resetTimer(taskId) {
            if (timers[taskId]) {
                // Ferma l'intervallo se in esecuzione
                if (timers[taskId].intervalId) {
                    clearInterval(timers[taskId].intervalId);
                }
                
                // Resetta lo stato del timer
                timers[taskId].startTime = 0;
                timers[taskId].elapsedTime = 0;
                timers[taskId].intervalId = null;
                
                // Aggiorna il display
                timers[taskId].display.textContent = '00:00:00';
                timers[taskId].display.classList.remove('timer-active');
                
                // Reimposta i pulsanti
                const timerGroup = document.querySelector(`.task-timer-group[data-task-id="${taskId}"]`);
                const startBtn = timerGroup.querySelector('.start-timer-btn');
                const stopBtn = timerGroup.querySelector('.stop-timer-btn');
                startBtn.disabled = false;
                stopBtn.disabled = true;
            }
            
            // Rimuovi lo stato salvato
            localStorage.removeItem(`taskTimer_${taskId}`);
        }

        // Aggiungi event listener ai pulsanti del timer
        document.querySelectorAll('.start-timer-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                startTimer(taskId);
            });
        });

        document.querySelectorAll('.stop-timer-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                stopTimer(taskId);
            });
        });

        // Event listener per il pulsante di conferma nel modal
        document.getElementById('confirmSaveTimer').addEventListener('click', function() {
            const taskId = document.getElementById('taskIdForTimer').value;
            const minutes = document.getElementById('timerMinutes').value;
            
            // Chiudi il modal
            saveTimerModal.hide();
            
            // Salva il tempo
            saveTimerTime(taskId, minutes);
        });

        // Carica gli stati dei timer all'avvio
        loadTimerStates();
    });
</script>
@endpush