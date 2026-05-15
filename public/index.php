<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use App\Application\Config\Config;
use App\Application\Controllers\EmailController;
use Slim\Factory\AppFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';
session_start();

// Create App
$app = AppFactory::create();
$app->setBasePath('/kitest'); // ← Angepasst

// Create Twig
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));


$app->get('/', function (Request $request, Response $response) {
	$controller = new LoginController();
	if(!$controller->isLoggedIn()) {
		return $response
			->withHeader('Location', 'login.php') // ← Angepasst
			->withStatus(302);

	}
    $view = Twig::fromRequest($request);
	$token = TokenController::createToken();

	return $view->render($response, 'home.html.twig', [
         'csrf_token' => $token
		    ]);
});

$app->post('/php/logout[.php]', function (Request $request, Response $response) {
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

$app->get('/login[.php]', function (Request $request, Response $response) {
    $controller = new LoginController();
    if($controller->isLoggedIn()) {
	    return $response
		    ->withHeader('Location', './') // ← Angepasst
		    ->withStatus(302);
    }
    $view = Twig::fromRequest($request);
    $config = Config::getInstance();
	
	$token = TokenController::createToken();

	return $view->render($response, 'login.html.twig', [
          'csrf_token' => $token	
    ]);
});

$app->get('/register[.php]', function (Request $request, Response $response) {
    $controller = new LoginController();
    if($controller->isLoggedIn()) {
	    return $response
		    ->withHeader('Location', './') // ← Angepasst
		    ->withStatus(302);
    }
    $view = Twig::fromRequest($request);
    $config = Config::getInstance();
	
	$token = TokenController::createToken();

	return $view->render($response, 'register.html.twig', [
          'csrf_token' => $token	
    ]);
});


$app->post('/php/login[.php]', function (Request $request, Response $response) {
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


$app->post('/php/delete-account[.php]', function (Request $request, Response $response) {
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

$app->post('/php/register[.php]', function (Request $request, Response $response) {
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

// Run app
$app->run();
