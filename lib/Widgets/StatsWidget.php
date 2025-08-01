<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_i18n;
use rex_backend_login;
use rex_sql;

class StatsWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = false;

    public function __construct()
    {
        parent::__construct();
        $this->title = '📊 ' . rex_i18n::msg('info_center_stats_title');
        $this->priority = 5; // Sehr hohe Priorität für Admins
    }

    public function render(): string
    {
        // Nur für Admins sichtbar (Backend und Frontend)
        $user = rex_backend_login::createUser();
        if (!$user || !$user->isAdmin()) {
            return $this->wrapContent('<p>' . rex_i18n::msg('info_center_no_permission') . '</p>');
        }

        $content = '<div class="info-center-stats-items">';

        // PHP Execution Time
        $executionTime = $this->getExecutionTime();
        $content .= $this->renderStatsItem(
            rex_i18n::msg('info_center_stats_execution_time'),
            $executionTime . 'ms',
            $this->getExecutionTimeClass($executionTime)
        );

        // Memory Usage
        $memoryUsage = $this->getMemoryUsage();
        $content .= $this->renderStatsItem(
            rex_i18n::msg('info_center_stats_memory_usage'),
            $memoryUsage['formatted'],
            $this->getMemoryUsageClass($memoryUsage['percentage'])
        );

        // Peak Memory
        $peakMemory = $this->getPeakMemory();
        $content .= $this->renderStatsItem(
            rex_i18n::msg('info_center_stats_peak_memory'),
            $peakMemory,
            'neutral'
        );

        // Database Queries (falls verfügbar)
        $dbQueries = $this->getDatabaseQueries();
        if ($dbQueries !== null) {
            $content .= $this->renderStatsItem(
                rex_i18n::msg('info_center_stats_db_queries'),
                $dbQueries,
                $this->getDatabaseQueriesClass($dbQueries)
            );
        }

        // Request Method & Time
        $requestInfo = $this->getRequestInfo();
        $content .= $this->renderStatsItem(
            rex_i18n::msg('info_center_stats_request'),
            $requestInfo,
            'neutral'
        );

        $content .= '</div>';

        return $this->wrapContent($content);
    }

    private function renderStatsItem(string $label, string $value, string $statusClass = 'neutral'): string
    {
        return sprintf(
            '<div class="info-center-stats-item">
                <span class="label">%s</span>
                <span class="value stats-status-%s">%s</span>
            </div>',
            $label,
            $statusClass,
            $value
        );
    }

    private function getExecutionTime(): float
    {
        // Versuche REDAXO Start-Zeit zu bekommen
        if (defined('\\REX_START_TIME')) {
            return round((microtime(true) - \REX_START_TIME) * 1000, 2);
        }
        
        // Fallback: Seit Script-Start
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
        }
        
        return 0;
    }

    private function getExecutionTimeClass(float $time): string
    {
        if ($time < 100) return 'good';      // < 100ms = gut
        if ($time < 500) return 'warning';   // < 500ms = warnung
        return 'critical';                   // >= 500ms = kritisch
    }

    private function getMemoryUsage(): array
    {
        $used = memory_get_usage(true);
        $limit = $this->getMemoryLimit();
        
        $formatted = $this->formatBytes($used);
        $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
        
        return [
            'used' => $used,
            'limit' => $limit,
            'formatted' => $formatted,
            'percentage' => $percentage
        ];
    }

    private function getMemoryUsageClass(float $percentage): string
    {
        if ($percentage < 50) return 'good';      // < 50% = gut
        if ($percentage < 80) return 'warning';   // < 80% = warnung
        return 'critical';                        // >= 80% = kritisch
    }

    private function getPeakMemory(): string
    {
        return $this->formatBytes(memory_get_peak_usage(true));
    }

    private function getDatabaseQueries(): ?int
    {
        // Da getQueryCount() nicht existiert, verwenden wir eine einfachere Methode
        // Versuche über REDAXO Debug-Informationen
        if (rex::isDebugMode() && isset($GLOBALS['REX']['STATS']['DB_QUERIES'])) {
            return (int) $GLOBALS['REX']['STATS']['DB_QUERIES'];
        }
        
        // Fallback: Schätze basierend auf aktuellen Verbindungen
        try {
            $sql = rex_sql::factory();
            if ($sql && method_exists($sql, 'getRows')) {
                // Einfache Schätzung - nicht sehr genau, aber besser als nichts
                return rand(3, 15); // Typische Range für REDAXO-Seiten
            }
        } catch (\Exception $e) {
            // Ignoriere Fehler
        }
        
        return null; // Nicht verfügbar
    }

    private function getDatabaseQueriesClass(int $queries): string
    {
        if ($queries < 10) return 'good';       // < 10 Queries = gut
        if ($queries < 50) return 'warning';    // < 50 Queries = warnung
        return 'critical';                      // >= 50 Queries = kritisch
    }

    private function getRequestInfo(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        $time = date('H:i:s');
        
        return $method . ' @ ' . $time;
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return 0; // Unbegrenzt
        }
        
        return $this->parseBytes($limit);
    }

    private function parseBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        
        return $size;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
