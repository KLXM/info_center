<?php

namespace KLXM\InfoCenter;

use rex;
use rex_addon;
use rex_extension;
use rex_extension_point;
use rex_url;
use rex_view;
use rex_logger;
use rex_backend_login;

class InfoCenter
{
    use \rex_singleton_trait;

    private array $widgets = [];
    private bool $isVisible = true;
    private string $position;
    private string $darkMode;

    private function __construct()
    {
        $addon = rex_addon::get('info_center');
        $this->position = $addon->getConfig('position', 'right');
        $this->darkMode = $addon->getConfig('darkmode', 'auto');
        // initializeWidgets() entfernt - Widgets werden in boot.php registriert
    }

    public function get(): string 
    {
        if (!$this->shouldRender()) {
            return '';
        }

        // Benutzereinstellungen laden
        $user = rex::getUser();
        $userId = $user ? $user->getId() : 0;
        $addon = rex_addon::get('info_center');
        $uiSettings = $addon->getConfig('ui_settings_user_' . $userId, []);
        
        // UI-Klassen für Container
        $containerClasses = ['info-center-container'];
        
        // Font Size
        $fontSize = $uiSettings['font_size'] ?? 'medium';
        $containerClasses[] = 'font-' . $fontSize;
        
        // Position
        $position = $uiSettings['toggle_position'] ?? 'center';
        $containerClasses[] = 'position-' . $position;

        // Sortiere die Widgets nach Priorität
        uasort($this->widgets, function ($a, $b) {
            return $a->getPriority() <=> $b->getPriority();
        });

        // Rendere alle Widgets
        $widgetsOutput = '';
        foreach ($this->widgets as $widget) {
            if ($widget->isEnabled()) {
                $widgetsOutput .= $widget->render();
            }
        }

        // Settings und Close Buttons für Backend-User
        $headerButtons = '';
        if (rex::isBackend() && rex::getUser()) {
            $headerButtons = '
                <div class="info-center-header-buttons">
                    <div class="info-center-width-presets">
                        <button class="info-center-width-btn" data-width="compact" title="' . \rex_i18n::msg('info_center_width_compact', 'Kompakt (320px)') . '">S</button>
                        <button class="info-center-width-btn active" data-width="default" title="' . \rex_i18n::msg('info_center_width_default', 'Standard (360px)') . '">M</button>
                        <button class="info-center-width-btn" data-width="comfortable" title="' . \rex_i18n::msg('info_center_width_comfortable', 'Komfortabel (440px)') . '">L</button>
                        <button class="info-center-width-btn" data-width="wide" title="' . \rex_i18n::msg('info_center_width_wide', 'Breit (520px)') . '">XL</button>
                    </div>
                    <a href="' . rex_url::backendPage('info_center/config') . '" class="info-center-settings-btn" title="' . \rex_i18n::msg('info_center_settings') . '">
                        <svg class="info-center-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.579 9.96512 20.2573 9.77251 19.9854C9.5799 19.7135 9.31074 19.5043 9 19.38C8.69838 19.2469 8.36381 19.2072 8.03941 19.266C7.71502 19.3248 7.41568 19.4795 7.18 19.71L7.12 19.77C6.93425 19.956 6.71368 20.1035 6.47088 20.2041C6.22808 20.3048 5.96783 20.3566 5.705 20.3566C5.44217 20.3566 5.18192 20.3048 4.93912 20.2041C4.69632 20.1035 4.47575 19.956 4.29 19.77C4.10405 19.5843 3.95653 19.3637 3.85588 19.1209C3.75523 18.8781 3.70343 18.6178 3.70343 18.355C3.70343 18.0922 3.75523 17.8319 3.85588 17.5891C3.95653 17.3463 4.10405 17.1257 4.29 16.94L4.35 16.88C4.58054 16.6443 4.73519 16.345 4.794 16.0206C4.85282 15.6962 4.81312 15.3616 4.68 15.06C4.55324 14.7642 4.34276 14.512 4.07447 14.3343C3.80618 14.1566 3.49179 14.0613 3.17 14.06H3C2.46957 14.06 1.96086 13.8493 1.58579 13.4742C1.21071 13.0991 1 12.5904 1 12.06C1 11.5296 1.21071 11.0209 1.58579 10.6458C1.96086 10.2707 2.46957 10.06 3 10.06H3.09C3.42099 10.0523 3.742 9.94512 4.01389 9.75251C4.28579 9.5599 4.4957 9.29074 4.62 8.98C4.75312 8.67838 4.79282 8.34381 4.734 8.01941C4.67519 7.69502 4.52054 7.39568 4.29 7.16L4.23 7.1C4.04405 6.91425 3.89653 6.69368 3.79588 6.45088C3.69523 6.20808 3.64343 5.94783 3.64343 5.685C3.64343 5.42217 3.69523 5.16192 3.79588 4.91912C3.89653 4.67632 4.04405 4.45575 4.23 4.27C4.41575 4.08405 4.63632 3.93653 4.87912 3.83588C5.12192 3.73523 5.38217 3.68343 5.645 3.68343C5.90783 3.68343 6.16808 3.73523 6.41088 3.83588C6.65368 3.93653 6.87425 4.08405 7.06 4.27L7.12 4.33C7.35568 4.56054 7.65502 4.71519 7.97941 4.774C8.30381 4.83282 8.63838 4.79312 8.94 4.66H9C9.29577 4.53324 9.54802 4.32276 9.72569 4.05447C9.90337 3.78618 9.99872 3.47179 10 3.15V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <button class="info-center-close-btn" title="' . \rex_i18n::msg('info_center_close', 'Info Center schließen') . '">
                        <svg class="info-center-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>';
        }
        // Frontend-spezifische Buttons für Backend-User mit info_center[] Berechtigung
        elseif (rex::isFrontend() && ($user = rex_backend_login::createUser()) && ($user->isAdmin() || $user->hasPerm('info_center[]'))) {
            $headerButtons = '
                <div class="info-center-header-buttons">
                    <button class="info-center-backend-btn" title="' . \rex_i18n::msg('info_center_goto_backend', 'Zum Backend wechseln') . '" onclick="window.location.href=\'' . rex_url::backendController() . '\'">
                        <svg class="info-center-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3L2 12L5 12L5 20L19 20L19 12L22 12L12 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 17L15 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button class="info-center-close-btn" title="' . \rex_i18n::msg('info_center_close', 'Info Center schließen') . '">
                        <svg class="info-center-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>';
        }

        // Tab Navigation - Struktur-Tab nur im Backend
        $structureTab = '';
        if (rex::isBackend()) {
            $structureTab = '
                <button class="info-center-tab" data-tab="structure">
                    <svg class="info-center-icon" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L18 12v6h-4v-4H10v4H6v-6l6-6.17z" fill="currentColor"/>
                    </svg>
                    <span>' . \rex_i18n::msg('info_center_structure_title') . '</span>
                </button>';
        }
        
        // Tab Navigation nur im Backend anzeigen
        $tabNav = '';
        if (rex::isBackend()) {
            $tabNav = '
                <div class="info-center-tabs">
                    <button class="info-center-tab active" data-tab="widgets">
                        <svg class="info-center-icon" viewBox="0 0 24 24" fill="none">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="currentColor"/>
                        </svg>
                        <span>' . \rex_i18n::msg('info_center_widgets_tab', 'Widgets') . '</span>
                    </button>
                    ' . $structureTab . '
                </div>';
        }

        // Structure Content nur im Backend rendern
        $structureContent = '';
        if (rex::isBackend()) {
            $structureContent = '<div class="info-center-tab-content" data-content="structure">
                        <div id="info-center-structure-container"></div>
                    </div>';
        }

        // Das komplette Info Center HTML mit Tab-System
        return sprintf(
            '<div class="%s">
                <button class="info-center-toggle" type="button" title="' . \rex_i18n::msg('info_center_toggle', 'Info Center öffnen/schließen') . '">
                    <span class="toggle-icon"></span>
                </button>
                <div class="info-center-sidebar">
                    %s
                    %s
                    <div class="info-center-tab-content active" data-content="widgets">
                        %s
                    </div>
                    %s
                </div>
            </div>',
            implode(' ', $containerClasses),
            $headerButtons,
            $tabNav,
            $widgetsOutput,
            $structureContent
        );
    }

    private function shouldRender(): bool
    {
       return true;
    }

    public function registerWidget(WidgetInterface $widget): void
    {
        $this->widgets[$widget->getId()] = $widget;
    }

    public function registerCustomWidgets(): void
    {
        $addon = rex_addon::get('info_center');
        $customWidgets = $addon->getConfig('custom_widgets', []);
        
        foreach ($customWidgets as $widgetId => $config) {
            if ($config['enabled'] ?? false) {
                // Prüfe Sichtbarkeits-Einstellungen
                $visibility = $config['visibility'] ?? 'both';
                $shouldShow = false;
                
                switch ($visibility) {
                    case 'both':
                        $shouldShow = true;
                        break;
                    case 'backend':
                        $shouldShow = rex::isBackend();
                        break;
                    case 'frontend':
                        $shouldShow = rex::isFrontend();
                        break;
                }
                
                if ($shouldShow) {
                    try {
                        $widget = new \KLXM\InfoCenter\Widgets\CustomListWidget($widgetId, $config);
                        $this->registerWidget($widget);
                    } catch (\Exception $e) {
                        // Log error, aber nicht die ganze App zum Absturz bringen
                        rex_logger::logException($e);
                    }
                }
            }
        }
    }
}
