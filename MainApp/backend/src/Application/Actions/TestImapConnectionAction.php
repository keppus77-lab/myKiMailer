<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions\ImapAccounts;

use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\TestStoredImapConnectionUseCase;
use Psr\Http\Message\ResponseInterface as Response;

class TestImapConnectionAction extends BaseProtectedAction
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
        
        // Get current user ID
        $userId = $this->getCurrentUserId();
        
        // Test connection via Use Case
        $useCase = $this->container->get(TestStoredImapConnectionUseCase::class);
        
        try {
            $result = $useCase->execute($accountId, $userId);
            
            return $this->renderJson($result);
            
        } catch (\RuntimeException $e) {
            $this->logger->warning('IMAP connection test failed', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            return $this->renderJson([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during IMAP connection test', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            return $this->renderJson([
                'success' => false,
                'error' => 'An unexpected error occurred while testing connection'
            ], 500);
        }
    }
}