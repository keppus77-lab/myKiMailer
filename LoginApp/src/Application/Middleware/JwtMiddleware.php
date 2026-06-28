<?php
// src/Middleware/JwtMiddleware.php

namespace LoginApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Token aus Header holen
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Token fehlt'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $token = $matches[1];
        
        try {
            // Token validieren
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // User-Daten dem Request hinzufügen
            $request = $request->withAttribute('user', $decoded);
            
            return $handler->handle($request);
            
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Ungültiger Token'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
