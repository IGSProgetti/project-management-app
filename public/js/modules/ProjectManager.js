/**
 * ProjectManager.js - Gestione completa dei progetti
 */
class ProjectManager {
    constructor() {
        this.selectedResources = new Set();
        this.costSteps = new Set([1,2,3,4,5,6,7,8]); // tutti gli step attivi di default
        this.currentPage = 1;
        this.editingProjectId = null; // ID del progetto in fase di modifica
    }

    /**
     * Inizializza il gestore progetti
     */
    initialize() {
        console.log('Initializing ProjectManager');
        this.currentPage = 1;
        this.loadClients();
        this.loadResources();
        this.loadProjects();
        this.bindEvents();
    }

    /**
     * Collega gli eventi agli elementi dell'interfaccia
     */
    bindEvents() {
        // Eventi per gli step di costo
        document.querySelectorAll('.cost-steps input[type="checkbox"], .step-list input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const stepNumber = parseInt(e.target.id.replace('step', ''));
                if (checkbox.checked) {
                    this.costSteps.add(stepNumber);
                } else {
                    this.costSteps.delete(stepNumber);
                }
                this.updateCostSummary();
            });
        });

        // Eventi per le ore delle risorse
        document.querySelectorAll('.resource-hours-input').forEach(input => {
            input.addEventListener('input', () => this.updateCostSummary());
        });
    }

    /**
     * Carica i clienti nel selettore
     */
    loadClients() {
        const clientSelect = document.getElementById('clientSelect');
        if (!clientSelect) return;

        const clients = window.Database.getAllClients();
        clientSelect.innerHTML = `
            <option value="">Seleziona un cliente</option>
            ${clients.map(client => `
                <option value="${client.id}">${client.name}</option>
            `).join('')}
        `;
    }

    /**
     * Carica le risorse disponibili
     */
    loadResources() {
        const container = document.getElementById('resourcesSelection');
        if (!container) return;

        const resources = window.Database.getAllResources();
        container.innerHTML = resources.map(resource => `
            <div class="resource-item" data-resource-id="${resource.id}">
                <div class="resource-header">
                    <label>
                        <input type="checkbox" 
                               class="resource-checkbox"
                               data-resource-id="${resource.id}"
                               ${this.selectedResources.has(resource.id) ? 'checked' : ''}>
                        ${resource.name} - ${resource.role}
                    </label>
                </div>
                <div class="resource-details">
                    <p>Costo Orario: ${resource.sellingPrice ? resource.sellingPrice.toFixed(2) : '0.00'} €/h</p>
                    <div class="resource-hours" style="display: ${this.selectedResources.has(resource.id) ? 'block' : 'none'}">
                        <label>Ore previste:</label>
                        <input type="number" 
                               class="form-control resource-hours-input"
                               min="0"
                               value="0"
                               onchange="window.projectManager.updateCostSummary()">
                    </div>
                </div>
            </div>
        `).join('');

        // Aggiungi event listeners per le checkbox
        container.querySelectorAll('.resource-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const resourceId = e.target.dataset.resourceId;
                this.toggleResource(resourceId, e.target.checked);
            });
        });
    }

    /**
     * Attiva/disattiva una risorsa selezionata
     */
    toggleResource(resourceId, checked) {
        const resourceElement = document.querySelector(`[data-resource-id="${resourceId}"]`);
        const hoursContainer = resourceElement?.querySelector('.resource-hours');
        
        if (checked) {
            this.selectedResources.add(resourceId);
            if (hoursContainer) hoursContainer.style.display = 'block';
        } else {
            this.selectedResources.delete(resourceId);
            if (hoursContainer) hoursContainer.style.display = 'none';
        }
        
        this.updateCostSummary();
    }

    /**
     * Aggiorna il riepilogo dei costi
     */
    updateCostSummary() {
        const summaryContainer = document.getElementById('costSummary');
        if (!summaryContainer) return;

        let totalCost = 0;
        const summary = Array.from(this.selectedResources).map(resourceId => {
            const resource = window.Database.getResourceById(resourceId);
            const hoursInput = document.querySelector(`[data-resource-id="${resourceId}"] .resource-hours-input`);
            const hours = parseFloat(hoursInput?.value || 0);
            const adjustedRate = this.calculateAdjustedRate(resource.sellingPrice);
            const cost = adjustedRate * hours;
            totalCost += cost;

            return `
                <div class="cost-item">
                    <div>${resource.name}</div>
                    <div>${hours}h x ${adjustedRate.toFixed(2)}€/h = ${cost.toFixed(2)}€</div>
                </div>
            `;
        }).join('');

        summaryContainer.innerHTML = summary;
        const totalElement = document.getElementById('totalProjectCost');
        if (totalElement) {
            totalElement.textContent = `${totalCost.toFixed(2)} €`;
        }
    }

    /**
     * Calcola la tariffa oraria in base agli step di costo selezionati
     */
    calculateAdjustedRate(baseRate) {
        const deselectedSteps = Array.from({ length: 8 }, (_, i) => i + 1)
            .filter(step => !this.costSteps.has(step));
        
        const stepValues = {
            1: 25,    // Costo struttura
            2: 12.5,  // Utile gestore azienda
            3: 12.5,  // Utile IGS
            4: 20,    // Compenso professionista
            5: 5,     // Bonus professionista
            6: 3,     // Gestore società
            7: 8,     // Chi porta il lavoro
            8: 14     // Network IGS
        };

        const totalDeduction = deselectedSteps.reduce((sum, step) => sum + stepValues[step], 0);
        return baseRate * (1 - totalDeduction / 100);
    }

    /**
     * Salva un nuovo progetto o aggiorna uno esistente
     */
    saveProject() {
        const form = document.getElementById('projectForm');
        const isEditMode = form.dataset.editMode === 'true';
        const projectId = form.dataset.projectId;
        
        const name = document.getElementById('projectName')?.value?.trim();
        const description = document.getElementById('projectDescription')?.value?.trim();
        const clientId = document.getElementById('clientSelect')?.value;
        const startDate = document.getElementById('start_date')?.value;
        const endDate = document.getElementById('end_date')?.value;
        const status = document.getElementById('status')?.value;

        if (!this.validateProjectData(name, clientId)) return;

        // Preparazione dati del progetto
        const projectData = {
            name,
            description,
            clientId,
            costSteps: Array.from(this.costSteps),
            status: status || 'pending',
            resources: Array.from(this.selectedResources).map(resourceId => {
                const resource = window.Database.getResourceById(resourceId);
                const hoursInput = document.querySelector(`[data-resource-id="${resourceId}"] .resource-hours-input`);
                const hours = parseFloat(hoursInput?.value || 0);
                const adjustedRate = this.calculateAdjustedRate(resource.sellingPrice);
                return {
                    id: resourceId,
                    name: resource.name,
                    role: resource.role,
                    hours,
                    adjustedRate,
                    cost: hours * adjustedRate
                };
            })
        };

        // Aggiungi le date solo se specificate
        if (startDate) {
            projectData.start_date = startDate;
        }
        
        if (endDate) {
            projectData.end_date = endDate;
        }

        console.log('Dati progetto prima del salvataggio:', projectData);

        try {
            let savedProject;
            
            if (isEditMode && projectId) {
                // Aggiorna il progetto esistente
                savedProject = window.Database.updateProject(projectId, projectData);
                console.log('Progetto aggiornato:', savedProject);
                alert('Progetto aggiornato con successo!');
            } else {
                // Crea un nuovo progetto
                projectData.id = Date.now().toString();
                projectData.createdAt = new Date().toISOString();
                savedProject = window.Database.addProject(projectData);
                console.log('Nuovo progetto salvato:', savedProject);
                alert('Progetto salvato con successo!');
            }
            
            this.resetForm();
            this.loadProjects();
        } catch (error) {
            console.error('Errore nel salvare il progetto:', error);
            alert('Errore nel salvare il progetto: ' + error.message);
        }
    }

    /**
     * Valida i dati del progetto prima del salvataggio
     */
    validateProjectData(name, clientId) {
        if (!name) {
            alert('Inserire il nome del progetto');
            return false;
        }
        if (!clientId) {
            alert('Selezionare un cliente');
            return false;
        }
        if (this.selectedResources.size === 0) {
            alert('Selezionare almeno una risorsa');
            return false;
        }
        return true;
    }

    /**
     * Reimposta il form
     */
    resetForm() {
        const form = document.getElementById('projectForm');
        if (form) {
            form.reset();
            delete form.dataset.editMode;
            delete form.dataset.projectId;
            this.editingProjectId = null;
            this.selectedResources.clear();
            this.costSteps = new Set([1,2,3,4,5,6,7,8]);
            
            // Ripristina lo stato delle checkbox
            document.querySelectorAll('.cost-steps input[type="checkbox"], .step-list input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = true;
            });
            
            // Ripristina il titolo del form e il testo del pulsante
            const formTitle = document.querySelector('.form-container h2');
            if (formTitle) {
                formTitle.textContent = 'Nuovo Progetto';
            }
            
            const saveButton = document.querySelector('.form-actions .btn-primary');
            if (saveButton) {
                saveButton.textContent = 'Salva Progetto';
            }
            
            this.loadResources();
            this.updateCostSummary();
        }
    }

    /**
     * Carica la lista progetti
     */
    loadProjects() {
        const container = document.getElementById('projectsList');
        if (!container) return;

        const projects = window.Database.getAllProjects();
        if (projects.length === 0) {
            container.innerHTML = '<div class="empty-state">Nessun progetto disponibile</div>';
            return;
        }

        // Aggiungi controlli e filtri sopra la lista dei progetti
        const controlsHTML = `
            <div class="projects-controls">
                <div class="search-box">
                    <input type="text" id="projectSearchInput" class="form-control" placeholder="Cerca progetti...">
                </div>
                <div class="filter-controls">
                    <select id="projectClientFilter" class="form-control">
                        <option value="">Tutti i clienti</option>
                        ${this.getClientFilterOptions()}
                    </select>
                </div>
                <div class="sort-controls">
                    <select id="projectSortSelect" class="form-control">
                        <option value="name">Nome (A-Z)</option>
                        <option value="name-desc">Nome (Z-A)</option>
                        <option value="client">Cliente (A-Z)</option>
                        <option value="cost">Costo (crescente)</option>
                        <option value="cost-desc">Costo (decrescente)</option>
                        <option value="date">Data creazione (recenti)</option>
                    </select>
                </div>
                <div class="view-toggle">
                    <button id="gridViewBtn" class="btn btn-sm active">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button id="listViewBtn" class="btn btn-sm">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        `;

        // Container per i progetti con vista iniziale a griglia
        container.innerHTML = `
            ${controlsHTML}
            <div class="projects-container grid-view" id="projectsContainer"></div>
            <div class="pagination-controls" id="paginationControls"></div>
        `;

        this.renderProjects(projects);
        this.setupProjectSearch();
        this.setupProjectFilter();
        this.setupProjectSort();
        this.setupViewToggle();
        this.setupPagination(projects);
    }

    /**
     * Ottiene le opzioni per il filtro cliente
     */
    getClientFilterOptions() {
        const clients = window.Database.getAllClients();
        const options = clients.map(client => 
            `<option value="${client.id}">${client.name}</option>`
        ).join('');
        return options;
    }

    /**
     * Renderizza i progetti nella vista appropriata
     */
    renderProjects(projects, page = 1, itemsPerPage = 6) {
        const container = document.getElementById('projectsContainer');
        if (!container) return;
        
        const startIndex = (page - 1) * itemsPerPage;
        const paginatedProjects = projects.slice(startIndex, startIndex + itemsPerPage);
        
        // Controlla se siamo in modalità griglia o lista
        const isGridView = container.classList.contains('grid-view');
        
        if (isGridView) {
            this.renderGridView(paginatedProjects, container);
        } else {
            this.renderListView(paginatedProjects, container);
        }
    }

    /**
     * Renderizza i progetti in vista griglia
     */
    renderGridView(projects, container) {
        let html = '';
        
        projects.forEach(project => {
            const client = window.Database.getClientById(project.clientId);
            const totalCost = project.resources ? 
                project.resources.reduce((sum, r) => sum + (r.cost || 0), 0) : 0;
            
            // Formattazione delle date
            const startDate = project.start_date ? new Date(project.start_date).toLocaleDateString() : '-';
            const endDate = project.end_date ? new Date(project.end_date).toLocaleDateString() : '-';
            
            html += `
                <div class="project-card" data-project-id="${project.id}" data-client-id="${project.clientId}">
                    <div class="project-header">
                        <h3>${project.name}</h3>
                        <div class="project-actions">
                            <button onclick="window.projectManager.editProject('${project.id}')" class="btn-icon edit" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="window.projectManager.deleteProject('${project.id}')" class="btn-icon delete" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="project-client">
                        <i class="fas fa-building"></i> ${client?.name || 'N/D'}
                    </div>
                    <div class="project-dates">
                        <div><strong>Data Inizio:</strong> ${startDate}</div>
                        <div><strong>Data Fine:</strong> ${endDate}</div>
                    </div>
                    <div class="project-description">${this.truncateText(project.description || 'Nessuna descrizione', 100)}</div>
                    <div class="project-footer">
                        <div class="project-cost">
                            <i class="fas fa-euro-sign"></i> ${totalCost.toFixed(2)}€
                        </div>
                        <div class="project-resources">
                            <i class="fas fa-users"></i> ${project.resources ? project.resources.length : 0}
                        </div>
                        <button onclick="window.projectManager.showProjectDetails('${project.id}')" class="btn btn-sm btn-primary">
                            Dettagli
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    /**
     * Renderizza i progetti in vista lista
     */
    renderListView(projects, container) {
        let html = `
            <table class="table project-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cliente</th>
                        <th>Risorse</th>
                        <th>Costo</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        projects.forEach(project => {
            const client = window.Database.getClientById(project.clientId);
            const totalCost = project.resources ? 
                project.resources.reduce((sum, r) => sum + (r.cost || 0), 0) : 0;
            
            // Formattazione delle date
            const startDate = project.start_date ? new Date(project.start_date).toLocaleDateString() : '-';
            const endDate = project.end_date ? new Date(project.end_date).toLocaleDateString() : '-';
            
            html += `
                <tr data-project-id="${project.id}" data-client-id="${project.clientId}">
                    <td>
                        <div class="project-name-cell">
                            <span class="project-name">${project.name}</span>
                            <span class="project-description-sm">${this.truncateText(project.description || '', 50)}</span>
                        </div>
                    </td>
                    <td>${client?.name || 'N/D'}</td>
                    <td>${project.resources ? project.resources.length : 0} risorse</td>
                    <td>${totalCost.toFixed(2)}€</td>
                    <td class="date-cell">${startDate}</td>
                    <td class="date-cell">${endDate}</td>
                    <td>
                        <div class="btn-group">
                            <button onclick="window.projectManager.showProjectDetails('${project.id}')" class="btn btn-sm btn-primary" title="Dettagli">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <button onclick="window.projectManager.editProject('${project.id}')" class="btn btn-sm btn-warning" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="window.projectManager.deleteProject('${project.id}')" class="btn btn-sm btn-danger" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        container.innerHTML = html;
    }

    /**
     * Configura la paginazione per i progetti
     */
    setupPagination(projects) {
        const paginationContainer = document.getElementById('paginationControls');
        if (!paginationContainer) return;
        
        const itemsPerPage = 6;
        const totalPages = Math.ceil(projects.length / itemsPerPage);
        
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let paginationHTML = '<div class="pagination">';
        
        // Aggiungi pulsante precedente
        paginationHTML += `
            <button class="pagination-btn prev" ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Aggiungi pulsanti pagina
        for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `
                <button class="pagination-btn page-num ${i === this.currentPage ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>
            `;
        }
        
        // Aggiungi pulsante successivo
        paginationHTML += `
            <button class="pagination-btn next" ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        paginationHTML += '</div>';
        paginationContainer.innerHTML = paginationHTML;
        
        // Aggiungi event listeners
        paginationContainer.querySelectorAll('.pagination-btn.page-num').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const page = parseInt(e.target.dataset.page);
                this.currentPage = page;
                this.renderProjects(projects, page);
                this.setupPagination(projects);
            });
        });
        
        const prevBtn = paginationContainer.querySelector('.pagination-btn.prev');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.renderProjects(projects, this.currentPage);
                    this.setupPagination(projects);
                }
            });
        }
        
        const nextBtn = paginationContainer.querySelector('.pagination-btn.next');
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (this.currentPage < totalPages) {
                    this.currentPage++;
                    this.renderProjects(projects, this.currentPage);
                    this.setupPagination(projects);
                }
            });
        }
    }

    /**
     * Imposta il toggle tra vista griglia e lista
     */
    setupViewToggle() {
        const gridBtn = document.getElementById('gridViewBtn');
        const listBtn = document.getElementById('listViewBtn');
        const container = document.getElementById('projectsContainer');
        
        if (gridBtn && listBtn && container) {
            gridBtn.addEventListener('click', () => {
                container.classList.add('grid-view');
                container.classList.remove('list-view');
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
                
                // Ri-renderizza i progetti nella nuova vista
                const projects = this.getFilteredProjects();
                this.renderProjects(projects, this.currentPage);
            });
            
            listBtn.addEventListener('click', () => {
                container.classList.add('list-view');
                container.classList.remove('grid-view');
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
                
                // Ri-renderizza i progetti nella nuova vista
                const projects = this.getFilteredProjects();
                this.renderProjects(projects, this.currentPage);
            });
        }
    }

    /**
     * Imposta la funzionalità di ricerca
     */
    setupProjectSearch() {
        const searchInput = document.getElementById('projectSearchInput');
        if (!searchInput) return;

        searchInput.addEventListener('input', () => {
            const projects = this.getFilteredProjects();
            this.currentPage = 1;
            this.renderProjects(projects, 1);
            this.setupPagination(projects);
        });
    }

    /**
     * Imposta la funzionalità di filtro per cliente
     */
    setupProjectFilter() {
        const filterSelect = document.getElementById('projectClientFilter');
        if (!filterSelect) return;
        
        filterSelect.addEventListener('change', () => {
            const projects = this.getFilteredProjects();
            this.currentPage = 1;
            this.renderProjects(projects, 1);
            this.setupPagination(projects);
        });
    }

    /**
     * Imposta la funzionalità di ordinamento
     */
    setupProjectSort() {
        const sortSelect = document.getElementById('projectSortSelect');
        if (!sortSelect) return;

        sortSelect.addEventListener('change', () => {
            const projects = this.getFilteredProjects();
            this.renderProjects(projects, this.currentPage);
        });
    }

    /**
     * Ottiene i progetti filtrati in base a ricerca e filtri
     */
    getFilteredProjects() {
        const searchInput = document.getElementById('projectSearchInput');
        const filterSelect = document.getElementById('projectClientFilter');
        const sortSelect = document.getElementById('projectSortSelect');
        
        let projects = window.Database.getAllProjects();
        
        // Applica filtro ricerca
        if (searchInput && searchInput.value) {
            const searchTerm = searchInput.value.toLowerCase();
            projects = projects.filter(project => {
                const projectName = project.name.toLowerCase();
                const projectDesc = (project.description || '').toLowerCase();
                const client = window.Database.getClientById(project.clientId);
                const clientName = client ? client.name.toLowerCase() : '';
                
                return projectName.includes(searchTerm) || 
                       projectDesc.includes(searchTerm) ||
                       clientName.includes(searchTerm);
            });
        }
        
        // Applica filtro cliente
        if (filterSelect && filterSelect.value) {
            const clientId = filterSelect.value;
            projects = projects.filter(project => project.clientId === clientId);
        }
        
        // Applica ordinamento
        if (sortSelect) {
            const sortValue = sortSelect.value;
            projects.sort((a, b) => {
                switch(sortValue) {
                    case 'name':
                        return a.name.localeCompare(b.name);
                    case 'name-desc':
                        return b.name.localeCompare(a.name);
                    case 'client':
                        const clientA = window.Database.getClientById(a.clientId)?.name || '';
                        const clientB = window.Database.getClientById(b.clientId)?.name || '';
                        return clientA.localeCompare(clientB);
                    case 'cost':
                        const costA = a.resources ? a.resources.reduce((sum, r) => sum + (r.cost || 0), 0) : 0;
                        const costB = b.resources ? b.resources.reduce((sum, r) => sum + (r.cost || 0), 0) : 0;
                        return costA - costB;
                    case 'cost-desc':
                        const costADesc = a.resources ? a.resources.reduce((sum, r) => sum + (r.cost || 0), 0) : 0;
                        const costBDesc = b.resources ? b.resources.reduce((sum, r) => sum + (r.cost || 0), 0) : 0;
                        return costBDesc - costADesc;
                    case 'date':
                        const dateA = new Date(a.createdAt || 0);
                        const dateB = new Date(b.createdAt || 0);
                        return dateB - dateA;
                    default:
                        return 0;
                }
            });
        }
        
        return projects;
    }

    /**
     * Utilitaria per troncare testo lungo
     */
    truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    /**
     * Visualizza i dettagli del progetto
     */
    showProjectDetails(projectId) {
        const project = window.Database.getProjectById(projectId);
        if (!project) return;
        
        const client = window.Database.getClientById(project.clientId);
        const modalTitle = `Dettagli del progetto: ${project.name}`;
        
        // Formattazione delle date
        const startDate = project.start_date ? new Date(project.start_date).toLocaleDateString() : 'Non specificata';
        const endDate = project.end_date ? new Date(project.end_date).toLocaleDateString() : 'Non specificata';
        
        // Stato del progetto
        let statusText = "Non specificato";
        let statusClass = "";
        
        switch(project.status) {
            case 'pending':
                statusText = "In attesa";
                statusClass = "badge bg-warning";
                break;
            case 'in_progress':
                statusText = "In corso";
                statusClass = "badge bg-primary";
                break;
            case 'completed':
                statusText = "Completato";
                statusClass = "badge bg-success";
                break;
            case 'on_hold':
                statusText = "In pausa";
                statusClass = "badge bg-secondary";
                break;
        }
        
        let resourcesHTML = '';
        if (project.resources && project.resources.length > 0) {
            resourcesHTML = `
                <h4>Risorse:</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Ruolo</th>
                            <th>Ore</th>
                            <th>Tariffa</th>
                            <th>Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${project.resources.map(r => `
                            <tr>
                                <td>${r.name}</td>
                                <td>${r.role || 'N/D'}</td>
                                <td>${r.hours}</td>
                                <td>${r.adjustedRate.toFixed(2)}€/h</td>
                                <td>${r.cost.toFixed(2)}€</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            resourcesHTML = '<p>Nessuna risorsa assegnata a questo progetto.</p>';
        }
        
        const modalHTML = `
            <div class="modal" id="projectDetailsModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">${modalTitle}</h3>
                            <button type="button" class="close" onclick="document.getElementById('projectDetailsModal').remove()">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="project-details">
                                <div class="detail-row">
                                    <strong>Cliente:</strong> ${client?.name || 'N/D'}
                                </div>
                                <div class="detail-row">
                                    <strong>Descrizione:</strong> ${project.description || 'N/D'}
                                </div>
                                <div class="detail-row">
                                    <strong>Stato:</strong> <span class="${statusClass}">${statusText}</span>
                                </div>
                                <div class="detail-row">
                                    <strong>Data Inizio:</strong> ${startDate}
                                </div>
                                <div class="detail-row">
                                    <strong>Data Fine:</strong> ${endDate}
                                </div>
                                <div class="detail-row">
                                    <strong>Data creazione:</strong> ${new Date(project.createdAt || Date.now()).toLocaleString()}
                                </div>
                                ${resourcesHTML}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button onclick="window.projectManager.editProject('${project.id}')" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Modifica
                            </button>
                            <button onclick="document.getElementById('projectDetailsModal').remove()" class="btn btn-secondary">
                                Chiudi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Aggiungi il modale al body
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHTML;
        document.body.appendChild(modalContainer.firstChild);
        
        // Aggiungi stili per il modale se non esistono
        if (!document.getElementById('modalStyles')) {
            const modalStyles = document.createElement('style');
            modalStyles.id = 'modalStyles';
            modalStyles.innerHTML = `
                .modal {
                    display: block;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 1000;
                }
                .modal-dialog {
                    max-width: 800px;
                    margin: 30px auto;
                }
                .modal-content {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                }
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 1rem;
                    border-bottom: 1px solid #eee;
                }
                .modal-body {
                    padding: 1rem;
                    max-height: 70vh;
                    overflow-y: auto;
                }
                .modal-footer {
                    padding: 1rem;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    border-top: 1px solid #eee;
                }
                .close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                }
                .detail-row {
                    margin-bottom: 0.75rem;
                }
            `;
            document.head.appendChild(modalStyles);
        }
    }

    /**
     * Elimina un progetto
     */
    deleteProject(id) {
        if (confirm('Sei sicuro di voler eliminare questo progetto?')) {
            window.Database.deleteProject(id);
            this.loadProjects();
        }
    }

    /**
     * Apre il form di modifica di un progetto
     */
    editProject(id) {
        const project = window.Database.getProjectById(id);
        if (!project) {
            alert('Progetto non trovato');
            return;
        }

        // Chiudi il modale se aperto
        const modal = document.getElementById('projectDetailsModal');
        if (modal) {
            modal.remove();
        }

        this.editingProjectId = id;

        // Compila i campi del form con i dati del progetto
        document.getElementById('projectName').value = project.name || '';
        document.getElementById('projectDescription').value = project.description || '';
        
        const clientSelect = document.getElementById('clientSelect');
        if (clientSelect) {
            clientSelect.value = project.clientId || '';
        }

        // Imposta le date - Correzione per garantire che le date siano visualizzate nel form
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (startDateInput && project.start_date) {
            startDateInput.value = project.start_date;
            console.log('Data inizio impostata:', project.start_date);
        }
        
        if (endDateInput && project.end_date) {
            endDateInput.value = project.end_date;
            console.log('Data fine impostata:', project.end_date);
        }

        // Imposta lo stato se presente
        const statusSelect = document.getElementById('status');
        if (statusSelect && project.status) {
            statusSelect.value = project.status;
        }

        // Imposta gli step di costo
        this.costSteps = new Set(project.costSteps || [1,2,3,4,5,6,7,8]);
        document.querySelectorAll('.step-list input[type="checkbox"]').forEach(checkbox => {
            const stepNumber = parseInt(checkbox.id.replace('step', ''));
            checkbox.checked = this.costSteps.has(stepNumber);
        });

        // Imposta le risorse e le ore
        this.selectedResources = new Set();
        if (project.resources && project.resources.length > 0) {
            // Carica prima le risorse
            this.loadResources();
            
            // Poi seleziona quelle del progetto e imposta le ore
            project.resources.forEach(resource => {
                this.selectedResources.add(resource.id);
                
                // Trova il checkbox della risorsa e selezionalo
                const checkbox = document.querySelector(`.resource-checkbox[data-resource-id="${resource.id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
                
                // Trova il container delle ore e mostralo
                const resourceItem = document.querySelector(`.resource-item[data-resource-id="${resource.id}"]`);
                const hoursContainer = resourceItem?.querySelector('.resource-hours');
                if (hoursContainer) {
                    hoursContainer.style.display = 'block';
                    
                    // Imposta le ore
                    const hoursInput = hoursContainer.querySelector('.resource-hours-input');
                    if (hoursInput) {
                        hoursInput.value = resource.hours || 0;
                    }
                }
            });
        }

        // Aggiorna il riepilogo dei costi
        this.updateCostSummary();

        // Imposta la modalità di modifica
        document.getElementById('projectForm').dataset.editMode = 'true';
        document.getElementById('projectForm').dataset.projectId = id;
        
        // Cambia il titolo del form e il testo del pulsante
        const formTitle = document.querySelector('.form-container h2');
        if (formTitle) {
            formTitle.textContent = 'Modifica Progetto';
        }
        
        const saveButton = document.querySelector('.form-actions .btn-primary');
        if (saveButton) {
            saveButton.textContent = 'Aggiorna Progetto';
        }
        
        // Scrolla fino al form
        document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
    }
}

// Esporta la classe per renderla disponibile globalmente
window.projectManager = new ProjectManager();