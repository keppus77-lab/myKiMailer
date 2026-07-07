<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\ImapSearchQueryRepositoryInterface;
use Psr\Log\LoggerInterface;

class UpdateImapSearchQueryUseCase
{
    private ImapSearchQueryRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        ImapSearchQueryRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function execute(
        int $id,
        int $userId,
        string $name,
        array $criteria,
        string $criteriaString,
        ?string $description = null
    ): bool {
        // Query laden
        $query = $this->repository->findByIdAndUserId($id, $userId);
        
        if (!$query) {
            throw new \InvalidArgumentException('Query nicht gefunden');
        }

        // Validierung
        $this->validateBusinessRules($name, $criteria, $criteriaString);

        // Update durchführen
        $query->update(
            trim($name),
            $criteria,
            trim($criteriaString),
            $description ? trim($description) : null
        );

        $success = $this->repository->update($query);

        $this->logger->info('IMAP Search Query aktualisiert', [
            'id' => $id,
            'user_id' => $userId
        ]);

        return $success;
    }

    private function validateBusinessRules(string $name, array $criteria, string $criteriaString): void
    {
        if (strlen(trim($name)) < 3) {
            throw new \InvalidArgumentException('Name muss mindestens 3 Zeichen lang sein');
        }

        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Name darf maximal 255 Zeichen lang sein');
        }

        if (empty($criteria)) {
            throw new \InvalidArgumentException('Criteria darf nicht leer sein');
        }

        if (strlen(trim($criteriaString)) < 1) {
            throw new \InvalidArgumentException('Criteria String darf nicht leer sein');
        }
    }
}