<?php

declare(strict_types=1);

namespace MainApp\Domain;

class ImapSearchQuery
{
    private ?int $id;
    private int $userId;
    private string $name;
    private ?string $description;
    private array $criteria;
    private string $criteriaString;
    private bool $isActive;
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        int $userId,
        string $name,
        array $criteria,
        string $criteriaString,
        ?string $description = null,
        ?int $id = null,
        bool $isActive = true,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->description = $description;
        $this->criteria = $criteria;
        $this->criteriaString = $criteriaString;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Erstellt eine neue IMAP Search Query
     */
    public static function create(
        int $userId,
        string $name,
        array $criteria,
        string $criteriaString,
        ?string $description = null
    ): self {
        return new self(
            userId: $userId,
            name: $name,
            criteria: $criteria,
            criteriaString: $criteriaString,
            description: $description
        );
    }

    /**
     * Erstellt eine Query aus Datenbank-Daten
     */
    public static function fromDatabase(array $data): self
    {
        return new self(
            userId: (int)$data['user_id'],
            name: $data['name'],
            criteria: self::parseCriteria($data['criteria']),
            criteriaString: $data['criteria_string'],
            description: $data['description'] ?? null,
            id: isset($data['id']) ? (int)$data['id'] : null,
            isActive: isset($data['is_active']) ? (bool)$data['is_active'] : true,
            createdAt: isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );
    }

    /**
     * Erstellt eine Query aus einem Array (z.B. von API)
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['user_id'], $data['name'], $data['criteria'], $data['criteria_string'])) {
            throw new \InvalidArgumentException('Missing required fields for ImapSearchQuery');
        }

        return new self(
            userId: (int)$data['user_id'],
            name: $data['name'],
            criteria: is_array($data['criteria']) ? $data['criteria'] : json_decode($data['criteria'], true),
            criteriaString: $data['criteria_string'],
            description: $data['description'] ?? null,
            id: isset($data['id']) ? (int)$data['id'] : null,
            isActive: $data['is_active'] ?? true,
            createdAt: isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );
    }

    // ===========================================
    // Getters
    // ===========================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getCriteriaString(): string
    {
        return $this->criteriaString;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ===========================================
    // Business Methods
    // ===========================================

    /**
     * Aktualisiert die Query-Daten
     */
    public function update(
        string $name,
        array $criteria,
        string $criteriaString,
        ?string $description = null
    ): void {
        $this->name = $name;
        $this->criteria = $criteria;
        $this->criteriaString = $criteriaString;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Deaktiviert die Query (Soft-Delete)
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Reaktiviert eine deaktivierte Query
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Ändert nur den Namen
     */
    public function rename(string $newName): void
    {
        $this->name = $newName;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Ändert nur die Beschreibung
     */
    public function updateDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Prüft ob die Query einem bestimmten User gehört
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->userId === $userId;
    }

    /**
     * Prüft ob die Query gelöscht ist
     */
    public function isDeleted(): bool
    {
        return !$this->isActive;
    }

    /**
     * Gibt das Alter der Query in Tagen zurück
     */
    public function getAgeInDays(): int
    {
        if (!$this->createdAt) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        $interval = $this->createdAt->diff($now);
        return (int)$interval->days;
    }

    /**
     * Gibt zurück wie lange her das letzte Update war (in Minuten)
     */
    public function getMinutesSinceLastUpdate(): int
    {
        if (!$this->updatedAt) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->updatedAt->getTimestamp();
        return (int)($diff / 60);
    }

    /**
     * Prüft ob ein bestimmtes Criteria-Feld vorhanden ist
     */
    public function hasCriteria(string $key): bool
    {
        return isset($this->criteria[$key]);
    }

    /**
     * Gibt ein bestimmtes Criteria-Feld zurück
     */
    public function getCriteriaValue(string $key, mixed $default = null): mixed
    {
        return $this->criteria[$key] ?? $default;
    }

    /**
     * Zählt die Anzahl der Criteria-Felder
     */
    public function getCriteriaCount(): int
    {
        return count($this->criteria);
    }

    // ===========================================
    // Serialization
    // ===========================================

    /**
     * Konvertiert die Entity zu einem Array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'description' => $this->description,
            'criteria' => $this->criteria,
            'criteria_string' => $this->criteriaString,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Konvertiert die Entity zu einem Array (ohne sensible Daten)
     * Für API-Responses
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'criteria_string' => $this->criteriaString,
            'criteria_count' => $this->getCriteriaCount(),
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('c'), // ISO 8601
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }

    /**
     * Konvertiert die Entity zu einem kompakten Array (für Listen)
     */
    public function toListArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'criteria_string' => $this->criteriaString,
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Konvertiert zu JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Gibt nur die Criteria als JSON zurück
     */
    public function getCriteriaAsJson(): string
    {
        return json_encode($this->criteria);
    }

    // ===========================================
    // Validation
    // ===========================================

    /**
     * Validiert die Entity
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->name))) {
            $errors['name'] = 'Name darf nicht leer sein';
        }

        if (strlen($this->name) < 3) {
            $errors['name'] = 'Name muss mindestens 3 Zeichen lang sein';
        }

        if (strlen($this->name) > 255) {
            $errors['name'] = 'Name darf maximal 255 Zeichen lang sein';
        }

        if (empty($this->criteria)) {
            $errors['criteria'] = 'Criteria darf nicht leer sein';
        }

        if (empty(trim($this->criteriaString))) {
            $errors['criteria_string'] = 'Criteria String darf nicht leer sein';
        }

        if ($this->userId <= 0) {
            $errors['user_id'] = 'Ungültige User ID';
        }

        return $errors;
    }

    /**
     * Prüft ob die Entity valid ist
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    // ===========================================
    // Helper Methods
    // ===========================================

    /**
     * Parst Criteria aus verschiedenen Formaten
     */
    private static function parseCriteria(mixed $criteria): array
    {
        if (is_array($criteria)) {
            return $criteria;
        }

        if (is_string($criteria)) {
            $decoded = json_decode($criteria, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Clone-Methode für Duplikation
     */
    public function __clone()
    {
        $this->id = null;
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    /**
     * String-Repräsentation der Entity
     */
    public function __toString(): string
    {
        return sprintf(
            'ImapSearchQuery #%d: %s (%s)',
            $this->id ?? 0,
            $this->name,
            $this->isActive ? 'active' : 'deleted'
        );
    }

    // ===========================================
    // Comparison Methods
    // ===========================================

    /**
     * Vergleicht zwei Queries auf Gleichheit (nur Inhalt, nicht ID)
     */
    public function equals(ImapSearchQuery $other): bool
    {
        return $this->userId === $other->userId
            && $this->name === $other->name
            && $this->criteria === $other->criteria
            && $this->criteriaString === $other->criteriaString
            && $this->description === $other->description;
    }

    /**
     * Prüft ob die Criteria identisch sind
     */
    public function hasSameCriteria(ImapSearchQuery $other): bool
    {
        return $this->criteria === $other->criteria;
    }

    /**
     * Erstellt eine Kopie mit neuem Namen
     */
    public function duplicate(string $newName): self
    {
        return self::create(
            userId: $this->userId,
            name: $newName,
            criteria: $this->criteria,
            criteriaString: $this->criteriaString,
            description: $this->description ? $this->description . ' (Kopie)' : null
        );
    }
}