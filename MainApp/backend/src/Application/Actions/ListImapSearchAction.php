<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\Action;
use MainApp\Application\UseCases\ListImapSearchQueriesUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;

class ListImapSearchAction extends Action
{
    private ListImapSearchQueriesUseCase $useCase;

    public function __construct(
        ListImapSearchQueriesUseCase $useCase,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->useCase = $useCase;
    }

    protected function action(): Response
    {
        $userId = $this->getAuthenticatedUserId();

        $queries = $this->useCase->execute($userId);

        return $this->respondWithData([
            'queries' => array_map(fn($q) => $q->toArray(), $queries),
            'total' => count($queries)
        ]);
    }

    private function getAuthenticatedUserId(): int
    {
        $userId = $this->request->getAttribute('user_id') ?? $_SESSION['userID'] ?? null;
        
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Not authenticated');
        }

        return (int)$userId;
    }
}