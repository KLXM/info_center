// REDAXO Info Center JavaScript - Frontend und Backend kompatibel
(function() {
    'use strict';
    
    // Initialize InfoCenter namespace early
    window.InfoCenter = window.InfoCenter || {};
    
    // Define settings functions early
    window.InfoCenter.setFontSize = function(size) {
        localStorage.setItem('infoCenterFontSize', size);
        applySavedSettings();
    };
    
    // Save and apply position
    window.InfoCenter.setPosition = function(position) {
        localStorage.setItem('infoCenterPosition', position);
        applySavedSettings();
    };
    
    // Get current settings
    window.InfoCenter.getSettings = function() {
        return {
            fontSize: localStorage.getItem('infoCenterFontSize') || 'medium',
            position: localStorage.getItem('infoCenterPosition') || 'center'
        };
    };
    
    // Globale Initialisierung für Frontend und Backend
    function initInfoCenter() {
        initInfoCenterToggle();
    }

    // Frontend/Backend kompatible Initialisierung
    function initializeInfoCenter() {
        initInfoCenter();
    }

    // REDAXO Backend: rex:ready Event (nur wenn jQuery verfügbar)
    if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
        $(document).on('rex:ready', function(event, viewRoot) {
            // Info Center nach PJAX-Updates neu initialisieren
            initInfoCenter();
            
            // TimeTracker nach PJAX-Updates aktualisieren
            if (window.InfoCenterTimeTracker && window.InfoCenterTimeTracker.refreshAfterPjax) {
                window.InfoCenterTimeTracker.refreshAfterPjax();
            }
            
            // Article Widget Daten nach PJAX-Updates aktualisieren
            refreshArticleWidget(viewRoot);
            
            // URL Widget nach PJAX-Updates aktualisieren  
            refreshUrlWidget(viewRoot);
        });
    }

    // Frontend: Standard DOM Events
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeInfoCenter();
        });
    } else {
        initializeInfoCenter();
    }

    function initInfoCenterToggle() {
        const toggleBtns = document.querySelectorAll('.info-center-toggle');
        const closeBtns = document.querySelectorAll('.info-center-close-btn');
        
        // Apply saved settings
        applySavedSettings();
        
        toggleBtns.forEach(btn => {
            // Entferne alte Event Listener um Duplikate zu vermeiden
            btn.removeEventListener('click', handleToggleClick);
            btn.addEventListener('click', handleToggleClick);
            
            // Ensure toggle icon is properly set
            if (!btn.querySelector('.toggle-icon')) {
                const iconSpan = document.createElement('span');
                iconSpan.className = 'toggle-icon';
                btn.innerHTML = '';
                btn.appendChild(iconSpan);
            }
        });
        
        closeBtns.forEach(btn => {
            // Entferne alte Event Listener um Duplikate zu vermeiden
            btn.removeEventListener('click', handleCloseClick);
            btn.addEventListener('click', handleCloseClick);
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
            
            // Notify TimeTracker about visibility change
            if (window.InfoCenterTimeTracker && window.InfoCenterTimeTracker.updateMiniVisibility) {
                window.InfoCenterTimeTracker.updateMiniVisibility();
            }
        } else {
            console.warn('InfoCenter: Sidebar element not found');
        }
    }
    
    function handleCloseClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const sidebar = document.querySelector('.info-center-sidebar');
        const toggleBtn = document.querySelector('.info-center-toggle');
        
        if (sidebar) {
            sidebar.classList.remove('active');
            if (toggleBtn) toggleBtn.classList.remove('active');
            
            // Store closed state in localStorage
            localStorage.setItem('infoCenterOpen', '0');
            
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
        const closeBtn = e.target.closest('.info-center-close-btn');
        
        if (sidebar && !sidebar.contains(e.target) && !toggleBtn && !closeBtn) {
            sidebar.classList.remove('active');
            const btn = document.querySelector('.info-center-toggle');
            if (btn) btn.classList.remove('active');
            localStorage.setItem('infoCenterOpen', '0');
            
            // Notify TimeTracker about visibility change
            if (window.InfoCenterTimeTracker && window.InfoCenterTimeTracker.updateMiniVisibility) {
                window.InfoCenterTimeTracker.updateMiniVisibility();
            }
        }
    }
    
    function refreshArticleWidget(viewRoot) {
        // Article Widget verwendet kein Lazy Loading mehr - nicht benötigt
    }
    
    function refreshUrlWidget(viewRoot) {
        // URL Widget verwendet kein Lazy Loading - nicht benötigt
    }
    
    // Apply saved user settings
    function applySavedSettings() {
        const container = document.querySelector('.info-center-container');
        if (!container) return;
        
        // Apply font size setting
        const fontSize = localStorage.getItem('infoCenterFontSize') || 'medium';
        container.className = container.className.replace(/font-\w+/g, '');
        container.classList.add('font-' + fontSize);
        
        // Apply position setting
        const position = localStorage.getItem('infoCenterPosition') || 'center';
        container.className = container.className.replace(/position-\w+/g, '');
        container.classList.add('position-' + position);
        
    }

})(); // Self-executing anonymous function without jQuery dependency
