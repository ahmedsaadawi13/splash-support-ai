<?php
// FILE: /app/models/KBArticle.php

/**
 * SplashSupportAI - Knowledge Base Article Model
 */

class KBArticle extends Model {
    protected $table = 'kb_articles';

    public function findBySlug($tenantId, $slug) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':slug', $slug);
        return $this->db->single();
    }

    public function getPublishedArticles($tenantId, $categoryId = null, $limit = null, $offset = 0) {
        $where = "tenant_id = :tenant_id AND is_public = 1 AND published_at IS NOT NULL";
        $params = array(':tenant_id' => $tenantId);

        if ($categoryId) {
            $where .= " AND category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        }

        return $this->db->all();
    }

    public function search($tenantId, $query, $limit = 10) {
        $sql = "SELECT * FROM {$this->table}
                WHERE tenant_id = :tenant_id
                AND is_public = 1
                AND published_at IS NOT NULL
                AND (title LIKE :query OR content_text LIKE :query)
                ORDER BY view_count DESC
                LIMIT :limit";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':query', '%' . $query . '%');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);

        return $this->db->all();
    }

    public function incrementViewCount($articleId) {
        $this->db->query("UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = :id");
        $this->db->bind(':id', $articleId);
        return $this->db->execute();
    }
}
