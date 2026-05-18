<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use App\Application\Controllers\EmailController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;



class PostRegisterAction extends Action
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

   
	$errors = [];



	if(!isset($postData['name']) || strlen($postData['name']) > 255 || !preg_match('/^[a-zA-Z- ]+$/', $postData['name'])) {
		$errors[] = 1;
	}
	if(!isset($postData['email']) || strlen($postData['email']) > 255 || !filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) {
		$errors[] = 2;
	}
	else if(!checkdnsrr(substr($postData['email'], strpos($postData['email'], '@') + 1), 'MX')) {
		$errors[] = 3;
	}
	if(!isset($postData['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\~?!@#\$%\^&\*])(?=.{8,})/', $postData['password'])) {
		$errors[] = 4;
	}
	else if(!isset($postData['confirm-password']) || $postData['confirm-password'] !== $postData['password']) {
		$errors[] = 5;
	}



	if(count($errors) === 0) {
		if(isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
			//Connect to database
			$connection = $this->controller;
			if($connection) {
				//Check if user with same email already exists
				$res = $connection->sqlSelect('SELECT id FROM users WHERE email=?', 's', $postData['email']);
				if($res && $res->num_rows === 0) {
					//Actually create the account
					$hash = password_hash($postData['password'], PASSWORD_DEFAULT);
					$id = $connection->sqlInsert('INSERT INTO users VALUES (NULL, ?, ?, ?, 0)', 'sss', $postData['name'], $postData['email'], $hash);
					if($id !== -1) {
						$err = EmailController::sendValidationEmail($postData['email']);
						if($err === 0) {
							$errors[] = 0;
						}
						else {
							$errors[] = $err + 9;
						}
					}
					else {
						//Failed to insert into database
						$errors[] = 6;
					}
					$res->free_result();
				}
				else {
					//This email is already in use
					$errors[] = 7;
				}
			}
			else {
				//Failed to connect to database
				$errors[] = 8;
			}
		}
		else {
			//Invalid CSRF Token
			$errors[] = 9;
		}
	}


	
	$this->response->getBody()->write(json_encode($errors));
    
    return $this->response->withHeader('Content-Type', 'application/json');
  
    }
}

