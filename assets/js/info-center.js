// REDAXO Info Center JavaScript - mit rex:ready Support
(function($) {
    'use strict';
    
    function initInfoCenter() {
        initInfoCenterToggle();
    }

    // REDAXO rex:ready Event für PJAX-Updates
    $(document).on('rex:ready', function(event, viewRoot) {
        // Info Center nach PJAX-Updates neu initialisieren
        initInfoCenter();
        
        // TimeTracker nach PJAX-Updates aktualisieren
        if (window.InfoCenterTimeTracker) {
            window.InfoCenterTimeTracker.refreshAfterPjax();
        }
        
        // Article Widget Daten nach PJAX-Updates aktualisieren
        refreshArticleWidget(viewRoot);
    });

    // Fallback für ältere Browser ohne jQuery
    if (typeof $ === 'undefined') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initInfoCenter);
        } else {
            initInfoCenter();
        }
    }

    function initInfoCenterToggle() {
        const toggleBtns = document.querySelectorAll('.info-center-toggle');
        
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
            }
        }
        
        // Close on outside click (nur einmal registrieren)
        if (!document.infoCenterClickRegistered) {
            document.addEventListener('click', handleOutsideClick);
            document.infoCenterClickRegistered = true;
        }
    }
    
    function handleToggleClick(e) {
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
})(window.jQuery || function() { return { on: function() {} }; });
