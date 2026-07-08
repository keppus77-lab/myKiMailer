<?php

declare(strict_types=1);

namespace MainApp\Infrastructure\Persistence;

use MainApp\Domain\ImapSearchQuery;
use MainApp\Domain\ImapSearchQueryRepositoryInterface;

class MySQLImapSearchQueryRepository implements ImapSearchQueryRepositoryInterface
{
    private \mysqli $mysqli;

    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function save(ImapSearchQuery $query): int
    {
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO imap_search_queries 
                (user_id, name, description, criteria, criteria_string, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $userId = $query->getUserId();
            $name = $query->getName();
            $description = $query->getDescription();
            $criteriaJson = json_encode($query->getCriteria(), JSON_UNESCAPED_UNICODE);
            $criteriaString = $query->getCriteriaString();

            $stmt->bind_param(
                'issss',
                $userId,
                $name,
                $description,
                $criteriaJson,
                $criteriaString
            );

            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $id = (int)$stmt->insert_id;
            $stmt->close();

            return $id;

        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Fehler beim Speichern in der Datenbank: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(ImapSearchQuery $query): bool
    {
        try {
            $stmt = $this->mysqli->prepare('
                UPDATE imap_search_queries 
                SET name = ?, description = ?, criteria = ?, criteria_string = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $name = $query->getName();
            $description = $query->getDescription();
            $criteriaJson = json_encode($query->getCriteria(), JSON_UNESCAPED_UNICODE);
            $criteriaString = $query->getCriteriaString();
            $id = $query->getId();
            $userId = $query->getUserId();

            $stmt->bind_param(
                'ssssii',
                $name,
                $description,
                $criteriaJson,
                $criteriaString,
                $id,
                $userId
            );

            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            return $affectedRows > 0;

        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Fehler beim Aktualisieren in der Datenbank: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByIdAndUserId(int $id, int $userId): ?ImapSearchQuery
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT * FROM imap_search_queries 
                WHERE id = ? AND user_id = ? AND is_active = 1
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('ii', $id, $userId);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data ? ImapSearchQuery::fromDatabase($data) : null;

        } catch (\mysqli_sql_exception $e) {
            return null;
        }
    }

    public function findById(int $id): ?ImapSearchQuery
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT * FROM imap_search_queries 
                WHERE id = ?
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data ? ImapSearchQuery::fromDatabase($data) : null;

        } catch (\mysqli_sql_exception $e) {
            return null;
        }
    }

    public function findAllByUserId(int $userId, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = '
                SELECT * FROM imap_search_queries 
                WHERE user_id = ? AND is_active = 1
                ORDER BY updated_at DESC
            ';

            $params = [$userId];
            $types = 'i';

            if ($limit !== null) {
                $sql .= ' LIMIT ?';
                $params[] = $limit;
                $types .= 'i';
                
                if ($offset !== null) {
                    $sql .= ' OFFSET ?';
                    $params[] = $offset;
                    $types .= 'i';
                }
            }

            $stmt = $this->mysqli->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $queries = [];

            while ($row = $result->fetch_assoc()) {
                $queries[] = ImapSearchQuery::fromDatabase($row);
            }

            $stmt->close();

            return $queries;

        } catch (\mysqli_sql_exception $e) {
            return [];
        }
    }

    public function findByNamePattern(int $userId, string $searchTerm): array
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT * FROM imap_search_queries 
                WHERE user_id = ? AND is_active = 1 AND name LIKE ?
                ORDER BY name ASC
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $searchPattern = '%' . $searchTerm . '%';
            $stmt->bind_param('is', $userId, $searchPattern);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $queries = [];

            while ($row = $result->fetch_assoc()) {
                $queries[] = ImapSearchQuery::fromDatabase($row);
            }

            $stmt->close();

