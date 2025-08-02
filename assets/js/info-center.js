// REDAXO Info Center JavaScript - Frontend und Backend kompatibel
(function() {
    'use strict';
    
    // Globale Initialisierung für Frontend und Backend
    function initInfoCenter() {
        console.log('InfoCenter: Initializing...');
        initInfoCenterToggle();
    }

    // Frontend/Backend kompatible Initialisierung
    function initializeInfoCenter() {
        initInfoCenter();
    }

    // REDAXO Backend: rex:ready Event (nur wenn jQuery verfügbar)
    if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
        console.log('InfoCenter: jQuery detected, using rex:ready');
        $(document).on('rex:ready', function(event, viewRoot) {
            console.log('InfoCenter: rex:ready event triggered');
            // Info Center nach PJAX-Updates neu initialisieren
            initInfoCenter();
            
            // TimeTracker nach PJAX-Updates aktualisieren
            if (window.InfoCenterTimeTracker && window.InfoCenterTimeTracker.refreshAfterPjax) {
                window.InfoCenterTimeTracker.refreshAfterPjax();
            }
            
            // Article Widget Daten nach PJAX-Updates aktualisieren
            refreshArticleWidget(viewRoot);
        });
    }

    // Frontend: Standard DOM Events
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('InfoCenter: DOMContentLoaded event triggered');
            initializeInfoCenter();
        });
    } else {
        console.log('InfoCenter: DOM already loaded, initializing immediately');
        initializeInfoCenter();
    }

    function initInfoCenterToggle() {
        console.log('InfoCenter: Initializing toggle functionality');
        const toggleBtns = document.querySelectorAll('.info-center-toggle');
        
        console.log('InfoCenter: Found', toggleBtns.length, 'toggle buttons');
        
        toggleBtns.forEach(btn => {
            // Entferne alte Event Listener um Duplikate zu vermeiden
            btn.removeEventListener('click', handleToggleClick);
            btn.addEventListener('click', handleToggleClick);
        });
        
        // Restore previous state
        const wasOpen = localStorage.getItem('infoCenterOpen') === '1';
        if (wasOpen) {
            const sidebar = document.querySelector('.info-center-sidebar');
            const toggleBtn = document.querySelector('.info-center-toggle');
            if (sidebar && toggleBtn) {
                sidebar.classList.add('active');
                toggleBtn.classList.add('active');
                console.log('InfoCenter: Restored previous open state');
            }
        }
        
        // Close on outside click (nur einmal registrieren)
        if (!document.infoCenterClickRegistered) {
            document.addEventListener('click', handleOutsideClick);
            document.infoCenterClickRegistered = true;
            console.log('InfoCenter: Registered outside click handler');
        }
    }
    
    function handleToggleClick(e) {
        console.log('InfoCenter: Toggle clicked');
        e.preventDefault();
        e.stopPropagation();
        
        const sidebar = document.querySelector('.info-center-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('active');
            
            // Toggle button state
            this.classList.toggle('active');
            
            // Store state in localStorage
            const isOpen = sidebar.classList.contains('active');
            localStorage.setItem('infoCenterOpen', isOpen ? '1' : '0');
            
            console.log('InfoCenter: Toggled sidebar, now', isOpen ? 'open' : 'closed');
            
            // Notify TimeTracker about visibility change
            if (window.InfoCenterTimeTracker && window.InfoCenterTimeTracker.updateMiniVisibility) {
                window.InfoCenterTimeTracker.updateMiniVisibility();
            }
        } else {
            console.warn('InfoCenter: Sidebar element not found');
        }
    }
    
    function handleOutsideClick(e) {
        const sidebar = document.querySelector('.info-center-sidebar');
        const toggleBtn = e.target.closest('.info-center-toggle');
        
        if (sidebar && !sidebar.contains(e.target) && !toggleBtn) {
            sidebar.classList.remove('active');
            const btn = document.querySelector('.info-center-toggle');
            if (btn) btn.classList.remove('active');
            localStorage.setItem('infoCenterOpen', '0');
            
            console.log('InfoCenter: Closed sidebar via outside click');
            
            // Notify TimeTracker about visibility change
            if (window.InfoCenterTimeTracker && window.InfoCenterTimeTracker.updateMiniVisibility) {
                window.InfoCenterTimeTracker.updateMiniVisibility();
            }
        }
    }
    
    function refreshArticleWidget(viewRoot) {
        // Nach PJAX-Update: Überprüfe ob wir uns auf einer Artikel-/Kategorie-Seite befinden
        const currentUrl = window.location.href;
        const isArticlePage = currentUrl.includes('page=content') || currentUrl.includes('page=structure');
        
        if (isArticlePage) {
            // Versuche das Article Widget zu aktualisieren
            const articleWidget = document.querySelector('.info-center-widget[data-id*="article"]');
            if (articleWidget) {
                // Trigger refresh des Article Widgets via AJAX
                refreshArticleWidgetContent(articleWidget);
            }
        }
    }
    
    function refreshArticleWidgetContent(widget) {
        // Simple reload des Widget-Inhalts via AJAX
        const widgetContent = widget.querySelector('.info-center-widget-content');
        if (!widgetContent) return;
        
        // Temporären Loading-Indikator anzeigen
        const originalContent = widgetContent.innerHTML;
        widgetContent.innerHTML = '<div style="text-align:center;padding:20px;opacity:0.6;">Aktualisiere...</div>';
        
        // Nach kurzer Verzögerung wieder ursprünglichen Inhalt anzeigen
        // In einer echten Implementierung würde hier ein AJAX-Call zum Server gemacht
        setTimeout(() => {
            widgetContent.innerHTML = originalContent;
            
            // Event für andere Komponenten triggern
            const event = new CustomEvent('infocenter:widget-refreshed', {
                detail: { widget: widget, type: 'article' }
            });
            document.dispatchEvent(event);
        }, 500);
    }

})(); // Self-executing anonymous function without jQuery dependency
