<?php

declare(strict_types=1);

namespace MainAppBackend\Application\Actions;
      
use MainApp\Application\Actions\BaseProtectedAction;
use MainApp\Application\UseCases\DeleteImapAccountUseCase;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteImapAccountAction extends BaseProtectedAction
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
        
        // Delete account via Use Case
        $useCase = $this->container->get(DeleteImapAccountUseCase::class);
        
        try {
            $success = $useCase->execute($accountId, $userId);
            
            if ($success) {
                return $this->renderJson([
                    'success' => true,
                    'message' => 'IMAP account deleted successfully'
                ]);
            } else {
                return $this->renderJson([
                    'success' => false,
                    'error' => 'Account not found or access denied'
                ], 404);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete IMAP account: ' . $e->getMessage(), [
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            return $this->renderJson([
                'success' => false,
                'error' => 'Failed to delete IMAP account'
            ], 500);
        }
    
    }
  
}