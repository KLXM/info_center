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
    protected bool $supportsLazyLoading = false; // Deaktiviert um Artikel-Kontext zu erhalten
    private ?rex_article $article = null;

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

        // Check for URL2 first - if found, use URL2 logic
        $url2Info = $this->analyzeUrl2Url();
        
        if ($url2Info) {
            // URL2 detected - use URL2-specific rendering
            $html .= $this->renderBasicInfo($url2Info);
            $html .= $this->renderPathInfo($url2Info);
        } else {
            // Normal REDAXO article - use standard rendering (without URL2 parameter)
            $html .= $this->renderBasicInfo();
            $html .= $this->renderPathInfo();
        }
        
        // Edit/View links (handles both URL2 and normal articles)
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

    private function renderBasicInfo(?array $url2Info = null): string
    {
        $html = '';
        
        // If URL2 info is available, use the record data
        if ($url2Info && $url2Info['record_data']) {
            $recordData = $url2Info['record_data'];
            
            // Try to find a title/name field in the record data
            $title = null;
            $possibleTitleFields = ['title', 'name', 'bezeichnung', 'titel', 'subject'];
            foreach ($possibleTitleFields as $field) {
                if (isset($recordData[$field]) && !empty($recordData[$field])) {
                    $title = $recordData[$field];
                    break;
                }
            }
            
            // Article name from URL2 record
            $html .= $this->renderInfoItem(
                rex_i18n::msg('info_center_article_name'),
                rex_escape($title ?: ('Record ID: ' . $url2Info['record_id']))
            );
            
            // Record ID
            $html .= $this->renderInfoItem(
                'Record ID',
                $url2Info['record_id']
            );
            
            // Table info
            $html .= $this->renderInfoItem(
                'Tabelle',
                rex_escape($url2Info['table_label'])
            );
            
            // Status: URL2-managed records are always "online" if accessible
            $html .= $this->renderInfoItem(
                rex_i18n::msg('info_center_article_status'),
                '<span class="info-center-status-online">Online (URL2)</span>'
            );
            
        } else {
            // Standard REDAXO article info (original behavior)
            
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
        }

        return $html;
    }

    private function renderPathInfo(?array $url2Info = null): string
    {
        // If URL2 info is available, show URL2 path
        if ($url2Info) {
            $pathParts = [];
            
            // Add URL2 table info
            $pathParts[] = '<strong>URL2:</strong> ' . rex_escape($url2Info['table_label']);
            
            // Show the current URL path (without debug_info dependency)
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            if (!empty($currentUrl)) {
                $urlParts = explode('/', trim($currentUrl, '/'));
                if (count($urlParts) > 1) {
                    $pathParts[] = implode(' / ', array_map('rex_escape', array_slice($urlParts, 0, -1)));
                }
            }
            
            return $this->renderInfoItem(
                rex_i18n::msg('info_center_article_path'),
                implode(' → ', $pathParts)
            );
        }
        
        // Standard REDAXO article path (original behavior)
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
            
            // If no URL manager found, this is not a URL2-managed URL
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
            
            // Verify this is actually a custom URL2 table, not a standard REDAXO article
            if (empty($tableName) || $tableName === 'rex_article') {
                return null;
            }
            
            // Get YForm table info
            $yformTables = $this->getYFormTables();
            $tableInfo = null;
            
            foreach ($yformTables as $table) {
                if ($table['name'] === $tableName) {
                    $tableInfo = $table;
                    break;
                }
            }
            
            // Return URL2 info even if not a YForm table
            return [
                'table' => $tableName,
                'table_label' => $tableInfo ? $tableInfo['label'] : $tableName,
                'record_id' => $dataset ? $dataset->getId() : $urlManager->getDatasetId(),
                'record_identifier' => $urlManager->getDatasetId(),
                'record_data' => $dataset ? $dataset->getData() : null,
                'profile_id' => $urlManager->getProfileId(),
                'url_manager' => $urlManager,
                'profile' => $profile,
                'is_yform_table' => $tableInfo !== null
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
            
            return $tables;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Render URL2/YForm specific actions
     */
    private function renderUrl2Actions(array $url2Info): string
    {
        $html = '<div class="info-center-url2-detection">';
        
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
            // Try to get current article first
            $article = rex_article::getCurrent();
            
            // Alternative: Try to use current article ID from REDAXO
            if (!$article) {
                $currentArticleId = rex_article::getCurrentId();
                if ($currentArticleId > 0) {
                    $article = rex_article::get($currentArticleId, rex_clang::getCurrentId());
                }
            }
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
