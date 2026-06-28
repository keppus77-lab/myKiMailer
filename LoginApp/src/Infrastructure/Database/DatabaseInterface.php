<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Database;

use mysqli_result;

interface DatabaseInterface {
    public function select(string $query, string $types = '', ...$params): ?mysqli_result;
    public function insert(string $query, string $types = '', ...$params): int;
    public function update(string $query, string $types = '', ...$params): bool;
    public function delete(string $query, string $types = '', ...$params): bool;
    public function close(): void;
}