<?php

declare(strict_types=1);

namespace LoginAppBackend\Application\Actions;

use LoginAppBackend\Application\Actions\Action;
use LoginApp\Application\Config\Config;
use LoginApp\Application\Controllers\DbController;

use LoginApp\Application\Controllers\TokenController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

use Firebase\JWT\JWT;



class PostLoginAction extends Action
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
    
 
		if(isset($postData['email']) && isset($postData['password']) && isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
		$email = $postData['email'] ?? null;
		$password = $postData['password'] ?? null;
 	
		if($this->db) {
			$hourAgo = time() - 60*60;
			$res = $this->db->sqlSelect('SELECT users.id,password,verified,COUNT(loginattempts.id) FROM users LEFT JOIN loginattempts ON users.id = user AND timestamp>? WHERE email=? GROUP BY users.id', 'is', $hourAgo, $email);
			if($res && $res->num_rows === 1) {
				$user = $res->fetch_assoc();
				if($user['verified']) {
					if($user['COUNT(loginattempts.id)'] <= Config::getInstance()->get('MAX_LOGIN_ATTEMPTS_PER_HOUR')) {
						if(password_verify($password, $user['password'])) {
							// Log user in
							$_SESSION['loggedin'] = true;
							$_SESSION['userID'] = $user['id'];
							$this->db->sqlUpdate('DELETE FROM loginattempts WHERE user=?', 'i', $user['id']);
							$code = "0";
							$now = time();
							$payload = [
								'iat' => $now,
								'exp' => $now + 3600, // 1 Stunde Gültigkeit
								'sub' => $user['id'],
								'email' => $postData['email']
							];
							$token = JWT::encode($payload, Config::getInstance()->get('JWT_SECRET'), 'HS256');
							$now = time();
							$cookieParams = [
								'expires' => $now + Config::getInstance()->get('JWT_EXPIRATION'), // Ablaufzeit
								'path' => '/',
								'domain' => '', // Setze deine Domain für Subdomain-Sharing
								'secure' => true,      // Nur über HTTPS
								'httponly' => true,    // Kein JS-Zugriff (XSS-Schutz!)
								'samesite' => 'Strict' // CSRF-Schutz
							];
							$token = JWT::encode($payload, Config::getInstance()->get('JWT_SECRET'), 'HS256');	
							setcookie('jwt_token', $token, $cookieParams);
						
							
							
       
						}
						else {
							$id = $this->db->sqlSelect('INSERT INTO loginattempts VALUES (NULL, ?, ?, ?)', 'isi', $user['id'], $_SERVER['REMOTE_ADDR'], time());
							if($id !== -1) {
								$code = "1";
							}
							else {
								$code = "2";
							}
						}
					}
					else {
						$code = "3";
					}
				}
				else {
					$code = "4";
				}

				$res->free_result();
			}
			else {
				$code = "1";
			}
			$this->db->connectionClose();
		}
		else {
			$code = "2";
		}

		
 		


		

		$this->response->getBody()->write(json_encode(['code' => $code]));
		
		return $this->response->withHeader('Content-Type', 'application/json');
		}
    	else {
            $this->response->getBody()->write(json_encode(['error' => TokenController::validateToken($postData['csrf_token'])]));
            return $this->response->withHeader('Content-Type', 'application/json')->withStatus(400);
   		}
  
    }
}
