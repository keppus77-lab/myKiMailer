<?php

namespace App\Application\Controllers;

use mysqli;
use App\Application\Config\Config;


class LoginController
{
    private mysqli $connection;

    public function __construct()
    {
        try{
            $this->connection = new mysqli(Config::getInstance()->get('DB_HOST'), Config::getInstance()->get('DB_USERNAME'), Config::getInstance()->get('DB_PASSWORD'), Config::getInstance()->get('DB_DATABASE'));
        }
        catch(\Exception $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }       
            
    }
    public  function isLoggedIn(): bool
    {
        // Hier kannst du die Logik implementieren, um zu überprüfen, ob der Benutzer eingeloggt ist.
        // Zum Beispiel könntest du eine Session-Variable überprüfen.
        return false;
    }

    private function sqlSelect(string $query, string|false $format = false, mixed ...$vars)    {
		$stmt = $this->connection->prepare($query);
		if($format) {
			$stmt->bind_param($format, ...$vars);
		}
		if($stmt->execute()) {
			$res = $stmt->get_result();
			$stmt->close();
			return $res;
		}
		$stmt->close();
		return false;
	}

	private function sqlInsert(string $query, string|false $format = false, mixed ...$vars) {
		$stmt = $this->connection->prepare($query);
		if($format) {
			$stmt->bind_param($format, ...$vars);
		}
		if($stmt->execute()) {
			$id = $stmt->insert_id;
			$stmt->close();
			return $id;
		}
		$stmt->close();
		return -1;
	}

    private function sqlUpdate(string $query, string|false $format = false, mixed ...$vars): bool {
		$stmt = $this->connection->prepare($query);
		if($format) {
			$stmt->bind_param($format, ...$vars);
		}
		if($stmt->execute()) {
			$stmt->close();
			return true;
		}
		$stmt->close();
		return false;
	}

}