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

class LoginAction extends Action
{
    private ServiceContainer $container;
    protected Config $config;
    
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->container = ServiceContainer::getInstance();
        $this->config = Config::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {        
        // Check if user is already logged in
        if ($this->isUserLoggedIn()) {
            return $this->redirectToDashboard();
        }
        
        // Render login form
        return $this->renderLoginForm();
    }

    private function isUserLoggedIn(): bool
    {
        $checkAuthUseCase = $this->container->get(CheckAuthenticationUseCase::class);
        return $checkAuthUseCase->execute();
    }

    private function redirectToDashboard(): Response
    {
        $dashboardUrl = $this->config->get('DASHBOARD_URL') ?? './';
        
        return $this->response
            ->withHeader('Location', $dashboardUrl)
            ->withStatus(302);
    }

    private function renderLoginForm(): Response
    {
        $csrfService = $this->container->get(CsrfTokenServiceInterface::class);
        $token = $csrfService->generateToken();
        
        $view = Twig::fromRequest($this->request);
        
        return $view->render($this->response, 'login.html.twig', [
            'csrf_token' => $token,
            'request_url' => $this->config->get('AUTH_PATH'),
            'page_title' => 'Login'
        ]);
    }
}