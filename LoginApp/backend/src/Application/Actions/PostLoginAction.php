<?php

declare(strict_types=1);

namespace LoginAppBackend\Application\Actions;

use LoginApp\Application\Container\ServiceContainer;
use LoginApp\Application\UseCases\LoginWithJwtUseCase;
use LoginApp\Application\DTOs\LoginRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
class PostLoginAction extends Action
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
        $loginRequest = new LoginRequest($postData);
        
        // Validate required fields
        if (!$loginRequest->hasRequiredFields()) {
            $errors = $loginRequest->validate();
            $this->response->getBody()->write(json_encode(['error' => $errors]));
            return $this->response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // Execute use case
        $useCase = $this->container->get(LoginWithJwtUseCase::class);
        $code = $useCase->execute(
            $loginRequest->email,
            $loginRequest->password,
            $loginRequest->csrfToken
        );

        // Return response
        $this->response->getBody()->write(json_encode(['code' => $code]));
        
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}