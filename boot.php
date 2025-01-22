<?php

namespace KLXM\InfoCenter;

use rex;
use rex_extension;
use rex_extension_point;
use rex_view;
use rex_url;

// Initialisiere das Info Center
$infoCenter = InfoCenter::getInstance();

// Registriere die Standard-Widgets
if ($this->getConfig('widgets')['system']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\SystemWidget());
}

if ($this->getConfig('widgets')['article']['enabled'] ?? true) {
    $infoCenter->registerWidget(new Widgets\ArticleWidget());
}

// Assets einbinden
if (rex::isBackend()) {
    rex_view::addCssFile($this->getAssetsUrl('css/info-center.css'));
    rex_view::addJsFile($this->getAssetsUrl('js/info-center.js'));
}

// Ausgabe im Backend
if (rex::isBackend()) {
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
        $content = $ep->getSubject();
        $infoCenterOutput = $infoCenter->get();
        
        // Füge das Info Center vor dem schließenden Body-Tag ein
        $content = str_replace('</body>', $infoCenterOutput . '</body>', $content);
        
        $ep->setSubject($content);
    });
}

// Frontend Integration
if (rex::isFrontend() && rex::getUser()) {
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter, $this) {
        $content = $ep->getSubject();
        
        // Assets und Info Center vor den schließenden Tags einfügen
        $content = str_ireplace(
            ['</head>', '</body>'],
            [
                '<link rel="stylesheet" type="text/css" href="' . $this->getAssetsUrl('css/info-center.css') . '" /></head>',
                $infoCenter->get() . '
                <script src="' . $this->getAssetsUrl('js/info-center.js') . '"></script></body>'
            ],
            $content
        );
        
        $ep->setSubject($content);
    });
}
