<?php

declare(strict_types=1);

namespace MainApp\Domain;



interface ImapSearchQueryRepositoryInterface
{
    /**
     * Speichert eine neue IMAP Search Query
     * 
     * @param ImapSearchQuery $query Die zu speichernde Query
     * @return int Die ID der gespeicherten Query
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function save(ImapSearchQuery $query): int;

    /**
     * Aktualisiert eine bestehende IMAP Search Query
     * 
     * @param ImapSearchQuery $query Die zu aktualisierende Query (muss ID haben)
     * @return bool True bei Erfolg, False wenn keine Zeile betroffen
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function update(ImapSearchQuery $query): bool;

    /**
     * Findet eine Query anhand ihrer ID und User-ID
     * 
     * @param int $id Die Query-ID
     * @param int $userId Die User-ID (für Zugriffskontrolle)
     * @return ImapSearchQuery|null Die Query oder null wenn nicht gefunden
     */
    public function findByIdAndUserId(int $id, int $userId): ?ImapSearchQuery;

    /**
     * Findet eine Query nur anhand ihrer ID (ohne User-Check)
     * Vorsicht: Nur für Admin-Funktionen verwenden!
     * 
     * @param int $id Die Query-ID
     * @return ImapSearchQuery|null Die Query oder null wenn nicht gefunden
     */
    public function findById(int $id): ?ImapSearchQuery;

    /**
     * Findet alle aktiven Queries eines bestimmten Users
     * 
     * @param int $userId Die User-ID
     * @param int|null $limit Optional: Maximale Anzahl der Ergebnisse
     * @param int|null $offset Optional: Offset für Pagination
     * @return ImapSearchQuery[] Array von Query-Objekten
     */
    public function findAllByUserId(int $userId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Findet Queries nach Name (für Autocomplete/Suche)
     * 
     * @param int $userId Die User-ID
     * @param string $searchTerm Suchbegriff für den Namen
     * @return ImapSearchQuery[] Array von Query-Objekten
     */
    public function findByNamePattern(int $userId, string $searchTerm): array;

    /**
     * Prüft ob eine Query mit diesem Namen bereits existiert (für den User)
     * 
     * @param int $userId Die User-ID
     * @param string $name Der Name der Query
     * @param int|null $excludeId Optional: ID die ausgeschlossen werden soll (für Updates)
     * @return bool True wenn Name existiert, sonst False
     */
    public function existsByName(int $userId, string $name, ?int $excludeId = null): bool;

    /**
     * Löscht eine Query (Soft-Delete)
     * Setzt is_active auf 0
     * 
     * @param int $id Die Query-ID
     * @param int $userId Die User-ID (für Zugriffskontrolle)
     * @return bool True bei Erfolg, False wenn Query nicht gefunden
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function delete(int $id, int $userId): bool;

    /**
     * Löscht eine Query permanent aus der Datenbank (Hard-Delete)
     * Vorsicht: Nicht wiederherstellbar!
     * 
     * @param int $id Die Query-ID
     * @param int $userId Die User-ID (für Zugriffskontrolle)
     * @return bool True bei Erfolg, False wenn Query nicht gefunden
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function hardDelete(int $id, int $userId): bool;

    /**
     * Reaktiviert eine gelöschte Query
     * 
     * @param int $id Die Query-ID
     * @param int $userId Die User-ID (für Zugriffskontrolle)
     * @return bool True bei Erfolg, False wenn Query nicht gefunden
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function restore(int $id, int $userId): bool;

    /**
     * Zählt alle aktiven Queries eines Users
     * 
     * @param int $userId Die User-ID
     * @return int Anzahl der Queries
     */
    public function countByUserId(int $userId): int;

    /**
     * Findet die zuletzt verwendeten/aktualisierten Queries
     * 
     * @param int $userId Die User-ID
     * @param int $limit Maximale Anzahl der Ergebnisse
     * @return ImapSearchQuery[] Array von Query-Objekten
     */
    public function findRecentByUserId(int $userId, int $limit = 10): array;

    /**
     * Findet alle gelöschten (inaktiven) Queries eines Users
     * 
     * @param int $userId Die User-ID
     * @return ImapSearchQuery[] Array von Query-Objekten
     */
    public function findDeletedByUserId(int $userId): array;

    /**
     * Batch-Operation: Löscht mehrere Queries auf einmal
     * 
     * @param array $ids Array von Query-IDs
     * @param int $userId Die User-ID (für Zugriffskontrolle)
     * @return int Anzahl der gelöschten Queries
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function batchDelete(array $ids, int $userId): int;

    /**
     * Duplikiert eine bestehende Query mit neuem Namen
     * 
     * @param int $sourceId Die ID der zu duplizierenden Query
     * @param int $userId Die User-ID
     * @param string $newName Der Name für die Kopie
     * @return int Die ID der neuen Query
     * @throws \InvalidArgumentException wenn Source-Query nicht gefunden
     * @throws \RuntimeException bei Datenbankfehlern
     */
    public function duplicate(int $sourceId, int $userId, string $newName): int;
}