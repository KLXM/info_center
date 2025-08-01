<?php

namespace KLXM\InfoCenter;

use rex;
use rex_extension;
use rex_extension_point;
use rex_view;
use rex_url;
use rex_addon;
use rex_backend_login;

// Frontend-Session starten, damit rex::getUser() funktioniert
if (rex::isFrontend()) {
    rex_backend_login::startSession();
}

// Get addon instance
$addon = rex_addon::get('info_center');

// Initialisiere das Info Center
$infoCenter = InfoCenter::getInstance();

// Registriere Widgets in der gewünschten Reihenfolge
// TimeTracker Widget zuerst
if ($addon->getConfig('widgets')['timetracker']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\TimeTrackerWidget());
}

// Article Widget
if ($addon->getConfig('widgets')['article']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\ArticleWidget());
}

// Dann die anderen Widgets
if ($addon->getConfig('widgets')['system']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\SystemWidget());
}

if ($addon->getConfig('widgets')['stats']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\StatsWidget());
}

if ($addon->getConfig('widgets')['upkeep']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\UpkeepWidget());
}





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
                    <link rel="stylesheet" type="text/css" href="' . $addon->getAssetsUrl('css/timetracker.css') . '" /></head>',
                    $infoCenterOutput . '
                    <script src="' . $addon->getAssetsUrl('js/info-center.js') . '"></script>
                    <script src="' . $addon->getAssetsUrl('js/timetracker.js') . '"></script></body>'
                ],
                $content
            );
        }
        
        $ep->setSubject($content);
    });
}
