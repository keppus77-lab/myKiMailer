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
	
	$token = TokenController::createToken($config->get('CSRF_TOKEN_SECRET'));

	return $view->render($response, 'login.html.twig', [
          'token' => $token,	
    ]);
});

$app->post('/login[.php]', function ($request, $response, array $args) {
    print_r($request->getParsedBody());
    print_r($args);
}); 

// Run app
$app->run();
