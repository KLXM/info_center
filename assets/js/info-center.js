// REDAXO Info Center JavaScript - Vanilla JS mit Fallback
(function() {
    function initInfoCenter() {
        initInfoCenterToggle();
    }

    // DOM Ready mit mehreren Fallbacks
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInfoCenter);
    } else {
        initInfoCenter();
    }

    function initInfoCenterToggle() {
        const toggleBtns = document.querySelectorAll('.info-center-toggle');
        
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const sidebar = document.querySelector('.info-center-sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                    
                    // Toggle button state
                    btn.classList.toggle('active');
                    
                    // Store state in localStorage
                    const isOpen = sidebar.classList.contains('active');
                    localStorage.setItem('infoCenterOpen', isOpen ? '1' : '0');
                }
            });
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
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.info-center-sidebar');
            const toggleBtn = e.target.closest('.info-center-toggle');
            
            if (sidebar && !sidebar.contains(e.target) && !toggleBtn) {
                sidebar.classList.remove('active');
                const btn = document.querySelector('.info-center-toggle');
                if (btn) btn.classList.remove('active');
                localStorage.setItem('infoCenterOpen', '0');
            }
        });
    }
})();
