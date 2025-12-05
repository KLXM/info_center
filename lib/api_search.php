<?php

use rex;
use rex_api_function;
use rex_article;
use rex_category;
use rex_clang;
use rex_extension;
use rex_i18n;
use rex_media;
use rex_module;
use rex_path;
use rex_request;
use rex_response;
use rex_sql;
use rex_template;
use rex_user;
use rex_url;

class rex_api_info_center_search extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        rex_response::cleanOutputBuffers();

        $user = rex::getUser();
        if (!$user) {
            rex_response::sendJson(['error' => 'Unauthorized'], 401);
            exit;
        }

        $query = trim(rex_request('query', 'string', ''));
        $types = rex_request('types', 'array', ['articles', 'categories', 'modules', 'templates', 'media']);
        
        if (!is_array($types)) {
            $types = ['articles', 'categories', 'modules', 'templates', 'media'];
        }

        if (strlen($query) < 2) {
            rex_response::sendJson(['results' => []]);
            exit;
        }

        $results = [];

        // Search Articles
        if (in_array('articles', $types) && $user->getComplexPerm('clang')) {
            $results['articles'] = $this->searchArticles($query, $user);
        }

        // Search Categories
        if (in_array('categories', $types) && $user->getComplexPerm('clang')) {
            $results['categories'] = $this->searchCategories($query, $user);
        }

        // Search Modules
        if (in_array('modules', $types) && $user->hasPerm('module[]')) {
            $results['modules'] = $this->searchModules($query, $user);
        }

        // Search Templates
        if (in_array('templates', $types) && $user->hasPerm('template[]')) {
            $results['templates'] = $this->searchTemplates($query, $user);
        }

        // Search Media
        if (in_array('media', $types) && $user->getComplexPerm('media')) {
            $results['media'] = $this->searchMedia($query, $user);
        }

        // Allow addons to register custom search providers
        $results = rex_extension::registerPoint(new \rex_extension_point(
            'INFO_CENTER_SEARCH',
            $results,
            ['query' => $query, 'user' => $user]
        ));

        rex_response::sendJson(['results' => $results]);
        exit;
    }

    protected function searchArticles(string $query, rex_user $user): array
    {
        $results = [];
        $clangs = $user->getComplexPerm('clang')->getClangs();
        
        foreach ($clangs as $clangId) {
            $sql = rex_sql::factory();
            
            // @psalm-suppress TaintedSql - User input is properly escaped by rex_sql
            $sql->setQuery('
                SELECT DISTINCT
                    a.id, 
                    a.clang_id,
                    a.parent_id,
                    a.name,
                    a.path,
                    a.updateuser,
                    a.updatedate,
                    a.status
                FROM ' . rex::getTable('article') . ' a
                LEFT JOIN ' . rex::getTable('article_slice') . ' s ON a.id = s.article_id AND a.clang_id = s.clang_id
                WHERE a.clang_id = :clang_id
                AND (
                    a.name LIKE :query
                    OR a.id LIKE :query_exact
                    OR CONCAT_WS(" ", s.value1, s.value2, s.value3, s.value4, s.value5, s.value6, s.value7, s.value8, s.value9, s.value10, s.value11, s.value12, s.value13, s.value14, s.value15, s.value16, s.value17, s.value18, s.value19, s.value20) LIKE :query
                )
                AND a.startarticle = 0
                ORDER BY a.name ASC
                LIMIT 20
            ', [
                'clang_id' => $clangId,
                'query' => '%' . $query . '%',
                'query_exact' => $query . '%'
            ]);

            while ($sql->hasNext()) {
                $article = rex_article::get($sql->getValue('id'), $sql->getValue('clang_id'));
                
                if ($article && $user->getComplexPerm('structure')->hasCategoryPerm($article->getCategoryId())) {
                    $clang = rex_clang::get($sql->getValue('clang_id'));
                    $path = $this->buildPath($sql->getValue('path'), $sql->getValue('clang_id'));
                    
                    $results[] = [
                        'id' => $sql->getValue('id'),
                        'clang_id' => $sql->getValue('clang_id'),
                        'clang_name' => $clang ? $clang->getName() : '',
                        'name' => $sql->getValue('name'),
                        'path' => $path,
                        'updateuser' => $sql->getValue('updateuser'),
                        'updatedate' => $sql->getValue('updatedate'),
                        'status' => $sql->getValue('status'),
                        'url_backend' => rex_url::backendPage('content/edit', [
                            'article_id' => $sql->getValue('id'),
                            'clang' => $sql->getValue('clang_id'),
                            'mode' => 'edit'
                        ]),
                        'url_frontend' => $article ? $article->getUrl() : ''
                    ];
                }
                
                $sql->next();
            }
        }

        return $results;
    }

    protected function searchCategories(string $query, rex_user $user): array
    {
        $results = [];
        $clangs = $user->getComplexPerm('clang')->getClangs();
        
        foreach ($clangs as $clangId) {
            $sql = rex_sql::factory();
            // @psalm-suppress TaintedSql - User input is properly escaped by rex_sql
            $sql->setQuery('
                SELECT 
                    id, 
                    clang_id,
                    parent_id,
                    name,
                    path,
                    updateuser,
                    updatedate,
                    status
                FROM ' . rex::getTable('article') . '
                WHERE clang_id = :clang_id
                AND (
                    name LIKE :query
                    OR id LIKE :query_exact
                )
                AND startarticle = 1
                ORDER BY name ASC
                LIMIT 20
            ', [
                'clang_id' => $clangId,
                'query' => '%' . $query . '%',
                'query_exact' => $query . '%'
            ]);

            while ($sql->hasNext()) {
                $category = rex_category::get($sql->getValue('id'), $sql->getValue('clang_id'));
                
                if ($category && $user->getComplexPerm('structure')->hasCategoryPerm($category->getId())) {
                    $clang = rex_clang::get($sql->getValue('clang_id'));
                    $path = $this->buildPath($sql->getValue('path'), $sql->getValue('clang_id'));
                    
                    $results[] = [
                        'id' => $sql->getValue('id'),
                        'clang_id' => $sql->getValue('clang_id'),
                        'clang_name' => $clang ? $clang->getName() : '',
                        'name' => $sql->getValue('name'),
                        'path' => $path,
                        'updateuser' => $sql->getValue('updateuser'),
                        'updatedate' => $sql->getValue('updatedate'),
                        'status' => $sql->getValue('status'),
                        'url_backend' => rex_url::backendPage('structure', [
                            'category_id' => $sql->getValue('id'),
                            'clang' => $sql->getValue('clang_id')
                        ]),
                        'url_frontend' => $category ? $category->getUrl() : ''
                    ];
                }
                
                $sql->next();
            }
        }

        return $results;
    }

    protected function searchModules(string $query, rex_user $user): array
    {
        $results = [];
        
        $sql = rex_sql::factory();
        // @psalm-suppress TaintedSql - User input is properly escaped by rex_sql
        $sql->setQuery('
            SELECT 
                id,
                name,
                input,
                output,
                updateuser,
                updatedate
            FROM ' . rex::getTable('module') . '
            WHERE 
                name LIKE :query
                OR input LIKE :query
                OR output LIKE :query
                OR id LIKE :query_exact
            ORDER BY name ASC
            LIMIT 10
        ', [
            'query' => '%' . $query . '%',
            'query_exact' => $query . '%'
        ]);

        while ($sql->hasNext()) {
            $input = $sql->getValue('input');
            $output = $sql->getValue('output');
            
            // Find code snippet with match
            $snippet = $this->findCodeSnippet($query, $input, $output);
            
            $results[] = [
                'id' => $sql->getValue('id'),
                'name' => $sql->getValue('name'),
                'updateuser' => $sql->getValue('updateuser'),
                'updatedate' => $sql->getValue('updatedate'),
                'code_snippet' => $snippet,
                'url_backend' => rex_url::backendPage('modules/modules', [
                    'module_id' => $sql->getValue('id'),
                    'function' => 'edit'
                ])
            ];
            
            $sql->next();
        }

        return $results;
    }

    protected function searchTemplates(string $query, rex_user $user): array
    {
        $results = [];
        
        $sql = rex_sql::factory();
        // @psalm-suppress TaintedSql - User input is properly escaped by rex_sql
        $sql->setQuery('
            SELECT 
                id,
                name,
                content,
                updateuser,
                updatedate,
                active
            FROM ' . rex::getTable('template') . '
            WHERE 
                name LIKE :query
                OR content LIKE :query
                OR id LIKE :query_exact
            ORDER BY name ASC
            LIMIT 10
        ', [
            'query' => '%' . $query . '%',
            'query_exact' => $query . '%'
        ]);

        while ($sql->hasNext()) {
            $content = $sql->getValue('content');
            
            // Find code snippet with match
            $snippet = $this->findCodeSnippet($query, $content);
            
            $results[] = [
                'id' => $sql->getValue('id'),
                'name' => $sql->getValue('name'),
                'active' => $sql->getValue('active'),
                'updateuser' => $sql->getValue('updateuser'),
                'updatedate' => $sql->getValue('updatedate'),
                'code_snippet' => $snippet,
                'url_backend' => rex_url::backendPage('templates', [
                    'template_id' => $sql->getValue('id'),
                    'function' => 'edit'
                ])
            ];
            
            $sql->next();
        }

        return $results;
    }

    protected function searchMedia(string $query, rex_user $user): array
    {
        $results = [];
        $mediapool = $user->getComplexPerm('media');
        
        $sql = rex_sql::factory();
        // @psalm-suppress TaintedSql - User input is properly escaped by rex_sql
        $sql->setQuery('
            SELECT 
                id,
                filename,
                title,
                category_id,
                filetype,
                filesize,
                updateuser,
                updatedate
            FROM ' . rex::getTable('media') . '
            WHERE 
                filename LIKE :query
                OR title LIKE :query
                OR originalname LIKE :query
            ORDER BY filename ASC
            LIMIT 20
        ', [
            'query' => '%' . $query . '%'
        ]);

        while ($sql->hasNext()) {
            $categoryId = $sql->getValue('category_id');
            
            // Check media permissions
            if ($mediapool && $mediapool->hasCategoryPerm($categoryId)) {
                $media = rex_media::get($sql->getValue('filename'));
                
                if ($media) {
                    $results[] = [
                        'id' => $sql->getValue('id'),
                        'filename' => $sql->getValue('filename'),
                        'title' => $sql->getValue('title') ?: $sql->getValue('filename'),
                        'category_id' => $categoryId,
                        'filetype' => $sql->getValue('filetype'),
                        'filesize' => $sql->getValue('filesize'),
                        'updateuser' => $sql->getValue('updateuser'),
                        'updatedate' => $sql->getValue('updatedate'),
                        'url_backend' => rex_url::backendPage('mediapool/media', [
                            'file_id' => $sql->getValue('id')
                        ]),
                        'url_media' => $media->getUrl()
                    ];
                }
            }
            
            $sql->next();
        }

        return $results;
    }

    protected function buildPath(string $path, int $clangId): string
    {
        if (!$path || $path === '|') {
            return '';
        }

        $pathNames = [];
        $pathIds = array_filter(explode('|', $path));

        foreach ($pathIds as $id) {
            $cat = rex_category::get((int)$id, $clangId);
            if ($cat) {
                $pathNames[] = $cat->getName();
            }
        }

        return implode(' › ', $pathNames);
    }
    
    protected function findCodeSnippet(string $query, string ...$contents): string
    {
        $query = strtolower($query);
        
        foreach ($contents as $content) {
            if (empty($content)) {
                continue;
            }
            
            $lines = explode("\n", $content);
            $lowerContent = strtolower($content);
            
            // Find position of match
            $pos = strpos($lowerContent, $query);
            if ($pos === false) {
                continue;
            }
            
            // Find line number
            $currentPos = 0;
            foreach ($lines as $lineNum => $line) {
                $lineLen = strlen($line) + 1; // +1 for newline
                if ($currentPos + $lineLen > $pos) {
                    // Found the line with the match
                    $snippet = [];
                    
                    // Get context: 1 line before and after
                    if ($lineNum > 0) {
                        $snippet[] = trim($lines[$lineNum - 1]);
                    }
                    $snippet[] = trim($line);
                    if ($lineNum < count($lines) - 1) {
                        $snippet[] = trim($lines[$lineNum + 1]);
                    }
                    
                    // Limit length
                    $result = implode(' ', $snippet);
                    if (strlen($result) > 200) {
                        $result = substr($result, 0, 200) . '...';
                    }
                    
                    return $result;
                }
                $currentPos += $lineLen;
            }
        }
        
        return '';
    }
}
