<?php

namespace KLXM\InfoCenter;

use rex;
use rex_extension;
use rex_extension_point;
use rex_be_controller;
use rex_view;
use rex_url;

// Autoloading für das Addon
if (rex::isBackend() && is_readable($this->getPath('vendor/autoload.php'))) {
    require_once $this->getPath('vendor/autoload.php');
}

// Initialisiere das Info Center
$infoCenter = InfoCenter::getInstance();

// Registriere die Standard-Widgets
if ($this->getConfig('widgets')['system']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\SystemWidget());
}

if ($this->getConfig('widgets')['article']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\ArticleWidget());
}

// Erlaube anderen Addons Widgets zu registrieren
rex_extension::register('INFO_CENTER_INIT', function(rex_extension_point $ep) use ($infoCenter) {
    // Andere Addons können hier ihre Widgets registrieren
    return $ep->getSubject();
});

// Assets im Backend einbinden
if (rex::isBackend() && !rex_be_controller::getCurrentPage('login')) {
    rex_view::addJsFile($this->getAssetsUrl('js/info-center.js'));
    rex_view::addCssFile($this->getAssetsUrl('css/info-center.css'));

    // Füge das Info Center zum Layout hinzu
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
        $content = $ep->getSubject();
        
        // Füge das Info Center vor dem schließenden Body-Tag ein
        $content = str_replace(
            '</body>',
            $infoCenter->get() . '</body>',
            $content
        );

        $ep->setSubject($content);
    });
}

// Frontend Integration
if (rex::isFrontend()) {
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
        // Nur für eingeloggte Backend-Benutzer anzeigen
        if (!rex::getUser()) {
            return;
        }

        $content = $ep->getSubject();

        // Füge Assets und Info Center HTML ein
        $content = str_ireplace(
            ['</head>', '</body>'],
            [
                '<link rel="stylesheet" type="text/css" href="' . rex_url::addonAssets('info_center', 'css/info-center.css') . '" /></head>',
                $infoCenter->get() . '
                <script src="' . rex_url::addonAssets('info_center', 'js/info-center.js') . '"></script>
                </body>'
            ],
            $content
        );

        $ep->setSubject($content);
    });
}

// AJAX Handler für Lazy Loading der Widgets
rex_extension::register('PAGES_PREPARED', function() {
    if (rex::isBackend() && rex_get('widget', 'string')) {
        $widgetId = rex_get('widget', 'string');
        $infoCenter = InfoCenter::getInstance();
        
        foreach ($infoCenter->getWidgets() as $widget) {
            if ($widget->getId() === $widgetId) {
                rex_response::sendContent($widget->render());
                exit();
            }
        }
    }
});

// API Endpoint für Widget Aktionen
rex_extension::register('PACKAGES_INCLUDED', function() {
    if (rex_get('info_center_action', 'string')) {
        $action = rex_get('info_center_action', 'string');
        $widgetId = rex_get('widget_id', 'string');
        
        try {
            $infoCenter = InfoCenter::getInstance();
            $widget = $infoCenter->getWidgets()[$widgetId] ?? null;
            
            if ($widget && method_exists($widget, 'handleAction')) {
                $response = $widget->handleAction($action, rex_request('data', 'array', []));
                rex_response::sendJson(['success' => true, 'data' => $response]);
            } else {
                throw new \Exception('Widget or action not found');
            }
        } catch (\Exception $e) {
            rex_response::sendJson(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
});

// Dark Mode Extension Point
rex_extension::register('INFO_CENTER_THEME', function(rex_extension_point $ep) {
    $theme = $ep->getParams()['theme'] ?? 'auto';
    InfoCenter::getInstance()->setDarkMode($theme);
    return $theme;
});
