<?php

declare(strict_types=1);

namespace LoginAppFrontend\Application\Actions;

use LoginAppFrontend\Application\Actions\Action;
use LoginApp\Application\Controllers\DbController;
use Psr\Http\Message\ResponseInterface as Response;

use LoginApp\Application\Controllers\LoginController;
use LoginApp\Application\Controllers\TokenController;
use LoginApp\Application\Config\Config;
use Psr\Log\LoggerInterface;


use Slim\Views\Twig;


class LoginAction extends Action
{

 private DbController $db;
    
     public function __construct(LoggerInterface $logger, DbController $db)
    {
        parent::__construct($logger);
       $this->db = $db;
    }
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {        
        if(LoginController::isLoggedIn()) {
            return $this->response
                ->withHeader('Location', './') // ← Angepasst
                ->withStatus(302);
        }
        
        
        $token = TokenController::createToken();
        $view = Twig::fromRequest($this->request);
       
        return $view->render($this->response, 'login.html.twig', [
            'csrf_token' => $token,
            'request_url' => Config::getInstance()->get('AUTH_PATH')
        ]);
        return $this->response;
    }
}
