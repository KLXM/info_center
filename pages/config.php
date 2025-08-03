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
    $widgets = ['url', 'timetracker', 'article', 'upkeep', 'stats', 'system', 'messaging'];
    
    foreach ($widgets as $widget) {
        $widgetConfig[$widget] = [
            'enabled' => isset($config['widget_' . $widget . '_' . $user]) ? 1 : 0
        ];
    }
    
    $package->setConfig('widgets_user_' . $user, $widgetConfig);
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
    'article' => 'Article Widget',
    'upkeep' => 'Upkeep Widget',
    'stats' => 'Statistics Widget',
    'system' => 'System Widget',
    'messaging' => 'Messaging Widget'
];

foreach ($widgets as $widgetId => $widgetTitle) {
    $formElements = [];
    $n = [];
    
    // Prüfe ob Widget global aktiviert ist
    $globalEnabled = $globalConfig[$widgetId]['enabled'] ?? true;
    $userEnabled = $currentConfig[$widgetId]['enabled'] ?? $globalEnabled;
    
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
