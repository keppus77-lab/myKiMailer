<?php

declare(strict_types=1);

namespace LoginAppBackend\Application\Actions;

use LoginApp\Application\Container\ServiceContainer;
use LoginApp\Application\UseCases\RegisterUserUseCase;
use LoginApp\Application\DTOs\RegistrationRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class PostRegisterAction extends Action
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
        $registrationRequest = new RegistrationRequest($postData);
        
        // Check if required fields are present
        if (!$registrationRequest->hasRequiredFields()) {
            $errors = $registrationRequest->validate();
            $this->response->getBody()->write(json_encode($errors));
            return $this->response->withHeader('Content-Type', 'application/json');
        }

        // Execute use case
        $useCase = $this->container->get(RegisterUserUseCase::class);
        
        $errors = $useCase->execute(
            $registrationRequest->name,
            $registrationRequest->email,
            $registrationRequest->password,
            $registrationRequest->confirmPassword,
            $registrationRequest->csrfToken
        );

        // Return response
        $this->response->getBody()->write(json_encode($errors));
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}