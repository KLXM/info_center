<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_sql;
use rex_yform_manager_table;
use rex_url;
use rex_formatter;
use rex_backend_login;
use rex_csrf_token;

class CustomListWidget extends AbstractWidget
{
    protected array $customConfig;
    private string $customId;

    public function __construct(string $customId, array $config)
    {
        parent::__construct();
        $this->customId = $customId;
        $this->customConfig = $config;
        $this->title = '📋 ' . ($config['name'] ?? 'Custom Widget');
        $this->id = 'custom_' . $customId;
    }

    public function render(): string
    {
        if (!$this->customConfig['enabled']) {
            return '';
        }

        try {
            $data = $this->fetchData();
            $content = $this->renderList($data);
            return $this->wrapContent($content);
        } catch (\Exception $e) {
            $content = '<div class="alert alert-danger">Fehler beim Laden der Daten: ' . rex_escape($e->getMessage()) . '</div>';
            return $this->wrapContent($content);
        }
    }

    private function fetchData(): array
    {
        $tableName = $this->customConfig['table_name'];
        $limit = min(50, max(1, (int)($this->customConfig['limit'] ?? 5)));
        $filter = trim($this->customConfig['filter'] ?? '');

        // Prüfe ob Tabelle existiert
        $sql = rex_sql::factory();
        $tableExists = $sql->getArray('SHOW TABLES LIKE ?', [$tableName]);
        
        if (empty($tableExists)) {
            throw new \Exception("Tabelle '{$tableName}' nicht gefunden");
        }

        // YForm-Tabelle laden für Metadaten
        $yformTable = null;
        if (rex_addon::get('yform')->isAvailable()) {
            $yformTable = rex_yform_manager_table::get($tableName);
        }

        // Felder auswählen - ID IMMER dabei für URL-Generierung
        $selectedFields = $this->customConfig['fields'] ?? [];
        $fieldList = '*';
        if (!empty($selectedFields)) {
            // ID immer hinzufügen für Links, auch wenn nicht explizit gewählt
            $fieldsToSelect = $selectedFields;
            if (!in_array('id', $fieldsToSelect)) {
                array_unshift($fieldsToSelect, 'id'); // ID an den Anfang
            }
            
            // SQL-Feldliste erstellen mit korrekter Escape-Funktion
            $escapedFields = [];
            foreach ($fieldsToSelect as $field) {
                $escapedFields[] = '`' . str_replace('`', '``', $field) . '`';
            }
            $fieldList = implode(', ', $escapedFields);
        }

        // Basis-Query mit optionalem Filter
        $whereClause = '';
        if (!empty($filter)) {
            // Validiere Filter-SQL (einfache Prüfung)
            if (!$this->isValidFilter($filter)) {
                throw new \Exception("Ungültige Filter-Bedingung");
            }
            $whereClause = ' WHERE ' . $filter;
        }
        
        $query = "SELECT {$fieldList} FROM `{$tableName}`{$whereClause} ORDER BY id DESC LIMIT {$limit}";
        
        return $sql->getArray($query);
    }

    private function isValidFilter(string $filter): bool
    {
        // Einfache Validierung - gefährliche Befehle blockieren
        $dangerous = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
        $upperFilter = strtoupper($filter);
        
        foreach ($dangerous as $cmd) {
            if (strpos($upperFilter, $cmd) !== false) {
                return false;
            }
        }
        
        return true;
    }

    private function renderList(array $data): string
    {
        if (empty($data)) {
            return '<div class="info-center-empty">Keine Datensätze gefunden</div>';
        }

        // Darstellung wie beim Article Widget mit "Recent Articles"
        $content = '<div class="info-center-recent-articles">';
        $content .= '<h4>Datensätze</h4>';
        
        foreach ($data as $row) {
            $content .= $this->renderListItem($row);
        }
        
        $content .= '</div>';

        // Link zur Tabellenverwaltung (nur Backend)
        if (rex::isBackend() && rex::getUser() && rex_addon::get('yform')->isAvailable()) {
            $params = ['table_name' => $this->customConfig['table_name']];
            
            // CSRF-Token auch für Tabellenverwaltung hinzufügen
            $csrf_token = $this->getCSRFToken($this->customConfig['table_name']);
            if ($csrf_token) {
                $params['_csrf_token'] = $csrf_token;
            }
            
            $content .= '<div class="info-center-widget-actions">';
            $content .= '<a href="' . rex_url::backendPage('yform/manager/data_edit', $params) . '" class="info-center-btn-secondary">';
            $content .= '<i class="rex-icon fa-table"></i> Tabelle verwalten';
            $content .= '</a>';
            $content .= '</div>';
        }

        return $content;
    }

