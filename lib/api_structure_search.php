<?php

class rex_api_info_center_structure_search extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        rex_response::cleanOutputBuffers();
        
        // Check backend user
        if (!rex::getUser()) {
            rex_response::sendJson(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $query = rex_request('query', 'string', '');
        $clangId = rex_request('clang', 'int', rex_clang::getCurrentId());
        $selectedDomain = rex_request('domain', 'int', 0);
        
        if (strlen($query) < 2) {
            rex_response::sendJson(['success' => true, 'matches' => []]);
            exit;
        }
        
        try {
            $user = rex::getUser();
            $matches = [];
            
            // Search categories
            $sql = rex_sql::factory();
            $sql->setQuery('
                SELECT id, catname as name, status, path
                FROM ' . rex::getTable('article') . '
                WHERE startarticle = 1
                AND clang_id = :clang_id
                AND (catname LIKE :query OR id LIKE :queryId)
                ORDER BY catname
            ', [
                'clang_id' => $clangId,
                'query' => '%' . $query . '%',
                'queryId' => '%' . $query . '%'
            ]);
            
            while ($sql->hasNext()) {
                $categoryId = $sql->getValue('id');
                
                // Check permissions
                if (!$user->getComplexPerm('structure')->hasCategoryPerm($categoryId)) {
                    $sql->next();
                    continue;
                }
                
                // Check domain filter
                if ($selectedDomain > 0 && rex_addon::get('yrewrite')->isAvailable()) {
                    $domain = rex_yrewrite::getDomainByArticleId($categoryId);
                    if (!$domain || $domain->getId() != $selectedDomain) {
                        $sql->next();
                        continue;
                    }
                }
                
                $matches[] = [
                    'type' => 'category',
                    'id' => $categoryId,
                    'name' => $sql->getValue('name'),
                    'status' => $sql->getValue('status'),
                    'path' => $sql->getValue('path')
                ];
                
                $sql->next();
            }
            
            // Search articles (non-start articles)
            $sql = rex_sql::factory();
            $sql->setQuery('
                SELECT id, name, status, catname, path, parent_id
                FROM ' . rex::getTable('article') . '
                WHERE startarticle = 0
                AND clang_id = :clang_id
                AND (name LIKE :query OR id LIKE :queryId)
                ORDER BY name
            ', [
                'clang_id' => $clangId,
                'query' => '%' . $query . '%',
                'queryId' => '%' . $query . '%'
            ]);
            
            while ($sql->hasNext()) {
                $articleId = $sql->getValue('id');
                $parentId = $sql->getValue('parent_id');
                
                // Check permissions via parent category
                if ($parentId > 0) {
                    if (!$user->getComplexPerm('structure')->hasCategoryPerm($parentId)) {
                        $sql->next();
                        continue;
                    }
                }
                
                // Check domain filter
                if ($selectedDomain > 0 && rex_addon::get('yrewrite')->isAvailable()) {
                    $domain = rex_yrewrite::getDomainByArticleId($articleId);
                    if (!$domain || $domain->getId() != $selectedDomain) {
                        $sql->next();
                        continue;
                    }
                }
                
                $matches[] = [
                    'type' => 'article',
                    'id' => $articleId,
                    'name' => $sql->getValue('name'),
                    'status' => $sql->getValue('status'),
                    'path' => $sql->getValue('path'),
                    'parent_id' => $parentId
                ];
                
                $sql->next();
            }
            
            rex_response::sendJson([
                'success' => true,
                'matches' => $matches
            ]);
            exit;
            
        } catch (Exception $e) {
            rex_response::sendJson([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
}
