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
        
        // Parse filters from query
        const filters = parseSearchFilters(query);
        const searchQuery = filters.query;
        
        // Build API URL with filters
        let apiUrl = 'index.php?rex-api-call=info_center_search&query=' + encodeURIComponent(searchQuery);
        if (filters.modified) apiUrl += '&modified=' + encodeURIComponent(filters.modified);
        if (filters.created) apiUrl += '&created=' + encodeURIComponent(filters.created);
        if (filters.author) apiUrl += '&author=' + encodeURIComponent(filters.author);
        if (filters.editor) apiUrl += '&editor=' + encodeURIComponent(filters.editor);

        fetch(apiUrl)
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
            
            // Create placeholder action that will be updated with API data
            const wikiAction = {
                type: 'wikipedia',
                title: 'Wikipedia: ' + searchTerm,
                subtitle: 'Lade Vorschau...',
                isLoading: true,
                action: () => {
                    window.open('https://de.wikipedia.org/wiki/Special:Search?search=' + encodeURIComponent(searchTerm), '_blank');
                }
            };
            
            actions.push(wikiAction);
            
            // Fetch Wikipedia summary asynchronously
            fetchWikipediaSummary(searchTerm).then(summary => {
                if (summary) {
                    wikiAction.subtitle = summary;
                    wikiAction.isLoading = false;
                    // Re-render the quick actions section
                    const searchResults = document.getElementById('info-center-search-results');
                    if (searchResults) {
                        searchResults.innerHTML = '';
                        const section = createQuickActionsSection([wikiAction]);
                        searchResults.appendChild(section);
                    }
                }
            });
        }
        
        // Dictionary/Define
        if (trimmedQuery.toLowerCase().startsWith('define ') && trimmedQuery.length > 7) {
            const word = trimmedQuery.substring(7);
            
            // Create placeholder action that will be updated with API data
            const dictAction = {
                type: 'dictionary',
                title: 'Definition: ' + word,
                subtitle: 'Lade Definition...',
                isLoading: true,
                action: () => {
                    window.open('https://de.wiktionary.org/wiki/' + encodeURIComponent(word), '_blank');
                }
            };
            
            actions.push(dictAction);
            
            // Fetch Wiktionary summary asynchronously
            fetchWiktionarySummary(word).then(summary => {
                if (summary) {
                    dictAction.subtitle = summary;
                    dictAction.isLoading = false;
                    // Re-render the quick actions section
                    const searchResults = document.getElementById('info-center-search-results');
                    if (searchResults) {
                        searchResults.innerHTML = '';
                        const section = createQuickActionsSection([dictAction]);
                        searchResults.appendChild(section);
                    }
                }
            });
        }
        
        // Blindtext Generator
        if (trimmedQuery.toLowerCase().startsWith('blindtext')) {
            const match = trimmedQuery.match(/blindtext\s+(\d+)/i);
            const length = match ? parseInt(match[1]) : 300;
            const blindtext = generateBlindtext(length);
            
            actions.push({
                type: 'calculator', // Use calculator icon (clipboard)
                title: 'Blindtext generiert (' + blindtext.length + ' Zeichen)',
                subtitle: blindtext.substring(0, 200) + (blindtext.length > 200 ? '...' : ''),
                action: () => {
                    navigator.clipboard.writeText(blindtext);
                    alert('Blindtext kopiert (' + blindtext.length + ' Zeichen)');
                }
            });
        }
        
        // QR Code Generator
        if (trimmedQuery.toLowerCase().startsWith('qr ') && trimmedQuery.length > 3) {
            const qrText = trimmedQuery.substring(3);
            actions.push({
                type: 'url',
                title: 'QR Code: ' + qrText.substring(0, 50) + (qrText.length > 50 ? '...' : ''),
                subtitle: 'QR Code generieren und als PNG herunterladen',
                action: () => {
                    generateQRCode(qrText);
                }
            });
        }
        
        // Help Command
        if (trimmedQuery.toLowerCase() === '#help' || trimmedQuery.toLowerCase() === 'help') {
            actions.push({
                type: 'dictionary',
                title: 'Hilfe - Quick Actions & Filter',
                subtitle: 'Alle verfügbaren Befehle und Funktionen anzeigen',
                action: () => {
                    showHelpModal();
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
    
    async function fetchWikipediaSummary(searchTerm) {
        try {
            const response = await fetch(`https://de.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(searchTerm)}`);
            if (!response.ok) {
                return 'Keine Wikipedia-Seite gefunden';
            }
            const data = await response.json();
            
            // Get extract (first paragraph)
            let summary = data.extract || 'Keine Beschreibung verfügbar';
            
            // Limit length to 500 characters for better preview
            if (summary.length > 500) {
                summary = summary.substring(0, 500) + '...';
            }
            
            return summary;
        } catch (error) {
            console.error('Wikipedia API error:', error);
            return 'Wikipedia-Vorschau nicht verfügbar';
        }
    }
    
    async function fetchWiktionarySummary(word) {
        try {
            // Wiktionary has no official REST API, so we fetch the HTML page
            const response = await fetch(`https://de.wiktionary.org/api/rest_v1/page/html/${encodeURIComponent(word)}`);
            if (!response.ok) {
                return 'Kein Wiktionary-Eintrag gefunden';
            }
            const html = await response.text();
            
            // Parse HTML to extract the first definition
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Try to find the first definition (usually in <dd> tags)
            const definitions = doc.querySelectorAll('dd');
            if (definitions.length > 0) {
                let definition = definitions[0].textContent.trim();
                
                // Limit length to 500 characters
                if (definition.length > 500) {
                    definition = definition.substring(0, 500) + '...';
                }
                
                return definition;
            }
            
            // Fallback: try to find any paragraph text
            const paragraphs = doc.querySelectorAll('p');
            for (let p of paragraphs) {
                const text = p.textContent.trim();
                if (text.length > 20) {
                    let definition = text;
                    if (definition.length > 500) {
                        definition = definition.substring(0, 500) + '...';
                    }
                    return definition;
                }
            }
            
            return 'Definition nicht gefunden';
        } catch (error) {
            console.error('Wiktionary API error:', error);
            return 'Wiktionary-Vorschau nicht verfügbar';
        }
    }
    
    function generateBlindtext(targetLength) {
        const texts = [
            "Dies ist ein Platzhaltertext, der zeigt, wie der echte Inhalt später aussehen wird. Er füllt den Raum und gibt einen Eindruck von Länge und Struktur. Perfekt zum Testen von Layouts und Designs.",
            "Hier könnte Ihr Text stehen! Momentan bin ich nur ein bescheidener Blindtext, der darauf wartet, durch echten Content ersetzt zu werden. Ich mache meinen Job gut: Platz halten, Länge zeigen, Layout testen.",
            "Dieser Text ist absichtlich bedeutungslos und dient nur dazu, das Design zu prüfen. Wie sieht die Schrift aus? Passt der Zeilenabstand? Funktioniert der Umbruch? All diese Fragen beantworte ich.",
            "Ein Dummy-Text wie ich hat es nicht leicht. Ständig werde ich kopiert, eingefügt und wieder gelöscht. Aber ich beschwere mich nicht! Ich bin stolz darauf, Designern und Entwicklern zu helfen.",
            "Wussten Sie schon? Ich bin ein Blindtext der modernen Art. Keine lateinischen Floskeln, sondern verständliches Deutsch. So können Sie wirklich sehen, wie Ihr deutscher Content später wirkt.",
            "Als professioneller Platzhalter erfülle ich eine wichtige Funktion: Ich zeige, wo Text hingehört, ohne vom Design abzulenken. Neutral, unauffällig und doch präsent – das ist meine Mission.",
            "Entwickler lieben mich, Designer brauchen mich. Ich bin der perfekte Testtext für Ihre Website, App oder Printprodukt. Kopieren Sie mich, nutzen Sie mich, und ersetzen Sie mich später!",
            "Manchmal frage ich mich, ob echte Texte auch so ein erfülltes Leben haben wie ich. Jeden Tag helfe ich Menschen, großartige Designs zu erstellen. Was will man mehr?"
        ];
        
        // Pick random starting text
        let result = texts[Math.floor(Math.random() * texts.length)];
        
        // Add more random texts until we reach target length
        while (result.length < targetLength) {
            const randomText = texts[Math.floor(Math.random() * texts.length)];
            result += ' ' + randomText;
        }
        
        // Trim to exact length at sentence end if possible
        if (result.length > targetLength) {
            const trimmed = result.substring(0, targetLength);
            const lastPeriod = trimmed.lastIndexOf('.');
            const lastExclamation = trimmed.lastIndexOf('!');
            const lastQuestion = trimmed.lastIndexOf('?');
            const lastSentenceEnd = Math.max(lastPeriod, lastExclamation, lastQuestion);
            
            if (lastSentenceEnd > targetLength * 0.8) {
                // If we find a sentence end in the last 20%, use it
                result = trimmed.substring(0, lastSentenceEnd + 1);
            } else {
                // Otherwise just cut at word boundary
                const lastSpace = trimmed.lastIndexOf(' ');
                result = trimmed.substring(0, lastSpace) + '...';
            }
        }
        
        return result;
    }
    
    function parseSearchFilters(query) {
        const filters = {
            query: query,
            modified: null,
            created: null,
            author: null,
            editor: null
        };
        
        // Extract modified: filter
        const modifiedMatch = query.match(/modified:(today|yesterday|last-week|last-month|\\d{4}-\\d{2}-\\d{2})/i);
        if (modifiedMatch) {
            filters.modified = modifiedMatch[1];
            filters.query = filters.query.replace(modifiedMatch[0], '').trim();
        }
        
        // Extract created: filter
        const createdMatch = query.match(/created:(today|yesterday|last-week|last-month|\\d{4}-\\d{2}-\\d{2})/i);
        if (createdMatch) {
            filters.created = createdMatch[1];
            filters.query = filters.query.replace(createdMatch[0], '').trim();
        }
        
        // Extract author: filter
        const authorMatch = query.match(/author:(\\S+)/i);
        if (authorMatch) {
            filters.author = authorMatch[1];
            filters.query = filters.query.replace(authorMatch[0], '').trim();
        }
        
        // Extract editor: filter
        const editorMatch = query.match(/editor:(\\S+)/i);
        if (editorMatch) {
            filters.editor = editorMatch[1];
            filters.query = filters.query.replace(editorMatch[0], '').trim();
        }
        
        return filters;
    }
    
    function generateQRCode(text) {
        // Create modal to show QR code
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:999999;';
        
        const content = document.createElement('div');
        content.style.cssText = 'background:white;padding:20px;border-radius:8px;text-align:center;max-width:90%;';
        
        const title = document.createElement('h3');
        title.textContent = 'QR Code';
        title.style.marginTop = '0';
        content.appendChild(title);
        
        // QR Code container
        const qrContainer = document.createElement('div');
        qrContainer.style.cssText = 'margin:20px auto;display:inline-block;padding:10px;background:white;';
        
        // Generate QR Code as SVG
        const svg = generateQRCodeSVG(text, 400);
        qrContainer.appendChild(svg);
        content.appendChild(qrContainer);
        
        // Button container
        const buttonContainer = document.createElement('div');
        buttonContainer.style.marginTop = '20px';
        
        // Download SVG button
        const downloadSvgBtn = document.createElement('button');
        downloadSvgBtn.textContent = 'Als SVG herunterladen';
        downloadSvgBtn.className = 'btn btn-primary';
        downloadSvgBtn.style.marginRight = '10px';
        downloadSvgBtn.onclick = () => {
            const svgData = new XMLSerializer().serializeToString(svg);
            const blob = new Blob([svgData], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'qrcode.svg';
            a.click();
            URL.revokeObjectURL(url);
        };
        buttonContainer.appendChild(downloadSvgBtn);
        
        // Download PNG button
        const downloadPngBtn = document.createElement('button');
        downloadPngBtn.textContent = 'Als PNG herunterladen';
        downloadPngBtn.className = 'btn btn-default';
        downloadPngBtn.style.marginRight = '10px';
        downloadPngBtn.onclick = () => {
            const canvas = document.createElement('canvas');
            canvas.width = 800;
            canvas.height = 800;
            const ctx = canvas.getContext('2d');
            
            const svgData = new XMLSerializer().serializeToString(svg);
            const img = new Image();
            const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(svgBlob);
            
            img.onload = () => {
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, 800, 800);
                ctx.drawImage(img, 0, 0, 800, 800);
                
                canvas.toBlob((blob) => {
                    const pngUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = pngUrl;
                    a.download = 'qrcode.png';
                    a.click();
                    URL.revokeObjectURL(pngUrl);
                    URL.revokeObjectURL(url);
                });
            };
            img.src = url;
        };
        buttonContainer.appendChild(downloadPngBtn);
        
        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.textContent = 'Schließen';
        closeBtn.className = 'btn btn-default';
        closeBtn.onclick = () => modal.remove();
        buttonContainer.appendChild(closeBtn);
        
        content.appendChild(buttonContainer);
        modal.appendChild(content);
        modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        
        document.body.appendChild(modal);
    }
    
    function generateQRCodeSVG(text, size) {
        // Generate QR matrix using a simple algorithm
        const matrix = generateQRMatrix(text);
        const moduleSize = size / matrix.length;
        
        // Create SVG
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', size);
        svg.setAttribute('height', size);
        svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
        
        // Background
        const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('width', size);
        bg.setAttribute('height', size);
        bg.setAttribute('fill', '#FFFFFF');
        svg.appendChild(bg);
        
        // Draw modules
        for (let y = 0; y < matrix.length; y++) {
            for (let x = 0; x < matrix[y].length; x++) {
                if (matrix[y][x]) {
                    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    rect.setAttribute('x', x * moduleSize);
                    rect.setAttribute('y', y * moduleSize);
                    rect.setAttribute('width', moduleSize);
                    rect.setAttribute('height', moduleSize);
                    rect.setAttribute('fill', '#000000');
                    svg.appendChild(rect);
                }
            }
        }
        
        return svg;
    }
    
    function generateQRMatrix(text) {
        // QR Code Version 2 (25x25) - supports up to 47 alphanumeric characters
        const size = 25;
        const matrix = Array(size).fill(0).map(() => Array(size).fill(0));
        
        // Add finder patterns (7x7 in corners)
        addFinderPattern(matrix, 0, 0);
        addFinderPattern(matrix, size - 7, 0);
        addFinderPattern(matrix, 0, size - 7);
        
        // Add separators (white border around finders)
        addSeparators(matrix, size);
        
        // Add timing patterns
        for (let i = 8; i < size - 8; i++) {
            matrix[6][i] = (i % 2 === 0) ? 1 : 0;
            matrix[i][6] = (i % 2 === 0) ? 1 : 0;
        }
        
        // Add alignment pattern for Version 2
        addAlignmentPattern(matrix, 18, 18);
        
        // Encode data
        encodeData(matrix, text, size);
        
        return matrix;
    }
    
    function addFinderPattern(matrix, startY, startX) {
        for (let y = 0; y < 7; y++) {
            for (let x = 0; x < 7; x++) {
                const isOuter = y === 0 || y === 6 || x === 0 || x === 6;
                const isCenter = y >= 2 && y <= 4 && x >= 2 && x <= 4;
                matrix[startY + y][startX + x] = (isOuter || isCenter) ? 1 : 0;
            }
        }
    }
    
    function addSeparators(matrix, size) {
        // Top-left separator
        for (let i = 0; i < 8; i++) {
            matrix[7][i] = 0;
            matrix[i][7] = 0;
        }
        // Top-right separator
        for (let i = 0; i < 8; i++) {
            matrix[7][size - 8 + i] = 0;
            matrix[i][size - 8] = 0;
        }
        // Bottom-left separator
        for (let i = 0; i < 8; i++) {
            matrix[size - 8][i] = 0;
            matrix[size - 8 + i][7] = 0;
        }
    }
    
    function addAlignmentPattern(matrix, centerY, centerX) {
        for (let y = -2; y <= 2; y++) {
            for (let x = -2; x <= 2; x++) {
                const isOuter = Math.abs(y) === 2 || Math.abs(x) === 2;
                const isCenter = y === 0 && x === 0;
                matrix[centerY + y][centerX + x] = (isOuter || isCenter) ? 1 : 0;
            }
        }
    }
    
    function encodeData(matrix, text, size) {
        // Convert text to binary (simplified encoding)
        const data = textToBinary(text);
        let dataIndex = 0;
        
        // Zigzag pattern (right to left, bottom to top)
        for (let col = size - 1; col > 0; col -= 2) {
            if (col === 6) col--; // Skip timing column
            
            for (let row = size - 1; row >= 0; row--) {
                for (let c = 0; c < 2; c++) {
                    const x = col - c;
                    if (!isReservedPosition(matrix, row, x, size)) {
                        matrix[row][x] = dataIndex < data.length ? parseInt(data[dataIndex]) : 0;
                        dataIndex++;
                    }
                }
            }
        }
    }
    
    function textToBinary(text) {
        let binary = '';
        for (let i = 0; i < text.length; i++) {
            const byte = text.charCodeAt(i).toString(2).padStart(8, '0');
            binary += byte;
        }
        // Pad to fill matrix
        while (binary.length < 400) {
            binary += '0';
        }
        return binary;
    }
    
    function isReservedPosition(matrix, y, x, size) {
        // Finder patterns + separators
        if ((y < 9 && x < 9) || (y < 9 && x >= size - 8) || (y >= size - 8 && x < 9)) return true;
        // Timing patterns
        if (y === 6 || x === 6) return true;
        // Alignment pattern (Version 2 at 18,18)
        if (Math.abs(y - 18) <= 2 && Math.abs(x - 18) <= 2) return true;
        return false;
    }
    
    function showHelpModal() {
        // Detect dark mode
        const isDarkMode = document.documentElement.classList.contains('rex-theme-dark') || 
                          document.body.classList.contains('rex-theme-dark') ||
                          window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        const bgColor = isDarkMode ? '#1e1e1e' : '#ffffff';
        const textColor = isDarkMode ? '#e0e0e0' : '#2c3e50';
        const headingColor = isDarkMode ? '#4a9eff' : '#3498db';
        const boxBg = isDarkMode ? '#2d2d2d' : '#f8f9fa';
        const codeBg = isDarkMode ? '#3d3d3d' : '#e9ecef';
        const codeColor = isDarkMode ? '#e0e0e0' : '#333333';
        
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:999999;overflow-y:auto;padding:20px;';
        
        const content = document.createElement('div');
        content.style.cssText = `background:${bgColor};color:${textColor};padding:30px;border-radius:8px;max-width:800px;width:100%;max-height:90vh;overflow-y:auto;`;
        
        content.innerHTML = `
            <h2 style="margin-top:0;color:${headingColor};">🔍 Search Widget - Hilfe</h2>
            
            <h3 style="color:${headingColor};margin-top:25px;">📊 Quick Actions</h3>
            <div style="background:${boxBg};padding:15px;border-radius:5px;margin-bottom:20px;">
                <p style="margin:5px 0;color:${textColor};"><strong>🧮 Rechner:</strong> <code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">2+2</code> oder <code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">15*20%</code> - Ergebnis wird kopiert</p>
                <p style="margin:5px 0;color:${textColor};"><strong>📚 Wikipedia:</strong> <code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">wiki REDAXO</code> - Live-Vorschau aus Wikipedia</p>
                <p style="margin:5px 0;color:${textColor};"><strong>📖 Wörterbuch:</strong> <code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">define CMS</code> - Definition aus Wiktionary</p>
                <p style="margin:5px 0;color:${textColor};"><strong>📝 Blindtext:</strong> <code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">blindtext 500</code> - Generiert deutschen Platzhaltertext</p>
                <p style="margin:5px 0;color:${textColor};"><strong>📱 QR Code:</strong> <code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">qr https://example.com</code> - Erstellt QR Code als SVG/PNG</p>
                <p style="margin:5px 0;color:${textColor};"><strong>🔗 URL:</strong> URLs werden automatisch erkannt und können geöffnet werden</p>
            </div>
            
            <h3 style="color:${headingColor};margin-top:25px;">🔎 Erweiterte Filter</h3>
            <div style="background:${boxBg};padding:15px;border-radius:5px;margin-bottom:20px;">
                <p style="margin:10px 0;color:${textColor};"><strong>📅 Datum-Filter (geändert):</strong></p>
                <ul style="margin:5px 0 10px 20px;color:${textColor};">
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">modified:today</code> - Heute geändert</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">modified:yesterday</code> - Gestern geändert</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">modified:last-week</code> - Letzte 7 Tage</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">modified:last-month</code> - Letzte 30 Tage</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">modified:2025-12-05</code> - Exaktes Datum</li>
                </ul>
                
                <p style="margin:10px 0;color:${textColor};"><strong>📅 Datum-Filter (erstellt):</strong></p>
                <ul style="margin:5px 0 10px 20px;color:${textColor};">
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">created:today</code> - Heute erstellt</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">created:last-week</code> - Letzte 7 Tage</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">created:2025-12-01</code> - Exaktes Datum</li>
                </ul>
                
                <p style="margin:10px 0;color:${textColor};"><strong>👤 Benutzer-Filter:</strong></p>
                <ul style="margin:5px 0 10px 20px;color:${textColor};">
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">author:admin</code> - Erstellt von User "admin"</li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">editor:thomas</code> - Bearbeitet von User "thomas"</li>
                </ul>
                
                <p style="margin:10px 0;color:${textColor};"><strong>🔗 Kombinationen:</strong></p>
                <ul style="margin:5px 0 10px 20px;color:${textColor};">
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">test modified:today</code></li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">artikel author:admin created:last-week</code></li>
                    <li><code style="background:${codeBg};color:${codeColor};padding:2px 6px;border-radius:3px;">news modified:yesterday editor:thomas</code></li>
                </ul>
            </div>
            
            <h3 style="color:${headingColor};margin-top:25px;">⌨️ Tastatur-Shortcuts</h3>
            <div style="background:${boxBg};padding:15px;border-radius:5px;margin-bottom:20px;">
                <p style="margin:5px 0;color:${textColor};"><strong>⌘K</strong> oder <strong>Ctrl+K</strong> - Suche fokussieren</p>
                <p style="margin:5px 0;color:${textColor};"><strong>↑/↓</strong> - Durch Ergebnisse navigieren</p>
                <p style="margin:5px 0;color:${textColor};"><strong>Enter</strong> - Ausgewähltes Ergebnis öffnen</p>
                <p style="margin:5px 0;color:${textColor};"><strong>ESC</strong> - Suche schließen</p>
            </div>
            
            <h3 style="color:${headingColor};margin-top:25px;">🔍 Normale Suche</h3>
            <div style="background:${boxBg};padding:15px;border-radius:5px;margin-bottom:20px;">
                <p style="margin:5px 0;color:${textColor};">Durchsucht automatisch:</p>
                <ul style="margin:5px 0 0 20px;color:${textColor};">
                    <li><strong>Artikel</strong> - Name, ID und alle Content-Felder (value1-20)</li>
                    <li><strong>Kategorien</strong> - Name und ID</li>
                    <li><strong>Module</strong> - Name und Code</li>
                    <li><strong>Templates</strong> - Name und Code</li>
                    <li><strong>Medien</strong> - Dateiname und Titel</li>
                </ul>
            </div>
            
            <div style="text-align:center;margin-top:25px;">
                <button class="btn btn-primary" onclick="this.closest('div[style*=fixed]').remove()">
                    Schließen
                </button>
            </div>
        `;
        
        modal.appendChild(content);
        modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        
        document.body.appendChild(modal);
    }

    // Initialize tabs on load
    document.addEventListener('DOMContentLoaded', function() {
        initTabs();
        initSearchWidget();
    });

})(); // Self-executing anonymous function without jQuery dependency
