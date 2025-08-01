<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_i18n;
use rex_backend_login;

class TimeTrackerWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = false;

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_timetracker_title');
    }

    public function render(): string
    {
        $user = rex_backend_login::createUser();
        $greeting = $this->getTimeBasedGreeting($user);
        
        $content = sprintf(
            '<div class="time-tracker-container">
                <div class="time-tracker-greeting">%s</div>
                <div class="time-tracker-display">
                    <div class="time-display" id="timeDisplay">00:00:00</div>
                    <div class="session-info" id="sessionInfo">Bereit zum Starten</div>
                </div>
                <div class="time-tracker-controls">
                    <button class="time-btn time-btn-start" id="startBtn" title="%s">
                        <span class="btn-icon">▶</span>
                        <span class="btn-text">Start</span>
                    </button>
                    <button class="time-btn time-btn-pause" id="pauseBtn" title="%s" disabled>
                        <span class="btn-icon">⏸</span>
                        <span class="btn-text">Pause</span>
                    </button>
                    <button class="time-btn time-btn-stop" id="stopBtn" title="%s" disabled>
                        <span class="btn-icon">⏹</span>
                        <span class="btn-text">Stop</span>
                    </button>
                </div>
                <div class="time-tracker-stats" id="trackerStats">
                    <div class="stat-item">
                        <span class="stat-label">Heute:</span>
                        <span class="stat-value" id="todayTime">00:00</span>
                    </div>
                </div>
            </div>',
            $greeting,
            rex_i18n::msg('info_center_timetracker_start'),
            rex_i18n::msg('info_center_timetracker_pause'),
            rex_i18n::msg('info_center_timetracker_stop')
        );

        return $this->wrapContent($content);
    }

    private function getTimeBasedGreeting($user): string
    {
        $hour = (int)date('H');
        $name = $user ? ($user->getValue('name') ?: $user->getValue('login')) : 'Benutzer';
        
        if ($hour >= 5 && $hour < 12) {
            $greeting = rex_i18n::msg('info_center_timetracker_good_morning');
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = rex_i18n::msg('info_center_timetracker_good_afternoon');
        } elseif ($hour >= 18 && $hour < 22) {
            $greeting = rex_i18n::msg('info_center_timetracker_good_evening');
        } else {
            $greeting = rex_i18n::msg('info_center_timetracker_good_night');
        }
        
        return sprintf($greeting, $name);
    }

    protected function wrapContent(string $content): string
    {
        return sprintf(
            '<div class="info-center-widget time-tracker-widget" data-id="%s" data-lazy="%s">
                <div class="info-center-widget-header">
                    <h3 class="info-center-widget-title">⏱️ %s</h3>
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
}
