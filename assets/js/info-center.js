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
            
            // Refresh structure tree if on structure tab
            const structureContainer = document.getElementById('info-center-structure-container');
            if (structureContainer && structureContainer.dataset.loaded === 'true') {
                structureContainer.dataset.loaded = 'false';
                const activeTab = document.querySelector('.info-center-tab.active');
                if (activeTab && activeTab.dataset.tab === 'structure') {
                    loadStructure();
                }
            }
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
            const isOpening = !sidebar.classList.contains('active');
            sidebar.classList.toggle('active');
            
            // Toggle button state
            this.classList.toggle('active');
            
            // Save state
            localStorage.setItem('infoCenterOpen', sidebar.classList.contains('active') ? '1' : '0');
            
            // If opening and on structure tab, refresh structure
            if (isOpening) {
                const activeTab = document.querySelector('.info-center-tab.active');
                if (activeTab && activeTab.dataset.tab === 'structure') {
                    const structureContainer = document.getElementById('info-center-structure-container');
                    if (structureContainer && structureContainer.dataset.loaded === 'true') {
                        structureContainer.dataset.loaded = 'false';
                        setTimeout(() => loadStructure(), 100);
                    }
                }
            }
            
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

    // Tab System
    function initTabs() {
        const tabs = document.querySelectorAll('.info-center-tab');
        const tabContents = document.querySelectorAll('.info-center-tab-content');
        
        // Check if on structure page
        const isStructurePage = window.location.href.includes('page=structure') || 
                                window.location.href.includes('page=content');
        
        // Get last active tab from localStorage or default based on page
        let activeTab = localStorage.getItem('infoCenterActiveTab');
        if (!activeTab) {
            activeTab = isStructurePage ? 'structure' : 'widgets';
        }
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // If switching to structure tab, refresh it
                if (targetTab === 'structure') {
                    const structureContainer = document.getElementById('info-center-structure-container');
                    if (structureContainer && structureContainer.dataset.loaded === 'true') {
                        structureContainer.dataset.loaded = 'false';
                    }
                }
                
                // Remove active from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Add active to clicked tab
                this.classList.add('active');
                const targetContent = document.querySelector(`[data-content="${targetTab}"]`);
                if (targetContent) {
                    targetContent.classList.add('active');
                    
                    // Save active tab to localStorage
                    localStorage.setItem('infoCenterActiveTab', targetTab);
                    
                    // Load structure if structure tab is clicked
                    if (targetTab === 'structure') {
                        loadStructure();
                    }
                }
            });
            
            // Activate saved/default tab
            if (tab.dataset.tab === activeTab) {
                tab.click();
            }
        });
    }

    // Structure Tree
    function loadStructure() {
        const container = document.getElementById('info-center-structure-container');
        if (!container) return;
        
        // Always reload to ensure current category is correct
        container.innerHTML = '<div class="info-center-loading">Lade Struktur...</div>';
        
        // Get saved domain selection
        const savedDomain = localStorage.getItem('infoCenterSelectedDomain');
        const currentUrl = new URL(window.location.href);
        
        // Apply saved domain to URL if exists
        if (savedDomain && savedDomain !== '0') {
            currentUrl.searchParams.set('info_center_domain', savedDomain);
        }
        
        // Fetch structure via API
        fetch(currentUrl.pathname + currentUrl.search + '&rex-api-call=info_center_structure')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    container.innerHTML = data.html;
                    container.dataset.loaded = 'true';
                    initStructureTree();
                } else {
                    container.innerHTML = '<div class="info-center-error">Fehler beim Laden der Struktur</div>';
                }
            })
            .catch(error => {
                console.error('Error loading structure:', error);
                container.innerHTML = '<div class="info-center-error">Fehler beim Laden der Struktur</div>';
            });
    }

    function initStructureTree() {
        // Domain Switcher
        const domainSelect = document.getElementById('info-center-domain-select');
        if (domainSelect) {
            const autoDomain = domainSelect.dataset.autoDomain;
            const autoSwitchEnabled = domainSelect.dataset.autoSwitch === '1';
            const savedDomain = localStorage.getItem('infoCenterSelectedDomain');
            
            // Only auto-switch if enabled in settings
            if (autoSwitchEnabled) {
                // If auto-detected domain differs from saved, update localStorage
                // This includes switching to '0' (all domains) when navigating to root categories
                if (autoDomain !== undefined && autoDomain !== savedDomain) {
                    localStorage.setItem('infoCenterSelectedDomain', autoDomain);
                    
                    // Update select value if not already set correctly
                    if (domainSelect.value !== autoDomain) {
                        domainSelect.value = autoDomain;
                    }
                }
            } else if (savedDomain && savedDomain !== domainSelect.value && autoDomain === undefined) {
                // Restore saved domain selection only if no auto-domain detected
                domainSelect.value = savedDomain;
                
                // Trigger reload with saved domain
                const container = document.getElementById('info-center-structure-container');
                if (container && container.dataset.loaded === 'true') {
                    container.dataset.loaded = 'false';
                    const currentUrl = new URL(window.location.href);
                    if (savedDomain === '0') {
                        currentUrl.searchParams.delete('info_center_domain');
                    } else {
                        currentUrl.searchParams.set('info_center_domain', savedDomain);
                    }
                    
                    container.innerHTML = '<div class="info-center-loading">Lade Struktur...</div>';
                    fetch(currentUrl.pathname + currentUrl.search + '&rex-api-call=info_center_structure')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                container.innerHTML = data.html;
                                container.dataset.loaded = 'true';
                                initStructureTree();
                            }
                        });
                }
            }
            
            domainSelect.addEventListener('change', function() {
                const selectedDomain = this.value;
                
                // Save domain selection to localStorage
                localStorage.setItem('infoCenterSelectedDomain', selectedDomain);
                
                const currentUrl = new URL(window.location.href);
                
                if (selectedDomain === '0') {
                    currentUrl.searchParams.delete('info_center_domain');
                } else {
                    currentUrl.searchParams.set('info_center_domain', selectedDomain);
                }
                
                // Reload structure
                const container = document.getElementById('info-center-structure-container');
                if (container) {
                    container.dataset.loaded = 'false';
                    container.innerHTML = '<div class="info-center-loading">Lade Struktur...</div>';
                    
                    fetch(currentUrl.pathname + currentUrl.search + '&rex-api-call=info_center_structure')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                container.innerHTML = data.html;
                                container.dataset.loaded = 'true';
                                initStructureTree();
                            }
                        });
                }
            });
        }
        
        // Toggle für Akkordeon
        const toggles = document.querySelectorAll('.info-center-tree-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const item = this.closest('.info-center-tree-item');
                if (item) {
                    const wasExpanded = item.classList.contains('expanded');
                    item.classList.toggle('expanded');
                    
                    // Lazy load children if not already loaded
                    if (!wasExpanded && !item.dataset.childrenLoaded && item.dataset.hasChildren === 'true') {
                        loadCategoryChildren(item);
                    }
                }
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('structure-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchStructure(this.value);
                }, 300);
            });
        }
        
        // Domain switcher functionality
        (function() {
            const domainSelect = document.getElementById('info-center-domain-select');
            if (domainSelect && !domainSelect.dataset.initialized) {
                domainSelect.dataset.initialized = 'true';
                
                const autoSwitch = domainSelect.dataset.autoSwitch === '1';
                const autoDomainId = parseInt(domainSelect.dataset.autoDomain) || 0;
                const selectedDomain = parseInt(domainSelect.dataset.selectedDomain) || 0;
                
                // Use selected domain from PHP (already set via selected attribute)
                // Only use localStorage if explicitly saved by user
                const savedDomain = localStorage.getItem('infoCenterSelectedDomain');
                if (savedDomain !== null) {
                    domainSelect.value = savedDomain;
                }
                // Otherwise use the PHP-selected value (already set via selected attribute)
                
                domainSelect.addEventListener('change', function() {
                    const selectedDomain = this.value;
                    localStorage.setItem('infoCenterSelectedDomain', selectedDomain);
                    reloadStructureTree(selectedDomain);
                });
            }
        })();
    }
    
    function reloadStructureTree(domainId) {
        // Einfache Lösung: Page reload mit Domain-Parameter
        const url = new URL(window.location.href);
        url.searchParams.set('info_center_domain', domainId);
        window.location.href = url.toString();
    }

    function searchStructure(query) {
        const items = document.querySelectorAll('.info-center-tree-item');
        const normalizedQuery = query.toLowerCase().trim();
        
        if (!normalizedQuery) {
            // Reset search
            items.forEach(item => {
                item.classList.remove('search-hidden', 'search-match');
            });
            return;
        }
        
        items.forEach(item => {
            const name = item.querySelector('.info-center-tree-name');
            const id = item.querySelector('.info-center-tree-id');
            const nameText = name ? name.textContent.toLowerCase() : '';
            const idText = id ? id.textContent.toLowerCase() : '';
            
            const matches = nameText.includes(normalizedQuery) || idText.includes(normalizedQuery);
            
            if (matches) {
                item.classList.remove('search-hidden');
                item.classList.add('search-match');
                // Expand parent items
                expandParents(item);
            } else {
                item.classList.add('search-hidden');
                item.classList.remove('search-match');
            }
        });
    }

    function expandParents(item) {
        let parent = item.parentElement?.closest('.info-center-tree-item');
        while (parent) {
            parent.classList.add('expanded');
            parent.classList.remove('search-hidden');
            parent = parent.parentElement?.closest('.info-center-tree-item');
        }
    }
    
    function loadCategoryChildren(item) {
        const categoryId = item.dataset.id.replace('category-', '').replace('article-', '');
        const clang = item.dataset.clang || '1';
        
        // Mark as loading
        item.classList.add('loading');
        
        // API URL
        const apiUrl = typeof rex !== 'undefined' && rex.backend_url 
            ? rex.backend_url + 'index.php?rex-api-call=info_center_structure_children'
            : '../redaxo/index.php?rex-api-call=info_center_structure_children';
        
        fetch(apiUrl + '&category_id=' + categoryId + '&clang=' + clang)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.children) {
                    renderChildren(item, data.children);
                    item.dataset.childrenLoaded = 'true';
                }
            })
            .catch(error => {
                console.error('Error loading children:', error);
            })
            .finally(() => {
                item.classList.remove('loading');
            });
    }
    
    function renderChildren(parentItem, children) {
        // Find or create children container
        let childrenContainer = parentItem.querySelector(':scope > ul');
        if (!childrenContainer) {
            childrenContainer = document.createElement('ul');
            childrenContainer.className = 'info-center-tree-children';
            parentItem.appendChild(childrenContainer);
        }
        
        // Clear existing content
        childrenContainer.innerHTML = '';
        
        // Render each child
        children.forEach(child => {
            const li = document.createElement('li');
            li.className = 'info-center-tree-item';
            
            if (child.type === 'category') {
                li.classList.add('info-center-tree-category');
                li.dataset.id = child.id;
                li.dataset.hasChildren = child.hasChildren;
                
                const statusClass = child.status == 0 ? 'status-offline' : (child.status == 2 ? 'status-locked' : 'status-online');
                
                // Build title with update info
                let categoryTitle = (child.domain ? rex_i18n_msg('info_center_domain') + ': ' + child.domain + ' | ' : '') + 'ID: ' + child.id;
                if (child.updatedate) {
                    const updateDate = new Date(child.updatedate).toLocaleString('de-DE', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    categoryTitle += ' | ' + rex_i18n_msg('info_center_last_updated', 'Zuletzt geändert') + ': ' + updateDate;
                    if (child.updateuser) {
                        categoryTitle += ' (' + child.updateuser + ')';
                    }
                }
                
                li.innerHTML = `
                    <div class="info-center-tree-node">
                        ${child.hasChildren ? '<button class="info-center-tree-toggle" type="button"></button>' : '<span class="info-center-tree-spacer"></span>'}
                        <a href="${child.url}" class="info-center-tree-link" title="${categoryTitle}">
                            <svg class="info-center-tree-folder-icon ${statusClass}" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 5a2 2 0 012-2h5l2 3h9a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
                            </svg>
                            <span class="info-center-tree-name">${child.name}</span>
                        </a>
                        <div class="info-center-tree-actions">
                            <a href="${child.viewUrl}" class="info-center-tree-view" title="${rex_i18n_msg('info_center_view_frontend')}" target="_blank">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="${child.editUrl}" class="info-center-tree-edit" title="${rex_i18n_msg('info_center_edit_category')}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>`;
            } else {
                // Article
                li.classList.add('info-center-tree-article');
                li.dataset.id = 'article-' + child.id;
                
                const statusClass = child.status == 0 ? 'status-offline' : (child.status == 2 ? 'status-locked' : 'status-online');
                
                // Build title with update info
                let articleTitle = rex_i18n_msg('info_center_article_id') + ': ' + child.id;
                if (child.updatedate) {
                    const updateDate = new Date(child.updatedate).toLocaleString('de-DE', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    articleTitle += ' | ' + rex_i18n_msg('info_center_last_updated', 'Zuletzt geändert') + ': ' + updateDate;
                    if (child.updateuser) {
                        articleTitle += ' (' + child.updateuser + ')';
                    }
                }
                
                li.innerHTML = `
                    <div class="info-center-tree-node">
                        <span class="info-center-tree-spacer"></span>
                        <a href="${child.url}" class="info-center-tree-link" title="${articleTitle}">
                            <svg class="info-center-tree-article-icon ${statusClass}" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
                                <path d="M14 2v6h6" fill="none" stroke="white" stroke-width="1" opacity="0.3"/>
                            </svg>
                            <span class="info-center-tree-name">${child.name}</span>
                        </a>
                        <div class="info-center-tree-actions">
                            <a href="${child.viewUrl}" class="info-center-tree-view" title="${rex_i18n_msg('info_center_view_frontend')}" target="_blank">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="${child.url}" class="info-center-tree-edit" title="${rex_i18n_msg('info_center_edit_article')}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>`;
            }
            
            childrenContainer.appendChild(li);
        });
        
        // Re-init toggle buttons for new items
        const newToggles = childrenContainer.querySelectorAll('.info-center-tree-toggle');
        newToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const item = this.closest('.info-center-tree-item');
                if (item) {
                    const wasExpanded = item.classList.contains('expanded');
                    item.classList.toggle('expanded');
                    
                    // Lazy load children if not already loaded
                    if (!wasExpanded && !item.dataset.childrenLoaded && item.dataset.hasChildren === 'true') {
                        loadCategoryChildren(item);
                    }
                }
            });
        });
    }
    
    // Helper for i18n messages (fallback if not available)
    function rex_i18n_msg(key, fallback = '') {
        return fallback || key;
    }

    // ========================================
    // SEARCH WIDGET
    // ========================================
    
    let searchDebounceTimer = null;
    let searchSelectedIndex = -1;
    let searchResults = [];

    function initSearchWidget() {
        const searchInput = document.getElementById('info-center-search-input');
        const searchResults = document.getElementById('info-center-search-results');
        const clearButton = document.getElementById('info-center-search-clear');
        
        if (!searchInput || !searchResults) {
            return;
        }

        // Focus on Cmd/Ctrl + K
        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });

        // Input handler with debounce
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (clearButton) {
                clearButton.style.display = query ? 'block' : 'none';
            }
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                searchSelectedIndex = -1;
                return;
            }

            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Clear button
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                searchResults.innerHTML = '';
                clearButton.style.display = 'none';
                searchSelectedIndex = -1;
                searchInput.focus();
            });
        }

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const items = searchResults.querySelectorAll('.info-center-search-result-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                searchSelectedIndex = Math.min(searchSelectedIndex + 1, items.length - 1);
                updateSearchSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                searchSelectedIndex = Math.max(searchSelectedIndex - 1, -1);
                updateSearchSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (searchSelectedIndex >= 0 && items[searchSelectedIndex]) {
                    const link = items[searchSelectedIndex].querySelector('a');
                    if (link) {
                        // Check if Cmd/Ctrl is pressed for new tab
                        if (e.metaKey || e.ctrlKey) {
                            window.open(link.href, '_blank');
                        } else {
                            window.location.href = link.href;
                        }
                    }
                }
            } else if (e.key === 'Escape') {
                searchInput.blur();
                searchResults.innerHTML = '';
                searchSelectedIndex = -1;
            }
        });
    }

    function performSearch(query) {
        const searchResults = document.getElementById('info-center-search-results');
        if (!searchResults) return;

        searchResults.innerHTML = '<div class="info-center-search-loading">Suche läuft...</div>';
        
        // Check for quick actions first
        const quickActions = getQuickActions(query);
        if (quickActions.length > 0) {
            renderSearchResults({ quickActions: quickActions });
            return;
        }

        fetch('index.php?rex-api-call=info_center_search&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    searchResults.innerHTML = '<div class="info-center-search-error">' + data.error + '</div>';
                    return;
                }

                renderSearchResults(data.results);
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="info-center-search-error">Fehler beim Suchen</div>';
            });
    }

    function renderSearchResults(results) {
        const searchResults = document.getElementById('info-center-search-results');
        if (!searchResults) return;

        searchResults.innerHTML = '';
        searchSelectedIndex = -1;

        let totalResults = 0;
        
        // Render Quick Actions first
        if (results.quickActions && results.quickActions.length > 0) {
            const section = createQuickActionsSection(results.quickActions);
            searchResults.appendChild(section);
            return; // Only show quick actions, no regular results
        }
        
        // Count total results
        Object.values(results).forEach(items => {
            if (Array.isArray(items)) {
                totalResults += items.length;
            }
        });

        if (totalResults === 0) {
            searchResults.innerHTML = '<div class="info-center-search-empty">Keine Ergebnisse gefunden</div>';
            return;
        }

        // Render Categories
        if (results.categories && results.categories.length > 0) {
            const section = createSearchSection('Kategorien', results.categories, 'category');
            searchResults.appendChild(section);
        }

        // Render Articles
        if (results.articles && results.articles.length > 0) {
            const section = createSearchSection('Artikel', results.articles, 'article');
            searchResults.appendChild(section);
        }

        // Render Modules
        if (results.modules && results.modules.length > 0) {
            const section = createSearchSection('Module', results.modules, 'module');
            searchResults.appendChild(section);
        }

        // Render Templates
        if (results.templates && results.templates.length > 0) {
            const section = createSearchSection('Templates', results.templates, 'template');
            searchResults.appendChild(section);
        }

        // Render Media
        if (results.media && results.media.length > 0) {
            const section = createSearchSection('Medien', results.media, 'media');
            searchResults.appendChild(section);
        }

        // Render custom addon results
        Object.keys(results).forEach(key => {
            if (!['categories', 'articles', 'modules', 'templates', 'media'].includes(key)) {
                const section = createSearchSection(key, results[key], 'custom');
                searchResults.appendChild(section);
            }
        });
    }
    
    function createQuickActionsSection(actions) {
        const section = document.createElement('div');
        section.className = 'info-center-search-section';

        const header = document.createElement('div');
        header.className = 'info-center-search-section-header';
        header.textContent = 'Quick Actions';
        section.appendChild(header);

        actions.forEach(action => {
            const resultItem = document.createElement('div');
            resultItem.className = 'info-center-search-result-item';
            resultItem.style.cursor = 'pointer';
            
            const iconMap = {
                'calculator': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/><line x1="8" y1="18" x2="16" y2="18"/></svg>',
                'wikipedia': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
                'dictionary': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
                'url': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'
            };
            
            const icon = iconMap[action.type] || iconMap['url'];
            
            resultItem.innerHTML = `
                <div class="info-center-search-result-wrapper">
                    <div class="info-center-search-result-icon">${icon}</div>
                    <div class="info-center-search-result-content">
                        <div class="info-center-search-result-title">${escapeHtml(action.title)}</div>
                        <div class="info-center-search-result-subtitle">${escapeHtml(action.subtitle)}</div>
                    </div>
                </div>
            `;
            
            resultItem.addEventListener('click', action.action);
            section.appendChild(resultItem);
        });

        return section;
    }

    function createSearchSection(title, items, type) {
        const section = document.createElement('div');
        section.className = 'info-center-search-section';

        const header = document.createElement('div');
        header.className = 'info-center-search-section-header';
        header.textContent = title + ' (' + items.length + ')';
        section.appendChild(header);

        items.forEach(item => {
            const resultItem = document.createElement('div');
            resultItem.className = 'info-center-search-result-item';

            let html = '';
            let url = item.url_backend || '#';
            let icon = getIconForType(type);
            let subtitle = '';
            let badge = '';
            let actions = '';

            switch(type) {
                case 'category':
                    subtitle = item.path ? item.path : 'ID: ' + item.id;
                    if (item.clang_name) {
                        badge = '<span class="info-center-search-badge">' + item.clang_name + '</span>';
                    }
                    if (item.status == 0) {
                        badge += '<span class="info-center-search-badge offline">Offline</span>';
                    }
                    if (item.url_frontend) {
                        actions = '<a href="' + item.url_frontend + '" target="_blank" class="info-center-search-action" title="Frontend-Vorschau" onclick="event.stopPropagation();"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>';
                    }
                    break;

                case 'article':
                    subtitle = item.path ? item.path : 'ID: ' + item.id;
                    if (item.clang_name) {
                        badge = '<span class="info-center-search-badge">' + item.clang_name + '</span>';
                    }
                    if (item.status == 0) {
                        badge += '<span class="info-center-search-badge offline">Offline</span>';
                    }
                    if (item.url_frontend) {
                        actions = '<a href="' + item.url_frontend + '" target="_blank" class="info-center-search-action" title="Frontend-Vorschau" onclick="event.stopPropagation();"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>';
                    }
                    break;

                case 'module':
                    subtitle = 'ID: ' + item.id;
                    if (item.code_snippet) {
                        icon = `<div class="info-center-code-preview" data-code-snippet="${escapeAttr(item.code_snippet)}">
                            ${icon}
                        </div>`;
                    }
                    break;

                case 'template':
                    subtitle = 'ID: ' + item.id;
                    if (item.active == 0) {
                        badge = '<span class="info-center-search-badge offline">Inaktiv</span>';
                    }
                    if (item.code_snippet) {
                        icon = `<div class="info-center-code-preview" data-code-snippet="${escapeAttr(item.code_snippet)}">
                            ${icon}
                        </div>`;
                    }
                    break;

                case 'media':
                    subtitle = item.filetype + ' • ' + formatFileSize(item.filesize);
                    break;
            }

            // Add update info if available
            if (item.updatedate && item.updateuser) {
                const timestamp = parseInt(item.updatedate, 10);
                const updateDate = new Date(timestamp * 1000);
                const formattedDate = updateDate.toLocaleString('de-DE', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                subtitle += ' • ' + formattedDate + ' (' + item.updateuser + ')';
            }

            // Special handling for media - open in popup
            let linkOnClick = '';
            
            if (type === 'media') {
                linkOnClick = `onclick="event.preventDefault(); openMediapoolPopup('${url}'); return false;"`;
                
                // Replace icon with thumbnail for images and videos
                const filetype = item.filetype ? item.filetype.toLowerCase() : '';
                const isImage = filetype.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'].includes(filetype);
                const isVideo = filetype.startsWith('video/') || ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'].includes(filetype);
                
                if (isImage && item.url_media_small) {
                    // Use rex_media_small for thumbnail and original for hover preview
                    icon = `<div class="info-center-image-preview" style="--preview-image: url('${item.url_media_small}');">
                        <img src="${item.url_media_small}" alt="${escapeAttr(item.filename)}" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;" />
                    </div>`;
                } else if (isVideo && item.url_media) {
                    icon = `<div class="info-center-video-preview" data-video-url="${item.url_media}">
                        ${getIconForType('media')}
                    </div>`;
                }
            }

            html = `
                <div class="info-center-search-result-wrapper">
                    <div class="info-center-search-result-icon">${icon}</div>
                    <a href="${url}" ${linkOnClick} class="info-center-search-result-link">
                        <div class="info-center-search-result-content">
                            <div class="info-center-search-result-title">
                                ${escapeHtml(item.name || item.title || item.filename)}
                                ${badge}
                            </div>
                            <div class="info-center-search-result-subtitle">${escapeHtml(subtitle)}</div>
                        </div>
                    </a>
                </div>
            `;

            resultItem.innerHTML = html;
            section.appendChild(resultItem);
        });

        // Add video preview tooltips
        section.querySelectorAll('.info-center-video-preview').forEach(videoPreview => {
            const videoUrl = videoPreview.getAttribute('data-video-url');
            if (videoUrl) {
                const tooltip = document.createElement('div');
                tooltip.className = 'info-center-video-preview-tooltip';
                tooltip.innerHTML = `<video src="${videoUrl}" muted loop autoplay></video>`;
                videoPreview.appendChild(tooltip);
            }
        });
        
        // Add code preview tooltips
        section.querySelectorAll('.info-center-code-preview').forEach(codePreview => {
            const codeSnippet = codePreview.getAttribute('data-code-snippet');
            if (codeSnippet) {
                const tooltip = document.createElement('div');
                tooltip.className = 'info-center-code-preview-tooltip';
                tooltip.innerHTML = `<pre><code>${escapeHtml(codeSnippet)}</code></pre>`;
                codePreview.appendChild(tooltip);
            }
        });

        return section;
    }

    // Open mediapool in popup
    window.openMediapoolPopup = function(url) {
        // Extract file_id from URL
        const urlParams = new URLSearchParams(url.split('?')[1]);
        const fileId = urlParams.get('file_id');
        
        if (fileId && typeof newPoolWindow === 'function') {
            return newPoolWindow('index.php?page=mediapool/media&file_id=' + fileId + '&opener_input_field=');
        } else {
            // Fallback: open in new window
            window.open(url, 'mediapool', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        }
    };

    function escapeAttr(text) {
        return text.replace(/'/g, '\\\'').replace(/"/g, '&quot;');
    }

    function updateSearchSelection(items) {
        items.forEach((item, index) => {
            if (index === searchSelectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    function getIconForType(type) {
        const icons = {
            'category': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-6l-2-2H5a2 2 0 0 0-2 2Z"/></svg>',
            'article': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>',
            'module': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
            'template': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
            'media': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
            'custom': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>'
        };
        return icons[type] || icons.custom;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function getQuickActions(query) {
        const actions = [];
        const trimmedQuery = query.trim();
        
        // Calculator
        if (/^[\d\s+\-*\/().%]+$/.test(trimmedQuery) && /[\d]/.test(trimmedQuery)) {
            try {
                const result = eval(trimmedQuery);
                if (!isNaN(result) && isFinite(result)) {
                    actions.push({
                        type: 'calculator',
                        title: '= ' + result,
                        subtitle: trimmedQuery,
                        action: () => {
                            navigator.clipboard.writeText(result.toString());
                            alert('Ergebnis kopiert: ' + result);
                        }
                    });
                }
            } catch (e) {
                // Invalid expression, ignore
            }
        }
        
        // Wikipedia
        if (trimmedQuery.toLowerCase().startsWith('wiki ') && trimmedQuery.length > 5) {
            const searchTerm = trimmedQuery.substring(5);
            actions.push({
                type: 'wikipedia',
                title: 'Wikipedia: ' + searchTerm,
                subtitle: 'Suche in Wikipedia öffnen',
                action: () => {
                    window.open('https://de.wikipedia.org/wiki/Special:Search?search=' + encodeURIComponent(searchTerm), '_blank');
                }
            });
        }
        
        // Dictionary/Define
        if (trimmedQuery.toLowerCase().startsWith('define ') && trimmedQuery.length > 7) {
            const word = trimmedQuery.substring(7);
            actions.push({
                type: 'dictionary',
                title: 'Definition: ' + word,
                subtitle: 'Suche in Wiktionary öffnen',
                action: () => {
                    window.open('https://de.wiktionary.org/wiki/' + encodeURIComponent(word), '_blank');
                }
            });
        }
        
        // URL Detection
        if (/^https?:\/\/.+/.test(trimmedQuery)) {
            actions.push({
                type: 'url',
                title: 'URL öffnen',
                subtitle: trimmedQuery,
                action: () => {
                    window.open(trimmedQuery, '_blank');
                }
            });
        }
        
        return actions;
    }

    // Initialize tabs on load
    document.addEventListener('DOMContentLoaded', function() {
        initTabs();
        initSearchWidget();
    });

})(); // Self-executing anonymous function without jQuery dependency
