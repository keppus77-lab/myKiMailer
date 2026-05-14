<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


use App\Application\Controllers\LoginController;
use App\Application\Controllers\TokenController;
use App\Application\Config\Config;
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
    
	
    return $view->render($response, 'home.html.twig', [
        'name' => 'John erwr',
    ]);
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

// Run app
$app->run();
