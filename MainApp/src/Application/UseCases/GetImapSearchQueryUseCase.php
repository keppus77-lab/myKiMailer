<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\ImapSearchQuery;
use MainApp\Domain\ImapSearchQueryRepositoryInterface;
use Psr\Log\LoggerInterface;

class GetImapSearchQueryUseCase
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

    public function execute(int $id, int $userId): ?ImapSearchQuery
    {
        return $this->repository->findByIdAndUserId($id, $userId);
    }
}