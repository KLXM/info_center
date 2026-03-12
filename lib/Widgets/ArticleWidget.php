<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_article;
use rex_url;
use rex_clang;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_backend_login;
use rex_escape;
use rex_sql;

class ArticleWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = true;
    private ?rex_article $article = null;

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_article_title');
        // Artikel NICHT im Constructor holen - wird bei render() gemacht
    }

    public function getInitialContent(): string
    {
        // Sicherstellen, dass Artikel verfügbar ist
        if (!$this->article) {
            $this->article = $this->getCurrentArticle();
        }
        
        if (!$this->article) {
            return $this->wrapContent(rex_i18n::msg('info_center_no_article_found'));
        }

        // Light initial content showing only article name
        $content = sprintf(
            '<div class="info-center-article-basic">
                <div class="info-center-article-item">
                    <span class="label">%s</span>
                    <span class="value">%s</span>
                </div>
            </div>',
            rex_i18n::msg('info_center_article_name'),
            rex_escape($this->article->getName())
        );

        return $this->wrapContent($content);
    }

    public function render(): string
    {
        // Backend-User-Session erstellen (wie bei Minibar) - VOR der Artikel-Verarbeitung
        rex_backend_login::createUser();
        
        // Artikel holen (jetzt mit korrekter Session)
        if (!$this->article) {
            $this->article = $this->getCurrentArticle();
        }
        
        $content = '<div class="info-center-article-items">';

        if (rex::isBackend()) {
            // Backend: Administrative Funktionen
            $content .= $this->renderBackendContent();
        } else {
            // Frontend: Aktueller Artikel
            if (!$this->article) {
                return $this->wrapContent(rex_i18n::msg('info_center_no_article_found'));
            }
            
            $content .= $this->renderFrontendContent();
        }

        $content .= '</div>';
        return $this->wrapContent($content);
    }

    private function renderBackendContent(): string
    {
        $html = '';
        
        // Quick Links zu häufig genutzten Bereichen
        $html .= $this->renderQuickLinks();
        
        // Zuletzt bearbeitete Artikel (wie quick_navigation)
        $html .= $this->renderRecentArticles();
        
        return $html;
    }

    private function renderFrontendContent(): string
    {
        $html = '';

        // Zuletzt bearbeitete Artikel auch im Frontend anzeigen
        $html .= $this->renderRecentArticles();

        // Basic article information
        $html .= $this->renderBasicInfo();
        
        // Article path
        $html .= $this->renderPathInfo();
        
        // Edit/View links
        $html .= $this->renderActionLinks();
        
        // Metadata
        if ($this->shouldShowMetaInfo()) {
            $html .= $this->renderMetaInfo();
        }
        
        return $html;
    }

    private function renderQuickLinks(): string
    {
        $user = rex_backend_login::createUser();
        if (!$user) {
            return '';
        }

        $html = '<div class="info-center-quick-links">';
        
        // Struktur-Hauptseite - für User mit access (die haben sie immer, sonst kämen sie nicht ins Backend zur Struktur)
        $html .= sprintf(
            '<div class="info-center-quick-link">
                <a href="%s">%s</a>
            </div>',
            rex_url::backendPage('structure'),
            rex_i18n::msg('info_center_structure_overview')
        );

        $html .= '</div>';
        return $html;
    }

    private function renderRecentArticles(): string
    {
        $user = rex_backend_login::createUser();
        if (!$user) {
            return '';
        }

        // Analog zu quick_navigation[history]: ohne Berechtigung kein Verlauf
        if (!$user->isAdmin() && !$user->hasPerm('info_center[recent_articles]')) {
            return '';
        }

        $recentArticles = $this->getRecentArticles();
        if (empty($recentArticles)) {
            return '';
        }

        $html = '<div class="info-center-recent-articles">';
        $html .= '<h4>' . rex_i18n::msg('info_center_recent_articles') . '</h4>';
        
        foreach ($recentArticles as $article) {
            // Status: 0 = offline (grau), 1 = online (blau), 2 = locked (rot)
            $statusClass = 'status-online';
            if ($article['status'] == 0) {
                $statusClass = 'status-offline';
            } elseif ($article['status'] == 2) {
                $statusClass = 'status-locked';
            }
            
            // Bessere Datumsformatierung - prüfe ob updatedate ein gültiger Timestamp ist
            $updatedate = (int)$article['updatedate'];
            if ($updatedate > 0) {
                $date = date('d.m. H:i', $updatedate);
            } else {
                // Fallback: aktuelles Datum
                $date = date('d.m. H:i');
            }
            
            $html .= sprintf(
                '<div class="info-center-recent-article %s">
                    <div class="article-main">
                        <a href="%s" title="%s">
                            <span class="article-name">%s</span>
                        </a>
                    </div>
                    <div class="article-meta">
                        <span class="article-user">%s</span>
                        <span class="article-date">%s</span>
                    </div>
                </div>',
                $statusClass,
                rex_url::backendPage('content/edit', [
                    'article_id' => $article['id'],
                    'category_id' => $article['category_id'],
                    'clang' => $article['clang_id'],
                    'mode' => 'edit'
                ]),
                rex_escape($article['name']),
                rex_escape($this->truncateString($article['name'], 30)),
                rex_escape($article['updateuser']),
                $date
            );
        }
        
        $html .= '</div>';
        return $html;
    }

    private function getRecentArticles(): array
    {
        try {
            $where = '';
            $whereParams = [];
            
            $user = rex_backend_login::createUser();
            if (!$user) {
                return [];
            }

            // Berechtigungsprüfung analog zu quick_navigation:
            // 1. Admins / info_center[all_articles] → alle Änderungen sehen
            // 2. Alle anderen → nur eigene Änderungen (WHERE updateuser = login)
            // Keine zusätzliche hasCategoryPerm-Prüfung nötig – wer den Artikel
            // bearbeitet hat, hatte zum Zeitpunkt der Bearbeitung Zugriff.
            if (!$user->isAdmin() && !$user->hasPerm('info_center[all_articles]')) {
                $where = 'WHERE updateuser = :user';
                $whereParams['user'] = $user->getValue('login');
            }

            $sql = rex_sql::factory();
            $query = '
                SELECT 
                    id, 
                    parent_id as category_id,
                    clang_id, 
                    name, 
                    updateuser,
                    UNIX_TIMESTAMP(updatedate) as updatedate,
                    status
                FROM ' . rex::getTable('article') . ' 
                ' . $where . ' 
                ORDER BY updatedate DESC 
                LIMIT 15
            ';
            
            return $sql->getArray($query, $whereParams);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function truncateString(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }

    private function renderBasicInfo(): string
    {
        $html = '';
        
        // Article name
        $html .= $this->renderInfoItem(
            rex_i18n::msg('info_center_article_name'),
            rex_escape($this->article->getName())
        );

        // Article ID
        $html .= $this->renderInfoItem(
            'ID',
            $this->article->getId()
        );

        // Status
        $statusClass = $this->article->isOnline() ? 'online' : 'offline';
        $statusText = $this->article->isOnline() ? 
            rex_i18n::msg('info_center_status_online') : 
            rex_i18n::msg('info_center_status_offline');
        
        $html .= $this->renderInfoItem(
            rex_i18n::msg('info_center_article_status'),
            sprintf('<span class="info-center-status-%s">%s</span>', $statusClass, $statusText)
        );

        return $html;
    }

    private function renderPathInfo(): string
    {
        $path = [];
        // Verwende rex_backend_login::createUser() wie bei der Minibar
        $user = rex_backend_login::createUser();
        
        foreach ($this->article->getParentTree() as $parent) {
            $canLinkToStructure = false;
            
            if ($user) {
                if (rex::isBackend()) {
                    // Im Backend: Normale Berechtigungsprüfung
                    $canLinkToStructure = $user->getComplexPerm('structure')?->hasCategoryPerm($parent->getId()) ?? false;
                } else {
                    // Im Frontend: info_center[] + Struktur-Rechte für die Kategorie
                    $hasInfoCenter = $user->isAdmin() || $user->hasPerm('info_center[]');
                    $categoryPerm = $user->getComplexPerm('structure');
                    $canLinkToStructure = $hasInfoCenter && ($user->isAdmin() || ($categoryPerm && $categoryPerm->hasCategoryPerm($parent->getId())));
                }
            }
            
            if ($canLinkToStructure) {
                $path[] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    rex_url::backendPage('structure', ['category_id' => $parent->getId()]),
                    rex_escape($parent->getName())
                );
            } else {
                $path[] = rex_escape($parent->getName());
            }
        }

        return $this->renderInfoItem(
            rex_i18n::msg('info_center_article_path'),
            implode(' / ', $path)
        );
    }

    private function renderActionLinks(): string
    {
        $html = '<div class="info-center-article-actions">';

        // Verwende rex_backend_login::createUser() wie bei der Minibar
        $user = rex_backend_login::createUser();
        $hasStructurePerm = false;
        
        // Prüfe Berechtigung - Backend oder Frontend mit Backend-Session
        if ($user) {
            if (rex::isBackend()) {
                // Im Backend: Normale Berechtigungsprüfung
                $hasStructurePerm = $user->getComplexPerm('structure')?->hasCategoryPerm($this->article->getCategoryId()) ?? false;
            } else {
                // Im Frontend: Prüfe info_center[] Berechtigung UND Struktur-Zugriff
                $hasInfoCenterPerm = $user->isAdmin() || $user->hasPerm('info_center[]');
                if ($hasInfoCenterPerm) {
                    // Prüfe dann Struktur-Berechtigung für den konkreten Artikel
                    $categoryPerm = $user->getComplexPerm('structure');
                    $hasStructurePerm = $user->isAdmin() || 
                                       ($categoryPerm && $categoryPerm->hasCategoryPerm($this->article->getCategoryId()));
                }
            }
        }
        
        // Edit link - im Backend und Frontend für eingeloggte Backend-Benutzer
        if ($hasStructurePerm) {
            $editUrl = rex_url::backendPage('content/edit', [
                'article_id' => $this->article->getId(),
                'category_id' => $this->article->getCategoryId(),
                'clang' => $this->article->getClangId(),
                'mode' => 'edit'
            ]);
            
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-edit">
                    %s
                </a>',
                $editUrl,
                rex_i18n::msg('info_center_article_edit')
            );
        }
        
        // Structure/Category link - für Navigation zur Kategorie-Verwaltung
        if ($hasStructurePerm) {
            $structureUrl = rex_url::backendPage('structure', [
                'category_id' => $this->article->getCategoryId(),
                'clang' => $this->article->getClangId()
            ]);
            
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-structure">
                    %s
                </a>',
                $structureUrl,
                rex_i18n::msg('info_center_article_structure')
            );
        }
        
        // View link - nur im Backend, da im Frontend bereits sichtbar
        if (rex::isBackend()) {
            $html .= sprintf(
                '<a href="%s" target="_blank" class="info-center-btn info-center-btn-view">
                    %s
                </a>',
                $this->article->getUrl(),
                rex_i18n::msg('info_center_article_view')
            );
        }

        $html .= '</div>';
        return $html;
    }

    private function renderMetaInfo(): string
    {
        $html = '';
        
        // Get metadata via extension point
        $metadata = rex_extension::registerPoint(new rex_extension_point(
            'INFO_CENTER_ARTICLE_METADATA',
            [],
            ['article' => $this->article]
        ));

        if (!empty($metadata)) {
            $html .= '<div class="info-center-article-metadata">';
            $html .= '<h4>' . rex_i18n::msg('info_center_article_metadata') . '</h4>';
            
            foreach ($metadata as $key => $value) {
                $html .= $this->renderInfoItem($key, $value);
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }

    private function renderInfoItem(string $label, string $value): string
    {
        return sprintf(
            '<div class="info-center-article-item">
                <span class="label">%s</span>
                <span class="value">%s</span>
            </div>',
            $label,
            $value
        );
    }

    private function getCurrentArticle(): ?rex_article
    {
        $clangId = rex_request('clang', 'int');
        $clangId = rex_clang::exists($clangId) ? $clangId : rex_clang::getStartId();

        if (rex::isBackend()) {
            $article = rex_article::get(rex_request('article_id', 'int'), $clangId);

            if (!$article) {
                $article = rex_article::get(rex_request('category_id', 'int'), $clangId);
            }
        } else {
            $article = rex_article::getCurrent();
        }

        if (!$article) {
            $article = rex_article::getSiteStartArticle();
        }

        return $article;
    }

    protected function wrapContent(string $content): string
    {
        // Status-Punkt nur im Frontend anzeigen
        $statusDot = '';
        if (rex::isFrontend() && $this->article) {
            $statusClass = $this->article->isOnline() ? 'online' : 'offline';
            $statusDot = '<span class="info-center-status-dot info-center-status-' . $statusClass . '"></span>';
        }
        
        return sprintf(
            '<div class="info-center-widget" data-id="%s" data-lazy="%s">
                <div class="info-center-widget-header">
                    <h3 class="info-center-widget-title">%s%s</h3>
                </div>
                <div class="info-center-widget-content">
                    %s
                </div>
            </div>',
            rex_escape($this->getId()),
            $this->supportsLazyLoading() ? 'true' : 'false',
            $statusDot,
            rex_escape($this->getTitle()),
            $content
        );
    }

    private function shouldShowMetaInfo(): bool
    {
        $user = rex_backend_login::createUser();
        return $user?->isAdmin() ?? false;
    }
}
