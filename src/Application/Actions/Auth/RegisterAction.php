<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;

use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use Psr\Log\LoggerInterface;


use Slim\Views\Twig;


class RegisterAction extends Action
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
        if($this->controller->isLoggedIn()) {
            return $this->response
                ->withHeader('Location', './') // ← Angepasst
                ->withStatus(302);
        }
        
    $view = Twig::fromRequest($this->request);
   	
	$token = TokenController::createToken();

	return $view->render($this->response, 'register.html.twig', [
          'csrf_token' => $token,
          'title'=>'Register'	
    ]);
}
}
 
   
   