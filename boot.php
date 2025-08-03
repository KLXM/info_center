<?php

namespace KLXM\InfoCenter;

use rex;
use rex_extension;
use rex_extension_point;
use rex_view;
use rex_url;
use rex_addon;
use rex_backend_login;
use rex_api_function;

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
    // Prüfe User-spezifische Einstellungen
    if (isset($userWidgetConfig[$widgetId]['enabled'])) {
        return (bool) $userWidgetConfig[$widgetId]['enabled'];
    }
    
    // Fallback auf globale Einstellungen
    $globalConfig = $addon->getConfig('widgets', []);
    return $globalConfig[$widgetId]['enabled'] ?? true;
};

// Registriere Widgets in der Reihenfolge ihrer Prioritäten (niedrigste zuerst)
// URL Widget (prio: -1)
if ($isWidgetEnabled('url')) {
    $infoCenter->registerWidget(new Widgets\UrlWidget());
}

// TimeTracker Widget (prio: 0)
if ($isWidgetEnabled('timetracker')) {
    $infoCenter->registerWidget(new Widgets\TimeTrackerWidget());
}

// Article Widget (prio: 1)
if ($isWidgetEnabled('article')) {
    $infoCenter->registerWidget(new Widgets\ArticleWidget());
}

// Upkeep Widget (prio: 2)
if ($isWidgetEnabled('upkeep')) {
    $infoCenter->registerWidget(new Widgets\UpkeepWidget());
}

// Messaging Widget (prio: 3) - Neue Widget-Registrierung
if ($isWidgetEnabled('messaging')) {
    $infoCenter->registerWidget(new Widgets\MessagingWidget());
}

// Stats Widget (prio: 5)
if ($isWidgetEnabled('stats')) {
    $infoCenter->registerWidget(new Widgets\StatsWidget());
}

// System Widget (prio: 10)
if ($isWidgetEnabled('system')) {
    $infoCenter->registerWidget(new Widgets\SystemWidget());
}

// REX API für Messaging registrieren
rex_api_function::register('info_center_messaging', \KLXM\InfoCenter\Api\MessagingApi::class);





// Assets einbinden - Backend und Frontend
if (rex::isBackend() && rex::getUser()) {
    // Backend: Normale Asset-Einbindung
    rex_view::addCssFile($addon->getAssetsUrl('css/info-center.css'));
    rex_view::addCssFile($addon->getAssetsUrl('css/timetracker.css'));
    rex_view::addCssFile($addon->getAssetsUrl('css/messaging.css'));
    rex_view::addJsFile($addon->getAssetsUrl('js/info-center.js'));
    rex_view::addJsFile($addon->getAssetsUrl('js/timetracker.js'));
    rex_view::addJsFile($addon->getAssetsUrl('js/messaging.js'));
}

// Ausgabe für Backend und Frontend
if (rex::isBackend() && rex::getUser()) {
    // Backend: Normale Ausgabe
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
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
                    <link rel="stylesheet" type="text/css" href="' . $addon->getAssetsUrl('css/timetracker.css') . '" />
                    <link rel="stylesheet" type="text/css" href="' . $addon->getAssetsUrl('css/messaging.css') . '" /></head>',
                    $infoCenterOutput . '
                    <script src="' . $addon->getAssetsUrl('js/info-center.js') . '"></script>
                    <script src="' . $addon->getAssetsUrl('js/timetracker.js') . '"></script>
                    <script src="' . $addon->getAssetsUrl('js/messaging.js') . '"></script>
                    </body>'
                ],
                $content
            );
        }
        
        $ep->setSubject($content);
    });
}
