<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\ImapSearchQueryRepositoryInterface;
use Psr\Log\LoggerInterface;

class DeleteImapSearchQueryUseCase
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

    public function execute(int $id, int $userId): bool
    {
        $success = $this->repository->delete($id, $userId);

        if ($success) {
            $this->logger->info('IMAP Search Query gelöscht', [
                'id' => $id,
                'user_id' => $userId
            ]);
        }

        return $success;
    }
}