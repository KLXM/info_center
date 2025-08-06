<?php

namespace KLXM\InfoCenter;

interface WidgetInterface
{
    /**
     * Get the unique widget ID
     */
    public function getId(): string;

    /**
     * Get the widget title
     */
    public function getTitle(): string;

    /**
     * Get the widget priority (lower = higher up)
     */
    public function getPriority(): float;

    /**
     * Set the widget priority
     */
    public function setPriority(float $priority): void;

    /**
     * Check if the widget is enabled
     */
    public function isEnabled(): bool;

    /**
     * Enable/disable the widget
     */
    public function setEnabled(bool $enabled): void;

    /**
     * Render the widget content
     * 
     * @return string The widget's HTML content wrapped in a web component
     */
    public function render(): string;

    /**
     * Get the widget's configuration
     */
    public function getConfig(): array;

    /**
     * Set the widget's configuration
     */
    public function setConfig(array $config): void;

    /**
     * Check if the widget supports lazy loading
     */
    public function supportsLazyLoading(): bool;

    /**
     * Get the initial lightweight content for lazy loading
     * Only called if supportsLazyLoading() returns true
     */
    public function getInitialContent(): string;
}
