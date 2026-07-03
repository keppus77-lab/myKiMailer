<?php

declare(strict_types=1);

use MainApp\Application\Actions\HomeAction;
use Slim\App;


return function (App $app) {
   	$app->get('/[index.php]', HomeAction::class);
};
