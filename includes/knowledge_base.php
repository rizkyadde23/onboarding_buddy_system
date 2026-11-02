<?php
/**
 * Knowledge Base helper — fulltext search against the DB,
 * returns formatted context string for injection into the system prompt.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

/**
 * Search the knowledge_base table using MySQL FULLTEXT search.
 * Falls back to a simple LIKE scan when the query is too short for FT.
 *
 * @param  string $query   User's raw question text
 * @return string          Formatted KB context ready for the system prompt
 */
function kb_retrieve(string $query): string
{
    $pdo    = Database::connect();
    $limit  = KB_MAX_RESULTS;
    $rows   = [];

    $clean = trim($query);

    if (mb_strlen($clean) >= 3) {
        // Try FULLTEXT first
        try {
            $stmt = $pdo->prepare(
                "SELECT title, content, category
                 FROM knowledge_base
                 WHERE is_active = 1
                   AND MATCH(title, content, tags) AGAINST (? IN BOOLEAN MODE)
                 LIMIT ?"
            );
            $stmt->execute([$clean . '*', $limit]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException $e) {
            // FULLTEXT not available — fall through to LIKE
        }
    }

    // Fallback: broad LIKE search on title / tags
    if (empty($rows)) {
        $like = '%' . $clean . '%';
        $stmt = $pdo->prepare(
            "SELECT title, content, category
             FROM knowledge_base
             WHERE is_active = 1
               AND (title LIKE ? OR tags LIKE ?)
             ORDER BY id ASC
             LIMIT ?"
        );
        $stmt->execute([$like, $like, $limit]);
        $rows = $stmt->fetchAll();
    }

    // If still nothing, return all active articles (safety net)
    if (empty($rows)) {
        $stmt = $pdo->prepare(
            "SELECT title, content, category FROM knowledge_base
             WHERE is_active = 1 ORDER BY category, id LIMIT ?"
        );
        $stmt->execute([$limit]);
        $rows = $stmt->fetchAll();
    }

    if (empty($rows)) {
        return '(Tidak ada artikel tersedia di Knowledge Base saat ini.)';
    }

    $parts = [];
    foreach ($rows as $row) {
        $parts[] = sprintf(
            "[%s] **%s**\n%s",
            strtoupper($row['category']),
            $row['title'],
            $row['content']
        );
    }

    return implode("\n\n---\n\n", $parts);
}

/**
 * Return all active KB articles (for admin listing).
 */
function kb_all(): array
{
    $pdo  = Database::connect();
    $stmt = $pdo->query(
        "SELECT id, category, title, tags, is_active, created_at
         FROM knowledge_base ORDER BY category, id"
    );
    return $stmt->fetchAll();
}
