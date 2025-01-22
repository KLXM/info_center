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

// Backend Integration
if (rex::isBackend()) {
    rex_view::addCssFile($this->getAssetsUrl('css/info-center.css'));
    rex_view::addJsFile($this->getAssetsUrl('js/info-center.js'));

    // Füge Info Center zum Backend Layout hinzu
    rex_extension::register('PAGE_HEADER', function(rex_extension_point $ep) use ($infoCenter) {
        $content = $ep->getSubject();
        
        // Debug-Ausgabe
        $content .= "<!-- Info Center Debug: Assets loaded -->\n";
        
        return $content;
    });

    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
        $content = $ep->getSubject();
        
        // Debug-Ausgabe
        $infoCenterHtml = "<!-- Info Center Debug: Starting -->\n";
        $infoCenterHtml .= $infoCenter->get();
        $infoCenterHtml .= "\n<!-- Info Center Debug: Ending -->\n";
        
        // Füge das Info Center vor dem schließenden Body-Tag ein
        $content = str_replace('</body>', $infoCenterHtml . '</body>', $content);
        
        $ep->setSubject($content);
    });
}

// Frontend Integration
if (rex::isFrontend() && rex::getUser()) {
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) use ($infoCenter) {
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
