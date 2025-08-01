<?php

namespace KLXM\InfoCenter;

use rex;
use rex_addon;
use rex_extension;
use rex_extension_point;
use rex_url;
use rex_view;

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
        $this->initializeWidgets();
    }

    public function get(): string 
    {
        if (!$this->shouldRender()) {
            return '';
        }

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

        // Das komplette Info Center HTML mit neuer Sidebar-Struktur
        return sprintf(
            '<div class="info-center-container">
                <button class="info-center-toggle" type="button" title="Info Center öffnen/schließen">
                    <span>☰</span>
                </button>
                <div class="info-center-sidebar">
                    <div class="info-center-content">
                        %s
                    </div>
                </div>
            </div>',
            $widgetsOutput
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

    private function initializeWidgets(): void
    {
        $addon = rex_addon::get('info_center');
        $widgets = $addon->getConfig('widgets', []);

        foreach ($widgets as $id => $config) {
            if (!isset($config['enabled']) || !$config['enabled']) {
                continue;
            }

            $className = 'KLXM\\InfoCenter\\Widgets\\' . ucfirst($id) . 'Widget';
            if (class_exists($className)) {
                $widget = new $className();
                $widget->setPriority($config['prio'] ?? 999);
                $this->registerWidget($widget);
            }
        }
    }
}
