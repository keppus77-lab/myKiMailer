<?php

declare(strict_types=1);

namespace LoginAppBackend\Application\Actions;

use LoginApp\Application\Container\ServiceContainer;
use LoginApp\Application\UseCases\LogoutUseCase;
use LoginApp\Application\DTOs\LogoutRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class PostLogoutAction extends Action
{
    private ServiceContainer $container;
    
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->container = ServiceContainer::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {        
        $postData = $this->request->getParsedBody();
        
        // Create DTO from request
        $logoutRequest = new LogoutRequest($postData);
        
        // Validate request has CSRF token
        if (!$logoutRequest->isValid()) {
            $this->response->getBody()->write(json_encode(['code' => '1']));
            return $this->response->withHeader('Content-Type', 'application/json');
        }

        // Execute use case
        $useCase = $this->container->get(LogoutUseCase::class);
        $success = $useCase->execute($logoutRequest->getCsrfToken());

        // Return response
        $code = $success ? '0' : '1';
        $this->response->getBody()->write(json_encode(['code' => $code]));
        
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}