<?php

use KLXM\InfoCenter\Widgets\StructureWidget;

class rex_api_info_center_structure extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        rex_response::cleanOutputBuffers();
        
        // Prüfe Backend-User
        if (!rex::getUser()) {
            return new rex_api_result(false, ['error' => 'Unauthorized']);
        }

        try {
            $widget = new StructureWidget();
            $html = $widget->render();
            
            $data = [
                'success' => true,
                'html' => $html,
            ];
            
            rex_response::sendJson($data);
            exit;
        } catch (Exception $e) {
            $data = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            
            rex_response::sendJson($data);
            exit;
        }
    }
}
