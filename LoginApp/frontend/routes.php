<?php

declare(strict_types=1);




use LoginAppFrontend\Application\Actions\LoginAction;
use LoginAppFrontend\Application\Actions\RegisterAction;
use LoginAppFrontend\Application\Actions\ValidateAction;
use Slim\App;


return function (App $app) {

	$app->get('/login[.php]', LoginAction::class);
	$app->get('/register[.php]', RegisterAction::class);
	$app->get('/validate/{id}/{hash}', ValidateAction::class);
};
