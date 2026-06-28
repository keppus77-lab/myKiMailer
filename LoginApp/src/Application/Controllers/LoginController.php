<?php

namespace LoginApp\Application\Controllers;

use mysqli;
use LoginApp\Application\Config\Config;


class LoginController
{
    
    public static function isLoggedIn(): bool
    {
		if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
				 return false;
		}
    	return true;
    }

     public static function login(string $username, string $password): bool
	 {
		$db = new DbController();
		$res = $db->sqlSelect("SELECT id, password FROM users WHERE username = ?", "s", $username);
		if($res && $res->num_rows > 0) {
			$row = $res->fetch_assoc();
			if(password_verify($password, $row['password'])) {
				$_SESSION['loggedin'] = true;
				$_SESSION['userid'] = $row['id'];
				return true;
			}
		}
		return false;
	 }

}