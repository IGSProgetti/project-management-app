/**
 * Classe per gestire le chiamate API della Task Board
 */
class TaskBoardApi {
    /**
     * Carica tutti i task con filtri opzionali
     * @param {string|null} projectId - ID del progetto per filtrare
     * @param {string|null} activityId - ID dell'attività per filtrare
     * @returns {Promise<Array>} - Promise con i task
     */
    static async getTasks(projectId = null, activityId = null) {
        let url = '/api/tasks';
        const params = [];
        
        if (projectId) {
            params.push(`project_id=${projectId}`);
        }
        
        if (activityId) {
            params.push(`activity_id=${activityId}`);
        }
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                return data.tasks;
            } else {
                console.error('Errore nel caricamento dei task:', data.errors);
                return [];
            }
        } catch (error) {
            console.error('Errore nella richiesta API:', error);
            return [];
        }
    }
    
    /**
     * Aggiorna lo stato di un task
     * @param {string} taskId - ID del task da aggiornare
     * @param {string} newStatus - Nuovo stato (pending, in_progress, completed)
     * @returns {Promise<Object>} - Promise con il task aggiornato
     */
    static async updateTaskStatus(taskId, newStatus) {
        try {
            const response = await fetch(`/api/tasks/${taskId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: newStatus })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                console.error('Errore nell\'aggiornamento dello stato:', data.errors);
                throw new Error('Errore nell\'aggiornamento dello stato');
            }
        } catch (error) {
            console.error('Errore nella richiesta API:', error);
            throw error;
        }
    }
    
    /**
     * Riordina i task in una colonna
     * @param {Array<string>} taskIds - Array di ID dei task ordinati
     * @param {string} status - Stato della colonna
     * @returns {Promise<Object>} - Promise con risultato operazione
     */
    static async reorderTasks(taskIds, status) {
        try {
            const response = await fetch('/api/tasks/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ 
                    tasks: taskIds,
                    status: status
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                console.error('Errore nel riordinamento dei task:', data.errors);
                throw new Error('Errore nel riordinamento dei task');
            }
        } catch (error) {
            console.error('Errore nella richiesta API:', error);
            throw error;
        }
    }
}