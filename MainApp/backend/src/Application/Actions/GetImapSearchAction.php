<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;


use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\GetImapSearchQueryUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class GetImapSearchAction extends BaseProtectedAction
{
    private GetImapSearchQueryUseCase $useCase;

    public function __construct(
        GetImapSearchQueryUseCase $useCase,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->useCase = $useCase;
    }

    protected function protectedAction(): Response
    {
        $userId = $this->getCurrentUserId();
        $id = (int)$this->resolveArg('id');
        $queries = $this->useCase->execute($id, $userId);

        return $this->respondWithData([
            'queries' => array_map(fn($q) => $q->toArray(), $queries? : []),
            'total' => count($queries? : [])
        ]);
    }

    
}