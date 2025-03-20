// TaskTimer.js - Script per gestire il cronometro del task

class TaskTimer {
    constructor(elementId, taskId) {
        this.elementId = elementId;
        this.taskId = taskId;
        this.timerElement = document.getElementById(elementId);
        this.startBtn = document.getElementById('startTimerBtn');
        this.stopBtn = document.getElementById('stopTimerBtn');
        this.resetBtn = document.getElementById('resetTimerBtn');
        this.actualMinutesInput = document.getElementById('actual_minutes');
        
        this.seconds = 0;
        this.minutes = 0;
        this.hours = 0;
        this.timerInterval = null;
        this.isRunning = false;
        this.startTime = null;
        this.elapsedTime = 0;

        // Recupera lo stato del timer da localStorage se disponibile
        this.loadTimerState();
        
        // Inizializza i pulsanti
        this.initButtons();
        
        // Aggiorna il display iniziale
        this.updateDisplay();
    }

    initButtons() {
        if (this.startBtn) {
            this.startBtn.addEventListener('click', () => this.start());
        }
        
        if (this.stopBtn) {
            this.stopBtn.addEventListener('click', () => this.stop());
        }
        
        if (this.resetBtn) {
            this.resetBtn.addEventListener('click', () => this.reset());
        }
        
        // Aggiorna lo stato dei pulsanti
        this.updateButtonState();
    }

    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.startTime = Date.now() - this.elapsedTime;
        
        this.timerInterval = setInterval(() => {
            this.elapsedTime = Date.now() - this.startTime;
            this.updateTimeValues();
            this.updateDisplay();
        }, 1000);
        
        this.updateButtonState();
        this.saveTimerState();
    }

    stop() {
        if (!this.isRunning) return;
        
        clearInterval(this.timerInterval);
        this.isRunning = false;
        
        // Quando si stoppa il timer, aggiorna i minuti effettivi nel form
        if (this.actualMinutesInput) {
            // Converti il tempo totale in minuti
            const totalMinutes = Math.floor(this.elapsedTime / 60000);
            this.actualMinutesInput.value = totalMinutes;
        }
        
        this.updateButtonState();
        this.saveTimerState();
        
        // Chiedi conferma all'utente
        if (confirm('Vuoi salvare questo tempo (' + this.formatTime() + ') come minuti effettivi per questo task?')) {
            this.saveActualMinutes();
        }
    }

    reset() {
        clearInterval(this.timerInterval);
        this.isRunning = false;
        this.elapsedTime = 0;
        this.seconds = 0;
        this.minutes = 0;
        this.hours = 0;
        
        this.updateDisplay();
        this.updateButtonState();
        this.saveTimerState();
    }

    updateTimeValues() {
        // Calcola ore, minuti e secondi dall'elapsed time (che è in millisecondi)
        this.seconds = Math.floor((this.elapsedTime / 1000) % 60);
        this.minutes = Math.floor((this.elapsedTime / (1000 * 60)) % 60);
        this.hours = Math.floor(this.elapsedTime / (1000 * 60 * 60));
    }

    updateDisplay() {
        if (this.timerElement) {
            this.timerElement.textContent = this.formatTime();
        }
    }
    
    formatTime() {
        return `${this.hours.toString().padStart(2, '0')}:${this.minutes.toString().padStart(2, '0')}:${this.seconds.toString().padStart(2, '0')}`;
    }
    
    updateButtonState() {
        if (this.startBtn) {
            this.startBtn.disabled = this.isRunning;
        }
        
        if (this.stopBtn) {
            this.stopBtn.disabled = !this.isRunning;
        }
    }
    
    saveTimerState() {
        const state = {
            taskId: this.taskId,
            isRunning: this.isRunning,
            elapsedTime: this.elapsedTime,
            startTime: this.startTime,
            lastUpdated: Date.now()
        };
        
        localStorage.setItem(`taskTimer_${this.taskId}`, JSON.stringify(state));
    }
    
    loadTimerState() {
        const savedState = localStorage.getItem(`taskTimer_${this.taskId}`);
        
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                
                // Se il timer era in esecuzione quando la pagina è stata chiusa
                if (state.isRunning) {
                    // Calcola il tempo trascorso tenendo conto del tempo passato mentre la pagina era chiusa
                    const timePassed = Date.now() - state.lastUpdated;
                    this.elapsedTime = state.elapsedTime + timePassed;
                    this.isRunning = false; // Inizia fermato per sicurezza
                } else {
                    this.elapsedTime = state.elapsedTime;
                    this.isRunning = false;
                }
                
                this.updateTimeValues();
            } catch (e) {
                console.error('Errore nel caricamento dello stato del timer', e);
                this.reset();
            }
        }
    }
    
    saveActualMinutes() {
        // Calcola i minuti totali dal tempo cronometrato
        const totalMinutes = Math.floor(this.elapsedTime / 60000);
        
       // Crea un form nascosto
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/tasks/${this.taskId}/update-timer`;
    
    // Aggiungi il token CSRF
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Aggiungi i minuti
    const minutesInput = document.createElement('input');
    minutesInput.type = 'hidden';
    minutesInput.name = 'actual_minutes';
    minutesInput.value = totalMinutes;
    
    // Aggiungi gli input al form
    form.appendChild(csrfInput);
    form.appendChild(minutesInput);
    
    // Aggiungi il form al documento e invialo
    document.body.appendChild(form);
    form.submit();
}
    
    // Converte il tempo totale in minuti
    getTotalMinutes() {
        return Math.floor(this.elapsedTime / 60000);
    }
}

// Inizializza il timer quando il documento è pronto
document.addEventListener('DOMContentLoaded', function() {
    const taskIdElement = document.getElementById('taskId');
    
    if (taskIdElement) {
        const taskId = taskIdElement.value;
        const taskTimer = new TaskTimer('taskTimer', taskId);
        
        // Esponi l'istanza del timer a livello globale per l'accesso da altri script
        window.taskTimer = taskTimer;
    }
    
});