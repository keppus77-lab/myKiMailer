<?php

declare(strict_types=1);

namespace LoginAppFrontend\Application\Actions;

use LoginApp\Application\Container\ServiceContainer;
use LoginApp\Application\UseCases\CheckAuthenticationUseCase;
use LoginApp\Domain\Services\CsrfTokenServiceInterface;
use LoginApp\Application\Config\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class RegisterAction extends Action
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
        // Check if user is already logged in
        $checkAuthUseCase = $this->container->get(CheckAuthenticationUseCase::class);
        
        if ($checkAuthUseCase->execute()) {
            return $this->response
                ->withHeader('Location', './')
                ->withStatus(302);
        }
        
        // Generate CSRF token
        $csrfService = $this->container->get(CsrfTokenServiceInterface::class);
        $token = $csrfService->generateToken();
        
        // Render template
        $view = Twig::fromRequest($this->request);
        $config = Config::getInstance();

        return $view->render($this->response, 'register.html.twig', [
            'csrf_token' => $token,
            'title' => 'Register',
            'request_url' => $config->get('AUTH_PATH')    
        ]);
    }
}