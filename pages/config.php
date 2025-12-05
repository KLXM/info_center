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
    $widgets = ['search', 'url', 'timetracker', 'article', 'upkeep', 'stats', 'system'];
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
    
    // UI-Einstellungen pro User speichern
    $uiSettings = [];
    if (isset($config['font_size'])) {
        $uiSettings['font_size'] = $config['font_size'];
    }
    if (isset($config['toggle_position'])) {
        $uiSettings['toggle_position'] = $config['toggle_position'];
    }
    if (isset($config['auto_switch_domain'])) {
        $uiSettings['auto_switch_domain'] = (bool) $config['auto_switch_domain'];
    }
    
    if (!empty($uiSettings)) {
        $package->setConfig('ui_settings_user_' . $user, $uiSettings);
    }
    
    echo rex_view::success($package->i18n('info_center_config_saved'));
}

// Aktuelle Widget-Konfiguration laden
$currentConfig = $package->getConfig('widgets_user_' . $user, []);
$globalConfig = $package->getConfig('widgets', []);
$uiSettings = $package->getConfig('ui_settings_user_' . $user, []);

$content .= '<fieldset><legend>' . $package->i18n('info_center_widget_settings') . '</legend>';

// Widget-Checkboxen
$widgets = [
    'search' => 'Search Widget',
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

// UI Settings
$content .= '<fieldset><legend>' . $package->i18n('info_center_ui_settings') . '</legend>';

// Font Size Setting
$formElements = [];
$n = [];
$currentFontSize = $uiSettings['font_size'] ?? 'medium';
$fontSizeOptions = [
    'small' => $package->i18n('info_center_font_size_small'),
    'medium' => $package->i18n('info_center_font_size_medium'), 
    'large' => $package->i18n('info_center_font_size_large'),
    'xlarge' => $package->i18n('info_center_font_size_xlarge')
];

$fontSizeSelect = '<select name="config[font_size]" class="form-control selectpicker">';
foreach ($fontSizeOptions as $value => $label) {
    $selected = ($value === $currentFontSize) ? ' selected="selected"' : '';
    $fontSizeSelect .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}
$fontSizeSelect .= '</select>';

$n['label'] = '<label for="font_size">' . $package->i18n('info_center_font_size') . '</label>';
$n['field'] = $fontSizeSelect;
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Toggle Position Setting
$formElements = [];
$n = [];
$currentPosition = $uiSettings['toggle_position'] ?? 'center';
$positionOptions = [
    'top' => $package->i18n('info_center_position_top'),
    'center' => $package->i18n('info_center_position_center'),
    'bottom' => $package->i18n('info_center_position_bottom')
];

$positionSelect = '<select name="config[toggle_position]" class="form-control selectpicker">';
foreach ($positionOptions as $value => $label) {
    $selected = ($value === $currentPosition) ? ' selected="selected"' : '';
    $positionSelect .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
}
$positionSelect .= '</select>';

$n['label'] = '<label for="toggle_position">' . $package->i18n('info_center_toggle_position') . '</label>';
$n['field'] = $positionSelect;
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Auto-Switch Domain Setting
$formElements = [];
$n = [];
$autoSwitchDomain = $uiSettings['auto_switch_domain'] ?? true; // Default: aktiviert

$n['label'] = '<label for="auto_switch_domain">' . $package->i18n('info_center_auto_switch_domain') . '</label>';
$n['field'] = '<input type="checkbox" id="auto_switch_domain" name="config[auto_switch_domain]"' . 
              ($autoSwitchDomain ? ' checked="checked"' : '') . 
              ' value="1" />';
$n['note'] = '<small class="text-muted">' . $package->i18n('info_center_auto_switch_domain_note') . '</small>';

$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

$content .= '</fieldset>';

// JavaScript for live preview
$content .= '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const fontSizeSelect = document.querySelector("select[name=\'config[font_size]\']");
    const positionSelect = document.querySelector("select[name=\'config[toggle_position]\']");
    
    if (fontSizeSelect) {
        fontSizeSelect.addEventListener("change", function() {
            if (window.InfoCenter) {
                window.InfoCenter.setFontSize(this.value);
            }
        });
    }
    
    if (positionSelect) {
        positionSelect.addEventListener("change", function() {
            if (window.InfoCenter) {
                window.InfoCenter.setPosition(this.value);
            }
        });
    }
});
</script>';

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
