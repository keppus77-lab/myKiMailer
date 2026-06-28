<?php

declare(strict_types=1);




use LoginAppBackend\Application\Actions\PostDeleteAccountAction;
use LoginAppBackend\Application\Actions\PostLoginAction;
use LoginAppBackend\Application\Actions\PostLogoutAction;
use LoginAppBackend\Application\Actions\PostRegisterAction;

use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\App;


return function (App $app) {



  $app->group('/api', function (Group $api) {
		$api->group('/v1', function (Group $v1) {
			$v1->group('/auth', function (Group $auth) {
				
				$auth->post('/login[.php]', PostLoginAction::class);
				$auth->post('/delete-account[.php]', PostDeleteAccountAction::class); 
				$auth->post('/register[.php]', PostRegisterAction::class);
				$auth->post('/logout[.php]', PostLogoutAction::class);
			});
		});
	});
	
		
};
