<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;

use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\CreateImapAccountUseCase;
use MainApp\Application\DTO\CreateImapAccountRequest;
use Psr\Http\Message\ResponseInterface as Response;

class CreateImapAccountAction extends BaseProtectedAction
{
    protected function protectedAction(): Response
    {         
        $postData = $this->request->getParsedBody();
        
        // Validate input
        $dto = new CreateImapAccountRequest($postData);
        
        if (!$dto->isValid()) {
            return $this->renderJson([
                'success' => false,
                'errors' => $dto->getErrors()
            ], 400);
        }
        
        // Create account
        $useCase = $this->container->get(CreateImapAccountUseCase::class);
        
        try {
            $account = $useCase->execute(
                $this->getCurrentUserId(),
                $dto->email,
                $dto->host,
                $dto->port,
                $dto->password,
                $dto->encryption
            );
            
            return $this->renderJson([
                'success' => true,
                'account' => $account->toArray(),
                'message' => 'IMAP account created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create IMAP account: ' . $e->getMessage());
            
            return $this->renderJson([
                'success' => false,
                'error' => 'Failed to create IMAP account'
            ], 500);
        }
    }
}