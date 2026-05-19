<?php

declare(strict_types=1);

namespace App\Application\Controllers;


use mysqli;
use Psr\Log\LoggerInterface;

class ImapSearchController
{
    private LoginController $cl;
    private LoggerInterface $logger;
    private Mysqli $mysqli;
    
    public function __construct( LoginController $cl, LoggerInterface $logger)
    {
        $this->mysqli = $cl->getConnection();
        $this->logger = $logger;
    }
    
    /**
     * Speichert eine neue Search Query
     */
    public function saveQuery(int $userId, string $name, array $criteria, string $criteriaString, ?string $description = null): int
    {
        try {
            $stmt = $this->mysqli->prepare('
                INSERT INTO imap_search_queries 
                (user_id, name, description, criteria, criteria_string) 
                VALUES (?, ?, ?, ?, ?)
            ');
            
          $criteriaJson = json_encode($criteria);

            // Parameter binden: integer, string, string, string, string
            $stmt->bind_param('issss', $userId, $name, $description, $criteriaJson, $criteriaString);

            // Ausführen
            $stmt->execute();

            // Insert-ID holen (bei MySQLi: insert_id statt lastInsertId)
            $id = (int) $stmt->insert_id;
            
            $this->logger->info('IMAP Query gespeichert', [
                'id' => $id,
                'user_id' => $userId,
                'name' => $name
            ]);
            
            return $id;
            
        } catch (\mysqli_sql_exception $e) {
            $this->logger->error('Fehler beim Speichern der IMAP Query', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Aktualisiert eine bestehende Query
     */
    public function updateQuery(int $id, int $userId, string $name, array $criteria, string $criteriaString, ?string $description = null): bool
    {
        try {
            $stmt = $this->mysqli->prepare('
                UPDATE imap_search_queries 
                SET name = ?, description = ?, criteria = ?, criteria_string = ?
                WHERE id = ? AND user_id = ?
            ');
            $criteriaJson = json_encode($criteria);

            $stmt->bind_param('ssssii', $name, $description, $criteriaJson, $criteriaString, $id, $userId);

            // Ausführen
            $result = $stmt->execute();

            // Betroffene Zeilen
            $affectedRows = $stmt->affected_rows;
            
            $this->logger->info('IMAP Query aktualisiert', [
                'id' => $id,
                'user_id' => $userId,
                'affected_rows' => $affectedRows
            ]);
            
            return $result;
            
        } catch (\PDOException $e) {
            $this->logger->error('Fehler beim Aktualisieren der IMAP Query', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Lädt eine Query nach ID
     */
    public function getQuery( int $id, int $userId): ?array
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT * FROM imap_search_queries 
                WHERE id = ? AND user_id = ? AND is_active = 1
            ');
            
            $stmt->bind_param('ii', $id, $userId);

            // Query ausführen
            $stmt->execute();

            // Ergebnis holen
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            if ($result) {
                $data['criteria'] = json_decode($data['criteria'], true);
            }
            
            return $data ?: null;
            
        } catch (\PDOException $e) {
            $this->logger->error('Fehler beim Laden der IMAP Query', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Lädt alle Queries eines Users
     */
    public function getUserQueries( int $userId): array
    {
        try {
            $stmt = $this->mysqli->prepare('
                SELECT id, name, description, criteria_string, created_at, updated_at 
                FROM imap_search_queries 
                WHERE user_id = ? AND is_active = 1
                ORDER BY updated_at DESC
            ');
            
            
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();

// Alle Zeilen als Array holen

            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (\PDOException $e) {
            $this->logger->error('Fehler beim Laden der User Queries', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Löscht eine Query (Soft-Delete)
     */
    public function deleteQuery(int $id, int $userId): bool
    {
        try {
            $stmt = $this->mysqli->prepare('
                UPDATE imap_search_queries 
                SET is_active = 0 
                WHERE id = ? AND user_id = ?
            ');
            $stmt->bind_param('ii', $id, $userId);

            // Ausführen
            $result = $stmt->execute();
           $stmt->bind_param('ii', $id, $userId);

        // Ausführen
        $result = $stmt->execute();
         $affectedRows = $stmt->affected_rows;   
            $this->logger->info('IMAP Query gelöscht', [
                'id' => $id,
                'user_id' => $userId,
                'affected_rows' => $affectedRows
            ]);
            
            return $result;
            
        } catch (\PDOException $e) {
            $this->logger->error('Fehler beim Löschen der IMAP Query', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
