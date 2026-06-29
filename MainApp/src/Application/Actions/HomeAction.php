<?php

declare(strict_types=1);

namespace MainApp\Application\Actions;

use MainApp\Application\Container\ServiceContainer;
use MainApp\Application\UseCases\CheckAuthenticationUseCase;
use MainApp\Domain\Services\CsrfTokenServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class HomeAction extends Action
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
        // Check if user is logged in
        $checkAuthUseCase = $this->container->get(CheckAuthenticationUseCase::class);
        
        if (!$checkAuthUseCase->execute()) {
            return $this->response
                ->withHeader('Location', './login.php')
                ->withStatus(302);
        }
        
        // Generate CSRF token
        $csrfService = $this->container->get(CsrfTokenServiceInterface::class);
        $token = $csrfService->generateToken();
        
        // Get current user info (optional)
        $userId = $checkAuthUseCase->getCurrentUserId();
        
        // Render home template
        $view = Twig::fromRequest($this->request);
        
        return $view->render($this->response, 'home.html.twig', [
            'csrf_token' => $token,
            'user_id' => $userId
        ]);
    }
}