/**
 * Script per gestire le funzionalità responsive dell'applicazione IGS Project Management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementi DOM
    const appContainer = document.querySelector('.app-container');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const body = document.body;
    
    // Crea l'overlay per il menu mobile
    const overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    body.appendChild(overlay);
    
    // Crea il pulsante toggle per il menu mobile
    const toggleButton = document.createElement('button');
    toggleButton.className = 'mobile-menu-toggle';
    toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
    toggleButton.setAttribute('aria-label', 'Toggle menu');
    body.appendChild(toggleButton);
    
    // Funzione per mostrare/nascondere il menu laterale
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        // Cambia l'icona del pulsante
        if (sidebar.classList.contains('show')) {
            toggleButton.innerHTML = '<i class="fas fa-times"></i>';
        } else {
            toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        }
        
        // Impedisci lo scrolling del body quando il menu è aperto
        if (sidebar.classList.contains('show')) {
            body.style.overflow = 'hidden';
        } else {
            body.style.overflow = '';
        }
    }
    
    // Aggiungi event listener al pulsante toggle
    toggleButton.addEventListener('click', toggleSidebar);
    
    // Chiudi il menu quando si fa clic sull'overlay
    overlay.addEventListener('click', toggleSidebar);
    
    // Chiudi il menu quando si fa clic su un link del menu
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                toggleSidebar();
            }
        });
    });
    
    // Gestione delle tabelle responsive
    function setupResponsiveTables() {
        const tables = document.querySelectorAll('.table-responsive table');
        
        // Rileva se siamo in modalità mobile
        const isMobile = window.innerWidth < 768;
        
        tables.forEach(table => {
            const tableContainer = table.closest('.table-responsive');
            
            // Se siamo in modalità mobile e il container ha la classe mobile-card-view
            if (isMobile && tableContainer.classList.contains('mobile-card-view')) {
                convertTableToCards(table, tableContainer);
            }
        });
    }
    
    // Converte una tabella in cards per la visualizzazione mobile
    function convertTableToCards(table, container) {
        // Nascondi la tabella
        table.style.display = 'none';
        
        // Ottieni headers e righe
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        
        // Crea il container per le cards
        const cardsContainer = document.createElement('div');
        cardsContainer.className = 'mobile-cards';
        
        // Crea una card per ogni riga
        rows.forEach(row => {
            const card = document.createElement('div');
            card.className = 'card mb-3';
            
            // Header della card (prima colonna come titolo)
            const cardHeader = document.createElement('div');
            cardHeader.className = 'card-header';
            
            const firstCellContent = row.cells[0].innerHTML;
            cardHeader.innerHTML = firstCellContent;
            
            // Body della card
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body';
            
            // Aggiungi i dati rimanenti
            for (let i = 1; i < row.cells.length; i++) {
                // Salta la colonna delle azioni
                if (headers[i]?.toLowerCase() === 'azioni') continue;
                
                const dataRow = document.createElement('div');
                dataRow.className = 'data-row';
                
                const label = document.createElement('span');
                label.className = 'data-label';
                label.textContent = headers[i] || `Colonna ${i}`;
                
                const value = document.createElement('span');
                value.className = 'data-value';
                value.innerHTML = row.cells[i].innerHTML;
                
                dataRow.appendChild(label);
                dataRow.appendChild(value);
                cardBody.appendChild(dataRow);
            }
            
            // Aggiungi azioni alla card, se presenti
            const actionsIndex = headers.findIndex(h => h.toLowerCase() === 'azioni');
            if (actionsIndex !== -1 && row.cells[actionsIndex]) {
                const cardFooter = document.createElement('div');
                cardFooter.className = 'card-footer text-center';
                cardFooter.innerHTML = row.cells[actionsIndex].innerHTML;
                
                card.appendChild(cardHeader);
                card.appendChild(cardBody);
                card.appendChild(cardFooter);
            } else {
                card.appendChild(cardHeader);
                card.appendChild(cardBody);
            }
            
            cardsContainer.appendChild(card);
        });
        
        // Aggiungi le cards al container
        container.appendChild(cardsContainer);
    }
    
    // Gestisci lo scrolling del menu laterale in modalità desktop
    function handleSidebarScroll() {
        if (window.innerWidth >= 992) {
            sidebar.style.height = `${window.innerHeight}px`;
            sidebar.style.overflowY = 'auto';
        } else {
            sidebar.style.height = '';
            sidebar.style.overflowY = '';
        }
    }
    
    // Adatta il padding del contenuto principale in base alla header
    function adjustMainContentPadding() {
        if (window.innerWidth < 992) {
            const header = document.querySelector('.header');
            if (header) {
                const headerHeight = header.offsetHeight;
                mainContent.style.paddingTop = `${headerHeight}px`;
            }
        } else {
            mainContent.style.paddingTop = '';
        }
    }
    
    // Inizializza le funzionalità responsive
    function initResponsive() {
        handleSidebarScroll();
        adjustMainContentPadding();
        setupResponsiveTables();
        
        // Aggiungi classe utility per il mobile a elementi specifici
        if (window.innerWidth < 768) {
            const actionGroups = document.querySelectorAll('.col-md-6.text-end');
            actionGroups.forEach(group => {
                group.classList.add('mobile-text-center');
                group.classList.remove('text-end');
            });
            
            // Fix per i bottoni nei gruppi
            const btnGroups = document.querySelectorAll('.btn-group[role="group"]');
            btnGroups.forEach(group => {
                if (group.children.length > 2) {
                    group.classList.add('d-flex', 'flex-wrap');
                }
            });
        }
    }
    
    // Esegui all'avvio
    initResponsive();
    
    // Gestisci il resize della finestra
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            handleSidebarScroll();
            adjustMainContentPadding();
            
            // Se la sidebar è aperta in mobile e passiamo a desktop, chiudila
            if (window.innerWidth >= 992 && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
                body.style.overflow = '';
            }
        }, 250);
    });
    
    // Aggiungi classe per abilitare le animazioni solo dopo il caricamento
    setTimeout(function() {
        document.body.classList.add('transitions-enabled');
    }, 300);
});