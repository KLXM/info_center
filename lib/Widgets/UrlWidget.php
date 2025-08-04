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
use rex_logger;

class UrlWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = false;
    private ?array $url2Info = null;
    private bool $url2InfoLoaded = false;

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_url_title');
        // Don't analyze URL2 in constructor - do it lazily to avoid errors during boot
    }

    /**
     * Get dynamic title based on URL2 detection
     */
    public function getTitle(): string
    {
        $url2Info = $this->getUrl2Info();
        if ($url2Info && !empty($url2Info['table_label'])) {
            return $url2Info['table_label'];
        }
        return $this->title;
    }

    /**
     * Lazy loading of URL2 info with proper error handling
     */
    private function getUrl2Info(): ?array
    {
        if (!$this->url2InfoLoaded) {
            try {
                $this->url2Info = $this->analyzeUrl2Url();
            } catch (\Exception $e) {
                // Log error but don't break the page
                if (rex::isDebugMode()) {
                    rex_logger::logException($e);
                }
                $this->url2Info = null;
            }
            $this->url2InfoLoaded = true;
        }
        
        return $this->url2Info;
    }

    public function getInitialContent(): string
    {
        $url2Info = $this->getUrl2Info();
        if (!$url2Info) {
            return '';
        }

        // Tabellenname prominent anzeigen anstelle von "URL2 Datensatz"
        $content = sprintf(
            '<div class="info-center-url-basic">
                <div class="info-center-url-item">
                    <span class="label">%s</span>
                    <span class="value">Datensatz erkannt</span>
                </div>
                <div class="info-center-url-note">
                    Sie können diesen Datensatz direkt bearbeiten.
                </div>
            </div>',
            rex_escape($url2Info['table_label'])
        );

        return $this->wrapContent($content);
    }

    public function render(): string
    {
        // Only show this widget if URL2 is detected
        $url2Info = $this->getUrl2Info();
        if (!$url2Info) {
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

        // Kurze Info über den erkannten Datensatz
        $html .= $this->renderUrl2BasicInfo();
        
        // Die zwei Hauptbuttons
        $html .= $this->renderUrl2ActionLinks();
        
        return $html;
    }

    private function renderUrl2BasicInfo(): string
    {
        $url2Info = $this->getUrl2Info();
        if (!$url2Info) {
            return '';
        }
        
        $html = '<div class="info-center-url-info">';
        
        // Info-Text ohne redundante Tabellen-Information
        $html .= '<div class="info-center-url-note">';
        $html .= 'Sie können diesen Datensatz direkt bearbeiten.';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    private function renderUrl2ActionLinks(): string
    {
        $url2Info = $this->getUrl2Info();
        if (!$url2Info) {
            return '';
        }
        
        $html = '<div class="info-center-url2-actions">';
        
        // Check permissions
        $user = rex_backend_login::createUser();
        if (!$user) {
            $html .= '</div>';
            return $html;
        }

        // Check permissions using correct YForm table permissions (like quick_navigation)
        $hasPermissions = false;
        
        if ($user->isAdmin()) {
            $hasPermissions = true;
        } elseif ($url2Info['is_yform_table']) {
            // For YForm tables: Check specific table permissions (exactly like quick_navigation)
            $yform = rex_addon::get('yform');
            $yperm_suffix = '';
            if (version_compare($yform->getVersion(), '4.0.0-dev', '>=')) {
                $yperm_suffix = '_edit';
            }
            
            $complexPerm = $user->getComplexPerm('yform_manager_table' . $yperm_suffix);
            $hasPermissions = $complexPerm && $complexPerm->hasPerm($url2Info['table']);
        } 

        if (!$hasPermissions) {
            $html .= '<div class="info-center-url-note">Keine Berechtigungen für Datensatz-Bearbeitung.</div>';
            $html .= '</div>';
            return $html;
        }
        
        // Die zwei Hauptbuttons - unabhängig davon ob YForm-Tabelle oder nicht
        if ($url2Info['is_yform_table']) {
            // YForm-Tabelle: Die zwei Standard-Buttons
            $html .= $this->renderYFormButtons($url2Info);
        } else {
            // Nicht-YForm-Tabelle: Alternative Buttons
            $html .= $this->renderGenericButtons($url2Info);
        }
        
        $html .= '</div>';
        return $html;
    }

    private function renderYFormButtons(array $url2Info): string
    {
        $html = '';
        
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
        
        // Button 2: Tabelle öffnen (Hauptbutton - zuerst)  
        $tableParams = [
            'table_name' => $url2Info['table'],
        ];
        if ($csrf_token) {
            $tableParams['_csrf_token'] = $csrf_token;
        }
        
        $tableUrl = rex_url::backendPage('yform/manager/data_edit', $tableParams);
        
        $html .= sprintf(
            '<a href="%s" target="_blank" class="info-center-btn info-center-btn-primary">
                Tabelle öffnen
            </a>',
            $tableUrl
        );
        
        // Button 1: Datensatz bearbeiten (Sekundärbutton - zweite Position)
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
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-secondary">
                    Datensatz bearbeiten
                </a>',
                $recordUrl
            );
        }
        
        return $html;
    }

    private function renderGenericButtons(array $url2Info): string
    {
        $html = '';
        
        // Button 1: Adminer öffnen (Hauptbutton - falls verfügbar)
        if (rex_addon::get('adminer')->isAvailable()) {
            $adminerUrl = rex_url::backendPage('adminer', [
                'username' => '', // Will use default connection
                'table' => $url2Info['table']
            ]);
            
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-primary">
                    Tabelle in Adminer
                </a>',
                $adminerUrl
            );
        }
        
        // Button 2: URL2-Profile verwalten (Sekundärbutton)
        if (rex_addon::get('url')->isAvailable()) {
            $urlProfileUrl = rex_url::backendPage('url/generator');
            
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-secondary">
                    URL2-Profile verwalten
                </a>',
                $urlProfileUrl
            );
        }
        
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
            // URL2 couldn't resolve the current URL or other error occurred
            // Don't log this as it's expected behavior for non-URL2 URLs
            return null;
        } catch (\Error $e) {
            // Fatal errors like "Call to a member function on null"
            // Don't log this as it's expected behavior for broken URL2 states
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