            return $queries;

        } catch (\mysqli_sql_exception $e) {
            return [];
        }
    }

    public function existsByName(int $userId, string $name, ?int $excludeId = null): bool
    {
        try {
            if ($excludeId !== null) {
                $stmt = $this->mysqli->prepare('
                    SELECT COUNT(*) as count FROM imap_search_queries 
                    WHERE user_id = ? AND name = ? AND is_active = 1 AND id != ?
                ');
                
                if (!$stmt) {
                    throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
                }
                
                $stmt->bind_param('isi', $userId, $name, $excludeId);
            } else {
                $stmt = $this->mysqli->prepare('
                    SELECT COUNT(*) as count FROM imap_search_queries 
                    WHERE user_id = ? AND name = ? AND is_active = 1
                ');
                
                if (!$stmt) {
                    throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
                }
                
                $stmt->bind_param('is', $userId, $name);
            }

            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return ($data['count'] ?? 0) > 0;

        } catch (\mysqli_sql_exception $e) {
            return false;
        }
    }

    public function delete(int $id, int $userId): bool
    {
        try {
            $stmt = $this->mysqli->prepare('
                UPDATE imap_search_queries 
                SET is_active = 0, updated_at = NOW() 
                WHERE id = ? AND user_id = ?
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('ii', $id, $userId);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            return $affectedRows > 0;

        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Fehler beim Löschen: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hardDelete(int $id, int $userId): bool
    {
        try {
            $stmt = $this->mysqli->prepare('
                DELETE FROM imap_search_queries 
                WHERE id = ? AND user_id = ?
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('ii', $id, $userId);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            return $affectedRows > 0;

        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Fehler beim permanenten Löschen: ' . $e->getMessage(), 0, $e);
        }
    }

    public function restore(int $id, int $userId): bool
    {
        try {
            $stmt = $this->mysqli->prepare('
                UPDATE imap_search_queries 
                SET is_active = 1, updated_at = NOW() 
                WHERE id = ? AND user_id = ?
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('ii', $id, $userId);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            return $affectedRows > 0;

        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Fehler beim Wiederherstellen: ' . $e->getMessage(), 0, $e);
        }
    }

    public function countByUserId(int $userId): int
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT COUNT(*) as count FROM imap_search_queries 
                WHERE user_id = ? AND is_active = 1
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('i', $userId);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return (int)($data['count'] ?? 0);

        } catch (\mysqli_sql_exception $e) {
            return 0;
        }
    }

    public function findRecentByUserId(int $userId, int $limit = 10): array
    {
        return $this->findAllByUserId($userId, $limit);
    }

    public function findDeletedByUserId(int $userId): array
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT * FROM imap_search_queries 
                WHERE user_id = ? AND is_active = 0
                ORDER BY updated_at DESC
            ');

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param('i', $userId);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $queries = [];

            while ($row = $result->fetch_assoc()) {
                $queries[] = ImapSearchQuery::fromDatabase($row);
            }

            $stmt->close();

            return $queries;

        } catch (\mysqli_sql_exception $e) {
            return [];
        }
    }

    public function batchDelete(array $ids, int $userId): int
    {
        if (empty($ids)) {
            return 0;
        }

        try {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $sql = "
                UPDATE imap_search_queries 
                SET is_active = 0, updated_at = NOW() 
                WHERE user_id = ? AND id IN ($placeholders)
            ";

            $stmt = $this->mysqli->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('Prepare failed: ' . $this->mysqli->error);
            }

            $types = 'i' . str_repeat('i', count($ids));
            $params = array_merge([$userId], $ids);
            
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new \RuntimeException('Execute failed: ' . $stmt->error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            return $affectedRows;

        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException('Fehler beim Batch-Delete: ' . $e->getMessage(), 0, $e);
        }
    }

    public function duplicate(int $sourceId, int $userId, string $newName): int
    {
        try {
            $source = $this->findByIdAndUserId($sourceId, $userId);
            
            if (!$source) {
                throw new \InvalidArgumentException('Source Query nicht gefunden oder keine Berechtigung');
            }

            $newQuery = ImapSearchQuery::create(
                userId: $userId,
                name: $newName,
                criteria: $source->getCriteria(),
                criteriaString: $source->getCriteriaString(),
                description: $source->getDescription() ? $source->getDescription() . ' (Kopie)' : null
            );

            return $this->save($newQuery);

        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException('Fehler beim Duplizieren: ' . $e->getMessage(), 0, $e);
        }
    }
}