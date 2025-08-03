<?php

namespace KLXM\InfoCenter;

use rex;
use rex_addon;
use rex_fragment;
use rex_url;
use rex_view;

$package = rex_addon::get('info_center');
$content = '';
$user = '';
$buttons = '';
$formElements = [];
$n = [];

// User-ID ermitteln
$user = rex::getUser()->getId();

// Einstellungen speichern
if (rex_post('formsubmit', 'string') == '1') {
    $config = rex_post('config', 'array');
    
    // Widget-Einstellungen pro User speichern
    $widgetConfig = [];
    $widgets = ['url', 'timetracker', 'article', 'upkeep', 'stats', 'system'];
    $globalConfig = $package->getConfig('widgets', []);
    
    foreach ($widgets as $widget) {
        // Prüfe ob Widget global aktiviert ist
        $globalEnabled = $globalConfig[$widget]['enabled'] ?? true;
        
        // Nur speichern wenn Widget global aktiviert ist (sonst macht User-Setting keinen Sinn)
        if ($globalEnabled) {
            $widgetConfig[$widget] = [
                'enabled' => isset($config['widget_' . $widget . '_' . $user]) ? 1 : 0
            ];
        }
    }
    
    // Nur speichern wenn es User-spezifische Einstellungen gibt
    if (!empty($widgetConfig)) {
        $package->setConfig('widgets_user_' . $user, $widgetConfig);
    } else {
        // Wenn keine User-spezifischen Einstellungen existieren, lösche die Config
        $package->removeConfig('widgets_user_' . $user);
    }
    
    echo rex_view::success($package->i18n('info_center_config_saved'));
}

// Aktuelle Widget-Konfiguration laden
$currentConfig = $package->getConfig('widgets_user_' . $user, []);
$globalConfig = $package->getConfig('widgets', []);

$content .= '<fieldset><legend>' . $package->i18n('info_center_widget_settings') . '</legend>';

// Widget-Checkboxen
$widgets = [
    'url' => 'URL Widget',
    'timetracker' => 'Time Tracker Widget',
    'article' => 'Article Widget'
];

// Admin-only Widgets
$adminWidgets = [
    'upkeep' => 'Upkeep Widget',
    'stats' => 'Statistics Widget',
    'system' => 'System Widget'
];

// Füge Admin-Widgets hinzu wenn User Admin ist
if (rex::getUser()->isAdmin()) {
    $widgets = array_merge($widgets, $adminWidgets);
}

foreach ($widgets as $widgetId => $widgetTitle) {
    $formElements = [];
    $n = [];
    
    // Prüfe ob Widget global aktiviert ist
    $globalEnabled = $globalConfig[$widgetId]['enabled'] ?? true;
    
    // User-Einstellung: Nur wenn global aktiviert UND User-spezifische Einstellung existiert
    $userEnabled = $globalEnabled;  // Standard: folge globaler Einstellung
    if ($globalEnabled && isset($currentConfig[$widgetId]['enabled'])) {
        $userEnabled = (bool) $currentConfig[$widgetId]['enabled'];
    }
    
    $n['label'] = '<label for="info-center-widget-' . $widgetId . '">' . $widgetTitle . '</label>';
    $n['field'] = '<input type="checkbox" id="info-center-widget-' . $widgetId . '" name="config[widget_' . $widgetId . '_' . $user . ']"' . 
                  ($userEnabled ? ' checked="checked"' : '') . 
                  ($globalEnabled ? '' : ' disabled="disabled"') . 
                  ' value="1" />';
    
    if (!$globalEnabled) {
        $n['note'] = '<small class="text-muted">' . $package->i18n('info_center_widget_disabled_globally') . '</small>';
    }
    
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');
}

$content .= '</fieldset>';

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $package->i18n('info_center_config_save') . '">' . $package->i18n('info_center_config_save') . '</button>';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$buttons = '
<fieldset class="rex-form-action">
    ' . $buttons . '
</fieldset>
';

// Ausgabe Formular
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $package->i18n('info_center_widget_configuration'));
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$output = $fragment->parse('core/page/section.php');

$output = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="formsubmit" value="1" />
' . $output . '
</form>
';

echo $output;
