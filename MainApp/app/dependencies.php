<?php

declare(strict_types=1);

use MainApp\Application\Controllers\DbController;
use MainApp\Application\Controllers\ImapSearchController;
use MainApp\Application\Controllers\LoginController;
use MainApp\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

              $errorHandler = new RotatingFileHandler(
                __DIR__ . '/../logs/errors.log',
                30,
                Logger::ERROR
            );
            $logger->pushHandler($errorHandler);

            return $logger;
        },  
        LoginController::class => function (ContainerInterface $c) {
            return new LoginController();
        },
        ImapSearchController::class => function (ContainerInterface $c) {
            return new ImapSearchController(
                new DbController(),
                $c->get(LoggerInterface::class)
            );
        },

    ]);
};
