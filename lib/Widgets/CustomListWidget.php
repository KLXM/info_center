<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_sql;
use rex_yform_manager_table;
use rex_url;
use rex_formatter;

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

        // Felder auswählen
        $selectedFields = $this->customConfig['fields'] ?? [];
        $fieldList = '*';
        if (!empty($selectedFields)) {
            $fieldList = '`' . implode('`, `', array_map('rex_sql::escape', $selectedFields)) . '`';
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

        $content = '<div class="info-center-custom-list">';
        
        foreach ($data as $row) {
            $content .= $this->renderListItem($row);
        }
        
        $content .= '</div>';

        // Link zur Tabellenverwaltung (nur Backend)
        if (rex::isBackend() && rex::getUser() && rex_addon::get('yform')->isAvailable()) {
            $content .= '<div class="info-center-widget-actions">';
            $content .= '<a href="' . rex_url::backendPage('yform/manager/data_edit', ['table_name' => $this->customConfig['table_name']]) . '" class="info-center-btn-secondary">';
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
        
        $content = '<div class="info-center-list-item' . ($linkUrl ? ' info-center-list-item-clickable' : '') . '">';
        
        if ($linkUrl) {
            $content .= '<a href="' . rex_escape($linkUrl) . '" class="info-center-item-link">';
        }
        
        // Hauptinhalt - ausgewählte oder alle Felder anzeigen
        $displayFields = $this->getDisplayFields($row);
        
        if (!empty($displayFields)) {
            $primaryField = reset($displayFields);
            
            $content .= '<div class="info-center-list-item-main">';
            $content .= '<strong>' . rex_escape($primaryField['value']) . '</strong>';
            $content .= '</div>';
            
            // Zusätzliche Felder
            if (count($displayFields) > 1) {
                $content .= '<div class="info-center-list-item-meta">';
                $metaFields = array_slice($displayFields, 1, 2); // Max 2 zusätzliche Felder
                foreach ($metaFields as $field) {
                    $content .= '<span class="info-center-meta-item">' . rex_escape($field['label']) . ': ' . rex_escape($field['value']) . '</span>';
                }
                $content .= '</div>';
            }
        }
        
        // Datum falls vorhanden
        if (isset($row['createdate']) || isset($row['updatedate']) || isset($row['date'])) {
            $dateField = $row['createdate'] ?? $row['updatedate'] ?? $row['date'] ?? '';
            if ($dateField) {
                $content .= '<div class="info-center-list-item-date">';
                $content .= rex_formatter::format($dateField, 'date', 'd.m.Y H:i');
                $content .= '</div>';
            }
        }
        
        if ($linkUrl) {
            $content .= '</a>';
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
                if (rex::isBackend() && rex::getUser() && rex_addon::get('yform')->isAvailable()) {
                    $tableName = $this->customConfig['table_name'];
                    $recordId = $row['id'] ?? null;
                    if ($recordId) {
                        return rex_url::backendPage('yform/manager/data_edit', [
                            'table_name' => $tableName,
                            'data_id' => $recordId,
                            'func' => 'edit'
                        ]);
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

    public function getPriority(): int
    {
        return $this->customConfig['priority'] ?? 20; // Nach Standard-Widgets
    }

    public function isEnabled(): bool
    {
        return $this->customConfig['enabled'] ?? false;
    }
}
