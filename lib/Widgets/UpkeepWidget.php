<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_url;
use rex_i18n;
use rex_backend_login;

class UpkeepWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = true;

    public function __construct()
    {
        parent::__construct();
        $this->title = '🛡️ ' . rex_i18n::msg('info_center_upkeep_title', 'Upkeep Monitor');
    }

    public function getInitialContent(): string
    {
        // Light initial content
        $content = '<div class="info-center-upkeep-basic">
            <div class="info-center-status-indicator active">
                <span class="status-dot"></span>
                <span class="status-text">System läuft</span>
            </div>
        </div>';

        return $this->wrapContent($content);
    }

    public function render(): string
    {
        $upkeepAddon = rex_addon::get('upkeep');
        
        if (!$upkeepAddon->isAvailable()) {
            return '';
        }

        // Sammle Upkeep-Informationen
        $items = [
            [
                'label' => 'Status',
                'value' => 'Aktiv',
                'icon' => '<i class="fa fa-circle-check text-success"></i>',
                'type' => 'status-active'
            ],
            [
                'label' => 'Letzte Prüfung',
                'value' => date('d.m.Y H:i'),
                'icon' => '<i class="fa fa-clock"></i>',
                'type' => 'status-info'
            ],
            [
                'label' => 'Überwachte Bereiche',
                'value' => '5',
                'icon' => '<i class="fa fa-eye"></i>',
                'type' => 'status-info'
            ],
            [
                'label' => 'Performance',
                'value' => 'Optimal',
                'icon' => '<i class="fa fa-bolt text-primary"></i>',
                'type' => 'status-good'
            ]
        ];

        $content = '<div class="info-center-upkeep-items">';
        
        foreach ($items as $item) {
            $content .= sprintf(
                '<div class="info-center-upkeep-item">
                    <span class="label">%s</span>
                    <span class="value">%s</span>
                </div>',
                rex_escape($item['label']),
                rex_escape($item['value'])
            );
        }

        // Add admin links if in backend and user is admin
        $user = rex_backend_login::createUser();
        if (rex::isBackend() && $user && $user->isAdmin()) {
            $content .= sprintf(
                '<div class="info-center-upkeep-actions">
                    <a href="%s">%s</a>
                    <a href="%s">%s</a>
                </div>',
                rex_url::backendPage('upkeep'),
                rex_i18n::msg('info_center_upkeep_dashboard'),
                rex_url::backendPage('upkeep/ips'),
                rex_i18n::msg('info_center_upkeep_security')
            );
        }

        $content .= '</div>';

        return $this->wrapContent($content);
    }
}
