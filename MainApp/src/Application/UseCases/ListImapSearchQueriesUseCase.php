<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\ImapSearchQueryRepositoryInterface;
use Psr\Log\LoggerInterface;

class ListImapSearchQueriesUseCase
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

    public function execute(int $userId): array
    {
        return $this->repository->findAllByUserId($userId);
    }
}