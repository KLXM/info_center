<?php

use rex_addon;
use rex_api_function;
use rex_request;
use rex_response;
use rex_sql;
use Exception;

class rex_api_widget_builder extends rex_api_function
{
    public function execute()
    {
        // Output buffer leeren und headers saubermachen
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $action = rex_request('action', 'string', '');
        
        switch ($action) {
            case 'get_table_fields':
                $this->getTableFields();
                break;
                
            default:
                $this->sendError('Unknown action');
        }
    }
    
    private function getTableFields()
    {
        $tableName = rex_request('table_name', 'string', '');
        $widgetId = rex_request('widget_id', 'string', '');
        
        if (empty($tableName)) {
            $this->sendError('Table name is required');
            return;
        }
        
        try {
            $addon = rex_addon::get('info_center');
            $customWidgets = $addon->getConfig('custom_widgets', []);
            $selectedFields = [];
            
            // Bei Edit-Modus vorhandene Felder laden
            if ($widgetId && isset($customWidgets[$widgetId])) {
                $selectedFields = $customWidgets[$widgetId]['fields'] ?? [];
            }
            
            $html = $this->renderFieldSelection($tableName, $selectedFields);
            $this->sendSuccess($html);
            
        } catch (Exception $e) {
            $this->sendError('Error loading fields: ' . $e->getMessage());
        }
    }
    
    private function renderFieldSelection($tableName, $selectedFields = [])
    {
        if (!$tableName) {
            return '';
        }
        
        $sql = rex_sql::factory();
        try {
            $fields = $sql->getArray('DESCRIBE `' . $tableName . '`');
        } catch (Exception $e) {
            throw new Exception('Error loading table fields: ' . $e->getMessage());
        }
        
        $html = '<div class="widget-field-selection">';
        $html .= '<p class="help-block">Wählen Sie die Felder aus, die im Widget angezeigt werden sollen:</p>';
        
        foreach ($fields as $field) {
            $fieldName = $field['Field'];
            $fieldType = $field['Type'];
            $isChecked = in_array($fieldName, $selectedFields) ? ' checked' : '';
            
            // System-Felder markieren
            $isSystemField = in_array($fieldName, ['id', 'createdate', 'updatedate', 'createuser', 'updateuser', 'prio']);
            $fieldLabel = $fieldName . ($isSystemField ? ' <small class="text-muted">(System)</small>' : '');
            
            $html .= '<div class="checkbox">';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="fields[]" value="' . htmlspecialchars($fieldName) . '"' . $isChecked . '> ';
            $html .= $fieldLabel . ' <small class="text-muted">(' . $fieldType . ')</small>';
            $html .= '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function sendSuccess($data)
    {
        rex_response::setStatus(rex_response::HTTP_OK);
        rex_response::sendContent($data);
        exit();
    }
    
    private function sendError($message)
    {
        rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
        rex_response::sendContent('<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>');
        exit();
    }
}
