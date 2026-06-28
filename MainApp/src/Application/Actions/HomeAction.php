<?php

declare(strict_types=1);

namespace MainApp\Application\Actions;

use MainApp\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;

use MainApp\Application\Controllers\DbController;
use MainApp\Application\Controllers\LoginController;
use MainApp\Application\Controllers\TokenController;
use Psr\Log\LoggerInterface;


use Slim\Views\Twig;


class HomeAction extends Action
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
        if(!LoginController::isLoggedIn()) {
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
