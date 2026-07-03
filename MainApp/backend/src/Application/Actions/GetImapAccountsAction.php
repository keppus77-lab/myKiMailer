<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\GetUserImapAccountsUseCase;
use Psr\Http\Message\ResponseInterface as Response;

class GetImapAccountsAction extends BaseProtectedAction
{
    /**
     * {@inheritdoc}
     */
    protected function protectedAction(): Response
    {        
        // User ist garantiert eingeloggt (BaseProtectedAction prüft das)
        $userId = $this->getCurrentUserId();
        
        // Get accounts via Use Case
        $useCase = $this->container->get(GetUserImapAccountsUseCase::class);
        $accounts = $useCase->execute($userId);
        
        // Return JSON response
        return $this->renderJson([
            'success' => true,
            'accounts' => $accounts,
            'count' => count($accounts)
        ]);
    }

    protected function renderJson(array $data, int $status = 200): Response
    {
        $this->response->getBody()->write(json_encode($data));
        
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}