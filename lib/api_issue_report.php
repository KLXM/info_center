<?php

use FriendsOfREDAXO\IssueTracker\AppShell;
use FriendsOfREDAXO\IssueTracker\HistoryService;
use FriendsOfREDAXO\IssueTracker\Issue;
use FriendsOfREDAXO\IssueTracker\NotificationService;
use KLXM\InfoCenter\Widgets\IssueTrackerWidget;

/**
 * Issue Tracker Widget: meldet die aktuelle Seite als neues Thema im AddOn issue_tracker.
 *
 * Aufruf nur per POST an den Backend-Controller (auch aus dem Frontend), angemeldeter Backend-Benutzer
 * mit Recht für den Issue Tracker, CSRF-Token im POST-Body. Nach dem Speichern dieselben Schritte wie
 * beim Anlegen im Issue Tracker selbst (Verlauf, Benachrichtigungen).
 */
class rex_api_info_center_issue_report extends rex_api_function
{
    // Auch im Frontend aufrufbar (Formular im Info Center auf der Website); geprüft werden Backend-Session, Recht und CSRF
    protected $published = true;

    public function execute(): void
    {
        rex_response::cleanOutputBuffers();

        if ('POST' !== strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
            $this->fail('info_center_it_error', 405);
        }

        // Frontend: nur eine vorhandene Backend-Session aufnehmen (Login-Cookie), sonst keine Session anlegen
        $user = rex::isBackend() ? rex::getUser() : rex_backend_login::createUser();
        if (!IssueTrackerWidget::isAvailableForUser($user)) {
            $this->fail('info_center_it_no_permission', 403);
        }

        // CSRF nur aus dem POST-Body
        // (rex_csrf_token::isValid() liest auch GET-Parameter, daher hier selbst gegen den Wert aus der Session vergleichen)
        $token = rex_post('_csrf_token', 'string', '');
        if ('' === $token || !hash_equals(rex_csrf_token::factory(IssueTrackerWidget::CSRF_KEY)->getValue(), $token)) {
            $this->fail('info_center_it_csrf', 403);
        }

        $title = trim((string) preg_replace('/\s+/u', ' ', rex_post('title', 'string', '')));
        $description = trim(str_replace("\r\n", "\n", rex_post('description', 'string', '')));
        $priority = rex_post('priority', 'string', 'normal');
        $assignee = trim(rex_post('assigned_user_id', 'string', 'all'));
        $context = rex_post('context', 'string', '');
        $pageUrl = trim(rex_post('page_url', 'string', ''));
        $pageTitle = rex_post('page_title', 'string', '');

        if ('' === $title) {
            $this->fail('info_center_it_title_required', 422, 'title');
        }
        if (mb_strlen($title) > IssueTrackerWidget::TITLE_MAX || mb_strlen($description) > IssueTrackerWidget::DESCRIPTION_MAX) {
            $this->fail('info_center_it_too_long', 422);
        }
        if (!array_key_exists($priority, IssueTrackerWidget::getPriorities())) {
            $priority = 'normal';
        }

        // Zuständig: „Alle“ (gemeinsame Zuständigkeit) oder ein aktiver Benutzer mit Zugriff auf den Tracker
        $assignedUserId = null;
        if ('all' !== $assignee) {
            $assignedUserId = (int) $assignee;
            $assignedUser = $assignedUserId > 0 ? rex_user::get($assignedUserId) : null;
            if (!$assignedUser || 1 !== (int) $assignedUser->getValue('status') || !IssueTrackerWidget::userHasTrackerAccess($assignedUser)) {
                $this->fail('info_center_it_assignee_invalid', 422);
            }
        }

        $issue = new Issue();
        $issue->setCreatedBy((int) $user->getId());
        $issue->setTitle($title);
        $issue->setDescription($description);
        $issue->setStatus('open');
        $issue->setPriority($priority);
        $issue->setAssignedUserId($assignedUserId);

        // Formular und Prüfung gehören zum selben Bereich (Frontend-Formular → Website, Backend-Formular → Backend)
        if ($context !== (rex::isBackend() ? 'backend' : 'frontend')) {
            $this->fail('info_center_it_error', 422);
        }

        if ('frontend' === $context) {
            // Betroffene Seite: Artikel muss existieren, Adresse muss auf diese Website zeigen
            $articleId = rex_post('article_id', 'int', 0);
            $clangId = rex_post('clang_id', 'int', 0);
            if ($articleId > 0) {
                if (!rex_clang::exists($clangId)) {
                    $clangId = rex_clang::getStartId();
                }
                if (!rex_article::get($articleId, $clangId)) {
                    $this->fail('info_center_it_page_invalid', 422);
                }
                $issue->setAffectedArticle($articleId, $clangId);
            }
            if ('' !== $pageUrl) {
                if (!self::isOwnSiteUrl($pageUrl) || !Issue::isValidAffectedUrl($pageUrl)) {
                    $this->fail('info_center_it_page_invalid', 422);
                }
                $issue->setAffectedUrl($pageUrl);
            }
        } elseif ('backend' === $context) {
            // Meldende Backend-Seite nach den Regeln des Issue Trackers; beim Bearbeiten eines Artikels dieser als betroffene Seite
            $issue->setSource($pageUrl, $pageTitle);
            if ('' !== $pageUrl && null === $issue->getSourceUrl() && !self::isTrackerPage($pageUrl)) {
                $this->fail('info_center_it_page_invalid', 422);
            }
            $sourceArticle = $issue->getSourceUrl() ? AppShell::articleFromBackendUrl($issue->getSourceUrl()) : null;
            if ($sourceArticle) {
                $issue->setAffectedArticle($sourceArticle[0], $sourceArticle[1]);
            }
        } else {
            $this->fail('info_center_it_error', 422);
        }

        try {
            if (!$issue->save() || $issue->getId() <= 0) {
                $this->fail('info_center_it_error', 500);
            }
        } catch (rex_sql_exception $e) {
            rex_logger::logException($e);
            $this->fail('info_center_it_error', 500);
        }

        // Wie pages/issues.create.php des Issue Trackers: Verlauf + Benachrichtigungen
        HistoryService::add($issue->getId(), (int) $user->getId(), 'create');
        try {
            NotificationService::notifyNewIssue($issue);
            if ($assignedUserId && isset($assignedUser) && $assignedUser) {
                NotificationService::notifyAssignment($issue, $assignedUser);
            }
        } catch (Throwable $e) {
            // Thema ist gespeichert; ein Mail-Fehler darf die Meldung nicht scheitern lassen
            rex_logger::logException($e);
        }

        $id = $issue->getId();
        rex_response::sendJson([
            'success' => true,
            'issue_id' => $id,
            'message' => rex_i18n::msg('info_center_it_success', $id),
            'link_label' => rex_i18n::msg('info_center_it_open_issue', $id),
            'url' => IssueTrackerWidget::backendUrl(['page' => 'issue_tracker/issues/view', 'issue_id' => $id]),
        ]);
        exit;
    }

