<?php

declare(strict_types=1);

namespace MainApp\Application\Actions;

use MainApp\Application\Container\ServiceContainer;
use MainApp\Application\UseCases\CheckAuthenticationUseCase;
use MainApp\Domain\Services\CsrfTokenServiceInterface;
use MainApp\Application\Config\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

abstract class BaseProtectedAction extends Action
{
    protected ServiceContainer $container;
    protected Config $config;
    
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->container = ServiceContainer::getInstance();
        $this->config = Config::getInstance();
    }

    /**
     * Check if user is authenticated
     */
    protected function isUserLoggedIn(): bool
    {
        $checkAuthUseCase = $this->container->get(CheckAuthenticationUseCase::class);
        return $checkAuthUseCase->execute();
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?int
    {
        $checkAuthUseCase = $this->container->get(CheckAuthenticationUseCase::class);
        return $checkAuthUseCase->getCurrentUserId();
    }

    /**
     * Redirect to login page
     */
    protected function redirectToLogin(): Response
    {
        $loginUrl = $this->config->get('LOGIN_URL') ?? './login.php';
        
        return $this->response
            ->withHeader('Location', $loginUrl)
            ->withStatus(302);
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken(): string
    {
        $csrfService = $this->container->get(CsrfTokenServiceInterface::class);
        return $csrfService->generateToken();
    }

    /**
     * Render view with common data
     */
    protected function renderView(string $template, array $data = []): Response
    {
        $view = Twig::fromRequest($this->request);
        
        // Add common data to all protected views
        $commonData = [
            'csrf_token' => $this->generateCsrfToken(),
            'user_id' => $this->getCurrentUserId(),
            'app_name' => $this->config->get('APP_NAME') ?? 'MainApp',
            'current_year' => date('Y')
        ];
        
        $mergedData = array_merge($commonData, $data);
        
        return $view->render($this->response, $template, $mergedData);
    }

    /**
     * Execute action - must check authentication first
     */
    final protected function action(): Response
    {
        // Always check authentication first
        if (!$this->isUserLoggedIn()) {
            $this->logger->info('User not authenticated, redirecting to login');
            return $this->redirectToLogin();
        }

        // Execute the protected action
        return $this->protectedAction();
    }

    /**
     * Override this method in child classes
     */
    abstract protected function protectedAction(): Response;
}