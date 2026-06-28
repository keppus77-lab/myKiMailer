<?php

declare(strict_types=1);

namespace LoginAppBackend\Application\Actions;

use LoginAppBackend\Application\Actions\Action;

use LoginApp\Application\Controllers\DbController;

use LoginApp\Application\Controllers\TokenController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;



class PostDeleteAccountAction extends Action
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
       $postData = $this->request->getParsedBody();
   
	    if(isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
		if(isset($_SESSION['loggedin']) && isset($_SESSION['userID']) && $_SESSION['loggedin'] === true) {
			if($this->db) {
				if($this->db->sqlUpdate('DELETE FROM users WHERE id=?', 'i', $_SESSION['userID'])) {
					$this->db->sqlUpdate('DELETE FROM requests WHERE user=?', 'i', $_SESSION['userID']);
					$this->db->sqlUpdate('DELETE FROM loginattempts WHERE user=?', 'i', $_SESSION['userID']);
					session_destroy();
					$code="0";
				}
				else {
						$code ="1";
				}
				$this->db->connectionClose();
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
