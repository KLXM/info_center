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
        // Search Widget only works in backend (requires rex API)
        if (!rex::isBackend()) {
            return '';
        }
        
        $user = rex::getUser();
        if (!$user) {
            return '';
        }
        
        $isAdmin = $user->isAdmin() ? 'true' : 'false';

        // Add i18n translations to JavaScript
        $translations = [
            'help_title' => rex_i18n::msg('info_center_help_title'),
            'help_quick_actions' => rex_i18n::msg('info_center_help_quick_actions'),
            'help_calculator' => rex_i18n::msg('info_center_help_calculator'),
            'help_wiki' => rex_i18n::msg('info_center_help_wiki'),
            'help_navigation' => rex_i18n::msg('info_center_help_navigation'),
            'help_tools' => rex_i18n::msg('info_center_help_tools'),
            'help_blindtext' => rex_i18n::msg('info_center_help_blindtext'),
            'help_doku' => rex_i18n::msg('info_center_help_doku'),
            'help_other' => rex_i18n::msg('info_center_help_other'),
            'help_filters' => rex_i18n::msg('info_center_help_filters'),
            'help_modified' => rex_i18n::msg('info_center_help_modified'),
            'help_created' => rex_i18n::msg('info_center_help_created'),
            'help_user' => rex_i18n::msg('info_center_help_user'),
            'help_combinations' => rex_i18n::msg('info_center_help_combinations'),
            'help_shortcuts' => rex_i18n::msg('info_center_help_shortcuts'),
            'help_shortcuts_focus' => rex_i18n::msg('info_center_help_shortcuts_focus'),
            'help_shortcuts_nav' => rex_i18n::msg('info_center_help_shortcuts_nav'),
            'help_shortcuts_open' => rex_i18n::msg('info_center_help_shortcuts_open'),
            'help_shortcuts_close' => rex_i18n::msg('info_center_help_shortcuts_close'),
            'help_search' => rex_i18n::msg('info_center_help_search'),
            'help_search_desc' => rex_i18n::msg('info_center_help_search_desc'),
            'help_close' => rex_i18n::msg('info_center_help_close'),
            'help_wiki_desc' => rex_i18n::msg('info_center_help_wiki_desc'),
            'help_nav_desc' => rex_i18n::msg('info_center_help_nav_desc'),
            'help_tools_desc' => rex_i18n::msg('info_center_help_tools_desc'),
            'help_doku_desc' => rex_i18n::msg('info_center_help_doku_desc'),
            'help_other_desc' => rex_i18n::msg('info_center_help_other_desc'),
            'help_search_articles' => rex_i18n::msg('info_center_help_search_articles'),
            'help_search_categories' => rex_i18n::msg('info_center_help_search_categories'),
            'help_search_modules' => rex_i18n::msg('info_center_help_search_modules'),
            'help_search_templates' => rex_i18n::msg('info_center_help_search_templates'),
            'help_search_media' => rex_i18n::msg('info_center_help_search_media'),
        ];

        rex_view::setJsProperty('info_center_translations', $translations);

        $content = '
        <div class="info-center-widget info-center-search-widget" id="info-center-search-widget" data-id="search" data-is-admin="' . $isAdmin . '">
            <div class="info-center-widget-header">
                <h3 class="info-center-widget-title">' . rex_escape($this->getTitle()) . '</h3>
            </div>
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
                    <button type="button" class="info-center-search-clear" id="info-center-search-clear" style="display:none;" title="' . rex_i18n::msg('info_center_search_clear') . '">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <button type="button" class="info-center-search-help" id="info-center-search-help" title="' . rex_i18n::msg('info_center_search_help') . '">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
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
