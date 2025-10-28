/**
 * ===================================
 * IGS PROJECT MANAGEMENT - RESPONSIVE JS
 * Menu Hamburger Mobile Ottimizzato
 * ===================================
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Responsive JS caricato');

    // ===================================
    // ELEMENTI DOM
    // ===================================
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Crea overlay se non esiste
    let overlay = document.querySelector('.mobile-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        body.appendChild(overlay);
    }
    
    // Crea hamburger button se non esiste
    let toggleButton = document.querySelector('.mobile-menu-toggle');
    if (!toggleButton) {
        toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-menu-toggle';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.setAttribute('aria-label', 'Toggle menu');
        toggleButton.setAttribute('aria-expanded', 'false');
        body.appendChild(toggleButton);
    }

    // ===================================
    // FUNZIONE TOGGLE MENU
    // ===================================
    function toggleMenu() {
        const isOpen = sidebar.classList.contains('show');
        
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    function openMenu() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        body.classList.add('menu-open');
        
        // Cambia icona
        const icon = toggleButton.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-times';
        }
        
        // Accessibilità
        toggleButton.setAttribute('aria-expanded', 'true');
        
        // Previeni scroll
        body.style.overflow = 'hidden';
        
        console.log('📱 Menu aperto');
    }

    function closeMenu() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        body.classList.remove('menu-open');
        
        // Ripristina icona
        const icon = toggleButton.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-bars';
        }
        
        // Accessibilità
        toggleButton.setAttribute('aria-expanded', 'false');
        
        // Ripristina scroll
        body.style.overflow = '';
        
        console.log('📱 Menu chiuso');
    }

    // ===================================
    // EVENT LISTENERS
    // ===================================

    // Click sul pulsante hamburger
    toggleButton.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });

    // Click sull'overlay
    overlay.addEventListener('click', function() {
        closeMenu();
    });

    // Click sui link del menu
    const navLinks = sidebar.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Chiudi solo se siamo in modalità mobile
            if (window.innerWidth < 992) {
                setTimeout(() => {
                    closeMenu();
                }, 200);
            }
        });
    });

    // ===================================
    // GESTIONE SWIPE (per chiudere il menu)
    // ===================================
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const diffX = touchStartX - touchEndX;
        const diffY = Math.abs(touchStartY - touchEndY);
        
        // Swipe orizzontale verso sinistra (diffX > 0)
        // e non swipe verticale
        if (diffX > 50 && diffY < 50) {
            if (sidebar.classList.contains('show')) {
                closeMenu();
            }
        }
    }

    // ===================================
    // GESTIONE RESIZE FINESTRA
    // ===================================
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Chiudi il menu se la finestra diventa grande
            if (window.innerWidth >= 992) {
                closeMenu();
            }
        }, 250);
    });

    // ===================================
    // GESTIONE TASTO ESC
    // ===================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeMenu();
        }
    });

    // ===================================
    // FOCUS TRAP (per accessibilità)
    // ===================================
    sidebar.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' && sidebar.classList.contains('show')) {
            const focusableElements = sidebar.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    e.preventDefault();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    e.preventDefault();
                }
            }
        }
    });

    // ===================================
    // UTILITY: GESTIONE TABELLE RESPONSIVE
    // ===================================
    function makeTablesResponsive() {
        const tables = document.querySelectorAll('table:not(.table-responsive table)');
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }

    // Applica responsive alle tabelle
    makeTablesResponsive();

    // ===================================
    // UTILITY: AGGIUNGI CLASSI MOBILE
    // ===================================
    function updateMobileClasses() {
        const isMobile = window.innerWidth < 768;
        
        if (isMobile) {
            // Aggiungi classi mobile dove necessario
            const btnGroups = document.querySelectorAll('.btn-group');
            btnGroups.forEach(group => {
                group.classList.add('mobile-flex-column');
            });

            const forms = document.querySelectorAll('.form-inline');
            forms.forEach(form => {
                form.classList.add('mobile-flex-column');
            });
        }
    }

    updateMobileClasses();
    window.addEventListener('resize', updateMobileClasses);

    // ===================================
    // DEBUG INFO (rimuovi in produzione)
    // ===================================
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('📱 Responsive JS v2.0');
        console.log('🖥️ Window width:', window.innerWidth);
        console.log('📲 Mobile mode:', window.innerWidth < 992);
    }

    // ===================================
    // ESPORTA FUNZIONI PUBBLICHE
    // ===================================
    window.IGSResponsive = {
        openMenu: openMenu,
        closeMenu: closeMenu,
        toggleMenu: toggleMenu,
        makeTablesResponsive: makeTablesResponsive
    };

    console.log('✅ Menu hamburger mobile pronto!');
});

// ===================================
// GESTIONE ORIENTAMENTO DISPOSITIVO
// ===================================
window.addEventListener('orientationchange', function() {
    // Chiudi il menu quando cambia l'orientamento
    if (window.IGSResponsive && window.IGSResponsive.closeMenu) {
        window.IGSResponsive.closeMenu();
    }
});

// ===================================
// PREVENZIONE ZOOM iOS
// ===================================
document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
});

document.addEventListener('gesturechange', function(e) {
    e.preventDefault();
});

document.addEventListener('gestureend', function(e) {
    e.preventDefault();
});