<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\Action;
use MainApp\Application\UseCases\GetImapSearchQueryUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class GetImapSearchAction extends Action
{
    private GetImapSearchQueryUseCase $useCase;

    public function __construct(
        GetImapSearchQueryUseCase $useCase,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->useCase = $useCase;
    }

    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');
        $userId = $this->getAuthenticatedUserId();

        $query = $this->useCase->execute($id, $userId);

        if (!$query) {
            throw new HttpNotFoundException($this->request, 'Query not found');
        }

        return $this->respondWithData($query->toArray());
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