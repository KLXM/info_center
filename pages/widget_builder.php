<?php

namespace KLXM\InfoCenter;

use rex;
use rex_addon;
use rex_fragment;
use rex_url;
use rex_view;
use rex_yform_manager_table;
use rex_exception;
use rex_sql;
use Exception;

// Nur für Admins zugänglich
if (!rex::getUser()->isAdmin()) {
    throw new rex_exception('Keine Berechtigung für Widget Builder');
}

$package = rex_addon::get('info_center');
$content = '';
$buttons = '';
$formElements = [];
$n = [];

// Aktuelle Aktion ermitteln
$func = rex_request('func', 'string', '');
$widgetId = rex_request('widget_id', 'string', '');

// Widget-Konfigurationen laden
$customWidgets = $package->getConfig('custom_widgets', []);

// AJAX Handler für Feldauswahl
if ($func === 'get_table_fields') {
    $tableName = rex_request('table_name', 'string', '');
    if ($tableName) {
        echo renderFieldSelection($tableName, []);
        exit;
    }
}

switch ($func) {
    case 'add':
    case 'edit':
        echo renderWidgetForm($package, $func, $widgetId, $customWidgets);
        break;
    
    case 'delete':
        if ($widgetId && isset($customWidgets[$widgetId])) {
            unset($customWidgets[$widgetId]);
            $package->setConfig('custom_widgets', $customWidgets);
            echo rex_view::success($package->i18n('info_center_widget_deleted'));
        }
        echo renderWidgetList($package, $customWidgets);
        break;
    
    case 'save':
        $result = saveWidget($package, $customWidgets);
        echo $result['message'];
        if ($result['success']) {
            echo renderWidgetList($package, $result['widgets']);
        } else {
            echo renderWidgetForm($package, rex_post('widget_id') ? 'edit' : 'add', rex_post('widget_id', 'string', ''), $result['widgets']);
        }
        break;
    
    default:
        echo renderWidgetList($package, $customWidgets);
        break;
}

function renderWidgetList($package, $customWidgets)
{
    $content = '';
    
    // Beschreibung
    $content .= '<div class="alert alert-info">';
    $content .= '<h4>' . $package->i18n('info_center_widget_builder_title') . '</h4>';
    $content .= '<p>' . $package->i18n('info_center_widget_builder_description') . '</p>';
    $content .= '</div>';
    
    // Neues Widget Button
    $content .= '<div class="btn-group" style="margin-bottom: 20px;">';
    $content .= '<a href="' . rex_url::currentBackendPage(['func' => 'add']) . '" class="btn btn-primary">';
    $content .= '<i class="rex-icon fa-plus"></i> ' . $package->i18n('info_center_widget_create_new');
    $content .= '</a>';
    $content .= '</div>';
    
    // Widget-Liste
    if (!empty($customWidgets)) {
        // Manuelle Tabellenerstellung für Custom Widgets
        $content .= '<table class="table table-striped table-hover">';
        $content .= '<thead><tr>';
        $content .= '<th>Widget-Name</th>';
        $content .= '<th>YForm-Tabelle</th>';
        $content .= '<th>Status</th>';
        $content .= '<th>Aktionen</th>';
        $content .= '</tr></thead><tbody>';
        
        foreach ($customWidgets as $id => $widget) {
            $content .= '<tr>';
            $content .= '<td><strong>' . rex_escape($widget['name']) . '</strong></td>';
            $content .= '<td>' . rex_escape($widget['table_name']) . '</td>';
            $content .= '<td>';
            if ($widget['enabled']) {
                $content .= '<span class="label label-success">Aktiv</span>';
            } else {
                $content .= '<span class="label label-default">Inaktiv</span>';
            }
            $content .= '</td>';
            $content .= '<td>';
            $content .= '<a href="' . rex_url::currentBackendPage(['func' => 'edit', 'widget_id' => $id]) . '" class="btn btn-xs btn-primary">';
            $content .= '<i class="rex-icon fa-edit"></i> ' . $package->i18n('info_center_widget_edit');
            $content .= '</a> ';
            $content .= '<a href="' . rex_url::currentBackendPage(['func' => 'delete', 'widget_id' => $id]) . '" class="btn btn-xs btn-danger" onclick="return confirm(\'Widget wirklich löschen?\');">';
            $content .= '<i class="rex-icon fa-trash"></i> ' . $package->i18n('info_center_widget_delete');
            $content .= '</a>';
            $content .= '</td>';
            $content .= '</tr>';
        }
        
        $content .= '</tbody></table>';
    } else {
        $content .= '<div class="alert alert-info">Noch keine Custom Widgets erstellt. Klicken Sie auf "Neues Widget erstellen" um zu beginnen.</div>';
    }
    
    // Ausgabe
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit');
    $fragment->setVar('title', $package->i18n('info_center_widget_builder'));
    $fragment->setVar('body', $content, false);
    
    return $fragment->parse('core/page/section.php');
}

