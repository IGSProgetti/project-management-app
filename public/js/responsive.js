/**
 * IGS PROJECT MANAGEMENT - RESPONSIVE JS
 * Versione Semplificata e Robusta
 */

(function() {
    'use strict';
    
    console.log('🚀 Responsive JS - Caricamento...');

    // Aspetta che il DOM sia completamente caricato
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('✅ DOM Pronto - Inizializzazione menu mobile');

        const body = document.body;
        const sidebar = document.querySelector('.sidebar');
        
        if (!sidebar) {
            console.error('❌ Sidebar non trovata!');
            return;
        }

        // Crea o trova l'overlay
        let overlay = document.querySelector('.mobile-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'mobile-overlay';
            body.appendChild(overlay);
            console.log('✅ Overlay creato');
        }

        // Crea o trova il pulsante hamburger
        let toggleBtn = document.querySelector('.mobile-menu-toggle');
        if (!toggleBtn) {
            toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-menu-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.setAttribute('aria-label', 'Menu');
            body.appendChild(toggleBtn);
            console.log('✅ Pulsante hamburger creato');
        }

        // Funzione per aprire il menu
        function openMenu() {
            console.log('📱 Apertura menu...');
            sidebar.classList.add('show');
            overlay.classList.add('show');
            body.classList.add('menu-open');
            
            // Cambia icona
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-times';
            }
            
            // Blocca scroll
            body.style.overflow = 'hidden';
            body.style.position = 'fixed';
            body.style.width = '100%';
            
            toggleBtn.setAttribute('aria-expanded', 'true');
        }

        // Funzione per chiudere il menu
        function closeMenu() {
            console.log('📱 Chiusura menu...');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            body.classList.remove('menu-open');
            
            // Ripristina icona
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-bars';
            }
            
            // Ripristina scroll
            body.style.overflow = '';
            body.style.position = '';
            body.style.width = '';
            
            toggleBtn.setAttribute('aria-expanded', 'false');
        }

        // Funzione toggle
        function toggleMenu() {
            if (sidebar.classList.contains('show')) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        // EVENTI - Click sul pulsante hamburger
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖱️ Click su hamburger');
            toggleMenu();
        });

        // EVENTI - Click sull'overlay
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Click su overlay');
            closeMenu();
        });

        // EVENTI - Click sui link del menu
        const menuLinks = sidebar.querySelectorAll('.nav-menu a');
        console.log('🔗 Trovati ' + menuLinks.length + ' link nel menu');
        
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                console.log('🖱️ Click su link menu');
                // Chiudi solo su mobile
                if (window.innerWidth < 992) {
                    setTimeout(function() {
                        closeMenu();
                    }, 100);
                }
            });
        });

        // EVENTI - Tasto ESC per chiudere
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                console.log('⌨️ Tasto ESC premuto');
                closeMenu();
            }
        });

        // EVENTI - Touch/Swipe per chiudere
        let touchStartX = 0;
        let touchEndX = 0;

        sidebar.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });

        sidebar.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].clientX;
            
            // Swipe verso sinistra = chiudi
            if (touchStartX - touchEndX > 50) {
                console.log('👆 Swipe per chiudere');
                closeMenu();
            }
        }, { passive: true });

        // EVENTI - Resize finestra
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Chiudi menu se si passa a desktop
                if (window.innerWidth >= 992) {
                    closeMenu();
                }
            }, 250);
        });

        // EVENTI - Cambio orientamento
        window.addEventListener('orientationchange', function() {
            console.log('🔄 Orientamento cambiato');
            if (window.innerWidth < 992) {
                closeMenu();
            }
        });

        // Rendi tabelle responsive
        function makeTablesResponsive() {
            const tables = document.querySelectorAll('table:not(.table-responsive table)');
            tables.forEach(function(table) {
                if (!table.parentElement.classList.contains('table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        }

        makeTablesResponsive();

        // Osserva nuove tabelle aggiunte dinamicamente
        const observer = new MutationObserver(function() {
            makeTablesResponsive();
        });

        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            observer.observe(mainContent, {
                childList: true,
                subtree: true
            });
        }

        // Esporta funzioni globali
        window.IGSMenu = {
            open: openMenu,
            close: closeMenu,
            toggle: toggleMenu
        };

        console.log('✅ Menu mobile inizializzato con successo!');
        console.log('📱 Larghezza schermo:', window.innerWidth + 'px');
        console.log('🎯 Modalità:', window.innerWidth < 768 ? 'Mobile' : window.innerWidth < 992 ? 'Tablet' : 'Desktop');
    }

    // Previeni zoom su iOS
    document.addEventListener('gesturestart', function(e) {
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

    console.log('📱 Responsive JS caricato');
})();