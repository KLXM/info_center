<?php

namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_addon;
use rex_url;
use rex_be_controller;
use rex_article;
use rex_request;

class MessagingWidget extends AbstractWidget
{
    protected string $id = 'messaging';
    protected string $title = 'Messaging';
    protected int $priority = 3;

    public function render(): string
    {
        $addon = rex_addon::get('info_center');
        
        // Prüfe ob Benutzer berechtigt ist
        if (!rex::getUser() || !rex::getUser()->isAdmin()) {
            return '';
        }

        $currentUrl = $this->getCurrentUrl();
        $currentContext = $this->getCurrentContext();
        
        $html = '<div class="info-center-widget info-center-messaging" id="info-center-messaging">';
        $html .= '<div class="widget-header">';
        $html .= '<h4 class="widget-title">' . $addon->i18n('messaging_widget_title') . '</h4>';
        $html .= '</div>';
        $html .= '<div class="widget-content">';
        $html .= '<div class="messaging-actions">';
        $html .= '<button type="button" class="btn btn-sm btn-primary messaging-send-btn" data-url="' . rex_escape($currentUrl) . '" data-context="' . rex_escape($currentContext) . '">';
        $html .= '<i class="rex-icon rex-icon-mail"></i> ' . $addon->i18n('messaging_send_message');
        $html .= '</button>';
        $html .= '<button type="button" class="btn btn-sm btn-default messaging-screenshot-btn" title="' . $addon->i18n('messaging_take_screenshot') . '">';
        $html .= '<i class="rex-icon rex-icon-camera"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div class="messaging-status" id="messaging-status"></div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function getCurrentUrl(): string
    {
        if (rex::isBackend()) {
            return rex_url::currentBackendPage();
        } else {
            return rex_url::frontend();
        }
    }
    
    private function getCurrentContext(): string
    {
        $context = [];
        
        if (rex::isBackend()) {
            $context['backend'] = true;
            $context['page'] = rex_be_controller::getCurrentPage();
            $context['subpage'] = rex_be_controller::getCurrentPagePart(2);
            $context['user'] = rex::getUser()->getLogin();
            
            // Artikel-Kontext
            if (rex_request('article_id', 'int')) {
                $context['article_id'] = rex_request('article_id', 'int');
            }
            
            // Kategorie-Kontext  
            if (rex_request('category_id', 'int')) {
                $context['category_id'] = rex_request('category_id', 'int');
            }
            
        } else {
            $context['frontend'] = true;
            $article = rex_article::getCurrent();
            if ($article) {
                $context['article_id'] = $article->getId();
                $context['category_id'] = $article->getCategoryId();
                $context['template_id'] = $article->getTemplateId();
            }
        }
        
        return json_encode($context, JSON_PRETTY_PRINT);
    }
    
    public function isEnabled(): bool
    {
        $addon = rex_addon::get('info_center');
        $user = rex::getUser();
        
        if (!$user) {
            return false;
        }
        
        // Prüfe User-spezifische Einstellungen
        $userConfig = $addon->getConfig('widgets_user_' . $user->getId(), []);
        if (isset($userConfig['messaging']['enabled'])) {
            return (bool) $userConfig['messaging']['enabled'];
        }
        
        // Fallback auf globale Einstellungen
        $globalConfig = $addon->getConfig('widgets', []);
        return $globalConfig['messaging']['enabled'] ?? true;
    }
}
