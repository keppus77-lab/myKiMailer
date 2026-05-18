<?php

declare(strict_types=1);

namespace App\Application\Config;

class Config
{
    private static ?self $instance = null;
   
    private string $host;
    private string $db_name;            
    private string $db_username;
    private string $db_password;  
    private string $db_port;
    private string $csrf_token_secret;  
    private array $settings = [];


    private function __construct()
    {
       self::loadEnv(); 
    
       $this->host = $_ENV['DB_HOST'] ?? '';
       $this->db_name = $_ENV['DB_DATABASE'] ?? '';
       $this->db_username = $_ENV['DB_USERNAME'] ?? '';
       $this->db_password = $_ENV['DB_PASSWORD'] ?? '';  
       $this->db_port = $_ENV['DB_PORT'] ?? '3306';      
       $this->csrf_token_secret = $_ENV['CSRF_TOKEN_SECRET'] ?? '';
       $this->settings = [
           'DB_HOST' => $this->host,
           'DB_DATABASE' => $this->db_name,
           'DB_USERNAME' => $this->db_username,
           'DB_PASSWORD' => $this->db_password,
           'DB_PORT' => $this->db_port,
           'CSRF_TOKEN_SECRET' => $this->csrf_token_secret, 
       ];
    }
     /**
     * Lädt Umgebungsvariablen aus der .env-Datei
     */
    private static function loadEnv(): void
    {
        // Bestimme den Pfad zur .env-Datei
       
            $path = dirname(__DIR__, 3) . '/.env'; // 3 Ebenen hoch vom Config-Ordner
     
        
        // Prüfe ob die Datei existiert
        if (!file_exists($path)) {
            return; // Keine .env-Datei vorhanden, verwende Systemvariablen oder Defaults
        }
        
        // Lese die Datei Zeile für Zeile
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            return;
        }
        
        foreach ($lines as $line) {
            // Überspringe Kommentare und leere Zeilen
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Parse die Zeile (KEY=VALUE)
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Entferne Anführungszeichen
                $value = self::stripQuotes($value);
                
                // Setze die Umgebungsvariable
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value"); // Auch für getenv() verfügbar machen
                }
            }
        }
    }
    
    /**
     * Entfernt umschließende Anführungszeichen von einem String
     */
    private static function stripQuotes(string $value): string
    {
        // Entferne führende und trailing Leerzeichen
        $value = trim($value);
        
        // Entferne einfache oder doppelte Anführungszeichen
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        
        return $value;
    }
   
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
    
    
    public function getHost(): string
    {
        return $this->host;
    }       
    public function getDbName(): string
    {
        return $this->db_name;
    }       
    public function getDbUsername(): string
    {
        return $this->db_username;
    }
    public function getDbPassword(): string
    {
        return $this->db_password;
    }
    public function getDbPort(): string
    {
        return $this->db_port;
    }
    public function getCsrfTokenSecret(): string
    {
        return $this->csrf_token_secret;
    }   
}
