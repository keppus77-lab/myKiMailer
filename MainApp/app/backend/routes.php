<?php

declare(strict_types=1);


use MainApp\Application\Controllers\DbController;
use MainApp\Application\Controllers\ImapCredentialsController;
use MainApp\Application\Controllers\ImapSearchController;
use MainApp\Application\Controllers\LoginController;
use MainApp\Application\Controllers\TokenController;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use MainApp\Application\Middleware\JwtMiddleware;
use MainApp\Application\Config\Config;

return function (App $app) {
 

	$jwtSecret = Config::getInstance()->get('JWT_SECRET');

	$jwtMiddleware = new JwtMiddleware($jwtSecret);

	
    $app->group('/api', function (Group $api) {
		$api->group('/v1', function (Group $v1) {
			
			$v1->group('/imap-search', function (Group $imapsearch,) {
				// Speichern einer neuen Query
				$imapsearch->post('/save', function (Request $request, Response $response) {
					/** @var ImapSearchController $controller */
					$isController = $this->get(ImapSearchController::class);
					
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
					print_r($data);
					try {

						$criteria = $data['criteria'];
						if (is_string($criteria)) {
							$criteria = json_decode($criteria, true);
						}

						// Sicherstellen dass es ein Array ist
						if (!is_array($criteria)) {
							$response->getBody()->write(json_encode(['error' => 'Invalid criteria format']));
							return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
						}


						$id = $isController->saveQuery(
							$_SESSION['userID'],
							$data['name'],
							$criteria,
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
				$imapsearch->put('/update/{id}', function (Request $request, Response $response, array $args) {
					/** @var ImapSearchController $controller */
					$isController = $this->get(ImapSearchController::class);
					
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
					$controller= new LoginController();
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					
					try {
						$success = $isController->updateQuery(
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
				$imapsearch->get('/load/{id}', function (Request $request, Response $response, array $args) {
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
				$imapsearch->get('/list', function (Request $request, Response $response) {
					/** @var ImapSearchController $controller */
					$iscontroller = $this->get(ImapSearchController::class);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					
					$queries = $iscontroller->getUserQueries($_SESSION['userID']);
					
					$response->getBody()->write(json_encode($queries));
					return $response->withHeader('Content-Type', 'application/json');
				});
		
				// Query löschen
				$imapsearch->delete('/delete/{id}', function (Request $request, Response $response, array $args) {
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
					
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					
					$success = $controller->deleteQuery($id, $_SESSION['userID']);
					
					$response->getBody()->write(json_encode(['success' => $success]));
					return $response->withHeader('Content-Type', 'application/json');
				});
    
			});

			$v1->group('/imap-accounts', function (Group $imapaccounts) {

				$imapaccounts->get('/list', function (Request $request, Response $response) {
					$db = new DbController();
				
					
					$logger = $this->get(LoggerInterface::class);
					$imapCredantialController = new ImapCredentialsController($db, $logger);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
				
					$account=$imapCredantialController->getAccountsByUser($_SESSION['userID']);
					
					$response->getBody()->write(json_encode($account));
					return $response->withHeader('Content-Type', 'application/json');
				});

				$imapaccounts->delete('/delete/{id}', function (Request $request, Response $response, array $args) {
					
					$db = new DbController();
					
					$id = (int) $args['id'];
					
					$logger = $this->get(LoggerInterface::class);
					$imapCredantialController = new ImapCredentialsController($db, $logger);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
				
					$account=$imapCredantialController->deleteAccount($_SESSION['userID'], $id);
					if($account===true){
						$return=['success' => 1];

					}
					else{
						$return =['error' => 1];

					}
					$response->getBody()->write(json_encode($return));
					return $response->withHeader('Content-Type', 'application/json');

				});

				$imapaccounts->post('/save', function (Request $request, Response $response) {
					$db = new DbController();
				
					
					$logger = $this->get(LoggerInterface::class);
					$imapCredantialController = new ImapCredentialsController($db, $logger);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					$data = $request->getParsedBody();
					$account = json_encode($data);
						$account=$imapCredantialController->saveAccount(
							$_SESSION['userID'],
							$data['email'],
							$data['host'],
							$data['password'],
							$data['username'],
							$data['use_ssl'],
							$data['port']
						);
				
					$response->getBody()->write(json_encode($account));
					return $response->withHeader('Content-Type', 'application/json');

				});

				$imapaccounts->get('/test/{id}', function (Request $request, Response $response, array $args) {
					$db = new DbController();
				
					$id = (int) $args['id'];
					
					$logger = $this->get(LoggerInterface::class);
					$imapCredantialController = new ImapCredentialsController($db, $logger);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					$testConnection = $imapCredantialController->testConnection($_SESSION['userID'], $id);
					
				
					$response->getBody()->write(json_encode($testConnection));
					return $response->withHeader('Content-Type', 'application/json');

				});
			
				$imapaccounts->get('/get/{id}', function (Request $request, Response $response, array $args) {
					$db = new DbController();
				
					$id = (int) $args['id'];
					
					$logger = $this->get(LoggerInterface::class);
					$imapCredantialController = new ImapCredentialsController($db, $logger);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					$account = $imapCredantialController->getAccount($_SESSION['userID'], $id);
					
				
					$response->getBody()->write(json_encode($account));
					return $response->withHeader('Content-Type', 'application/json');

				});

				$imapaccounts->put('/update/{id}', function (Request $request, Response $response, array $args) {
					$db = new DbController();
				
					$id = (int) $args['id'];
					$data = $request->getParsedBody();
					$data['id'] = $id;
					$logger = $this->get(LoggerInterface::class);
					$imapCredantialController = new ImapCredentialsController($db, $logger);
					if (!LoginController::isLoggedIn()) {
						$response->getBody()->write(json_encode(['error' => 'Not logged in']));
						return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
					}
					$update = $imapCredantialController->updateAccount($_SESSION['userID'], $data);

					
				
					$response->getBody()->write(json_encode($update));
					return $response->withHeader('Content-Type', 'application/json');

				});
			});
		});
	})->add($jwtMiddleware);
};	