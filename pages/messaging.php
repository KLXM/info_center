<?php

namespace KLXM\InfoCenter;

use rex;
use rex_addon;
use rex_fragment;
use rex_url;
use rex_view;
use rex_request;
use rex_escape;
use rex_be_controller;
use rex_article;

try {
    $package = rex_addon::get('info_center');
    if (!$package->isAvailable()) {
        throw new \Exception('Info Center addon not available');
    }
    
    $content = '';
    $buttons = '';
    $formElements = [];
    $n = [];

    // Aktuelle URL und Kontext ermitteln
    $currentUrl = rex_request('current_url', 'string', '');
    $currentContext = rex_request('current_context', 'string', '');
    
    // Falls kein Kontext übergeben wurde, aktuellen Kontext ermitteln
    if (empty($currentContext)) {
        $context = [];
        
        if (rex::isBackend()) {
            $context['backend'] = true;
            $context['page'] = rex_be_controller::getCurrentPage();
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
        
        $currentContext = json_encode($context, JSON_PRETTY_PRINT);
    }
    
    // Falls keine URL übergeben wurde, aktuelle URL ermitteln
    if (empty($currentUrl)) {
        if (rex::isBackend()) {
            $currentUrl = rex_url::currentBackendPage();
        } else {
            $currentUrl = rex_url::frontend();
        }
    }

$content .= '<fieldset><legend>' . $package->i18n('messaging_send_message') . '</legend>';

// URL Feld
$formElements = [];
$n = [];
$n['label'] = '<label for="messaging-current-url">' . $package->i18n('messaging_current_url') . '</label>';
$n['field'] = '<input type="text" id="messaging-current-url" name="current_url" class="form-control" value="' . rex_escape($currentUrl) . '" readonly />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Kontext/Datensatz
$formElements = [];
$n = [];
$n['label'] = '<label for="messaging-current-context">' . $package->i18n('messaging_current_context') . '</label>';
$n['field'] = '<textarea id="messaging-current-context" name="current_context" class="form-control" rows="3" readonly>' . rex_escape($currentContext) . '</textarea>';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Betreff
$formElements = [];
$n = [];
$n['label'] = '<label for="messaging-subject">' . $package->i18n('messaging_subject') . '</label>';
$n['field'] = '<input type="text" id="messaging-subject" name="subject" class="form-control" placeholder="' . $package->i18n('messaging_subject_placeholder') . '" required />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Nachricht
$formElements = [];
$n = [];
$n['label'] = '<label for="messaging-message">' . $package->i18n('messaging_message') . '</label>';
$n['field'] = '<textarea id="messaging-message" name="message" class="form-control" rows="8" placeholder="' . $package->i18n('messaging_message_placeholder') . '" required></textarea>';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Screenshot-Option
$formElements = [];
$n = [];
$n['label'] = '<label for="messaging-screenshot">' . $package->i18n('messaging_include_screenshot') . '</label>';
$n['field'] = '<input type="checkbox" id="messaging-screenshot" name="include_screenshot" value="1" checked /> <small class="text-muted">' . $package->i18n('messaging_screenshot_note') . '</small>';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

    // Hidden Screenshot Data
    $content .= '<input type="hidden" id="messaging-screenshot-data" name="screenshot_data" />';
    
    // Status-Element hinzufügen
    $content .= '<div id="messaging-status" class="messaging-status"></div>';

    $content .= '</fieldset>';    // Buttons
    $formElements = [];
    $n = [];
    $n['field'] = '<button class="btn btn-primary" type="button" id="messaging-send-btn">' . $package->i18n('messaging_send') . '</button>
                   <a href="' . rex_url::backendController() . '" class="btn btn-default">' . $package->i18n('messaging_cancel') . '</a>';
    $formElements[] = $n;
    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');$buttons = '
<fieldset class="rex-form-action">
    ' . $buttons . '
</fieldset>
';

// Ausgabe Formular
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $package->i18n('messaging_title'));
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$output = $fragment->parse('core/page/section.php');

    echo $output;

    // CSS und JS werden bereits im boot.php geladen, hier nicht nochmal
    // rex_view::addCssFile($package->getAssetsUrl('css/messaging.css'));
    // rex_view::addJsFile($package->getAssetsUrl('js/messaging.js'));

} catch (\Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<h4>Error loading messaging form</h4>';
    echo '<p>' . rex_escape($e->getMessage()) . '</p>';
    echo '<p><small>File: ' . rex_escape($e->getFile()) . ' Line: ' . $e->getLine() . '</small></p>';
    echo '</div>';
}
