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


class PostDeleteAccountAction extends Action
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
       $postData = $this->request->getParsedBody();
   
	    if(isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
		if(isset($_SESSION['loggedin']) && isset($_SESSION['userID']) && $_SESSION['loggedin'] === true) {
			$connection = new LoginController();
			if($connection) {
				if($connection->sqlUpdate('DELETE FROM users WHERE id=?', 'i', $_SESSION['userID'])) {
					$connection->sqlUpdate('DELETE FROM requests WHERE user=?', 'i', $_SESSION['userID']);
					$connection->sqlUpdate('DELETE FROM loginattempts WHERE user=?', 'i', $_SESSION['userID']);
					session_destroy();
					$code="0";
				}
				else {
						$code ="1";
				}
				$connection->connectionClose();
			}
			else {
					$code ="2";
			}
		}
		else {
			$code ="3";
		}
	}
	else {
		$code ="4";
	}
	$this->response->getBody()->write(json_encode(['code' => $code]));
    return $this->response->withHeader('Content-Type', 'application/json');
  
    }
}
