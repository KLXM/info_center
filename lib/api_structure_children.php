<?php

class rex_api_info_center_structure_children extends rex_api_function
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

        $categoryId = rex_request('category_id', 'int', 0);
        $clangId = rex_request('clang', 'int', rex_clang::getCurrentId());
        
        try {
            $user = rex::getUser();
            
            // Check permission
            if (!$user->getComplexPerm('structure')->hasCategoryPerm($categoryId)) {
                rex_response::sendJson(['success' => false, 'error' => 'No permission']);
                exit;
            }
            
            $category = rex_category::get($categoryId, $clangId);
            if (!$category) {
                rex_response::sendJson(['success' => false, 'error' => 'Category not found']);
                exit;
            }
            
            $children = [];
            
            // Get subcategories
            $subcategories = $category->getChildren(false);
            foreach ($subcategories as $subcat) {
                if ($user->getComplexPerm('structure')->hasCategoryPerm($subcat->getId())) {
                    $children[] = $this->buildCategoryData($subcat, $clangId);
                }
            }
            
            // Get articles in this category
            $articles = $category->getArticles(false);
            foreach ($articles as $article) {
                $isStartArticleOfSubcategory = rex_category::get($article->getId(), $clangId) !== null;
                
                if (!$isStartArticleOfSubcategory) {
                    $children[] = $this->buildArticleData($article, $categoryId, $clangId);
                }
            }
            
            rex_response::sendJson([
                'success' => true,
                'children' => $children
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
    
    private function buildCategoryData(rex_category $category, int $clangId): array
    {
        $categoryId = $category->getId();
        $url = rex_url::backendPage('structure', [
            'category_id' => $categoryId,
            'article_id' => $categoryId,
            'clang' => $clangId
        ]);
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
        
        $editUrl = rex_url::backendPage('content/edit', [
            'category_id' => $categoryId,
            'article_id' => $categoryId,
            'clang' => $clangId,
            'mode' => 'edit'
        ]);
        $editUrl = html_entity_decode($editUrl, ENT_QUOTES | ENT_HTML5);
        
        // Frontend URL
        $viewUrl = '';
        if (rex_addon::get('yrewrite')->isAvailable()) {
            $viewUrl = rex_yrewrite::getFullUrlByArticleId($categoryId, $clangId);
        } else {
            $viewUrl = rex_getUrl($categoryId, $clangId);
        }
        
        // Check if category has children
        $hasChildren = count($category->getChildren(false)) > 0 || count($category->getArticles(false)) > 0;
        
        // Domain info
        $domain = '';
        if (rex_addon::get('yrewrite')->isAvailable()) {
            $yrewriteDomain = rex_yrewrite::getDomainByArticleId($categoryId);
            if ($yrewriteDomain) {
                $domain = $yrewriteDomain->getName();
            }
        }
        
        return [
            'type' => 'category',
            'id' => $categoryId,
            'name' => rex_escape($category->getName()),
            'status' => $category->getValue('status'),
            'url' => $url,
            'editUrl' => $editUrl,
            'viewUrl' => $viewUrl,
            'domain' => $domain,
            'hasChildren' => $hasChildren
        ];
    }
    
    private function buildArticleData(rex_article $article, int $categoryId, int $clangId): array
    {
        $articleId = $article->getId();
        
        $articleUrl = rex_url::backendPage('content/edit', [
            'category_id' => $categoryId,
            'article_id' => $articleId,
            'clang' => $clangId,
            'mode' => 'edit'
        ]);
        $articleUrl = html_entity_decode($articleUrl, ENT_QUOTES | ENT_HTML5);
        
        // Frontend URL
        $viewUrl = '';
        if (rex_addon::get('yrewrite')->isAvailable()) {
            $viewUrl = rex_yrewrite::getFullUrlByArticleId($articleId, $clangId);
        } else {
            $viewUrl = rex_getUrl($articleId, $clangId);
        }
        
        return [
            'type' => 'article',
            'id' => $articleId,
            'name' => rex_escape($article->getName()),
            'status' => $article->getValue('status'),
            'url' => $articleUrl,
            'viewUrl' => $viewUrl
        ];
    }
}
