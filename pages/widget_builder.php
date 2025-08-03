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
    $content .= '<ul style="margin-top: 10px; margin-bottom: 0;">';
    $content .= '<li><strong>Darstellung:</strong> Widgets werden wie das "Zuletzt bearbeitet" Widget angezeigt</li>';
    $content .= '<li><strong>Links:</strong> YForm-Links funktionieren im Backend und Frontend (automatische Session-Erstellung)</li>';
    $content .= '<li><strong>Sicherheit:</strong> CSRF-Token werden automatisch für YForm-Links hinzugefügt</li>';
    $content .= '<li><strong>Felder:</strong> Wählen Sie spezifische Felder oder lassen Sie das System die wichtigsten auswählen</li>';
    $content .= '</ul>';
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
        $content .= '<th>Verlinkung</th>';
        $content .= '<th>Sichtbarkeit</th>';
        $content .= '<th>Status</th>';
        $content .= '<th>Aktionen</th>';
        $content .= '</tr></thead><tbody>';
        
        foreach ($customWidgets as $id => $widget) {
            $content .= '<tr>';
            $content .= '<td><strong>' . rex_escape($widget['name']) . '</strong></td>';
            $content .= '<td>' . rex_escape($widget['table_name']) . '</td>';
            
            // Link-Typ anzeigen
            $content .= '<td>';
            $linkType = $widget['link_type'] ?? 'yform';
            switch ($linkType) {
                case 'none':
                    $content .= '<span class="label label-default">Keine</span>';
                    break;
                case 'yform':
                    $content .= '<span class="label label-info">YForm</span>';
                    break;
                case 'url_addon':
                    $content .= '<span class="label label-primary">URL Addon</span>';
                    break;
                case 'yrewrite':
                    $content .= '<span class="label label-primary">YRewrite</span>';
                    break;
                case 'custom':
                    $content .= '<span class="label label-warning">Custom</span>';
                    break;
                default:
                    $content .= '<span class="label label-default">-</span>';
            }
            $content .= '</td>';
            
            // Sichtbarkeit anzeigen
            $content .= '<td>';
            $visibility = $widget['visibility'] ?? 'both';
            switch ($visibility) {
                case 'both':
                    $content .= '<span class="label label-success">Backend + Frontend</span>';
                    break;
                case 'backend':
                    $content .= '<span class="label label-info">Nur Backend</span>';
                    break;
                case 'frontend':
                    $content .= '<span class="label label-warning">Nur Frontend</span>';
                    break;
                default:
                    $content .= '<span class="label label-default">-</span>';
            }
            $content .= '</td>';
            
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
    
    // Feldauswahl Container (dynamisch geladen via API)
    $formElements = [];
    $n = [];
    $n['label'] = '<label>Anzuzeigende Felder</label>';
    $fieldsHtml = '<div id="table-fields-container">';
    if (!empty($widget['table_name'])) {
        $fieldsHtml .= '<p class="help-block text-muted">Felder werden geladen...</p>';
    } else {
        // Hinweis für neue Widgets
        $fieldsHtml .= '<p class="help-block text-muted">Wählen Sie zuerst eine YForm-Tabelle aus, um die verfügbaren Felder zu sehen.</p>';
    }
    $fieldsHtml .= '</div>';
    $n['field'] = $fieldsHtml;
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
    
    // Filter-Bedingungen
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="widget-filter">Filter-Bedingungen <small class="text-muted">(Optional)</small></label>';
    $filterValue = $widget['filter'] ?? '';
    $n['field'] = '<textarea id="widget-filter" name="widget_config[filter]" class="form-control" rows="3" placeholder="z.B. status = 1 AND created > \'2024-01-01\'">' . rex_escape($filterValue) . '</textarea>';
    $n['note'] = '<small class="text-muted">SQL WHERE-Bedingung ohne "WHERE". Beispiele:<br>• <code>status = 1</code><br>• <code>createdate >= CURDATE() - INTERVAL 30 DAY</code><br>• <code>status = 1 AND email LIKE \'%@example.com\'</code></small>';
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');
    
    // Verlinkung/Action
    $formElements = [];
    $n = [];
    $n['label'] = '<label>Verlinkung der Datensätze</label>';
    $linkType = $widget['link_type'] ?? 'yform';
    $linkTarget = $widget['link_target'] ?? '';
    
    // Prüfe verfügbare URL-Addons
    $urlAddonAvailable = rex_addon::get('url')->isAvailable();
    $yrewriteAvailable = rex_addon::get('yrewrite')->isAvailable();
    
    $linkOptions = '
    <div class="radio">
        <label><input type="radio" name="widget_config[link_type]" value="none"' . ($linkType === 'none' ? ' checked' : '') . '> Keine Verlinkung</label>
    </div>
    <div class="radio">
        <label><input type="radio" name="widget_config[link_type]" value="yform"' . ($linkType === 'yform' ? ' checked' : '') . '> YForm Tabelle bearbeiten (empfohlen)</label>
        <small class="text-muted" style="margin-left: 20px; display: block;">
            Links zur YForm-Datensatz-Bearbeitung. Im Frontend wird automatisch eine Backend-Session erstellt.<br>
            <strong>Sicherheit:</strong> CSRF-Token werden automatisch hinzugefügt für sichere YForm-Operationen.
        </small>
    </div>';
    
    // URL Addon Schema Option
    if ($urlAddonAvailable) {
        $linkOptions .= '
        <div class="radio">
            <label><input type="radio" name="widget_config[link_type]" value="url_addon"' . ($linkType === 'url_addon' ? ' checked' : '') . '> URL Addon Schema</label>
            <small class="text-muted" style="margin-left: 20px; display: block;">
                Verwendet URL Addon Schemas für saubere URLs (z.B. /artikel/{id}/)
            </small>
        </div>';
    }
    
    // YRewrite Schema Option
    if ($yrewriteAvailable) {
        $linkOptions .= '
        <div class="radio">
            <label><input type="radio" name="widget_config[link_type]" value="yrewrite"' . ($linkType === 'yrewrite' ? ' checked' : '') . '> YRewrite Schema</label>
            <small class="text-muted" style="margin-left: 20px; display: block;">
                Verwendet YRewrite für strukturierte URLs
            </small>
        </div>';
    }
    
    $linkOptions .= '
    <div class="radio">
        <label><input type="radio" name="widget_config[link_type]" value="custom"' . ($linkType === 'custom' ? ' checked' : '') . '> Benutzerdefinierte URL</label>
        <div class="form-group" id="custom-link-container" style="margin-top: 10px; margin-left: 20px; ' . ($linkType !== 'custom' ? 'display: none;' : '') . '">
            <input type="text" name="widget_config[link_target]" class="form-control" placeholder="z.B. index.php?page=mypage&id={id}" value="' . rex_escape($linkTarget) . '">
            <small class="text-muted">Verwenden Sie {feldname} als Platzhalter, z.B. {id}, {name}</small>
        </div>
    </div>';
    
    $n['field'] = $linkOptions;
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');
    
    // Sichtbarkeit
    $formElements = [];
    $n = [];
    $n['label'] = '<label>Sichtbarkeit</label>';
    $visibility = $widget['visibility'] ?? 'both';
    
    $visibilityOptions = '
    <div class="checkbox-group">
        <div class="radio">
            <label><input type="radio" name="widget_config[visibility]" value="both"' . ($visibility === 'both' ? ' checked' : '') . '> Backend und Frontend</label>
            <small class="text-muted" style="margin-left: 20px; display: block;">Widget wird in beiden Bereichen angezeigt</small>
        </div>
        <div class="radio">
            <label><input type="radio" name="widget_config[visibility]" value="backend"' . ($visibility === 'backend' ? ' checked' : '') . '> Nur Backend</label>
            <small class="text-muted" style="margin-left: 20px; display: block;">Widget wird nur im REDAXO Backend angezeigt</small>
        </div>
        <div class="radio">
            <label><input type="radio" name="widget_config[visibility]" value="frontend"' . ($visibility === 'frontend' ? ' checked' : '') . '> Nur Frontend</label>
            <small class="text-muted" style="margin-left: 20px; display: block;">Widget wird nur im Frontend für eingeloggte Backend-Benutzer angezeigt</small>
        </div>
    </div>';
    
    $n['field'] = $visibilityOptions;
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
    </form>
    
    <script>
    // JavaScript für dynamische Feldauswahl und Link-Optionen
    jQuery(document).on("rex:ready", function() {
        // Tabellen-Auswahl Handler
        const tableSelect = document.getElementById("widget-table");
        if (tableSelect) {
            tableSelect.addEventListener("change", function() {
                loadTableFields(this.value);
            });
            
            // Beim Page-Load: Falls bereits eine Tabelle ausgewählt ist, Felder laden
            if (tableSelect.value) {
                loadTableFields(tableSelect.value);
            }
        }
        
        // Link-Type Radio Handler  
        const linkRadios = document.querySelectorAll("input[name=\'widget_config[link_type]\']");
        linkRadios.forEach(function(radio) {
            radio.addEventListener("change", function() {
                toggleCustomUrlContainer(this.value === "custom");
            });
        });
        
        // Initial state für Custom URL Container
        const checkedRadio = document.querySelector("input[name=\'widget_config[link_type]\']:checked");
        if (checkedRadio) {
            toggleCustomUrlContainer(checkedRadio.value === "custom");
        }
    });
    
    function toggleCustomUrlContainer(show) {
        const customContainer = document.getElementById("custom-link-container");
        if (customContainer) {
            customContainer.style.display = show ? "block" : "none";
        }
    }
    
    function loadTableFields(tableName) {
        const container = document.getElementById("table-fields-container");
        if (!container) return;
        
        if (!tableName) {
            container.innerHTML = "<p class=\"help-block text-muted\">Wählen Sie zuerst eine YForm-Tabelle aus, um die verfügbaren Felder zu sehen.</p>";
            return;
        }
        
        container.innerHTML = "<p><i class=\"rex-icon fa-spinner fa-spin\"></i> Lade Felder...</p>";
        
        // Widget-ID für AJAX-Request ermitteln
        const widgetIdInput = document.querySelector("input[name=\'widget_id\']");
        const widgetId = widgetIdInput ? widgetIdInput.value : "";
        
        // Verwende REDAXO API statt direktem AJAX
        let apiUrl = "' . rex_url::backendController() . '?rex-api-call=widget_builder&action=get_table_fields&table_name=" + encodeURIComponent(tableName);
        
        if (widgetId) {
            apiUrl += "&widget_id=" + encodeURIComponent(widgetId);
        }
        
        fetch(apiUrl, {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            console.error("Error loading fields:", error);
            container.innerHTML = "<div class=\"alert alert-danger\">Fehler beim Laden der Felder: " + error.message + "</div>";
        });
    }
    </script>';
    
    return $output;
}

function saveWidget($package, $customWidgets)
{
    $widgetId = rex_post('widget_id', 'string', '');
    $config = rex_post('widget_config', 'array', []);
    
    // Felder-Array separat abrufen, da es außerhalb von widget_config ist
    $fieldsArray = rex_post('fields', 'array', []);
    
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
        'fields' => $fieldsArray, // Verwende die separat abgerufenen Felder
        'filter' => trim($config['filter'] ?? ''),
        'link_type' => $config['link_type'] ?? 'yform',
        'link_target' => trim($config['link_target'] ?? ''),
        'visibility' => $config['visibility'] ?? 'both',
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
