<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_sql;
use rex_addon;
use rex_url;
use rex_i18n;

class SystemWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = true;

    public function __construct()
    {
        parent::__construct();
        $this->title = '⚙️ ' . rex_i18n::msg('info_center_system_title');
    }

    public function getInitialContent(): string
    {
        // Light initial content showing only REDAXO version
        $content = sprintf(
            '<div class="info-center-system-basic">
                <div class="info-center-system-item">
                    <span class="label">REDAXO</span>
                    <span class="value">%s</span>
                </div>
            </div>',
            rex::getVersion()
        );

        return $this->wrapContent($content);
    }

    public function render(): string
    {
        $database = rex::getProperty('db')[1];

        $items = [
            [
                'label' => 'REDAXO',
                'value' => rex::getVersion(),
                'link' => rex::isBackend() ? rex_url::backendPage('system/log') : null
            ],
            [
                'label' => 'PHP',
                'value' => PHP_VERSION,
                'link' => rex::isBackend() && rex::getUser()?->isAdmin() ? rex_url::backendPage('system/phpinfo') : null
            ],
            [
                'label' => 'MySQL',
                'value' => rex_sql::getServerVersion(),
            ],
            [
                'label' => rex_i18n::msg('info_center_database'),
                'value' => $database['name']
            ],
            [
                'label' => 'Host',
                'value' => $database['host']
            ],
        ];

        $content = '<div class="info-center-system-items">';
        
        foreach ($items as $item) {
            $value = $item['value'];
            if (isset($item['link'])) {
                $value = sprintf('<a href="%s">%s</a>', $item['link'], $value);
            }
            
            $content .= sprintf(
                '<div class="info-center-system-item">
                    <span class="label">%s</span>
                    <span class="value">%s</span>
                </div>',
                rex_escape($item['label']),
                $value
            );
        }

        // Add admin links if in backend and user is admin
        if (rex::isBackend() && rex::getUser()?->isAdmin()) {
            $content .= sprintf(
                '<div class="info-center-system-admin-links">
                    <a href="%s">%s</a>
                    <a href="%s">%s</a>
                    <a href="%s">%s</a>
                </div>',
                rex_url::backendPage('system'),
                rex_i18n::msg('info_center_system_settings'),
                rex_url::backendPage('system/report'),
                rex_i18n::msg('info_center_system_report'),
                rex_url::backendPage('system/phpinfo'),
                rex_i18n::msg('info_center_system_phpinfo')
            );
        }

        $content .= '</div>';

        return $this->wrapContent($content);
    }
}
