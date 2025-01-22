<?php

namespace KLXM\InfoCenter;

use rex_addon;
use rex_extension;
use rex_extension_point;
use rex_path;
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
        
        // Initialize default widgets from config
        $this->initializeWidgets();
    }

    /**
     * Initialize the InfoCenter assets and render it if needed
     */
    public function init(): void 
    {
        if (!$this->shouldRender()) {
            return;
        }

        // Add CSS and JS in backend
        if (rex::isBackend()) {
            rex_view::addCssFile($this->getAssetsUrl('info-center.css'));
            rex_view::addJsFile($this->getAssetsUrl('info-center.js'));
        }

        // Register extension points
        $this->registerExtensionPoints();
    }

    /**
     * Register a new widget
     */
    public function registerWidget(WidgetInterface $widget): void
    {
        $this->widgets[$widget->getId()] = $widget;
    }

    /**
     * Get all registered widgets
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * Get the HTML output of the InfoCenter
     */
    public function get(): string 
    {
        if (!$this->shouldRender()) {
            return '';
        }

        // Sort widgets by priority
        uasort($this->widgets, function ($a, $b) {
            return $a->getPriority() <=> $b->getPriority();
        });

        $html = '<info-center class="info-center" data-position="' . rex_escape($this->position) . '" data-theme="' . rex_escape($this->darkMode) . '">';
        
        // Render widgets
        foreach ($this->widgets as $widget) {
            if ($widget->isEnabled()) {
                $html .= $widget->render();
            }
        }

        $html .= '</info-center>';

        return $html;
    }

    /**
     * Check if the InfoCenter should be rendered
     */
    private function shouldRender(): bool
    {
      

        return $this->isVisible;
    }

    /**
     * Initialize default widgets from config
     */
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

    /**
     * Register extension points
     */
    private function registerExtensionPoints(): void
    {
        // Add InfoCenter to frontend
        rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep) {
            $content = $ep->getSubject();
            $infoCenterHtml = $this->get();

            if (rex::isFrontend()) {
                $content = str_ireplace(
                    ['</head>', '</body>'],
                    [
                        '<link rel="stylesheet" type="text/css" href="' . $this->getAssetsUrl('info-center.css') . '" /></head>',
                        $infoCenterHtml . '<script src="' . $this->getAssetsUrl('info-center.js') . '"></script></body>'
                    ],
                    $content
                );
                $ep->setSubject($content);
            }
        });
    }

    /**
     * Get assets URL
     */
    private function getAssetsUrl(string $file): string
    {
        return rex_url::addonAssets('info_center', $file);
    }

    /**
     * Toggle visibility
     */
    public function toggleVisibility(): void
    {
        $this->isVisible = !$this->isVisible;
    }

    /**
     * Set dark mode
     */
    public function setDarkMode(string $mode): void
    {
        if (in_array($mode, ['auto', 'light', 'dark'])) {
            $this->darkMode = $mode;
            rex_addon::get('info_center')->setConfig('darkmode', $mode);
        }
    }
}