    private function renderListItem(array $row): string
    {
        $linkType = $this->customConfig['link_type'] ?? 'yform';
        $linkUrl = $this->generateLinkUrl($row, $linkType);
        
        // Darstellung wie beim "Zuletzt bearbeitet" Widget
        $displayFields = $this->getDisplayFields($row);
        
        if (empty($displayFields)) {
            // Fallback: Zeige ID und eventuell andere wichtige Felder
            $primaryValue = $row['id'] ?? 'Datensatz';
            $displayFields = [[
                'name' => 'id',
                'label' => 'ID',
                'value' => $primaryValue
            ]];
        }
        
        $primaryField = reset($displayFields);
        
        // Datum ermitteln (createdate, updatedate oder date)
        $dateField = $row['createdate'] ?? $row['updatedate'] ?? $row['date'] ?? '';
        $formattedDate = '';
        if ($dateField) {
            try {
                // Try different date formats
                if (is_numeric($dateField)) {
                    $formattedDate = date('d.m. H:i', (int)$dateField);
                } else {
                    $timestamp = strtotime($dateField);
                    if ($timestamp !== false) {
                        $formattedDate = date('d.m. H:i', $timestamp);
                    }
                }
            } catch (\Exception $e) {
                $formattedDate = '';
            }
        }
        
        // Zusätzliche Meta-Informationen aus allen Feldern
        $metaInfo = '';
        $additionalFields = [];
        
        if (count($displayFields) > 1) {
            // Alle Felder außer dem ersten sammeln
            for ($i = 1; $i < count($displayFields); $i++) {
                $field = $displayFields[$i];
                $additionalFields[] = rex_escape($field['value']);
            }
            $metaInfo = implode(' • ', $additionalFields);
        }
        
        $content = '<div class="info-center-recent-article">';
        
        if ($linkUrl) {
            $content .= sprintf(
                '<a href="%s" title="%s">
                    <span class="article-name">%s</span>
                    %s
                    <span class="article-date">%s</span>
                </a>',
                $linkUrl, // URL NICHT escapen
                rex_escape($primaryField['value']),
                rex_escape($this->truncateString($primaryField['value'], 30)),
                $metaInfo ? '<span class="article-meta" style="display: block; font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 2px;">' . $this->truncateString($metaInfo, 50) . '</span>' : '',
                $formattedDate
            );
        } else {
            $content .= sprintf(
                '<div style="padding: 6px 8px; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 4px; background: rgba(255, 255, 255, 0.02);">
                    <span class="article-name">%s</span>
                    %s
                    <span class="article-date" style="float: right;">%s</span>
                </div>',
                rex_escape($this->truncateString($primaryField['value'], 30)),
                $metaInfo ? '<div style="font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 2px;">' . $this->truncateString($metaInfo, 50) . '</div>' : '',
                $formattedDate
            );
        }
        
        $content .= '</div>';
        
        return $content;
    }

    private function generateLinkUrl(array $row, string $linkType): string
    {
        switch ($linkType) {
            case 'none':
                return '';
                
            case 'yform':
                // Check permissions first
                $user = null;
                if (rex::isFrontend()) {
                    // Im Frontend: Backend-User-Session erstellen falls noch nicht vorhanden
                    $user = rex_backend_login::createUser();
                } else {
                    // Im Backend: Direkt aktuellen User verwenden
                    $user = rex::getUser();
                }
                
                if (!$user) {
                    return '';
                }
                
                // Check permissions for YForm access
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
                
                if ($hasPermissions && rex_addon::get('yform')->isAvailable()) {
                    $tableName = $this->customConfig['table_name'];
                    $recordId = $row['id'] ?? null;
                    if ($recordId) {
                        $params = [
                            'table_name' => $tableName,
                            'data_id' => $recordId,
                            'func' => 'edit'
                        ];
                        
                        // CSRF-Token für YForm hinzufügen (besonders wichtig im Frontend)
                        $csrf_token = $this->getCSRFToken($tableName);
                        if ($csrf_token) {
                            $params['_csrf_token'] = $csrf_token;
                        }
                        
                        return rex_url::backendPage('yform/manager/data_edit', $params);
                    }
                }
                return '';
                
            case 'custom':
                $template = $this->customConfig['link_target'] ?? '';
                if (!$template) return '';
                
                // Platzhalter ersetzen
                $url = $template;
                foreach ($row as $field => $value) {
                    $url = str_replace('{' . $field . '}', urlencode($value), $url);
                }
                return $url;
                
            default:
                return '';
        }
    }

