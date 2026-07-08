<?php

declare(strict_types=1);

use MainApp\Domain\User\UserRepository;
use MainApp\Domain\ImapSearchQueryRepositoryInterface;
use MainApp\Infrastructure\Database\DatabaseInterface;
use MainApp\Infrastructure\Database\MySQLDatabase;
use MainApp\Infrastructure\Persistence\User\InMemoryUserRepository;
use MainApp\Infrastructure\Persistence\MySQLImapSearchQueryRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(InMemoryUserRepository::class),

        // Database connection
        DatabaseInterface::class => \DI\autowire(MySQLDatabase::class),

        // IMAP Search Query Repository
        ImapSearchQueryRepositoryInterface::class => function (DatabaseInterface $db) {
            return new MySQLImapSearchQueryRepository($db->getConnection());
        },
    ]);
};
