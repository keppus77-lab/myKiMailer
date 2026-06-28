<?php

declare(strict_types=1);

namespace LoginAppBackend\Application\Actions;

use LoginAppBackend\Application\Actions\Action;

use LoginApp\Application\Controllers\DbController;
use LoginApp\Application\Controllers\TokenController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;



class PostLogoutAction extends Action
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
		    session_destroy();
	    	$code ="0";
            setcookie('jwt_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => true,
                'samesite' => 'Strict'
            ]);
	    }
	    else {
		    $code = "1";
	    }
	    $this->response->getBody()->write(json_encode(['code' => $code]));
    
        return $this->response->withHeader('Content-Type', 'application/json');
    }

}