<?php

// Install-Script für Info Center
// Stellt sicher, dass die Pages korrekt registriert werden

$addon = rex_addon::get('info_center');

// Addon deinstallieren und neu installieren, um Pages zu registrieren
try {
    if ($addon->isInstalled()) {
        echo 'Uninstalling Info Center...<br>';
        $addon->uninstall();
    }
    
    echo 'Installing Info Center...<br>';
    $addon->install();
    
    if ($addon->isAvailable()) {
        echo 'Info Center successfully installed and pages registered!<br>';
        
        // Debug: Zeige registrierte Pages
        $pages = $addon->getProperty('pages', []);
        echo '<h3>Registered Pages:</h3>';
        echo '<pre>' . print_r($pages, true) . '</pre>';
        
    } else {
        echo 'Error: Info Center installation failed.<br>';
    }
    
} catch (Exception $e) {
    echo 'Error during installation: ' . $e->getMessage() . '<br>';
}
