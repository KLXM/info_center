<?php

use rex_addon;
use rex_api_function;
use rex_request;
use rex_response;
use rex;

class rex_api_info_center_save_widget_order extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        // Clear output buffer to prevent REDAXO framework output from interfering with JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Check if user is logged in
        if (!rex::getUser()) {
            $this->sendError('Not authenticated');
            return;
        }
        
        $widgetOrder = rex_request('widget_order', 'string', '');
        
        if (empty($widgetOrder)) {
            $this->sendError('Widget order is required');
            return;
        }
        
        try {
            // Decode JSON
            $order = json_decode($widgetOrder, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->sendError('Invalid JSON');
                return;
            }
            
            // Save to user-specific configuration
            $addon = rex_addon::get('info_center');
            $user = rex::getUser();
            $userId = $user->getId();
            
            $addon->setConfig('widget_order_user_' . $userId, $order);
            
            $this->sendSuccess(['message' => 'Widget order saved successfully']);
            
        } catch (\Exception $e) {
            $this->sendError('Error saving widget order: ' . $e->getMessage());
        }
    }
    
    private function sendSuccess($data)
    {
        rex_response::setStatus(rex_response::HTTP_OK);
        header('Content-Type: application/json');
        rex_response::sendContent(json_encode($data));
        exit();
    }
    
    private function sendError($message)
    {
        rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
        header('Content-Type: application/json');
        rex_response::sendContent(json_encode(['error' => $message]));
        exit();
    }
}
