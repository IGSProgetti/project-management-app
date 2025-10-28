/**
 * ===================================
 * IGS PROJECT MANAGEMENT - RESPONSIVE JS
 * Menu Hamburger Mobile Ottimizzato - Fixed
 * ===================================
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Responsive JS caricato - Versione Mobile Fixed');

    // ===================================
    // ELEMENTI DOM
    // ===================================
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Verifica che gli elementi esistano
    if (!sidebar) {
        console.error('❌ Sidebar non trovata!');
        return;
    }

    // Crea overlay se non esiste
    let overlay = document.querySelector('.mobile-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        body.appendChild(overlay);
        console.log('✅ Overlay creato');
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
        console.log('✅ Hamburger button creato');
    }

    // ===================================
    // FUNZIONI TOGGLE MENU
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
        
        // Cambia icona da hamburger a X
        const icon = toggleButton.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-times';
        }
        
        // Accessibilità
        toggleButton.setAttribute('aria-expanded', 'true');
        
        // Previeni scroll del body
        body.style.overflow = 'hidden';
        body.style.position = 'fixed';
        body.style.width = '100%';
        
        console.log('📱 Menu aperto');
    }

    function closeMenu() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        body.classList.remove('menu-open');
        
        // Ripristina icona hamburger
        const icon = toggleButton.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-bars';
        }
        
        // Accessibilità
        toggleButton.setAttribute('aria-expanded', 'false');
        
        // Ripristina scroll del body
        body.style.overflow = '';
        body.style.position = '';
        body.style.width = '';
        
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

    // Click sull'overlay per chiudere
    overlay.addEventListener('click', function() {
        closeMenu();
    });

    // Click sui link del menu - chiude il menu su mobile
    const navLinks = sidebar.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Chiudi solo se siamo in modalità mobile
            if (window.innerWidth < 992) {
                // Piccolo delay per permettere l'animazione di click
                setTimeout(() => {
                    closeMenu();
                }, 150);
            }
        });
    });

    // Chiudi menu con tasto ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeMenu();
        }
    });

    // ===================================
    // GESTIONE SWIPE (tocco e trascina)
    // ===================================
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;
    let isSwiping = false;

    // Swipe sulla sidebar per chiudere
    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
        isSwiping = true;
    }, { passive: true });

    sidebar.addEventListener('touchmove', function(e) {
        if (!isSwiping) return;
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
    }, { passive: true });

    sidebar.addEventListener('touchend', function(e) {
        if (!isSwiping) return;
        handleSwipe();
        isSwiping = false;
    }, { passive: true });

    function handleSwipe() {
        const diffX = touchStartX - touchEndX;
        const diffY = Math.abs(touchStartY - touchEndY);
        
        // Swipe orizzontale verso sinistra per chiudere
        if (Math.abs(diffX) > 50 && diffY < 100) {
            if (diffX > 0) { // Swipe left
                closeMenu();
            }
        }
    }

    // Swipe dal bordo sinistro dello schermo per aprire
    let edgeSwipeStartX = 0;
    let edgeSwipeStartY = 0;

    document.addEventListener('touchstart', function(e) {
        edgeSwipeStartX = e.changedTouches[0].screenX;
        edgeSwipeStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        const touchEndX = e.changedTouches[0].screenX;
        const touchEndY = e.changedTouches[0].screenY;
        const diffX = touchEndX - edgeSwipeStartX;
        const diffY = Math.abs(touchEndY - edgeSwipeStartY);
        
        // Se swipe parte dal bordo sinistro (primi 30px) e va verso destra
        if (edgeSwipeStartX < 30 && diffX > 80 && diffY < 100) {
            if (!sidebar.classList.contains('show') && window.innerWidth < 992) {
                openMenu();
            }
        }
    }, { passive: true });

    // ===================================
    // GESTIONE RESIZE FINESTRA
    // ===================================
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Se passiamo a desktop, chiudi il menu e pulisci gli stili
            if (window.innerWidth >= 992) {
                if (sidebar.classList.contains('show')) {
                    closeMenu();
                }
                // Rimuovi eventuali stili inline
                body.style.overflow = '';
                body.style.position = '';
                body.style.width = '';
            }
            
            updateMobileClasses();
            console.log('📐 Window resized:', window.innerWidth + 'px');
        }, 250);
    });

    // ===================================
    // GESTIONE ORIENTAMENTO
    // ===================================
    window.addEventListener('orientationchange', function() {
        console.log('🔄 Orientamento cambiato');
        // Chiudi il menu quando cambia orientamento
        if (window.innerWidth < 992) {
            closeMenu();
        }
        
        // Aspetta che il browser completi il cambio orientamento
        setTimeout(function() {
            updateMobileClasses();
        }, 300);
    });

    // ===================================
    // UTILITY: RENDI TABELLE RESPONSIVE
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
        
        if (tables.length > 0) {
            console.log('📊 ' + tables.length + ' tabelle rese responsive');
        }
    }

    // Applica responsive alle tabelle all'avvio
    makeTablesResponsive();

    // Observer per tabelle aggiunte dinamicamente
    const tableObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                makeTablesResponsive();
            }
        });
    });

    // Osserva il main content per nuove tabelle
    if (mainContent) {
        tableObserver.observe(mainContent, {
            childList: true,
            subtree: true
        });
    }

    // ===================================
    // UTILITY: AGGIUNGI CLASSI MOBILE
    // ===================================
    function updateMobileClasses() {
        const isMobile = window.innerWidth < 768;
        const isTablet = window.innerWidth >= 768 && window.innerWidth < 992;
        
        if (isMobile) {
            body.classList.add('is-mobile');
            body.classList.remove('is-tablet', 'is-desktop');
            
            // Aggiungi classi mobile ai button groups
            const btnGroups = document.querySelectorAll('.btn-group');
            btnGroups.forEach(group => {
                group.classList.add('mobile-flex-column');
            });

            // Aggiungi classi mobile ai form inline
            const forms = document.querySelectorAll('.form-inline');
            forms.forEach(form => {
                form.classList.add('mobile-flex-column');
            });
        } else if (isTablet) {
            body.classList.add('is-tablet');
            body.classList.remove('is-mobile', 'is-desktop');
        } else {
            body.classList.add('is-desktop');
            body.classList.remove('is-mobile', 'is-tablet');
            
            // Rimuovi classi mobile
            const btnGroups = document.querySelectorAll('.btn-group');
            btnGroups.forEach(group => {
                group.classList.remove('mobile-flex-column');
            });

            const forms = document.querySelectorAll('.form-inline');
            forms.forEach(form => {
                form.classList.remove('mobile-flex-column');
            });
        }
    }

    // Applica classi all'avvio
    updateMobileClasses();

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

    // Previeni doppio tap zoom
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, false);

    // ===================================
    // FIX PER iOS SAFARI VIEWPORT
    // ===================================
    function fixIOSViewport() {
        // Fix per l'altezza della viewport su iOS
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    fixIOSViewport();
    window.addEventListener('resize', fixIOSViewport);
    window.addEventListener('orientationchange', function() {
        setTimeout(fixIOSViewport, 300);
    });

    // ===================================
    // SMOOTH SCROLL PER ANCHOR LINKS
    // ===================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // ===================================
    // ESPORTA FUNZIONI PUBBLICHE
    // ===================================
    window.IGSResponsive = {
        openMenu: openMenu,
        closeMenu: closeMenu,
        toggleMenu: toggleMenu,
        makeTablesResponsive: makeTablesResponsive,
        updateMobileClasses: updateMobileClasses
    };

    // ===================================
    // DEBUG INFO
    // ===================================
    console.log('✅ IGS Responsive JS inizializzato!');
    console.log('📱 Dimensioni finestra:', window.innerWidth + 'x' + window.innerHeight);
    console.log('🎯 Modalità:', window.innerWidth < 768 ? 'Mobile' : window.innerWidth < 992 ? 'Tablet' : 'Desktop');
    
    // Log solo in sviluppo
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('🔧 Modalità sviluppo attiva');
        console.log('📊 Elementi trovati:', {
            sidebar: !!sidebar,
            overlay: !!overlay,
            toggleButton: !!toggleButton,
            navLinks: navLinks.length + ' links'
        });
    }
});

// ===================================
// GESTIONE BACK BUTTON DEL BROWSER
// ===================================
window.addEventListener('popstate', function() {
    if (window.IGSResponsive && window.IGSResponsive.closeMenu) {
        window.IGSResponsive.closeMenu();
    }
});

// ===================================
// PERFORMANCE MONITORING (opzionale)
// ===================================
if ('PerformanceObserver' in window) {
    const perfObserver = new PerformanceObserver(function(list) {
        for (const entry of list.getEntries()) {
            if (entry.duration > 100) {
                console.warn('⚠️ Operazione lenta:', entry.name, entry.duration + 'ms');
            }
        }
    });
    
    perfObserver.observe({ entryTypes: ['measure'] });
}