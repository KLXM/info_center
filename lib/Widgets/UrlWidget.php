<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_url;
use rex_i18n;
use rex_backend_login;
use rex_escape;
use rex_sql;
use rex_yform_manager_table;
use rex_csrf_token;

class UrlWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = false;
    private ?array $url2Info = null;

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_url_title');
        $this->url2Info = $this->analyzeUrl2Url();
    }

    public function getInitialContent(): string
    {
        if (!$this->url2Info) {
            return '';
        }

        // Light initial content showing only URL2 detection
        $content = sprintf(
            '<div class="info-center-url-basic">
                <div class="info-center-url-item">
                    <span class="label">🔗 URL2 erkannt</span>
                    <span class="value">%s</span>
                </div>
            </div>',
            rex_escape($this->url2Info['table_label'])
        );

        return $this->wrapContent($content);
    }

    public function render(): string
    {
        // Only show this widget if URL2 is detected
        if (!$this->url2Info) {
            return '';
        }

        $content = '<div class="info-center-url-items">';

        if (rex::isBackend()) {
            // Backend: Don't show URL2 widget (not relevant)
            return '';
        } else {
            // Frontend: Show URL2 information
            $content .= $this->renderUrl2Content();
        }

        $content .= '</div>';
        return $this->wrapContent($content);
    }

    private function renderUrl2Content(): string
    {
        $html = '';

        // URL2 basic information
        $html .= $this->renderUrl2Info();
        
        // URL2 action links
        $html .= $this->renderUrl2ActionLinks();
        
        return $html;
    }

    private function renderUrl2Info(): string
    {
        $html = '';
        
        // Table info
        $html .= $this->renderInfoItem(
            'Tabelle',
            rex_escape($this->url2Info['table_label'])
        );
        
        // Record ID
        if ($this->url2Info['record_id']) {
            $html .= $this->renderInfoItem(
                'Datensatz-ID',
                $this->url2Info['record_id']
            );
        }
        
        // Try to show a meaningful title from the record data
        if ($this->url2Info['record_data']) {
            $recordData = $this->url2Info['record_data'];
            
            // Try to find a title/name field in the record data
            $title = null;
            $possibleTitleFields = ['title', 'name', 'bezeichnung', 'titel', 'subject'];
            foreach ($possibleTitleFields as $field) {
                if (isset($recordData[$field]) && !empty($recordData[$field])) {
                    $title = $recordData[$field];
                    break;
                }
            }
            
            if ($title) {
                $html .= $this->renderInfoItem(
                    'Datensatz-Titel',
                    rex_escape($title)
                );
            }
        }
        
        // URL2 path
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (!empty($currentUrl)) {
            $html .= $this->renderInfoItem(
                'URL2-Pfad',
                rex_escape($currentUrl)
            );
        }
        
        // Status: URL2-managed records are always "online" if accessible
        $html .= $this->renderInfoItem(
            'Status',
            '<span class="info-center-status-online">Online (URL2)</span>'
        );

        return $html;
    }

    private function renderUrl2ActionLinks(): string
    {
        $html = '<div class="info-center-url2-actions">';
        
        // Check permissions
        $user = rex_backend_login::createUser();
        if (!$user) {
            $html .= '</div>';
            return $html;
        }

        // Check if user has permissions for YForm/Backend access
        $hasPermissions = false;
        if (rex::isBackend()) {
            $hasPermissions = $user->isAdmin() || $user->hasPerm('yform');
        } else {
            // Frontend: Extended permission check for backend users
            $hasPermissions = $user->isAdmin() || 
                            $user->hasPerm('yform') || 
                            $user->hasPerm('structure') || 
                            $user->hasPerm('content');
        }

        if (!$hasPermissions) {
            $html .= '</div>';
            return $html;
        }
        
        // Only show YForm buttons if it's actually a YForm table
        if ($this->url2Info['is_yform_table']) {
            // Get CSRF token for YForm operations
            $csrf_token = null;
            if (rex::isFrontend() && rex_backend_login::hasSession()) {
                rex::setProperty('redaxo', true);
                try {
                    $table = rex_yform_manager_table::get($this->url2Info['table']);
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
                'table_name' => $this->url2Info['table'],
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
                rex_escape($this->url2Info['table_label'])
            );
            
            // Record edit link (if record found)
            if ($this->url2Info['record_id']) {
                $recordParams = [
                    'table_name' => $this->url2Info['table'],
                    'data_id' => $this->url2Info['record_id'],
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
                    'table' => $this->url2Info['table']
                ]);
                
                $html .= sprintf(
                    '<a href="%s" target="_blank" class="info-center-btn info-center-btn-adminer">
                        🗄️ Tabelle "%s" in Adminer öffnen
                    </a>',
                    $adminerUrl,
                    rex_escape($this->url2Info['table'])
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

    private function renderInfoItem(string $label, string $value): string
    {
        return sprintf(
            '<div class="info-center-url-item">
                <span class="label">%s</span>
                <span class="value">%s</span>
            </div>',
            $label,
            $value
        );
    }

    protected function wrapContent(string $content): string
    {
        // URL2 Status-Punkt
        $statusDot = '<span class="info-center-status-dot info-center-status-online"></span>';
        
        return sprintf(
            '<div class="info-center-widget info-center-url-widget" data-id="%s" data-lazy="%s">
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
}
