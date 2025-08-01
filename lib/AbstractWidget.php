<?php

namespace KLXM\InfoCenter;

use rex_addon;

abstract class AbstractWidget implements WidgetInterface
{
    protected string $id;
    protected string $title;
    protected int $priority = 999;
    protected bool $enabled = true;
    protected array $config = [];
    protected bool $supportsLazyLoading = false;

    public function __construct()
    {
        // Set default config from addon configuration
        $addon = rex_addon::get('info_center');
        $widgetConfig = $addon->getConfig('widgets', []);
        
        if (isset($widgetConfig[$this->getId()])) {
            $this->config = $widgetConfig[$this->getId()];
            $this->enabled = $widgetConfig[$this->getId()]['enabled'] ?? true;
            $this->priority = $widgetConfig[$this->getId()]['prio'] ?? 999;
        }
    }

    public function getId(): string 
    {
        // Default implementation uses lowercase class name without "Widget" suffix
        if (!isset($this->id)) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->id = strtolower(str_replace('Widget', '', $className));
        }
        return $this->id;
    }

    public function getTitle(): string
    {
        if (!isset($this->title)) {
            // Try to get title from language file using widget ID
            $this->title = rex_i18n::msg('info_center_widget_' . $this->getId() . '_title');
        }
        return $this->title;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
        
        // Update addon config
        $addon = rex_addon::get('info_center');
        $widgetConfig = $addon->getConfig('widgets', []);
        $widgetConfig[$this->getId()]['enabled'] = $enabled;
        $addon->setConfig('widgets', $widgetConfig);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        // Update addon config
        $addon = rex_addon::get('info_center');
        $widgetConfig = $addon->getConfig('widgets', []);
        $widgetConfig[$this->getId()] = array_merge($widgetConfig[$this->getId()] ?? [], $config);
        $addon->setConfig('widgets', $widgetConfig);
    }

    public function supportsLazyLoading(): bool
    {
        return $this->supportsLazyLoading;
    }

    public function getInitialContent(): string
    {
        // Default implementation for non-lazy widgets
        return $this->render();
    }

    /**
     * Helper method to wrap widget content in a web component
     */
    protected function wrapContent(string $content): string
    {
        return sprintf(
            '<div class="info-center-widget" data-id="%s" data-lazy="%s">
                <div class="info-center-widget-header">
                    <h3 class="info-center-widget-title">%s</h3>
                </div>
                <div class="info-center-widget-content">
                    %s
                </div>
            </div>',
            rex_escape($this->getId()),
            $this->supportsLazyLoading() ? 'true' : 'false',
            rex_escape($this->getTitle()),
            $content
        );
    }

    /**
     * Each widget must implement its own render method
     */
    abstract public function render(): string;
}
