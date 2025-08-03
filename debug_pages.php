<?php
// Debug Script für Page-Testing

echo '<h2>Info Center Page Debug</h2>';

// Addon-Status prüfen
$addon = rex_addon::get('info_center');
echo '<p><strong>Addon Status:</strong> ' . ($addon->isAvailable() ? 'Available' : 'Not Available') . '</p>';
echo '<p><strong>Addon Installed:</strong> ' . ($addon->isInstalled() ? 'Yes' : 'No') . '</p>';

// Pages prüfen
$pages = $addon->getProperty('pages', []);
echo '<h3>Registered Pages:</h3>';
echo '<pre>' . print_r($pages, true) . '</pre>';

// URLs testen
echo '<h3>Test URLs:</h3>';
echo '<ul>';
echo '<li><a href="' . rex_url::backendPage('info_center') . '">info_center</a></li>';
echo '<li><a href="' . rex_url::backendPage('info_center/config') . '">info_center/config</a></li>';
echo '<li><a href="' . rex_url::backendPage('info_center/messaging') . '">info_center/messaging</a></li>';
echo '</ul>';

// Current Page Debug
echo '<h3>Current Page Info:</h3>';
echo '<p><strong>Current Page:</strong> ' . rex_be_controller::getCurrentPage() . '</p>';
echo '<p><strong>Request URI:</strong> ' . $_SERVER['REQUEST_URI'] . '</p>';
echo '<p><strong>GET page:</strong> ' . ($_GET['page'] ?? 'not set') . '</p>';
