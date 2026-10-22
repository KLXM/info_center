<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_article;
use rex_backend_login;
use rex_clang;
use rex_csrf_token;
use rex_i18n;
use rex_sql;
use rex_user;

/**
 * Issue Tracker Widget: aktuelle Seite (Frontend-Artikel oder Backend-Seite) als Thema im
 * AddOn issue_tracker (FriendsOfREDAXO) melden, dazu die eigenen offenen Themen.
 *
 * Wird nur registriert, wenn issue_tracker verfügbar ist und der Benutzer Themen anlegen darf.
 * Gespeichert wird über rex_api_info_center_issue_report (Backend-Controller, CSRF im POST-Body).
 */
class IssueTrackerWidget extends AbstractWidget
{
    public const CSRF_KEY = 'info_center_issue_report';
    public const TITLE_MAX = 255;
    public const DESCRIPTION_MAX = 5000;

    /** Status, die nicht mehr als „offen“ zählen */
    public const DONE_STATUSES = ['closed', 'rejected', 'info'];

    public function __construct()
    {
        $this->id = 'issuetracker';
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_it_title');
    }

    /**
     * issue_tracker vorhanden und Benutzer darf Themen anlegen (Seitenrecht issue_tracker[]).
     */
    public static function isAvailableForUser(?rex_user $user): bool
    {
        if (!$user || !rex_addon::get('issue_tracker')->isAvailable()) {
            return false;
        }
        if (!class_exists(\FriendsOfREDAXO\IssueTracker\Issue::class)) {
            return false;
        }
        return $user->isAdmin() || $user->hasPerm('issue_tracker[]');
    }

