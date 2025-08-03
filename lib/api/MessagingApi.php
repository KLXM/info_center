<?php

namespace KLXM\InfoCenter\Api;

use rex;
use rex_addon;
use rex_api_function;
use rex_api_exception;
use rex_response;
use rex_request;
use rex_logger;
use rex_config;
use rex_mailer;

class MessagingApi extends rex_api_function
{
    protected $published = true;

    /**
     * Zentrale Methode für das Senden von JSON-Antworten
     * Stellt sicher, dass immer erst der Output Buffer geleert wird
     * und dass jede Antwort mit exit beendet wird
     *
     * @param mixed $data Die zu sendenden Daten
     * @param int $statusCode HTTP-Statuscode
     * @return void Diese Methode kehrt nicht zurück (exit)
     */
    protected function sendResponse($data, $statusCode = 200)
    {
        rex_response::cleanOutputBuffers();
        if ($statusCode !== 200) {
            rex_response::setStatus($statusCode);
        }
        rex_response::sendJson($data);
        exit;
    }

    public function execute()
    {
        try {
            // Authentifizierung prüfen
            if (!$this->isAuthorized()) {
                throw new rex_api_exception('Unauthorized access');
            }

            $action = rex_request('action', 'string', '');
            
            switch ($action) {
                case 'send_message':
                    $result = $this->handleSendMessage();
                    $this->sendResponse($result);
                
                case 'take_screenshot':
                    $result = $this->handleTakeScreenshot();
                    $this->sendResponse($result);
                    
                default:
                    throw new rex_api_exception('Invalid action');
            }
            
        } catch (rex_api_exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            rex_logger::logException($e);
            $this->sendResponse([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    private function isAuthorized(): bool
    {
        $user = rex::getUser();
        return $user && $user->isAdmin();
    }

    private function handleSendMessage(): array
    {
        $subject = rex_request('subject', 'string', '');
        $message = rex_request('message', 'string', '');
        $currentUrl = rex_request('current_url', 'string', '');
        $currentContext = rex_request('current_context', 'string', '');
        $screenshotData = rex_request('screenshot_data', 'string', '');
        $includeScreenshot = rex_request('include_screenshot', 'bool', false);
        
        if (empty($subject) || empty($message)) {
            throw new rex_api_exception('Subject and message are required');
        }
        
        // E-Mail-Adresse aus System-Konfiguration laden
        $errorEmail = rex_config::get('system', 'error_email');
        if (empty($errorEmail)) {
            throw new rex_api_exception('No error email configured in system');
        }
        
        // E-Mail zusammenstellen
        $user = rex::getUser();
        $fullMessage = $this->buildEmailMessage($message, $currentUrl, $currentContext, $user);
        
        try {
            $mail = new rex_mailer();
            $mail->addAddress($errorEmail);
            $mail->Subject = '[Info Center] ' . $subject;
            $mail->Body = $fullMessage;
            $mail->isHTML(true);
            
            // Screenshot als Anhang hinzufügen
            if ($includeScreenshot && !empty($screenshotData)) {
                $this->addScreenshotAttachment($mail, $screenshotData);
            }
            
            if (!$mail->send()) {
                throw new rex_api_exception('Failed to send email: ' . $mail->ErrorInfo);
            }
            
            // Log the message
            rex_logger::factory()->info('Info Center message sent', [
                'user' => $user->getLogin(),
                'subject' => $subject,
                'url' => $currentUrl
            ]);
            
            return [
                'success' => true,
                'message' => 'Message sent successfully'
            ];
            
        } catch (\Exception $e) {
            rex_logger::logException($e);
            throw new rex_api_exception('Failed to send message: ' . $e->getMessage());
        }
    }

    private function handleTakeScreenshot(): array
    {
        // Screenshot-Funktionalität wird über JavaScript im Frontend implementiert
        // Diese Methode könnte für zukünftige Server-seitige Screenshot-Funktionalität genutzt werden
        return [
            'success' => true,
            'message' => 'Screenshot functionality is handled by client-side JavaScript'
        ];
    }

    private function buildEmailMessage(string $message, string $currentUrl, string $currentContext, $user): string
    {
        $addon = rex_addon::get('info_center');
        
        $html = '<html><body>';
        $html .= '<h2>Info Center Message</h2>';
        $html .= '<hr>';
        $html .= '<p><strong>From:</strong> ' . rex_escape($user->getName() . ' (' . $user->getLogin() . ')') . '</p>';
        $html .= '<p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<p><strong>URL:</strong> <a href="' . rex_escape($currentUrl) . '">' . rex_escape($currentUrl) . '</a></p>';
        $html .= '<hr>';
        $html .= '<h3>Message:</h3>';
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-left: 4px solid #007cba;">';
        $html .= nl2br(rex_escape($message));
        $html .= '</div>';
        
        if (!empty($currentContext)) {
            $html .= '<hr>';
            $html .= '<h3>Context Information:</h3>';
            $html .= '<pre style="background: #f8f8f8; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">';
            $html .= rex_escape($currentContext);
            $html .= '</pre>';
        }
        
        $html .= '<hr>';
        $html .= '<p><small>This message was sent via Info Center from ' . rex_escape(rex::getServerName()) . '</small></p>';
        $html .= '</body></html>';
        
        return $html;
    }

    private function addScreenshotAttachment($mail, string $screenshotData): void
    {
        // Screenshot-Data ist base64-kodiert
        if (strpos($screenshotData, 'data:image/png;base64,') === 0) {
            $imageData = substr($screenshotData, strlen('data:image/png;base64,'));
            $imageData = base64_decode($imageData);
            
            if ($imageData !== false) {
                $filename = 'screenshot_' . date('Y-m-d_H-i-s') . '.png';
                $mail->addStringAttachment($imageData, $filename, 'base64', 'image/png');
            }
        }
    }
}
