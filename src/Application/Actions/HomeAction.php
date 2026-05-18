<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;

use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use Psr\Log\LoggerInterface;


use Slim\Views\Twig;


class HomeAction extends Action
{

 private LoginController $controller;
    
     public function __construct(LoggerInterface $logger, LoginController $loginController)
    {
        parent::__construct($logger);
       $this->controller = $loginController;
    }
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {        
        if(!$this->controller->isLoggedIn()) {
            return $this->response
                ->withHeader('Location', './login.php') // ← Angepasst
                ->withStatus(302);
        }
        
        
        $token = TokenController::createToken();
        $view = Twig::fromRequest($this->request);
       
        return $view->render($this->response, 'home.html.twig', [
            'csrf_token' => $token,
            
        ]);
        return $this->response;
    }
}
