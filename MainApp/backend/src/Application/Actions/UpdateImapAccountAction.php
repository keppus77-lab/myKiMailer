<?php

declare(strict_types=1);

namespace MainApp\Application\Actions\ImapAccounts;

use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\UpdateImapAccountUseCase;
use MainApp\Application\DTOs\UpdateImapAccountRequest;
use LoginApp\Domain\Services\CsrfTokenServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateImapAccountAction extends BaseProtectedAction
{
    /**
     * {@inheritdoc}
     */
    protected function protectedAction(): Response
    {        
        // Get account ID from route parameter
        $accountId = (int)$this->resolveArg('id');
        
        if ($accountId <= 0) {
            return $this->renderJson([
                'success' => false,
                'error' => 'Invalid account ID'
            ], 400);
        }
        
        // Get PUT data
        $putData = $this->request->getParsedBody();
        
        // Create DTO
        $dto = new UpdateImapAccountRequest($putData);
        
        // Validate CSRF
        $csrfService = $this->container->get(CsrfTokenServiceInterface::class);
        if (!$csrfService->validateToken($dto->csrfToken)) {
            return $this->renderJson([
                'success' => false,
                'error' => 'Invalid CSRF token'
            ], 403);
        }
        
        // Validate input
        if (!$dto->isValid()) {
            return $this->renderJson([
                'success' => false,
                'errors' => $dto->getErrors()
            ], 400);
        }
        
        // Get current user ID
        $userId = $this->getCurrentUserId();
        
        // Update account via Use Case
        $useCase = $this->container->get(UpdateImapAccountUseCase::class);
        
        try {
            $account = $useCase->execute(
                $accountId,
                $userId,
                $dto->email,
                $dto->host,
                $dto->port,
                $dto->password,
                $dto->encryption,
                $dto->active
            );
            
            return $this->renderJson([
                'success' => true,
                'account' => $account->toArray(),
                'message' => 'IMAP account updated successfully'
            ]);
            
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to update IMAP account', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            return $this->renderJson([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error updating IMAP account', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            return $this->renderJson([
                'success' => false,
                'error' => 'An unexpected error occurred'
            ], 500);
        }
    }
}