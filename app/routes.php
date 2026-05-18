<?php

declare(strict_types=1);

use Slim\App;
use App\Application\Actions\Auth\LoginAction;
use App\Application\Actions\Auth\PostLoginAction;
use App\Application\Actions\Auth\RegisterAction;
use App\Application\Actions\Auth\PostDeleteAccountAction;
use App\Application\Actions\Auth\PostRegisterAction;
use App\Application\Actions\Auth\PostLogoutAction;
use App\Application\Actions\Auth\ValidateAction;
use App\Application\Actions\HomeAction;
use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use App\Application\Config\Config;
use App\Application\Controllers\EmailController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\Controllers\ImapSearchController;


use Psr\Log\LoggerInterface;


return function (App $app) {
   	$app->get('/[index.php]', HomeAction::class);
	$app->get('/login[.php]', LoginAction::class);
	$app->get('/register[.php]', RegisterAction::class);
	$app->get('/validate/{id}/{hash}', ValidateAction::class);
	$app->group('/php', function (Group $group) {
		$group->post('/login[.php]', PostLoginAction::class);
		$group->post('/delete-account[.php]', PostDeleteAccountAction::class); 
		$group->post('/register[.php]', PostRegisterAction::class);
		$group->post('/logout[.php]', PostLogoutAction::class);
	 });
 
$app->post('/php/delete-account_[.php]', function (Request $request, Response $response) {
  $postData = $request->getParsedBody();
   
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
	  $response->getBody()->write(json_encode(['code' => $code]));
    
    return $response->withHeader('Content-Type', 'application/json');
	}); 
	$app->post('/php/login_[.php]', function (Request $request, Response $response) {
  $postData = $request->getParsedBody();
    
 
	if(isset($postData['email']) && isset($postData['password']) && isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
	$email = $postData['email'] ?? null;
    $password = $postData['password'] ?? null;
 	$connection = new LoginController();

		if($connection) {
			$hourAgo = time() - 60*60;
			$res = $connection->sqlSelect('SELECT users.id,password,verified,COUNT(loginattempts.id) FROM users LEFT JOIN loginattempts ON users.id = user AND timestamp>? WHERE email=? GROUP BY users.id', 'is', $hourAgo, $email);
			if($res && $res->num_rows === 1) {
				$user = $res->fetch_assoc();
				if($user['verified']) {
					if($user['COUNT(loginattempts.id)'] <= Config::getInstance()->get('MAX_LOGIN_ATTEMPTS_PER_HOUR')) {
						if(password_verify($password, $user['password'])) {
							// Log user in
							$_SESSION['loggedin'] = true;
							$_SESSION['userID'] = $user['id'];
							$connection->sqlUpdate('DELETE FROM loginattempts WHERE user=?', 'i', $user['id']);
							$code = "0";
						}
						else {
							$id = $connection->sqlSelect('INSERT INTO loginattempts VALUES (NULL, ?, ?, ?)', 'isi', $user['id'], $_SERVER['REMOTE_ADDR'], time());
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
			$connection->connectionClose();
		}
		else {
			$code = "2";
		}



    $response->getBody()->write(json_encode(['code' => $code]));
    
    return $response->withHeader('Content-Type', 'application/json');
	}
    else {
            $response->getBody()->write(json_encode(['error' => TokenController::validateToken($postData['csrf_token'])]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

    }
    
    //  Als JSON ausgeben
    
	}); 
$app->post('/php/register_[.php]', function (Request $request, Response $response) {
  $postData = $request->getParsedBody();

   
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
			$connection = new LoginController();
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


	
	  $response->getBody()->write(json_encode($errors));
    
    return $response->withHeader('Content-Type', 'application/json');
	}); 
$app->post('/php/logout_[.php]', function (Request $request, Response $response) {
 		$postData = $request->getParsedBody();
 
	if(isset($postData['csrf_token']) && TokenController::validateToken($postData['csrf_token'])) {
		session_destroy();
		$code ="0";
	}
	else {
		$code = "1";
	}
	  $response->getBody()->write(json_encode(['code' => $code]));
    
    return $response->withHeader('Content-Type', 'application/json');
	});
	$app->get('/test[.php]', function (Request $request, Response $response) {

	// Konfiguration
	$config = [
		'smtp_host' => 'smtp.gmail.com',
		'smtp_port' => 587,
		'smtp_user' => 'keppus77@gmail.com',
		'smtp_pass' => 'zanhmrwaybeqhzzj',  // 16-stelliges App-Passwort
		'from_email' => 'deine@gmail.com',
		'from_name' => 'Test Absender',
		'to_email' => 'm.keppler@infranken.de',
		'to_name' => 'Test Empfänger'
	];

	$mail = new PHPMailer(true);

	try {
		// Debug-Modus (ausführliche Ausgabe)
		$mail->SMTPDebug = SMTP::DEBUG_SERVER;  // Detaillierte Logs
		$mail->Debugoutput = function($str, $level) {
			echo "<pre style='color: blue;'>DEBUG ($level): $str</pre>";
		};
		
		// Server-Einstellungen
		$mail->isSMTP();
		$mail->Host = $config['smtp_host'];
		$mail->SMTPAuth = true;
		$mail->Username = $config['smtp_user'];
		$mail->Password = $config['smtp_pass'];
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->Port = $config['smtp_port'];
		
		// Absender und Empfänger
		$mail->setFrom($config['from_email'], $config['from_name']);
		$mail->addAddress($config['to_email'], $config['to_name']);
		$mail->addReplyTo($config['from_email'], $config['from_name']);
		
		// Inhalt
		$mail->isHTML(true);
		$mail->CharSet = 'UTF-8';
		$mail->Subject = 'Test E-Mail von localhost - ' . date('Y-m-d H:i:s');
		$mail->Body = '
			<html>
			<body style="font-family: Arial, sans-serif;">
				<h2 style="color: #4CAF50;">✅ PHPMailer Test erfolgreich!</h2>
				<p>Diese E-Mail wurde von <strong>localhost</strong> gesendet.</p>
				<p>Zeit: ' . date('d.m.Y H:i:s') . '</p>
				<p>Server: ' . gethostname() . '</p>
			</body>
			</html>
		';
		$mail->AltBody = 'PHPMailer Test erfolgreich! Diese E-Mail wurde von localhost gesendet.';
		
		// Versenden
		$mail->send();
		
		echo '<div style="background: #d4edda; color: #155724; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;">';
		echo '<h3>✅ E-Mail erfolgreich versendet!</h3>';
		echo '<p>Empfänger: ' . $config['to_email'] . '</p>';
		echo '<p>Betreff: ' . $mail->Subject . '</p>';
		echo '</div>';
		
	} catch (Exception $e) {
		echo '<div style="background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin-top: 20px;">';
		echo '<h3>❌ Fehler beim E-Mail-Versand</h3>';
		echo '<p><strong>Fehlermeldung:</strong> ' . $mail->ErrorInfo . '</p>';
		echo '<p><strong>Exception:</strong> ' . $e->getMessage() . '</p>';
		echo '</div>';
	}

	});
	$app->get('/validate_/{id}/{hash}', function (Request $request, Response $response, Array $args) {
 	$id = (int) $args['id'];
    $hash = (string) $args['hash'];
 	$view = Twig::fromRequest($request);
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
			
  
	
	$token = TokenController::createToken();

	return $view->render($response, 'validate.html.twig', [
		'title'=>'Email Verification',          
		  'reload' => $reload,
		  'csrf_token' => $token	
    ]);
			
			
		}
		else {
				return $view->render($response, 'validate.html.twig', [
          
		  'csrf_token' => $token	
    ]);


		}

	});





// IMAP Search Routes
$app->group('/api/imap-search', function (Group $group) {
    
    // Speichern einer neuen Query
    $group->post('/save', function (Request $request, Response $response) {
        /** @var ImapSearchController $controller */
        $controller = $this->get(ImapSearchController::class);
        
        /** @var TokenController $tokenController */
        $tokenController = $this->get(TokenController::class);
        
        /** @var LoggerInterface $logger */
        $logger = $this->get(LoggerInterface::class);
        
        $data = $request->getParsedBody();
        
        // CSRF-Check
        if (!isset($data['csrf_token']) || !$tokenController->validateToken($data['csrf_token'])) {
            $logger->warning('CSRF-Token ungültig bei IMAP Save');
            $response->getBody()->write(json_encode(['error' => 'Invalid CSRF token']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Login-Check
        if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
            $response->getBody()->write(json_encode(['error' => 'Not logged in']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Validierung
        if (!isset($data['name']) || !isset($data['criteria']) || !isset($data['criteria_string'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $id = $controller->saveQuery(
                $_SESSION['userID'],
                $data['name'],
                $data['criteria'],
                $data['criteria_string'],
                $data['description'] ?? null
            );
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'id' => $id
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $logger->error('Fehler beim Speichern der IMAP Query', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
    
    // Aktualisieren einer Query
    $group->put('/update/{id}', function (Request $request, Response $response, array $args) {
        /** @var ImapSearchController $controller */
        $controller = $this->get(ImapSearchController::class);
        
        /** @var TokenController $tokenController */
        $tokenController = $this->get(TokenController::class);
        
        /** @var LoggerInterface $logger */
        $logger = $this->get(LoggerInterface::class);
        
        $data = $request->getParsedBody();
        $id = (int) $args['id'];
        
        // CSRF & Login Check
        if (!isset($data['csrf_token']) || !$tokenController->validateToken($data['csrf_token'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid CSRF token']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
            $response->getBody()->write(json_encode(['error' => 'Not logged in']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $success = $controller->updateQuery(
                $id,
                $_SESSION['userID'],
                $data['name'],
                $data['criteria'],
                $data['criteria_string'],
                $data['description'] ?? null
            );
            
            $response->getBody()->write(json_encode(['success' => $success]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $logger->error('Fehler beim Update der IMAP Query', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
    
    // Laden einer Query
    $group->get('/load/{id}', function (Request $request, Response $response, array $args) {
        /** @var ImapSearchController $controller */
        $controller = $this->get(ImapSearchController::class);
        
        $id = (int) $args['id'];
        
        if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
            $response->getBody()->write(json_encode(['error' => 'Not logged in']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $query = $controller->getQuery($id, $_SESSION['userID']);
        
        if ($query) {
            $response->getBody()->write(json_encode($query));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'Query not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    });
    
    // Liste aller Queries
    $group->get('/list', function (Request $request, Response $response) {
        /** @var ImapSearchController $controller */
        $controller = $this->get(ImapSearchController::class);
        
        if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
            $response->getBody()->write(json_encode(['error' => 'Not logged in']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $queries = $controller->getUserQueries($_SESSION['userID']);
        
        $response->getBody()->write(json_encode($queries));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // Query löschen
    $group->delete('/delete/{id}', function (Request $request, Response $response, array $args) {
        /** @var ImapSearchController $controller */
        $controller = $this->get(ImapSearchController::class);
        
        /** @var TokenController $tokenController */
        $tokenController = $this->get(TokenController::class);
        
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        if (!isset($data['csrf_token']) || !$tokenController->validateToken($data['csrf_token'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid CSRF token']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
            $response->getBody()->write(json_encode(['error' => 'Not logged in']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $success = $controller->deleteQuery($id, $_SESSION['userID']);
        
        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
});

};

