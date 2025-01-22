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

class ArticleWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = true;
    private ?rex_article $article = null;

    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('info_center_article_title');
        $this->article = $this->getCurrentArticle();
    }

    public function getInitialContent(): string
    {
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
        if (!$this->article) {
            return $this->wrapContent(rex_i18n::msg('info_center_no_article_found'));
        }

        $content = '<div class="info-center-article-items">';

        // Basic article information
        $content .= $this->renderBasicInfo();
        
        // Article path
        $content .= $this->renderPathInfo();
        
        // Edit/View links
        $content .= $this->renderActionLinks();
        
        // Metadata
        if ($this->shouldShowMetaInfo()) {
            $content .= $this->renderMetaInfo();
        }

        $content .= '</div>';

        return $this->wrapContent($content);
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
            rex_i18n::msg('status_online') : 
            rex_i18n::msg('status_offline');
        
        $html .= $this->renderInfoItem(
            rex_i18n::msg('info_center_article_status'),
            sprintf('<span class="info-center-status-%s">%s</span>', $statusClass, $statusText)
        );

        return $html;
    }

    private function renderPathInfo(): string
    {
        $path = [];
        foreach ($this->article->getParentTree() as $parent) {
            if (rex::isBackend() && rex::getUser()?->getComplexPerm('structure')->hasCategoryPerm($parent->getId())) {
                $path[] = sprintf(
                    '<a href="%s">%s</a>',
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
        
        // Edit link in backend
        if (rex::isBackend() && rex::getUser()?->getComplexPerm('structure')->hasCategoryPerm($this->article->getCategoryId())) {
            $html .= sprintf(
                '<a href="%s">%s</a>',
                rex_url::backendPage('content/edit', [
                    'article_id' => $this->article->getId(),
                    'category_id' => $this->article->getCategoryId(),
                    'clang' => $this->article->getClangId(),
                    'mode' => 'edit'
                ]),
                rex_i18n::msg('info_center_article_edit')
            );
        }
        
        // View link
        if (rex::isBackend()) {
            $html .= sprintf(
                ' | <a href="%s" target="_blank">%s</a>',
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
        // In backend
        if (rex::isBackend()) {
            $articleId = rex_request('article_id', 'int');
            $clangId = rex_request('clang', 'int', rex_clang::getCurrentId());
            
            $article = rex_article::get($articleId, $clangId);
            
            // Fallback to current category
            if (!$article) {
                $article = rex_article::get(rex_request('category_id', 'int'), $clangId);
            }
        } 
        // In frontend
        else {
            $article = rex_article::getCurrent();
        }

        // Fallback to start article
        if (!$article) {
            $article = rex_article::getSiteStartArticle();
        }

        return $article;
    }

    private function shouldShowMetaInfo(): bool
    {
        return rex::getUser()?->isAdmin() ?? false;
    }
}
