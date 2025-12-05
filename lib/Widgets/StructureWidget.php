<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_article;
use rex_category;
use rex_clang;
use rex_context;
use rex_i18n;
use rex_url;
use rex_addon;
use rex_yrewrite;

class StructureWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = false;

    public function __construct()
    {
        parent::__construct();
        $this->title = '🏗️ ' . rex_i18n::msg('info_center_structure_title');
    }

    public function render(): string
    {
        // Backend-User-Session erstellen
        if (!rex::getUser()) {
            return '';
        }

        $clangId = rex_clang::getCurrentId();
        $currentArticleId = rex_request('article_id', 'int', 0);
        $currentCategoryId = rex_request('category_id', 'int', 0);
        
        // Determine current position from URL parameters
        // If we have article_id, that's our current article
        if ($currentArticleId > 0) {
            $article = rex_article::get($currentArticleId, $clangId);
            if ($article) {
                // If article has no category (root), keep currentCategoryId = 0
                // Otherwise use the article's category
                if ($article->getCategoryId() > 0) {
                    $currentCategoryId = $article->getCategoryId();
                }
            }
        }
        // If we only have category_id (structure page), that's our current category
        // currentCategoryId is already set from rex_request
        
        // Domain Switcher für YRewrite (nur wenn mehrere Domains vorhanden)
        $domainSwitcher = '';
        $autoDomainId = 0;
        
        if (rex_addon::get('yrewrite')->isAvailable()) {
            $domains = rex_yrewrite::getDomains();
            // Alle Domains verwenden - auch solche mit MountId 0 (Root-Ebene)
            // Die Default-Domain und Domains mit Root-Mount sind beide gültig
            $validDomains = [];
            foreach ($domains as $domain) {
                // Alle Domains einschließen, auch wenn getMountId() == 0
                $validDomains[] = $domain;
            }
            
            // Auto-detect domain based on current article/category
            if ($currentArticleId > 0 || $currentCategoryId > 0) {
                $checkId = $currentArticleId > 0 ? $currentArticleId : $currentCategoryId;
                $checkArticle = rex_article::get($checkId, $clangId);
                
                if ($checkArticle) {
                    $path = explode('|', trim($checkArticle->getPath(), '|'));
                    if (!empty($path)) {
                        $rootId = (int) $path[0];
                        
                        // Check if this root belongs to a domain
                        $foundDomain = false;
                        foreach ($validDomains as $domain) {
                            if ($domain->getMountId() == $rootId) {
                                $autoDomainId = $domain->getId();
                                $foundDomain = true;
                                break;
                            }
                        }
                        
                        // If root category is not assigned to any domain, set to 0 (all domains)
                        if (!$foundDomain) {
                            $autoDomainId = 0;
                        }
                    } else {
                        // No path = root level without domain assignment
                        $autoDomainId = 0;
                    }
                }
            }
            
            if (count($validDomains) > 1) {
                $selectedDomain = rex_request('info_center_domain', 'int', 0);
                
                // Use auto-detected domain if no manual selection
                if ($selectedDomain == 0 && $autoDomainId > 0) {
                    $selectedDomain = $autoDomainId;
                }
                
                // Check if auto-switch is enabled in user settings
                $package = rex_addon::get('info_center');
                $userId = rex::getUser()->getId();
                $uiSettings = $package->getConfig('ui_settings_user_' . $userId, []);
                $autoSwitchEnabled = $uiSettings['auto_switch_domain'] ?? true;
                
                $domainSwitcher = '
                    <div class="info-center-domain-switcher">
                        <select id="info-center-domain-select" class="form-control" data-auto-domain="' . $autoDomainId . '" data-auto-switch="' . ($autoSwitchEnabled ? '1' : '0') . '" data-selected-domain="' . $selectedDomain . '">
                            <option value="0"' . ($selectedDomain == 0 ? ' selected' : '') . '>' . rex_i18n::msg('info_center_all_domains', 'Alle Domains') . '</option>';
                
                foreach ($validDomains as $domain) {
                    // Filtere "default" Domain heraus wenn sie ID 0 hat
                    if ($domain->getId() == 0 && strtolower($domain->getName()) === 'default') {
                        continue;
                    }
                    
                    $domainSwitcher .= sprintf(
                        '<option value="%d"%s>%s</option>',
                        $domain->getId(),
                        $selectedDomain == $domain->getId() ? ' selected' : '',
                        $domain->getName()
                    );
                }
                
                $domainSwitcher .= '
                        </select>
                    </div>';
            }
        }
        
        $structure = $this->buildStructureTree($clangId, 0, $currentCategoryId, $currentArticleId);
        
        $searchbar = '
            <div class="info-center-structure-search">
                <input type="text" 
                    id="structure-search" 
                    class="form-control" 
                    placeholder="' . rex_i18n::msg('info_center_structure_search', 'Suche in Struktur...') . '">
            </div>';

        $content = $domainSwitcher . $searchbar . '
            <div class="info-center-structure-tree">
                ' . $this->renderTree($structure, 0) . '
            </div>';

        return $content;
    }

    private function buildStructureTree(int $clangId, int $parentId = 0, int $currentCategoryId = 0, int $currentArticleId = 0, bool $lazyLoad = true): array
    {
        $user = rex::getUser();
        $tree = [];
        $selectedDomain = rex_request('info_center_domain', 'int', 0);

        if ($parentId === 0) {
            // Wenn Domain ausgewählt und YRewrite verfügbar, filtere nach Domain-Mountpoint
            if ($selectedDomain > 0 && rex_addon::get('yrewrite')->isAvailable()) {
                $domain = rex_yrewrite::getDomainById($selectedDomain);
                if ($domain && $domain->getMountId() > 0) {
                    $mountId = $domain->getMountId();
                    if ($category = rex_category::get($mountId, $clangId)) {
                        if ($user->getComplexPerm('structure')->hasCategoryPerm($category->getId())) {
                            $tree[] = $this->buildCategoryNode($category, $clangId, $currentCategoryId, $currentArticleId, $lazyLoad);
                        }
                    }
                    return $tree;
                }
            }
            
            // Root-Level Kategorien oder Mountpoints
            $mountpoints = $user->getComplexPerm('structure')->getMountpoints();
            if (!empty($mountpoints)) {
                foreach ($mountpoints as $mpId) {
                    if ($category = rex_category::get($mpId, $clangId)) {
                        $tree[] = $this->buildCategoryNode($category, $clangId, $currentCategoryId, $currentArticleId, $lazyLoad);
                    }
                }
            } else {
                $categories = rex_category::getRootCategories(false, $clangId);
                foreach ($categories as $category) {
                    if ($user->getComplexPerm('structure')->hasCategoryPerm($category->getId())) {
                        $tree[] = $this->buildCategoryNode($category, $clangId, $currentCategoryId, $currentArticleId, $lazyLoad);
                    }
                }
                
                // Add root articles (not in any category) to tree
                $rootArticles = rex_article::getRootArticles(false, $clangId);
                foreach ($rootArticles as $article) {
                    if (!$article->isStartArticle()) {
                        // Check if article ID matches any category ID (would be a start article)
                        $isStartArticle = rex_category::get($article->getId(), $clangId) !== null;
                        if (!$isStartArticle) {
                            $articleUrl = rex_url::backendPage('content/edit', [
                                'article_id' => $article->getId(),
                                'clang' => $clangId
                            ]);
                            
                            $tree[] = [
                                'id' => $article->getId(),
                                'name' => $article->getName(),
                                'status' => $article->getValue('status'),
                                'url' => html_entity_decode($articleUrl, ENT_QUOTES | ENT_HTML5),
                                'domain' => '',
                                'hasChildren' => false,
                                'children' => [],
                                'articles' => [],
                                'isInPath' => false,
                                'isCurrent' => $article->getId() == $currentArticleId,
                                'isStartArticle' => false,
                                'isArticle' => true,
                                'updateuser' => $article->getValue('updateuser'),
                                'updatedate' => $article->getValue('updatedate'),
                            ];
                        }
                    }
                }
            }
        } else {
            if ($parentCategory = rex_category::get($parentId, $clangId)) {
                $categories = $parentCategory->getChildren(false);
                foreach ($categories as $category) {
                    if ($user->getComplexPerm('structure')->hasCategoryPerm($category->getId())) {
                        $tree[] = $this->buildCategoryNode($category, $clangId, $currentCategoryId, $currentArticleId, $lazyLoad);
                    }
                }
            }
        }

        return $tree;
    }

    private function buildCategoryNode(rex_category $category, int $clangId, int $currentCategoryId = 0, int $currentArticleId = 0, bool $lazyLoad = true): array
    {
        $user = rex::getUser();
        $categoryId = $category->getId();
        
        // Only load children if this category is in the path to current position
        $isInPath = $this->isInPath($categoryId, $currentCategoryId, $currentArticleId, $clangId);
        $isCurrent = ($categoryId == $currentCategoryId && $currentArticleId == 0) || 
                     ($categoryId == $currentArticleId);
        
        // Load children only if in path or not lazy loading
        $children = [];
        if (!$lazyLoad || $isInPath || $isCurrent) {
            $children = $this->buildStructureTree($clangId, $categoryId, $currentCategoryId, $currentArticleId, $lazyLoad);
        }
        
        // Build clean backend URL
        $url = rex_url::backendPage('structure', [
            'category_id' => $categoryId,
            'article_id' => $categoryId,
            'clang' => $clangId
        ]);
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
        
        $domain = '';
        if (rex_addon::get('yrewrite')->isAvailable()) {
            $yrewriteDomain = \rex_yrewrite::getDomainByArticleId($categoryId);
            if ($yrewriteDomain) {
                $domain = $yrewriteDomain->getName();
            }
        }
        
        // Check if this category is in the path to current article/category
        $isInPath = $this->isInPath($categoryId, $currentCategoryId, $currentArticleId, $clangId);
        $isCurrent = ($categoryId == $currentCategoryId && $currentArticleId == 0) || 
                     ($categoryId == $currentArticleId);
        
        // If this is the current category being viewed (from structure page), it should be expanded
        // This ensures that when you're on page=structure&category_id=3, category 3 is opened
        if ($categoryId == $currentCategoryId) {
            $isInPath = true;
        }
        
        // Get articles in this category if user has permission
        // Only load articles if we're loading children (not lazy loading or in path)
        $articles = [];
        if (!$lazyLoad || $isInPath || $isCurrent) {
            if ($user->getComplexPerm('structure')->hasCategoryPerm($categoryId)) {
                // Get articles using category method (excludes start article automatically)
                $articlesInCategory = $category->getArticles(false);
                foreach ($articlesInCategory as $article) {
                    // getArticles() already excludes start article, so we don't need to check
                    // But also check if article ID matches a category ID (would be a start article of a subcategory)
                    $isStartArticleOfSubcategory = rex_category::get($article->getId(), $clangId) !== null;
                    
                    if (!$isStartArticleOfSubcategory) {
                        $articleUrl = rex_url::backendPage('content/edit', [
                            'category_id' => $categoryId,
                            'article_id' => $article->getId(),
                            'clang' => $clangId,
                            'mode' => 'edit'
                        ]);
                        
                        $articles[] = [
                            'id' => $article->getId(),
                            'name' => $article->getName(),
                            'status' => $article->getValue('status'),
                            'url' => html_entity_decode($articleUrl, ENT_QUOTES | ENT_HTML5),
                            'isCurrent' => $article->getId() == $currentArticleId,
                            'updateuser' => $article->getValue('updateuser'),
                            'updatedate' => $article->getValue('updatedate'),
                        ];
                    }
                }
            }
        }
        
        // Always check if category has potential children (even if not loaded yet)
        // getChildren() returns subcategories, getArticles() returns articles (excluding start article)
        $subcategories = $category->getChildren(false);
        $articlesInCategory = $category->getArticles(false);
        
        // Filter out articles that are start articles of subcategories
        $actualArticles = [];
        foreach ($articlesInCategory as $article) {
            $isStartArticleOfSubcategory = rex_category::get($article->getId(), $clangId) !== null;
            if (!$isStartArticleOfSubcategory) {
                $actualArticles[] = $article;
            }
        }
        
        $hasChildren = count($subcategories) > 0 || count($actualArticles) > 0;
        
        // Get update information
        $updateUser = $category->getValue('updateuser');
        $updateDate = $category->getValue('updatedate');

        return [
            'id' => $categoryId,
            'name' => $category->getName(),
            'status' => $category->getValue('status'),
            'url' => $url,
            'domain' => $domain,
            'hasChildren' => $hasChildren,
            'children' => $children,
            'articles' => $articles,
            'isInPath' => $isInPath,
            'isCurrent' => $isCurrent,
            'isStartArticle' => true, // Categories are always start articles
            'isArticle' => false,
            'lazyLoaded' => count($children) > 0 || count($articles) > 0, // Mark if children are already loaded
            'updateuser' => $updateUser,
            'updatedate' => $updateDate,
        ];
    }
    
    private function isInPath(int $categoryId, int $currentCategoryId, int $currentArticleId, int $clangId): bool
    {
        // Check if category is in path to current position
        if ($currentArticleId > 0) {
            $article = rex_article::get($currentArticleId, $clangId);
            if ($article) {
                $path = explode('|', trim($article->getPath(), '|'));
                if (in_array($categoryId, $path)) {
                    return true;
                }
                // Also check if this is the article's direct category
                if ($article->getCategoryId() == $categoryId) {
                    return true;
                }
            }
        }
        
        if ($currentCategoryId > 0) {
            // Check if this is the current category itself
            if ($categoryId == $currentCategoryId) {
                return true;
            }
            $category = rex_category::get($currentCategoryId, $clangId);
            if ($category) {
                $path = explode('|', trim($category->getPath(), '|'));
                return in_array($categoryId, $path);
            }
        }
        
        return false;
    }

    private function renderTree(array $items, int $depth = 0): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<ul class="info-center-tree-level" data-depth="' . $depth . '">';
        
        foreach ($items as $item) {
            $offlineClass = $item['status'] == 0 ? ' offline' : '';
            $hasChildrenClass = $item['hasChildren'] ? ' has-children' : '';
            // Expand if in path to current position
            $expandedClass = $item['isInPath'] ? ' expanded' : '';
            $currentClass = $item['isCurrent'] ? ' current' : '';
            
            // Check if it's an article or category
            $isArticle = $item['isArticle'] ?? false;
            
            if ($isArticle) {
                // Render as article
                // Status: 0 = offline (orange), 1 = online (green), 2 = locked (red)
                $statusClass = 'status-online';
                if ($item['status'] == 0) {
                    $statusClass = 'status-offline';
                } elseif ($item['status'] == 2) {
                    $statusClass = 'status-locked';
                }
                
                // Frontend URL für View-Button
                $articleViewUrl = '';
                if (rex_addon::get('yrewrite')->isAvailable()) {
                    $articleViewUrl = rex_yrewrite::getFullUrlByArticleId($item['id'], rex_clang::getCurrentId());
                } else {
                    $articleViewUrl = rex_getUrl($item['id'], rex_clang::getCurrentId());
                }
                
                // Build title with update info
                $articleTitle = rex_i18n::msg('info_center_article_id') . ': ' . $item['id'];
                if (!empty($item['updatedate'])) {
                    $updateDate = date('d.m.Y H:i', strtotime($item['updatedate']));
                    $updateUser = $item['updateuser'] ?? '';
                    $articleTitle .= ' | ' . rex_i18n::msg('info_center_last_updated', 'Zuletzt geändert') . ': ' . $updateDate;
                    if ($updateUser) {
                        $articleTitle .= ' (' . rex_escape($updateUser) . ')';
                    }
                }
                
                $html .= sprintf(
                    '<li class="info-center-tree-item info-center-tree-article%s%s" data-id="article-%d">
                        <div class="info-center-tree-node">
                            <span class="info-center-tree-spacer"></span>
                            <a href="%s" class="info-center-tree-link" title="%s">
                                <svg class="info-center-tree-article-icon %s" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
                                    <path d="M14 2v6h6" fill="none" stroke="white" stroke-width="1" opacity="0.3"/>
                                </svg>
                                <span class="info-center-tree-name">%s</span>
                            </a>
                            <div class="info-center-tree-actions">
                                <a href="%s" class="info-center-tree-view" title="' . rex_i18n::msg('info_center_view_frontend', 'Im Frontend ansehen') . '" target="_blank">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                <a href="%s" class="info-center-tree-edit" title="' . rex_i18n::msg('info_center_edit_article', 'Artikel bearbeiten') . '">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </li>',
                    $offlineClass,
                    $currentClass,
                    $item['id'],
                    $item['url'],
                    $articleTitle,
                    $statusClass,
                    rex_escape($item['name']),
                    $articleViewUrl,
                    $item['url']
                );
            } else {
                // Render as category with folder icon
                // Status: 0 = offline (orange), 1 = online (green), 2 = locked (red)
                $statusClass = 'status-online';
                if ($item['status'] == 0) {
                    $statusClass = 'status-offline';
                } elseif ($item['status'] == 2) {
                    $statusClass = 'status-locked';
                }
                
                $editUrl = rex_url::backendPage('content/edit', [
                    'category_id' => $item['id'],
                    'article_id' => $item['id'],
                    'clang' => rex_clang::getCurrentId(),
                    'mode' => 'edit'
                ]);
                $editUrl = html_entity_decode($editUrl, ENT_QUOTES | ENT_HTML5);
                
                // Frontend URL für View-Button
                $viewUrl = '';
                if (rex_addon::get('yrewrite')->isAvailable()) {
                    $viewUrl = rex_yrewrite::getFullUrlByArticleId($item['id'], rex_clang::getCurrentId());
                } else {
                    $viewUrl = rex_getUrl($item['id'], rex_clang::getCurrentId());
                }
                
                // Build title with update info
                $itemTitle = $item['domain'] ? rex_i18n::msg('info_center_domain', 'Domain') . ': ' . rex_escape($item['domain']) . ' | ID: ' . $item['id'] : 'ID: ' . $item['id'];
                if (!empty($item['updatedate'])) {
                    $updateDate = date('d.m.Y H:i', strtotime($item['updatedate']));
                    $updateUser = $item['updateuser'] ?? '';
                    $itemTitle .= ' | ' . rex_i18n::msg('info_center_last_updated', 'Zuletzt geändert') . ': ' . $updateDate;
                    if ($updateUser) {
                        $itemTitle .= ' (' . rex_escape($updateUser) . ')';
                    }
                }
                
                // Mark if children are already loaded or need lazy loading
                $childrenLoadedAttr = ($item['lazyLoaded'] ?? false) ? 'data-children-loaded="true"' : '';
                $hasChildrenAttr = $item['hasChildren'] ? 'data-has-children="true"' : '';
                $clangAttr = 'data-clang="' . rex_clang::getCurrentId() . '"';
                
                $html .= sprintf(
                    '<li class="info-center-tree-item%s%s%s%s" data-id="%d" %s %s %s>
                        <div class="info-center-tree-node">
                            <a href="%s" class="info-center-tree-link" title="%s">
                                <svg class="info-center-tree-folder-icon %s" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 5a2 2 0 012-2h5l2 3h9a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
                                </svg>
                                <span class="info-center-tree-name">%s</span>
                            </a>
                            <div class="info-center-tree-actions">
                                <a href="%s" class="info-center-tree-view" title="' . rex_i18n::msg('info_center_view_frontend', 'Im Frontend ansehen') . '" target="_blank">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                <a href="%s" class="info-center-tree-edit" title="' . rex_i18n::msg('info_center_edit_category', 'Kategorie bearbeiten') . '">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                            </div>
                            %s
                        </div>
                        %s
                    </li>',
                    $offlineClass,
                    $hasChildrenClass,
                    $expandedClass,
                    $currentClass,
                    $item['id'],
                    $childrenLoadedAttr,
                    $hasChildrenAttr,
                    $clangAttr,
                    $item['url'],
                    $itemTitle,
                    $statusClass,
                    rex_escape($item['name']),
                    $viewUrl,
                    $editUrl,
                    $item['hasChildren'] ? '<button class="info-center-tree-toggle" type="button">
                        <svg class="icon-toggle" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                            <circle cx="12" cy="6" r="1.5" fill="currentColor"/>
                            <circle cx="12" cy="18" r="1.5" fill="currentColor"/>
                        </svg>
                    </button>' : '',
                    $this->renderChildrenAndArticles($item, $depth)
                );
            }
        }
        
        $html .= '</ul>';
        
        return $html;
    }
    
    private function renderChildrenAndArticles(array $item, int $depth): string
    {
        $html = '';
        
        if ($item['hasChildren']) {
            $html .= '<ul class="info-center-tree-level" data-depth="' . ($depth + 1) . '">';
            
            // First render child categories
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    $offlineClass = $child['status'] == 0 ? ' offline' : '';
                    $hasChildrenClass = $child['hasChildren'] ? ' has-children' : '';
                    $expandedClass = $child['isInPath'] ? ' expanded' : '';
                    $currentClass = $child['isCurrent'] ? ' current' : '';
                    
                    // Status: 0 = offline (orange), 1 = online (green), 2 = locked (red)
                    $childStatusClass = 'status-online';
                    if ($child['status'] == 0) {
                        $childStatusClass = 'status-offline';
                    } elseif ($child['status'] == 2) {
                        $childStatusClass = 'status-locked';
                    }
                    
                    $childEditUrl = rex_url::backendPage('content/edit', [
                        'category_id' => $child['id'],
                        'article_id' => $child['id'],
                        'clang' => rex_clang::getCurrentId(),
                        'mode' => 'edit'
                    ]);
                    
                    // Frontend URL für View-Button
                    $childViewUrl = '';
                    if (rex_addon::get('yrewrite')->isAvailable()) {
                        $childViewUrl = rex_yrewrite::getFullUrlByArticleId($child['id'], rex_clang::getCurrentId());
                    } else {
                        $childViewUrl = rex_getUrl($child['id'], rex_clang::getCurrentId());
                    }
                    
                    $childTitle = $child['domain'] ? 'Domain: ' . rex_escape($child['domain']) . ' | ID: ' . $child['id'] : 'ID: ' . $child['id'];
                    
                    $html .= sprintf(
                        '<li class="info-center-tree-item%s%s%s%s" data-id="%d">
                            <div class="info-center-tree-node">
                                <a href="%s" class="info-center-tree-link" title="%s">
                                    <svg class="info-center-tree-folder-icon %s" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 5a2 2 0 012-2h5l2 3h9a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
                                    </svg>
                                    <span class="info-center-tree-name">%s</span>
                                </a>
                                <div class="info-center-tree-actions">
                                    <a href="%s" class="info-center-tree-view" title="Im Frontend ansehen" target="_blank">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="%s" class="info-center-tree-edit" title="Kategorie bearbeiten">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                </div>
                                %s
                            </div>
                            %s
                        </li>',
                        $offlineClass,
                        $hasChildrenClass,
                        $expandedClass,
                        $currentClass,
                        $child['id'],
                        $child['url'],
                        $childTitle,
                        $childStatusClass,
                        rex_escape($child['name']),
                        $childViewUrl,
                        $childEditUrl,
                        $child['hasChildren'] ? '<button class="info-center-tree-toggle" type="button">
                            <svg class="icon-toggle" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                                <circle cx="12" cy="6" r="1.5" fill="currentColor"/>
                                <circle cx="12" cy="18" r="1.5" fill="currentColor"/>
                            </svg>
                        </button>' : '',
                        $this->renderChildrenAndArticles($child, $depth + 1)
                    );
                }
            }
            
            // Then render articles (after child categories)
            if (!empty($item['articles'])) {
                foreach ($item['articles'] as $article) {
                    $offlineClass = $article['status'] == 0 ? ' offline' : '';
                    $currentClass = $article['isCurrent'] ? ' current' : '';
                    
                    // Status: 0 = offline (orange), 1 = online (green), 2 = locked (red)
                    $articleStatusClass = 'status-online';
                    if ($article['status'] == 0) {
                        $articleStatusClass = 'status-offline';
                    } elseif ($article['status'] == 2) {
                        $articleStatusClass = 'status-locked';
                    }
                    
                    // Frontend URL für View-Button
                    $articleViewUrl = '';
                    if (rex_addon::get('yrewrite')->isAvailable()) {
                        $articleViewUrl = rex_yrewrite::getFullUrlByArticleId($article['id'], rex_clang::getCurrentId());
                    } else {
                        $articleViewUrl = rex_getUrl($article['id'], rex_clang::getCurrentId());
                    }
                    
                    $html .= sprintf(
                        '<li class="info-center-tree-item info-center-tree-article%s%s" data-id="article-%d">
                            <div class="info-center-tree-node">
                                <span class="info-center-tree-spacer"></span>
                                <a href="%s" class="info-center-tree-link" title="Artikel ID: %d">
                                    <svg class="info-center-tree-article-icon %s" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
                                        <path d="M14 2v6h6" fill="none" stroke="white" stroke-width="1" opacity="0.3"/>
                                    </svg>
                                    <span class="info-center-tree-name">%s</span>
                                </a>
                                <div class="info-center-tree-actions">
                                    <a href="%s" class="info-center-tree-view" title="Im Frontend ansehen" target="_blank">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="%s" class="info-center-tree-edit" title="Artikel bearbeiten">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </li>',
                        $offlineClass,
                        $currentClass,
                        $article['id'],
                        rex_escape($article['url']),
                        $article['id'],
                        $articleStatusClass,
                        rex_escape($article['name']),
                        $articleViewUrl,
                        rex_escape($article['url'])
                    );
                }
            }
            
            $html .= '</ul>';
        }
        
        return $html;
    }
}
