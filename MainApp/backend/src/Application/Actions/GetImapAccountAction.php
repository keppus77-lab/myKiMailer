<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\GetUserImapAccountUseCase;
use Psr\Http\Message\ResponseInterface as Response;

class GetImapAccountAction extends BaseProtectedAction
{
    /**
     * {@inheritdoc}
     */
    protected function protectedAction(): Response
    {        
        $accountId = (int)$this->resolveArg('id');
        
        if ($accountId <= 0) {
            return $this->renderJson([
                'success' => false,
                'error' => 'Invalid account ID'
            ], 400);
        }
    
         // User ist garantiert eingeloggt (BaseProtectedAction prüft das)
        $userId = $this->getCurrentUserId();


        
        // Get account via Use Case
        $useCase = $this->container->get(GetUserImapAccountUseCase::class);
        $accountId = (int)$this->resolveArg('id');
        $account = $useCase->execute($accountId, $userId);

        // Return JSON response
        return $this->renderJson([
            'success' => true,
            'account' => $account
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