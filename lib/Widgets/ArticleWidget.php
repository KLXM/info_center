<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_article;
use rex_url;
use rex_clang;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_backend_login;
use rex_escape;
use rex_sql;
use rex_logger;
use rex_yform_manager_table;
use rex_csrf_token;

class ArticleWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = true;
    private ?rex_article $article = null;
    private array $yformDebugInfo = [];

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_article_title');
        $this->article = $this->getCurrentArticle();
    }

    public function getInitialContent(): string
    {
        if (!$this->article) {
            return $this->wrapContent(rex_i18n::msg('info_center_no_article_found'));
        }

        // Light initial content showing only article name
        $content = sprintf(
            '<div class="info-center-article-basic">
                <div class="info-center-article-item">
                    <span class="label">%s</span>
                    <span class="value">%s</span>
                </div>
            </div>',
            rex_i18n::msg('info_center_article_name'),
            rex_escape($this->article->getName())
        );

        return $this->wrapContent($content);
    }

    public function render(): string
    {
        $content = '<div class="info-center-article-items">';

        if (rex::isBackend()) {
            // Backend: Administrative Funktionen
            $content .= $this->renderBackendContent();
        } else {
            // Frontend: Aktueller Artikel
            if (!$this->article) {
                return $this->wrapContent(rex_i18n::msg('info_center_no_article_found'));
            }
            
            $content .= $this->renderFrontendContent();
        }

        $content .= '</div>';
        return $this->wrapContent($content);
    }

    private function renderBackendContent(): string
    {
        $html = '';
        
        // Quick Links zu häufig genutzten Bereichen
        $html .= $this->renderQuickLinks();
        
        // Zuletzt bearbeitete Artikel (wie quick_navigation)
        $html .= $this->renderRecentArticles();
        
        return $html;
    }

    private function renderFrontendContent(): string
    {
        $html = '';

        // Basic article information
        $html .= $this->renderBasicInfo();
        
        // Article path
        $html .= $this->renderPathInfo();
        
        // Edit/View links
        $html .= $this->renderActionLinks();
        
        // Metadata
        if ($this->shouldShowMetaInfo()) {
            $html .= $this->renderMetaInfo();
        }
        
        return $html;
    }

    private function renderQuickLinks(): string
    {
        $user = rex_backend_login::createUser();
        if (!$user) {
            return '';
        }

        $html = '<div class="info-center-quick-links">';
        
        // Struktur-Hauptseite
        if ($user->hasPerm('structure')) {
            $html .= sprintf(
                '<div class="info-center-quick-link">
                    <a href="%s">🗂️ %s</a>
                </div>',
                rex_url::backendPage('structure'),
                rex_i18n::msg('info_center_structure_overview')
            );
        }

        $html .= '</div>';
        return $html;
    }

    private function renderRecentArticles(): string
    {
        $user = rex_backend_login::createUser();
        if (!$user || (!$user->hasPerm('structure') && !$user->hasPerm('content'))) {
            return '';
        }

        $recentArticles = $this->getRecentArticles();
        if (empty($recentArticles)) {
            return '';
        }

        $html = '<div class="info-center-recent-articles">';
        $html .= '<h4>' . rex_i18n::msg('info_center_recent_articles') . '</h4>';
        
        foreach ($recentArticles as $article) {
            $statusClass = $article['status'] == 1 ? 'online' : 'offline';
            
            // Bessere Datumsformatierung - prüfe ob updatedate ein gültiger Timestamp ist
            $updatedate = (int)$article['updatedate'];
            if ($updatedate > 0) {
                $date = date('d.m. H:i', $updatedate);
            } else {
                // Fallback: aktuelles Datum
                $date = date('d.m. H:i');
            }
            
            $html .= sprintf(
                '<div class="info-center-recent-article status-%s">
                    <div class="article-main">
                        <a href="%s" title="%s">
                            <span class="article-name">%s</span>
                        </a>
                    </div>
                    <div class="article-meta">
                        <span class="article-user">%s</span>
                        <span class="article-date">%s</span>
                    </div>
                </div>',
                $statusClass,
                rex_url::backendPage('content/edit', [
                    'article_id' => $article['id'],
                    'category_id' => $article['category_id'],
                    'clang' => $article['clang_id'],
                    'mode' => 'edit'
                ]),
                rex_escape($article['name']),
                rex_escape($this->truncateString($article['name'], 30)),
                rex_escape($article['updateuser']),
                $date
            );
        }
        
        $html .= '</div>';
        return $html;
    }

    private function getRecentArticles(): array
    {
        try {
            $where = '';
            $whereParams = [];
            
            $user = rex_backend_login::createUser();
            if (!$user) {
                return [];
            }

            // Prüfe Berechtigung für alle Änderungen oder nur eigene
            if (!$user->isAdmin() && !$user->hasPerm('quick_navigation[all_changes]')) {
                $where = 'WHERE updateuser = :user';
                $whereParams['user'] = $user->getValue('login');
            } else {
                $where = 'WHERE updatedate > 0';
            }

            $sql = rex_sql::factory();
            $query = '
                SELECT 
                    id, 
                    parent_id as category_id,
                    clang_id, 
                    name, 
                    updateuser,
                    UNIX_TIMESTAMP(updatedate) as updatedate,
                    status
                FROM ' . rex::getTable('article') . ' 
                ' . $where . ' 
                ORDER BY updatedate DESC 
                LIMIT 5
            ';
            
            $sql->setQuery($query, $whereParams);
            
            $articles = [];
            while ($sql->hasNext()) {
                $articles[] = [
                    'id' => $sql->getValue('id'),
                    'category_id' => $sql->getValue('category_id'),
                    'clang_id' => $sql->getValue('clang_id'),
                    'name' => $sql->getValue('name'),
                    'updateuser' => $sql->getValue('updateuser'),
                    'updatedate' => $sql->getValue('updatedate'),
                    'status' => $sql->getValue('status')
                ];
                $sql->next();
            }
            
            return $articles;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function truncateString(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }

    private function renderBasicInfo(): string
    {
        $html = '';
        
        // Article name
        $html .= $this->renderInfoItem(
            rex_i18n::msg('info_center_article_name'),
            rex_escape($this->article->getName())
        );

        // Article ID
        $html .= $this->renderInfoItem(
            'ID',
            $this->article->getId()
        );

        // Status
        $statusClass = $this->article->isOnline() ? 'online' : 'offline';
        $statusText = $this->article->isOnline() ? 
            rex_i18n::msg('info_center_status_online') : 
            rex_i18n::msg('info_center_status_offline');
        
        $html .= $this->renderInfoItem(
            rex_i18n::msg('info_center_article_status'),
            sprintf('<span class="info-center-status-%s">%s</span>', $statusClass, $statusText)
        );

        return $html;
    }

    private function renderPathInfo(): string
    {
        $path = [];
        // Verwende rex_backend_login::createUser() wie bei der Minibar
        $user = rex_backend_login::createUser();
        
        foreach ($this->article->getParentTree() as $parent) {
            $canLinkToStructure = false;
            
            if ($user) {
                if (rex::isBackend()) {
                    // Im Backend: Normale Berechtigungsprüfung
                    $canLinkToStructure = $user->getComplexPerm('structure')?->hasCategoryPerm($parent->getId()) ?? false;
                } else {
                    // Im Frontend: Erweiterte Prüfung für Backend-Benutzer
                    $canLinkToStructure = $user->isAdmin() || 
                                         $user->hasPerm('structure') || 
                                         $user->hasPerm('content') ||
                                         $user->hasPerm('article');
                }
            }
            
            if ($canLinkToStructure) {
                $path[] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    rex_url::backendPage('structure', ['category_id' => $parent->getId()]),
                    rex_escape($parent->getName())
                );
            } else {
                $path[] = rex_escape($parent->getName());
            }
        }

        return $this->renderInfoItem(
            rex_i18n::msg('info_center_article_path'),
            implode(' / ', $path)
        );
    }

    private function renderActionLinks(): string
    {
        $html = '<div class="info-center-article-actions">';
        
        // Verwende rex_backend_login::createUser() wie bei der Minibar
        $user = rex_backend_login::createUser();
        $hasStructurePerm = false;
        
        // Debug-Info hinzufügen
        if (rex::isFrontend()) {
            $debugInfo = sprintf(
                'Debug: Frontend-Modus, User: %s, Session: %s, Perms: %s',
                $user ? 'Ja' : 'Nein',
                rex_backend_login::hasSession() ? 'Ja' : 'Nein',
                $user ? ($user->isAdmin() ? 'Admin' : ($user->hasPerm('structure') ? 'Structure' : ($user->hasPerm('content') ? 'Content' : 'Andere'))) : 'Keine'
            );
            $html .= '<div style="font-size:10px;color:#888;margin-bottom:8px;">' . $debugInfo . '</div>';
        }
        
        // Prüfe Berechtigung - Backend oder Frontend mit Backend-Session
        if ($user) {
            if (rex::isBackend()) {
                // Im Backend: Normale Berechtigungsprüfung
                $hasStructurePerm = $user->getComplexPerm('structure')?->hasCategoryPerm($this->article->getCategoryId()) ?? false;
            } else {
                // Im Frontend: Erweiterte Prüfung für Backend-Benutzer
                $hasStructurePerm = $user->isAdmin() || 
                                   $user->hasPerm('structure') || 
                                   $user->hasPerm('content') ||
                                   $user->hasPerm('article');
            }
        }
        
        if (!$hasStructurePerm) {
            $html .= '</div>';
            return $html;
        }

        // Check for URL2/YForm-URLs first
        $url2Info = $this->analyzeUrl2Url();
        if ($url2Info) {
            $html .= $this->renderUrl2Actions($url2Info);
        } else {
            // Add debug info even when URL2 is not detected
            if (rex::isFrontend()) {
                $html .= '<div class="info-center-debug" style="background:#fff3cd;padding:8px;margin-bottom:8px;font-size:11px;">';
                $html .= '<strong>🔍 URL2 Debug:</strong><br>';
                $html .= 'URL Addon verfügbar: ' . (rex_addon::get('url')->isAvailable() ? 'Ja' : 'Nein') . '<br>';
                $html .= 'YForm Addon verfügbar: ' . (rex_addon::get('yform')->isAvailable() ? 'Ja' : 'Nein') . '<br>';
                $html .= 'Aktuelle URL: ' . rex_escape($_SERVER['REQUEST_URI'] ?? 'unknown') . '<br>';
                $html .= 'Ist Frontend: ' . (rex::isFrontend() ? 'Ja' : 'Nein');
                $html .= '</div>';
            }            
            // Standard REDAXO article actions
            $html .= $this->renderStandardActions();
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Analyze current URL to detect URL2/YForm patterns using proper URL2 API
     */
    private function analyzeUrl2Url(): ?array
    {
        // Only in frontend
        if (rex::isBackend()) {
            return null;
        }

        // Check if url addon is available
        if (!rex_addon::get('url')->isAvailable()) {
            return null;
        }

        try {
            // Use URL2 API to resolve current URL
            $urlManager = \Url\Url::resolveCurrent();
            
            if (!$urlManager) {
                return null;
            }

            // Get the profile and dataset information
            $profile = $urlManager->getProfile();
            if (!$profile) {
                return null;
            }

            $dataset = $urlManager->getDataset();
            $tableName = $profile->getTableName();
            
            // Get YForm table info
            $yformTables = $this->getYFormTables();
            $tableInfo = null;
            
            foreach ($yformTables as $table) {
                if ($table['name'] === $tableName) {
                    $tableInfo = $table;
                    break;
                }
            }
            
            // Return URL2 info even if not a YForm table (for debugging)
            return [
                'table' => $tableName,
                'table_label' => $tableInfo ? $tableInfo['label'] : $tableName,
                'record_id' => $dataset ? $dataset->getId() : $urlManager->getDatasetId(),
                'record_identifier' => $urlManager->getDatasetId(),
                'record_data' => $dataset ? $dataset->getData() : null,
                'profile_id' => $urlManager->getProfileId(),
                'url_manager' => $urlManager,
                'profile' => $profile,
                'is_yform_table' => $tableInfo !== null,
                'debug_info' => [
                    'current_url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'table_name' => $tableName,
                    'yform_tables_count' => count($yformTables),
                    'has_dataset' => $dataset !== null
                ]
            ];
            
        } catch (\Exception $e) {
            // URL2 couldn't resolve the current URL - not a URL2 managed URL
            return null;
        }
    }

    /**
     * Get all YForm tables
     */
    private function getYFormTables(): array
    {
        if (!rex_addon::get('yform')->isAvailable()) {
            return [];
        }

        try {
            // Get YForm tables from database - use correct column names
            $sql = rex_sql::factory();
            $sql->setQuery('SELECT table_name, name, status FROM ' . rex::getTable('yform_table') . ' WHERE status = 1 ORDER BY name, table_name');
            
            $tables = [];
            
            while ($sql->hasNext()) {
                $table = [
                    'name' => $sql->getValue('table_name'), // The actual table name in database
                    'label' => $sql->getValue('name') ?: $sql->getValue('table_name'), // The display name
                    'status' => $sql->getValue('status')
                ];
                
                $tables[] = $table;
                $sql->next();
            }
            
            // Store debug info for later use
            if (rex::isFrontend()) {
                $this->yformDebugInfo = [
                    'total_tables' => count($tables),
                    'returned_tables' => count($tables),
                    'all_tables' => $tables, // Show all tables for debugging
                    'sql_query' => 'SELECT table_name, name, status FROM ' . rex::getTable('yform_table') . ' WHERE status = 1 ORDER BY name, table_name'
                ];
            }
            
            return $tables;
        } catch (\Exception $e) {
            // Store error for debugging
            if (rex::isFrontend()) {
                $this->yformDebugInfo = [
                    'error' => $e->getMessage(),
                    'yform_table_name' => rex::getTable('yform_table'),
                    'yform_available' => rex_addon::get('yform')->isAvailable()
                ];
            }
            return [];
        }
    }

    /**
     * Render URL2/YForm specific actions
     */
    private function renderUrl2Actions(array $url2Info): string
    {
        $html = '<div class="info-center-url2-detection">';
        
        // Debug information (always show in frontend for now)
        if (rex::isFrontend()) {
            $html .= '<div class="info-center-debug" style="background:#f0f0f0;padding:8px;margin-bottom:8px;font-size:11px;">';
            $html .= '<strong>🔍 URL2 Debug:</strong><br>';
            $html .= 'Table: ' . rex_escape($url2Info['table']) . '<br>';
            $html .= 'Record ID: ' . ($url2Info['record_id'] ?: 'null') . '<br>';
            $html .= 'Is YForm: ' . ($url2Info['is_yform_table'] ? 'Yes' : 'No') . '<br>';
            $html .= 'Current URL: ' . rex_escape($url2Info['debug_info']['current_url']) . '<br>';
            $html .= 'YForm Tables: ' . $url2Info['debug_info']['yform_tables_count'] . '<br>';
            
            // Show YForm debug info if available
            if (!empty($this->yformDebugInfo)) {
                $html .= '<strong>YForm Debug:</strong><br>';
                if (isset($this->yformDebugInfo['error'])) {
                    $html .= 'Error: ' . rex_escape($this->yformDebugInfo['error']) . '<br>';
                    $html .= 'Table name: ' . rex_escape($this->yformDebugInfo['yform_table_name'] ?? 'unknown') . '<br>';
                } else {
                    $html .= 'Total tables found: ' . $this->yformDebugInfo['total_tables'] . '<br>';
                    $html .= 'Tables returned: ' . $this->yformDebugInfo['returned_tables'] . '<br>';
                    if (!empty($this->yformDebugInfo['all_tables'])) {
                        $html .= 'Tables: ';
                        foreach (array_slice($this->yformDebugInfo['all_tables'], 0, 3) as $table) {
                            $html .= rex_escape($table['name']) . '(status:' . $table['status'] . '), ';
                        }
                        $html .= '<br>';
                    }
                }
            }
            
            $html .= '</div>';
        }
        
        $html .= '<div class="info-center-url2-info">';
        $html .= '<strong>🔗 URL2 erkannt:</strong> ' . rex_escape($url2Info['table_label']);
        
        if ($url2Info['record_id']) {
            $html .= ' (ID: ' . $url2Info['record_id'] . ')';
        }
        
        $html .= '</div>';
        
        // Only show YForm buttons if it's actually a YForm table
        if ($url2Info['is_yform_table']) {
            // Get CSRF token for YForm operations
            $csrf_token = null;
            if (rex::isFrontend() && rex_backend_login::hasSession()) {
                rex::setProperty('redaxo', true);
                try {
                    $table = rex_yform_manager_table::get($url2Info['table']);
                    if ($table) {
                        $_csrf_key = $table->getCSRFKey();
                        $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();
                        $csrf_token = $_csrf_params['_csrf_token'];
                    }
                } catch (\Exception $e) {
                    // CSRF token generation failed, continue without token
                }
                rex::setProperty('redaxo', false);
            }
            
            // YForm table management link
            $tableParams = [
                'table_name' => $url2Info['table'],
            ];
            if ($csrf_token) {
                $tableParams['_csrf_token'] = $csrf_token;
            }
            
            $tableUrl = rex_url::backendPage('yform/manager/data_edit', $tableParams);
            
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-yform-table">
                    📋 %s öffnen
                </a>',
                $tableUrl,
                rex_escape($url2Info['table_label'])
            );
            
            // Record edit link (if record found)
            if ($url2Info['record_id']) {
                $recordParams = [
                    'table_name' => $url2Info['table'],
                    'data_id' => $url2Info['record_id'],
                    'func' => 'edit'
                ];
                if ($csrf_token) {
                    $recordParams['_csrf_token'] = $csrf_token;
                }
                
                $recordUrl = rex_url::backendPage('yform/manager/data_edit', $recordParams);
                
                $html .= sprintf(
                    '<a href="%s" target="_blank" class="info-center-btn info-center-btn-yform-edit">
                        ✏️ Datensatz bearbeiten
                    </a>',
                    $recordUrl
                );
            }
        } else {
            // For non-YForm URL2 tables, show generic database editing options
            $html .= '<div class="info-center-url2-note">⚠️ Keine YForm-Tabelle, aber URL2-verwaltet</div>';
            
            // Try to provide useful links anyway
            if (rex_addon::get('adminer')->isAvailable()) {
                $adminerUrl = rex_url::backendPage('adminer', [
                    'username' => '', // Will use default connection
                    'table' => $url2Info['table']
                ]);
                
                $html .= sprintf(
                    '<a href="%s" target="_blank" class="info-center-btn info-center-btn-adminer">
                        🗄️ Tabelle "%s" in Adminer öffnen
                    </a>',
                    $adminerUrl,
                    rex_escape($url2Info['table'])
                );
            }
            
            // Always show URL2 profile management
            if (rex_addon::get('url')->isAvailable()) {
                $urlProfileUrl = rex_url::backendPage('url/generator');
                
                $html .= sprintf(
                    '<a href="%s" target="_blank" class="info-center-btn info-center-btn-url-profiles">
                        🔧 URL2-Profile verwalten
                    </a>',
                    $urlProfileUrl
                );
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render standard REDAXO article actions
     */
    private function renderStandardActions(): string
    {
        $html = '';
        
        // Edit link - im Backend und Frontend für eingeloggte Backend-Benutzer
        $editUrl = rex_url::backendPage('content/edit', [
            'article_id' => $this->article->getId(),
            'category_id' => $this->article->getCategoryId(),
            'clang' => $this->article->getClangId(),
            'mode' => 'edit'
        ]);
        
        $html .= sprintf(
            '<a href="%s" target="_blank" class="info-center-btn info-center-btn-edit">
                ✏️ %s
            </a>',
            $editUrl,
            rex_i18n::msg('info_center_article_edit')
        );
        
        // Structure/Category link - für Navigation zur Kategorie-Verwaltung
        $structureUrl = rex_url::backendPage('structure', [
            'category_id' => $this->article->getCategoryId(),
            'clang' => $this->article->getClangId()
        ]);
        
        $html .= sprintf(
            '<a href="%s" target="_blank" class="info-center-btn info-center-btn-structure">
                🗂️ %s
            </a>',
            $structureUrl,
            rex_i18n::msg('info_center_article_structure')
        );
        
        // View link - nur im Backend, da im Frontend bereits sichtbar
        if (rex::isBackend()) {
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-view">
                    👁️ %s
                </a>',
                $this->article->getUrl(),
                rex_i18n::msg('info_center_article_view')
            );
        }

        return $html;
    }

    private function renderMetaInfo(): string
    {
        $html = '';
        
        // Get metadata via extension point
        $metadata = rex_extension::registerPoint(new rex_extension_point(
            'INFO_CENTER_ARTICLE_METADATA',
            [],
            ['article' => $this->article]
        ));

        if (!empty($metadata)) {
            $html .= '<div class="info-center-article-metadata">';
            $html .= '<h4>' . rex_i18n::msg('info_center_article_metadata') . '</h4>';
            
            foreach ($metadata as $key => $value) {
                $html .= $this->renderInfoItem($key, $value);
            }
            
            $html .= '</div>';
        }

        return $html;
    }

    private function renderInfoItem(string $label, string $value): string
    {
        return sprintf(
            '<div class="info-center-article-item">
                <span class="label">%s</span>
                <span class="value">%s</span>
            </div>',
            $label,
            $value
        );
    }

    private function getCurrentArticle(): ?rex_article
    {
        // In backend
        if (rex::isBackend()) {
            $articleId = rex_request('article_id', 'int');
            $clangId = rex_request('clang', 'int', rex_clang::getCurrentId());
            
            $article = rex_article::get($articleId, $clangId);
            
            // Fallback to current category
            if (!$article) {
                $article = rex_article::get(rex_request('category_id', 'int'), $clangId);
            }
        } 
        // In frontend
        else {
            $article = rex_article::getCurrent();
        }

        // Fallback to start article
        if (!$article) {
            $article = rex_article::getSiteStartArticle();
        }

        return $article;
    }

    protected function wrapContent(string $content): string
    {
        // Status-Punkt nur im Frontend anzeigen
        $statusDot = '';
        if (rex::isFrontend() && $this->article) {
            $statusClass = $this->article->isOnline() ? 'online' : 'offline';
            $statusDot = '<span class="info-center-status-dot info-center-status-' . $statusClass . '"></span>';
        }
        
        return sprintf(
            '<div class="info-center-widget" data-id="%s" data-lazy="%s">
                <div class="info-center-widget-header">
                    <h3 class="info-center-widget-title">%s%s</h3>
                </div>
                <div class="info-center-widget-content">
                    %s
                </div>
            </div>',
            rex_escape($this->getId()),
            $this->supportsLazyLoading() ? 'true' : 'false',
            $statusDot,
            rex_escape($this->getTitle()),
            $content
        );
    }

    private function shouldShowMetaInfo(): bool
    {
        $user = rex_backend_login::createUser();
        return $user?->isAdmin() ?? false;
    }
}
