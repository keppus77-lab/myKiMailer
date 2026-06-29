<?php
namespace MainApp\Application\Services;

interface CookieManagerInterface {
    public function set(string $name, string $value, array $options = []): void;
    public function get(string $name): ?string;
    public function delete(string $name, array $options = []): void;
    public function has(string $name): bool;
}