function renderWidgetForm($package, $func, $widgetId, $customWidgets)
{
    $content = '';
    $isEdit = ($func === 'edit' && $widgetId && isset($customWidgets[$widgetId]));
    $widget = $isEdit ? $customWidgets[$widgetId] : [];
    
    // YForm-Tabellen laden
    $yformTables = [];
    if (rex_addon::get('yform')->isAvailable()) {
        $tables = rex_yform_manager_table::getAll();
        foreach ($tables as $table) {
            $yformTables[$table->getTableName()] = $table->getName() . ' (' . $table->getTableName() . ')';
        }
    }
    
    if (empty($yformTables)) {
        return '<div class="alert alert-warning">YForm Addon ist nicht verfügbar oder keine Tabellen vorhanden.</div>';
    }
    
    $content .= '<fieldset>';
    $content .= '<legend>' . ($isEdit ? 'Widget bearbeiten' : 'Neues Widget erstellen') . '</legend>';
    
    // Widget-Name
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="widget-name">' . $package->i18n('info_center_widget_name') . '</label>';
    $n['field'] = '<input type="text" id="widget-name" name="widget_config[name]" class="form-control" value="' . rex_escape($widget['name'] ?? '') . '" required />';
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');
    
    // YForm-Tabelle
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="widget-table">' . $package->i18n('info_center_widget_table') . '</label>';
    $tableSelect = '<select id="widget-table" name="widget_config[table_name]" class="form-control" required>';
    $tableSelect .= '<option value="">-- Tabelle wählen --</option>';
    foreach ($yformTables as $tableName => $tableLabel) {
        $selected = ($widget['table_name'] ?? '') === $tableName ? ' selected' : '';
        $tableSelect .= '<option value="' . rex_escape($tableName) . '"' . $selected . '>' . rex_escape($tableLabel) . '</option>';
    }
    $tableSelect .= '</select>';
    $n['field'] = $tableSelect;
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');
    
    // Anzahl Datensätze
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="widget-limit">' . $package->i18n('info_center_widget_limit') . '</label>';
    $n['field'] = '<input type="number" id="widget-limit" name="widget_config[limit]" class="form-control" value="' . (int)($widget['limit'] ?? 5) . '" min="1" max="50" />';
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');
    
    // Status
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="widget-enabled">Status</label>';
    $n['field'] = '<input type="checkbox" id="widget-enabled" name="widget_config[enabled]" value="1"' . (($widget['enabled'] ?? true) ? ' checked' : '') . ' /> Widget aktiviert';
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');
    
    $content .= '</fieldset>';
    
    // Buttons
    $formElements = [];
    $n = [];
    $n['field'] = '<button class="btn btn-save" type="submit" name="save" value="1">' . $package->i18n('info_center_widget_save') . '</button>';
    $n['field'] .= ' <a href="' . rex_url::currentBackendPage() . '" class="btn btn-default">Abbrechen</a>';
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');
    
    $buttons = '<fieldset class="rex-form-action">' . $buttons . '</fieldset>';
    
    // Ausgabe
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit');
    $fragment->setVar('title', $isEdit ? 'Widget bearbeiten: ' . rex_escape($widget['name']) : 'Neues Widget erstellen');
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $output = $fragment->parse('core/page/section.php');
    
    $output = '
    <form action="' . rex_url::currentBackendPage(['func' => 'save']) . '" method="post">
    <input type="hidden" name="widget_id" value="' . rex_escape($widgetId) . '" />
    ' . $output . '
    </form>';
    
    return $output;
}

function saveWidget($package, $customWidgets)
{
    $widgetId = rex_post('widget_id', 'string', '');
    $config = rex_post('widget_config', 'array', []);
    
    // Validierung
    if (empty($config['name'])) {
        return [
            'success' => false,
            'message' => rex_view::error('Widget-Name ist erforderlich'),
            'widgets' => $customWidgets
        ];
    }
    
    if (empty($config['table_name'])) {
        return [
            'success' => false,
            'message' => rex_view::error('YForm-Tabelle ist erforderlich'),
            'widgets' => $customWidgets
        ];
    }
    
    // Widget-ID generieren falls neu
    if (!$widgetId) {
        $widgetId = 'custom_' . uniqid();
    }
    
    // Widget-Konfiguration speichern
    $customWidgets[$widgetId] = [
        'name' => $config['name'],
        'table_name' => $config['table_name'],
        'limit' => max(1, min(50, (int)($config['limit'] ?? 5))),
        'enabled' => isset($config['enabled']),
        'created' => $customWidgets[$widgetId]['created'] ?? date('Y-m-d H:i:s'),
        'updated' => date('Y-m-d H:i:s')
    ];
    
    $package->setConfig('custom_widgets', $customWidgets);
    
    return [
        'success' => true,
        'message' => rex_view::success($package->i18n('info_center_widget_created')),
        'widgets' => $customWidgets
    ];
}

function renderFieldSelection($tableName, $selectedFields = [])
{
    if (!$tableName) {
        return '';
    }
    
    $sql = rex_sql::factory();
    try {
        $fields = $sql->getArray('DESCRIBE `' . $tableName . '`');
    } catch (Exception $e) {
        return '<div class="alert alert-danger">Fehler beim Laden der Tabellenfelder: ' . rex_escape($e->getMessage()) . '</div>';
    }
    
    $html = '<div class="widget-field-selection">';
    $html .= '<p class="help-block">Wählen Sie die Felder aus, die im Widget angezeigt werden sollen:</p>';
    
    foreach ($fields as $field) {
        $fieldName = $field['Field'];
        $fieldType = $field['Type'];
        $isChecked = in_array($fieldName, $selectedFields) ? ' checked' : '';
        
        // System-Felder markieren
        $isSystemField = in_array($fieldName, ['id', 'createdate', 'updatedate', 'createuser', 'updateuser', 'prio']);
        $fieldLabel = $fieldName . ($isSystemField ? ' <small class="text-muted">(System)</small>' : '');
        
        $html .= '<div class="checkbox">';
        $html .= '<label>';
        $html .= '<input type="checkbox" name="fields[]" value="' . rex_escape($fieldName) . '"' . $isChecked . '> ';
        $html .= $fieldLabel . ' <small class="text-muted">(' . $fieldType . ')</small>';
        $html .= '</label>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