    private function getDisplayFields(array $row): array
    {
        $fields = [];
        $selectedFields = $this->customConfig['fields'] ?? [];
        
        // Wenn spezifische Felder ausgewählt wurden, diese verwenden
        // ABER: ID nur anzeigen wenn explizit gewählt (nicht die automatisch hinzugefügte)
        if (!empty($selectedFields)) {
            foreach ($selectedFields as $fieldName) {
                if (isset($row[$fieldName]) && !empty($row[$fieldName])) {
                    $fields[] = [
                        'name' => $fieldName,
                        'label' => $this->getFieldLabel($fieldName),
                        'value' => $this->formatFieldValue($fieldName, $row[$fieldName])
                    ];
                }
            }
            return $fields;
        }
        
        // Fallback: Standard-Felder in Prioritätsreihenfolge
        $priorityFields = ['name', 'title', 'subject', 'email', 'status', 'id'];
        
        // Zuerst Priority-Felder
        foreach ($priorityFields as $fieldName) {
            if (isset($row[$fieldName]) && !empty($row[$fieldName])) {
                $fields[] = [
                    'name' => $fieldName,
                    'label' => $this->getFieldLabel($fieldName),
                    'value' => $this->formatFieldValue($fieldName, $row[$fieldName])
                ];
            }
        }
        
        // Dann andere Felder (außer System-Felder)
        $skipFields = ['id', 'createdate', 'updatedate', 'createuser', 'updateuser', 'prio'];
        foreach ($row as $fieldName => $value) {
            if (!in_array($fieldName, $priorityFields) && 
                !in_array($fieldName, $skipFields) && 
                !empty($value) && 
                count($fields) < 4) {
                
                $fields[] = [
                    'name' => $fieldName,
                    'label' => $this->getFieldLabel($fieldName),
                    'value' => $this->formatFieldValue($fieldName, $value)
                ];
            }
        }
        
        return $fields;
    }

    private function getFieldLabel(string $fieldName): string
    {
        // Einfache Label-Generierung
        $labels = [
            'name' => 'Name',
            'title' => 'Titel',
            'subject' => 'Betreff',
            'email' => 'E-Mail',
            'status' => 'Status',
            'description' => 'Beschreibung',
            'content' => 'Inhalt'
        ];
        
        return $labels[$fieldName] ?? ucfirst($fieldName);
    }

    private function formatFieldValue(string $fieldName, $value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        // Lange Texte kürzen
        $value = (string)$value;
        if (strlen($value) > 50) {
            $value = substr($value, 0, 47) . '...';
        }
        
        return $value;
    }

    private function getCSRFToken(string $tableName): ?string
    {
        $csrf_token = null;
        
        try {
            $table = rex_yform_manager_table::get($tableName);
            if ($table) {
                if (rex::isBackend()) {
                    // Im Backend: Verwende standard REDAXO CSRF-Token
                    $_csrf_key = $table->getCSRFKey();
                    $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();
                    $csrf_token = $_csrf_params['_csrf_token'];
                } elseif (rex::isFrontend() && rex_backend_login::hasSession()) {
                    // Im Frontend: Nur mit Backend-Session
                    rex::setProperty('redaxo', true);
                    $_csrf_key = $table->getCSRFKey();
                    $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();
                    $csrf_token = $_csrf_params['_csrf_token'];
                    rex::setProperty('redaxo', false);
                }
            }
        } catch (\Exception $e) {
            // CSRF token generation failed, continue without token
            $csrf_token = null;
        }
        
        return $csrf_token;
    }

    private function truncateString(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }

    public function getPriority(): int
    {
        return $this->customConfig['priority'] ?? 20; // Nach Standard-Widgets
    }

    public function isEnabled(): bool
    {
        return $this->customConfig['enabled'] ?? false;
    }
}
