<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\Action;
use MainApp\Application\UseCases\DeleteImapSearchQueryUseCase;
use MainApp\Application\Controllers\TokenController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;

class DeleteImapSearchAction extends Action
{
    private DeleteImapSearchQueryUseCase $useCase;
    private TokenController $tokenController;

    public function __construct(
        DeleteImapSearchQueryUseCase $useCase,
        TokenController $tokenController,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->useCase = $useCase;
        $this->tokenController = $tokenController;
    }

    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');
        $data = $this->getFormData();

        // CSRF-Check
        $this->validateCsrfToken($data);
        $userId = $this->getAuthenticatedUserId();

        $success = $this->useCase->execute($id, $userId);

        return $this->respondWithData([
            'success' => $success,
            'message' => $success ? 'Query deleted successfully' : 'Query not found'
        ]);
    }

    private function validateCsrfToken(array $data): void
    {
        if (!isset($data['csrf_token']) || !$this->tokenController->validateToken($data['csrf_token'])) {
            throw new HttpForbiddenException($this->request, 'Invalid CSRF token');
        }
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