    /**
     * Adresse muss http(s) sein und auf den Host dieser Anfrage oder den eingetragenen Server zeigen.
     */
    private static function isOwnSiteUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $host = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $allowed = [strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''))];
        $server = parse_url((string) rex::getServer());
        if (is_array($server) && isset($server['host'])) {
            $allowed[] = strtolower($server['host']) . (isset($server['port']) ? ':' . $server['port'] : '');
        }
        if (rex_addon::get('yrewrite')->isAvailable() && class_exists('rex_yrewrite')) {
            foreach (rex_yrewrite::getDomains() as $domain) {
                $domainHost = parse_url('http://' . $domain->getHost(), PHP_URL_HOST);
                if (is_string($domainHost)) {
                    $allowed[] = strtolower($domainHost);
                    $allowed[] = strtolower((string) $domain->getHost());
                }
            }
        }
        return in_array($host, array_filter($allowed), true);
    }

    /**
     * Seiten des Issue Trackers selbst nimmt der Tracker nicht als Quelle an – dann wird nichts angehängt.
     */
    private static function isTrackerPage(string $url): bool
    {
        if (AppShell::normalizeBackendUrl((string) preg_replace('/([?&]page=)issue_tracker[^&#]*/', '$1structure', $url)) === null) {
            return false;
        }
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        return isset($query['page']) && is_string($query['page']) && str_starts_with($query['page'], 'issue_tracker');
    }

    /**
     * @return never
     */
    private function fail(string $msgKey, int $status, string $field = ''): void
    {
        $codes = [403 => '403 Forbidden', 405 => '405 Method Not Allowed', 422 => '422 Unprocessable Entity', 500 => '500 Internal Server Error'];
        rex_response::setStatus($codes[$status] ?? rex_response::HTTP_BAD_REQUEST);
        rex_response::sendJson(['success' => false, 'message' => rex_i18n::msg($msgKey), 'field' => $field]);
        exit;
    }
}
