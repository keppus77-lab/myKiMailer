<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;

use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use Psr\Log\LoggerInterface;


use Slim\Views\Twig;


class ValidateAction extends Action
{

 private LoginController $controller;
 private String $token;
    
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
        $id = (int) $this->resolveArg('id');
    $hash = (string) $this->resolveArg('hash'); 
 	$view = Twig::fromRequest($this->request);
	$reload = false;
	if(isset($id) && $id !== '' && isset($hash) && $hash !== '') {
		$connection = new LoginController();
		if($connection) {
			$res = $connection->sqlSelect('SELECT user,hash,timestamp FROM requests WHERE id=? AND type=0', 'i', $id);
			if($res && $res->num_rows === 1) {
				$request = $res->fetch_assoc();
				if($request['timestamp'] > time() - 60*60*24) {
					if(password_verify(TokenController::urlSafeDecode($hash), $request['hash'])) {
						if($connection->sqlUpdate('UPDATE users SET verified=1 WHERE id=?', 'i', $request['user'])) {
							$connection->sqlUpdate( 'DELETE FROM requests WHERE user=? AND type=0', 'i', $request['user']);
							$content= '<h2>Email Verified</h2>';
							$reload = true;
						}
						else {
							$content= '<h2>Failed to Update Database</h2>';
							}
						}
						else {
							$content= '<h2>Invalid Verification Request</h2>';
						}
					}
					else {
						$content= '<h2>Verification Request Expired</h2><a href="./validate">Click here to send another one</a>';
					}
					$res->free_result();
				}
				else {
					$content= '<h2>Invalid Verification Request</h2>';

				}
				$connection->connectionClose();
			}
			else {
				$content= '<h2>Failed to Connect to Database</h2>';
			}
			
  
	
	$this->token = TokenController::createToken();

	return $view->render($this->response, 'validate.html.twig', [
		'title'=>'Email Verification',
           'reload' => $reload,
		  'csrf_token' => $this->token	
    ]);
			
			
		}
		else {
				return $view->render($this->response, 'validate.html.twig', [
          'title'=>'Email Verification',
		  'csrf_token' => $this->token	
    ]);


		}

    }
}
