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

        // Basis-Query
        $query = "SELECT * FROM `{$tableName}` ORDER BY id DESC LIMIT {$limit}";
        
        return $sql->getArray($query);
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
        $content = '<div class="info-center-list-item">';
        
        // Hauptinhalt - erste paar Felder anzeigen
        $displayFields = $this->getDisplayFields($row);
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
        
        // Datum falls vorhanden
        if (isset($row['createdate']) || isset($row['updatedate']) || isset($row['date'])) {
            $dateField = $row['createdate'] ?? $row['updatedate'] ?? $row['date'] ?? '';
            if ($dateField) {
                $content .= '<div class="info-center-list-item-date">';
                $content .= rex_formatter::format($dateField, 'date', 'd.m.Y H:i');
                $content .= '</div>';
            }
        }
        
        $content .= '</div>';
        
        return $content;
    }

    private function getDisplayFields(array $row): array
    {
        $fields = [];
        
        // Standard-Felder die oft vorkommen, in Prioritätsreihenfolge
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
