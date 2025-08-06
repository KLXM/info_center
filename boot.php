<?php

namespace KLXM\InfoCenter;

use rex;
use rex_extension;
use rex_extension_point;
use rex_view;
use rex_url;
use rex_addon;
use rex_backend_login;
use rex_be_controller;


// Frontend-Session starten, damit rex::getUser() funktioniert
if (rex::isFrontend()) {
    rex_backend_login::startSession();
}

// Get addon instance
$addon = rex_addon::get('info_center');

// Initialisiere das Info Center
$infoCenter = InfoCenter::getInstance();

// Benutzer-ID für widget-spezifische Einstellungen
$userId = rex::getUser() ? rex::getUser()->getId() : 0;
$userWidgetConfig = $addon->getConfig('widgets_user_' . $userId, []);

// Funktion zur Prüfung ob Widget für den Benutzer aktiviert ist
$isWidgetEnabled = function($widgetId) use ($addon, $userWidgetConfig) {
    // Prüfe zuerst globale Einstellungen
    $globalConfig = $addon->getConfig('widgets', []);
    $globalEnabled = $globalConfig[$widgetId]['enabled'] ?? true;
    
    // Wenn global deaktiviert, ist Widget immer deaktiviert
    if (!$globalEnabled) {
        return false;
    }
    
    // Wenn global aktiviert, prüfe User-spezifische Einstellungen
    if (isset($userWidgetConfig[$widgetId]['enabled'])) {
        return (bool) $userWidgetConfig[$widgetId]['enabled'];
    }
    
    // Fallback: global aktiviert, keine User-Einstellung -> aktiviert
    return true;
};

// Registriere Widgets mit korrekten Prioritäten (niedrigste zuerst)
// URL Widget (prio: -1)
if ($isWidgetEnabled('url')) {
    $widget = new Widgets\UrlWidget();
    $widget->setPriority(-1);
    $infoCenter->registerWidget($widget);
}

// TimeTracker Widget (prio: 0)
if ($isWidgetEnabled('timetracker')) {
    $widget = new Widgets\TimeTrackerWidget();
    $widget->setPriority(0);
    $infoCenter->registerWidget($widget);
}

// Article Widget (prio: 1)
if ($isWidgetEnabled('article')) {
    $widget = new Widgets\ArticleWidget();
    $widget->setPriority(1);
    $infoCenter->registerWidget($widget);
}

// Upkeep Widget (prio: 2) - Nur für Admins (nur Backend)
if ($isWidgetEnabled('upkeep') && rex::getUser() && rex::getUser()->isAdmin()) {
    $widget = new Widgets\UpkeepWidget();
    $widget->setPriority(2);
    $infoCenter->registerWidget($widget);
}

// Stats Widget (prio: 5) - Nur für Admins (Backend und Frontend)
$user = rex_backend_login::createUser();
if ($isWidgetEnabled('stats') && $user && $user->isAdmin()) {
    $widget = new Widgets\StatsWidget();
    $widget->setPriority(5);
    $infoCenter->registerWidget($widget);
}

// System Widget (prio: 10) - Nur für Admins (nur Backend)
if ($isWidgetEnabled('system') && rex::getUser() && rex::getUser()->isAdmin()) {
    $widget = new Widgets\SystemWidget();
    $widget->setPriority(10);
    $infoCenter->registerWidget($widget);
}

// Registriere Custom Widgets (prio: 20+)
$infoCenter->registerCustomWidgets();

// Assets einbinden - Backend und Frontend
if (rex::isBackend() && rex::getUser()) {
    // Backend: Normale Asset-Einbindung
    rex_view::addCssFile($addon->getAssetsUrl('css/info-center.css'));
    rex_view::addCssFile($addon->getAssetsUrl('css/timetracker.css'));
    rex_view::addJsFile($addon->getAssetsUrl('js/info-center.js'));
    rex_view::addJsFile($addon->getAssetsUrl('js/timetracker.js'));
}



// Ausgabe für Backend und Frontend
if (rex::isBackend() && rex::getUser()) {
    // Backend: Normale Ausgabe - aber nicht in Popups
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
        // Prüfe ob wir uns in einem Popup befinden (z.B. Medienpool)
        $currentPage = rex_be_controller::getCurrentPageObject();
        if ($currentPage && $currentPage->isPopup()) {
            return;
        }
        
        $content = $ep->getSubject();
        $infoCenterOutput = $infoCenter->get();
        
        // Füge das Info Center vor dem schließenden Body-Tag ein
        $content = str_replace('</body>', $infoCenterOutput . '</body>', $content);
        
        $ep->setSubject($content);
    });
}

// Frontend Integration - für eingeloggte Backend-Benutzer
if (rex::isFrontend() && rex_backend_login::createUser()) {
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter, $addon) {
        $content = $ep->getSubject();
        $infoCenterOutput = $infoCenter->get();
        
        if ($infoCenterOutput) {
            // Assets und Info Center vor den schließenden Tags einfügen
            $content = str_ireplace(
                ['</head>', '</body>'],
                [
                    '<link rel="stylesheet" type="text/css" href="' . $addon->getAssetsUrl('css/info-center.css') . '" />
                    <link rel="stylesheet" type="text/css" href="' . $addon->getAssetsUrl('css/timetracker.css') . '" /></head>',
                    $infoCenterOutput . '
                    <script src="' . $addon->getAssetsUrl('js/info-center.js') . '"></script>
                    <script src="' . $addon->getAssetsUrl('js/timetracker.js') . '"></script>
                    </body>'
                ],
                $content
            );
        }
        
        $ep->setSubject($content);
    });
}
