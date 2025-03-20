const Database = {
    initializeDatabase() {
        // Initialize all required storage if not exists
        if (!localStorage.getItem('resources')) {
            localStorage.setItem('resources', JSON.stringify([]));
        }
        if (!localStorage.getItem('clients')) {
            localStorage.setItem('clients', JSON.stringify([]));
        }
        if (!localStorage.getItem('projects')) {
            localStorage.setItem('projects', JSON.stringify([]));
        }
        if (!localStorage.getItem('activities')) {
            localStorage.setItem('activities', JSON.stringify([]));
        }
    },

    // Resources Management
    getAllResources() {
        return JSON.parse(localStorage.getItem('resources') || '[]');
    },

    getResourceById(id) {
        const resources = this.getAllResources();
        return resources.find(r => r.id === id);
    },

    addResource(resource) {
        const resources = this.getAllResources();
        resource.id = Date.now().toString();
        resources.push(resource);
        localStorage.setItem('resources', JSON.stringify(resources));
        return resource;
    },

    updateResource(id, updatedResource) {
        const resources = this.getAllResources();
        const index = resources.findIndex(r => r.id === id);
        if (index !== -1) {
            resources[index] = { ...resources[index], ...updatedResource };
            localStorage.setItem('resources', JSON.stringify(resources));
            return resources[index];
        }
        return null;
    },

    deleteResource(id) {
        const resources = this.getAllResources();
        const filteredResources = resources.filter(r => r.id !== id);
        localStorage.setItem('resources', JSON.stringify(filteredResources));
    },

    // Clients Management
    getAllClients() {
        return JSON.parse(localStorage.getItem('clients') || '[]');
    },

    getClientById(id) {
        const clients = this.getAllClients();
        return clients.find(c => c.id === id);
    },

    addClient(client) {
        const clients = this.getAllClients();
        client.id = Date.now().toString();
        clients.push(client);
        localStorage.setItem('clients', JSON.stringify(clients));
        return client;
    },

    updateClient(id, updatedClient) {
        const clients = this.getAllClients();
        const index = clients.findIndex(c => c.id === id);
        if (index !== -1) {
            clients[index] = { ...clients[index], ...updatedClient };
            localStorage.setItem('clients', JSON.stringify(clients));
            return clients[index];
        }
        return null;
    },

    deleteClient(id) {
        const clients = this.getAllClients();
        const filteredClients = clients.filter(c => c.id !== id);
        localStorage.setItem('clients', JSON.stringify(filteredClients));
    },

    // Projects Management
    getAllProjects() {
        return JSON.parse(localStorage.getItem('projects') || '[]');
    },

    getProjectById(id) {
        const projects = this.getAllProjects();
        return projects.find(p => p.id === id);
    },

    getProjectsByClientId(clientId) {
        const projects = this.getAllProjects();
        return projects.filter(p => p.clientId === clientId);
    },

    addProject(project) {
        const projects = this.getAllProjects();
        project.id = Date.now().toString();
        projects.push(project);
        localStorage.setItem('projects', JSON.stringify(projects));
        return project;
    },

    updateProject(id, updatedProject) {
        const projects = this.getAllProjects();
        const index = projects.findIndex(p => p.id === id);
        if (index !== -1) {
            // Assicuriamoci di mantenere le proprietà esistenti che non vengono aggiornate
            projects[index] = { ...projects[index], ...updatedProject };
            
            // Debug - log del progetto dopo l'aggiornamento
            console.log('Progetto aggiornato:', projects[index]);
            
            localStorage.setItem('projects', JSON.stringify(projects));
            return projects[index];
        }
        return null;
    },

    deleteProject(id) {
        const projects = this.getAllProjects();
        const filteredProjects = projects.filter(p => p.id !== id);
        localStorage.setItem('projects', JSON.stringify(filteredProjects));
    },

    // Activities Management
    getAllActivities() {
        return JSON.parse(localStorage.getItem('activities') || '[]');
    },

    getActivityById(id) {
        const activities = this.getAllActivities();
        return activities.find(a => a.id === id);
    },

    getActivitiesByProjectId(projectId) {
        const activities = this.getAllActivities();
        return activities.filter(a => a.projectId === projectId);
    },

    addActivity(activity) {
        const activities = this.getAllActivities();
        activity.id = Date.now().toString();
        activities.push(activity);
        localStorage.setItem('activities', JSON.stringify(activities));
        return activity;
    },

    updateActivity(updatedActivity) {
        const activities = this.getAllActivities();
        const index = activities.findIndex(a => a.id === updatedActivity.id);
        if (index !== -1) {
            activities[index] = { ...activities[index], ...updatedActivity };
            localStorage.setItem('activities', JSON.stringify(activities));
            return activities[index];
        }
        return null;
    },

    deleteActivity(id) {
        const activities = this.getAllActivities();
        const filteredActivities = activities.filter(a => a.id !== id);
        localStorage.setItem('activities', JSON.stringify(filteredActivities));
    },
    
    initialize() {
        // Initialize all required storage if not exists
        if (!localStorage.getItem('resources')) {
            localStorage.setItem('resources', JSON.stringify([]));
        }
        if (!localStorage.getItem('clients')) {
            localStorage.setItem('clients', JSON.stringify([]));
        }
        if (!localStorage.getItem('projects')) {
            localStorage.setItem('projects', JSON.stringify([]));
        }
        if (!localStorage.getItem('activities')) {
            localStorage.setItem('activities', JSON.stringify([]));
        }
    },

    // Utility functions
    clearDatabase() {
        localStorage.removeItem('resources');
        localStorage.removeItem('clients');
        localStorage.removeItem('projects');
        localStorage.removeItem('activities');
        this.initializeDatabase();
    }
};

// Make it globally available
window.Database = Database;