<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\Action;
use MainApp\Application\Actions\BaseProtectedAction;

use MainApp\Application\UseCases\ListImapSearchQueriesUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;

class ListImapSearchAction extends BaseProtectedAction
{
    private ListImapSearchQueriesUseCase $useCase;

    public function __construct(
        ListImapSearchQueriesUseCase $useCase,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->useCase = $useCase;
    }

    protected function protectedAction(): Response
    {
        $userId = $this->getCurrentUserId();
error_log("ListImapSearchAction: userId = $userId");
        $queries = $this->useCase->execute($userId);

        return $this->respondWithData([
            'queries' => array_map(fn($q) => $q->toArray(), $queries),
            'total' => count($queries)
        ]);
    }

    
}