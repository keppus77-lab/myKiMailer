<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Application\Config\Config;
use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;


class PostLogoutAction extends Action
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
       $postData = $request->getParsedBody();
 
	if(isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
		session_destroy();
		$code ="0";
	}
	else {
		$code = "1";
	}
	  $response->getBody()->write(json_encode(['code' => $code]));
    
    return $response->withHeader('Content-Type', 'application/json');}
}
