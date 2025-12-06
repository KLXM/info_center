<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_i18n;
use rex_view;

class SearchWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = false;
    protected bool $isConfigurable = true;

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_search_title');
        $this->priority = -10; // Show at top
    }

    public function render(): string
    {
        $user = rex::getUser();
        if (!$user) {
            return '';
        }

        // Add i18n translations to JavaScript

        $content = '
        <div class="info-center-widget info-center-search-widget" id="info-center-search-widget">
            <div class="info-center-widget-content">
                <div class="info-center-search-input-wrapper" title="↑↓ ' . rex_i18n::msg('info_center_search_navigate') . ' • Enter ' . rex_i18n::msg('info_center_search_open') . ' • ⌘K ' . rex_i18n::msg('info_center_search_focus') . '">
                    <svg class="info-center-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input 
                        type="text" 
                        id="info-center-search-input" 
                        class="info-center-search-input" 
                        placeholder="' . rex_i18n::msg('info_center_search_placeholder') . '"
                        autocomplete="off"
                    />
                    <button type="button" class="info-center-search-clear" id="info-center-search-clear" style="display:none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="info-center-search-results" id="info-center-search-results"></div>
            </div>
        </div>';

        return $content;
    }

    public function getId(): string
    {
        return 'search';
    }
}