    /**
     * Absoluter Pfad zum Backend-Controller (auch im Frontend, wo rex_url relativ zur Website-Wurzel ist).
     *
     * @param array<string, int|string> $params
     */
    public static function backendUrl(array $params = []): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = rtrim(dirname($script), '/');
        if (rex::isBackend()) {
            $path = $dir . '/index.php';
        } else {
            $backend = ltrim((string) \rex_url::backendController(), './');
            $path = $dir . '/' . $backend;
        }
        return $path . ($params ? '?' . str_replace('%2F', '/', http_build_query($params, '', '&')) : '');
    }

    /**
     * Adresse der API: im Backend der Backend-Controller, im Frontend die Website selbst
     * (CSRF-Token liegen in REDAXO je Bereich getrennt in der Session, daher dort prüfen, wo das Formular entstand).
     */
    public static function apiUrl(): string
    {
        if (rex::isBackend()) {
            return self::backendUrl(['rex-api-call' => 'info_center_issue_report']);
        }
        $dir = rtrim(dirname(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
        return $dir . '/index.php?rex-api-call=info_center_issue_report';
    }

    /**
     * Prioritäten aus den Einstellungen des Issue Trackers (Schlüssel => Bezeichnung).
     *
     * @return array<string, string>
     */
    public static function getPriorities(): array
    {
        $priorities = self::getTrackerSetting('priorities');
        if (!$priorities) {
            $priorities = ['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch', 'urgent' => 'Dringend'];
        }
        return array_map('strval', $priorities);
    }

    /**
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return array_map('strval', self::getTrackerSetting('statuses'));
    }

    /**
     * @return array<mixed>
     */
    private static function getTrackerSetting(string $key): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT setting_value FROM ' . rex::getTable('issue_tracker_settings') . ' WHERE setting_key = ?', [$key]);
        $value = $sql->getRows() > 0 ? $sql->getValue('setting_value') : null;
        $decoded = is_string($value) ? json_decode($value, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Mögliche Zuständige wie im Formular des Issue Trackers: aktive Benutzer mit Zugriff auf den Tracker.
     *
     * @return array<int, string>
     */
    public static function getAssignableUsers(): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT id FROM ' . rex::getTable('user') . ' WHERE status = 1 ORDER BY name');
        $users = [];
        foreach ($sql as $row) {
            $user = rex_user::get((int) $row->getValue('id'));
            if ($user && self::userHasTrackerAccess($user)) {
                $name = trim((string) $user->getName());
                $users[(int) $user->getId()] = '' !== $name ? $name : (string) $user->getLogin();
            }
        }
        return $users;
    }

    public static function userHasTrackerAccess(rex_user $user): bool
    {
        return $user->isAdmin()
            || $user->hasPerm('issue_tracker[]')
            || $user->hasPerm('issue_tracker[issuer]')
            || $user->hasPerm('issue_tracker[issue_manager]');
    }

    public function render(): string
    {
        $user = rex_backend_login::createUser();
        if (!self::isAvailableForUser($user)) {
            return '';
        }

        $isBackend = rex::isBackend();
        $page = $isBackend ? $this->getBackendPage() : $this->getFrontendPage();

        $uid = 'ic-it-' . substr(md5((string) mt_rand()), 0, 6);
        $priorities = self::getPriorities();
        $users = self::getAssignableUsers();

        $priorityOptions = '';
        foreach ($priorities as $key => $label) {
            $priorityOptions .= '<option value="' . rex_escape((string) $key) . '"' . ('normal' === $key ? ' selected' : '') . '>' . rex_escape($label) . '</option>';
        }
        $assigneeOptions = '<option value="all" selected>' . rex_escape(rex_i18n::msg('info_center_it_assignee_all')) . '</option>';
        foreach ($users as $id => $name) {
            $assigneeOptions .= '<option value="' . $id . '">' . rex_escape($name) . '</option>';
        }

        $token = rex_csrf_token::factory(self::CSRF_KEY)->getValue();
        $listUrl = self::backendUrl(['page' => 'issue_tracker/issues/list']);
        $launcherAttr = $isBackend ? ' data-it-app-open data-pjax="false"' : '';

        $html = '<div class="ic-it" data-ic-it'
            . ' data-ic-it-api="' . rex_escape(self::apiUrl()) . '"'
            . ' data-ic-it-context="' . ($isBackend ? 'backend' : 'frontend') . '"'
            . ' data-ic-it-msg-error="' . rex_escape(rex_i18n::msg('info_center_it_error')) . '"'
            . ' data-ic-it-msg-sending="' . rex_escape(rex_i18n::msg('info_center_it_sending')) . '"'
            . ' data-ic-it-msg-title-required="' . rex_escape(rex_i18n::msg('info_center_it_title_required')) . '">';

        // Gemeldete Seite (was mitgeschickt wird)
        $html .= '<div class="ic-it-page" data-ic-it-page>'
            . '<span class="ic-it-label">' . rex_escape(rex_i18n::msg('info_center_it_page')) . '</span>'
            . '<span class="ic-it-page-title" data-ic-it-page-title>' . rex_escape($page['title']) . '</span>'
            . '<span class="ic-it-page-url" data-ic-it-page-url>' . rex_escape($page['url']) . '</span>'
            . '</div>';

        $html .= '<form class="ic-it-form" data-ic-it-form novalidate>'
            . '<input type="hidden" name="_csrf_token" value="' . rex_escape($token) . '">'
            . '<input type="hidden" name="context" value="' . ($isBackend ? 'backend' : 'frontend') . '">'
            . '<input type="hidden" name="article_id" value="' . (int) $page['article_id'] . '">'
            . '<input type="hidden" name="clang_id" value="' . (int) $page['clang_id'] . '">'
            . '<input type="hidden" name="page_url" value="' . rex_escape($page['url']) . '" data-ic-it-field-url>'
            . '<input type="hidden" name="page_title" value="' . rex_escape($page['title']) . '" data-ic-it-field-title>'
            . '<div class="ic-it-field">'
            . '<label for="' . $uid . '-title">' . rex_escape(rex_i18n::msg('info_center_it_subject')) . ' <span class="ic-it-req">(' . rex_escape(rex_i18n::msg('info_center_it_required')) . ')</span></label>'
            . '<input type="text" id="' . $uid . '-title" name="title" maxlength="' . self::TITLE_MAX . '" required autocomplete="off" aria-describedby="' . $uid . '-msg">'
            . '</div>'
            . '<div class="ic-it-field">'
            . '<label for="' . $uid . '-desc">' . rex_escape(rex_i18n::msg('info_center_it_description')) . '</label>'
            . '<textarea id="' . $uid . '-desc" name="description" rows="3" maxlength="' . self::DESCRIPTION_MAX . '"></textarea>'
            . '</div>'
            . '<div class="ic-it-row">'
            . '<div class="ic-it-field">'
            . '<label for="' . $uid . '-prio">' . rex_escape(rex_i18n::msg('info_center_it_priority')) . '</label>'
            . '<select id="' . $uid . '-prio" name="priority">' . $priorityOptions . '</select>'
            . '</div>'
            . '<div class="ic-it-field">'
            . '<label for="' . $uid . '-assignee">' . rex_escape(rex_i18n::msg('info_center_it_assignee')) . '</label>'
            . '<select id="' . $uid . '-assignee" name="assigned_user_id">' . $assigneeOptions . '</select>'
            . '</div>'
            . '</div>'
            . '<div class="ic-it-actions">'
            . '<button type="submit" class="ic-it-submit">' . rex_escape(rex_i18n::msg('info_center_it_submit')) . '</button>'
            . '</div>'
            . '<div class="ic-it-msg" id="' . $uid . '-msg" role="status" aria-live="polite" data-ic-it-msg></div>'
            . '</form>';

        $html .= $this->renderOwnIssues((int) $user->getId(), $launcherAttr);

        $html .= '<div class="ic-it-footer"><a class="ic-it-open" href="' . rex_escape($listUrl) . '"' . $launcherAttr . '>'
            . rex_escape(rex_i18n::msg('info_center_it_open_tracker')) . '</a></div>';

        $html .= '</div>';

        return $this->wrapContent($html);
    }

    /**
     * Mir zugewiesene, offene Themen: Anzahl + die drei zuletzt geänderten.
     */
    private function renderOwnIssues(int $userId, string $launcherAttr): string
    {
        $table = rex::getTable('issue_tracker_issues');
        $done = implode(',', array_fill(0, count(self::DONE_STATUSES), '?'));
        $params = array_merge([$userId], self::DONE_STATUSES);

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT COUNT(*) AS cnt FROM ' . $table . ' WHERE assigned_user_id = ? AND status NOT IN (' . $done . ')', $params);
        $count = (int) $sql->getValue('cnt');

        $html = '<div class="ic-it-mine">'
            . '<h4 class="ic-it-subtitle">' . rex_escape(rex_i18n::msg('info_center_it_mine')) . ' <span class="ic-it-count">' . $count . '</span></h4>';

        if (0 === $count) {
            return $html . '<p class="ic-it-empty">' . rex_escape(rex_i18n::msg('info_center_it_mine_empty')) . '</p></div>';
        }

        $sql->setQuery(
            'SELECT id, title, status FROM ' . $table . ' WHERE assigned_user_id = ? AND status NOT IN (' . $done . ') ORDER BY updated_at DESC, id DESC LIMIT 3',
            $params,
        );
        $statuses = self::getStatuses();
        $html .= '<ul class="ic-it-list">';
        foreach ($sql as $row) {
            $id = (int) $row->getValue('id');
            $status = (string) $row->getValue('status');
            $html .= '<li><a href="' . rex_escape(self::backendUrl(['page' => 'issue_tracker/issues/view', 'issue_id' => $id])) . '"' . $launcherAttr . '>'
                . '<span class="ic-it-id">#' . $id . '</span> '
                . '<span class="ic-it-issue-title">' . rex_escape((string) $row->getValue('title')) . '</span></a>'
                . '<span class="ic-it-status">' . rex_escape($statuses[$status] ?? $status) . '</span></li>';
        }
        return $html . '</ul></div>';
    }

    /**
     * Frontend: aktueller Artikel + volle Adresse der Anfrage.
     *
     * @return array{title:string,url:string,article_id:int,clang_id:int}
     */
    private function getFrontendPage(): array
    {
        $article = rex_article::getCurrent();
        $scheme = (!empty($_SERVER['HTTPS']) && 'off' !== strtolower((string) $_SERVER['HTTPS'])) ? 'https' : 'http';
        $url = $scheme . '://' . (string) ($_SERVER['HTTP_HOST'] ?? '') . (string) ($_SERVER['REQUEST_URI'] ?? '/');

        return [
            'title' => $article ? (string) $article->getName() : '',
            'url' => $url,
            'article_id' => $article ? (int) $article->getId() : 0,
            'clang_id' => $article ? (int) $article->getClangId() : (int) rex_clang::getCurrentId(),
        ];
    }

    /**
     * Backend: aktuelle Backend-Seite (wie der Knopf „Neues Thema“ des Issue Trackers).
     * Nach PJAX-Wechseln aktualisiert info-center.js Titel und Adresse.
     *
     * @return array{title:string,url:string,article_id:int,clang_id:int}
     */
    private function getBackendPage(): array
    {
        $source = \FriendsOfREDAXO\IssueTracker\AppShell::normalizeBackendUrl('index.php?' . http_build_query($_GET, '', '&'));
        return [
            'title' => null !== $source ? \FriendsOfREDAXO\IssueTracker\AppShell::backendUrlLabel($source) : '',
            'url' => $source ?? '',
            'article_id' => 0,
            'clang_id' => 0,
        ];
    }
}
