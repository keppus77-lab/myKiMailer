<?php   

namespace App\Application\Controllers;
use App\Application\Config\Config;

class TokenController  
{  
  
   public static function createToken(): string {
		$config = Config::getInstance();
		$seed = self::urlSafeEncode(random_bytes(8));
		$t = time();
		$hash = self::urlSafeEncode(hash_hmac('sha256', session_id() . $seed . $t, $config->getCsrfTokenSecret(), true));
		return self::urlSafeEncode($hash . '|' . $seed . '|' . $t);
	}

	public static function validateToken($token): bool {
		$parts = explode('|', self::urlSafeDecode($token));
		if(count($parts) === 3) {
			$config = Config::getInstance();
			$hash = hash_hmac('sha256', session_id() . $parts[1] . $parts[2], $config->getCsrfTokenSecret(), true);
			if(hash_equals($hash, self::urlSafeDecode($parts[0]))) {
				return true;
			}
		}
		return false;
	}


    private static function urlSafeEncode(String $m) {
		return rtrim(strtr(base64_encode($m), '+/', '-_'), '=');
	}
	private static function urlSafeDecode(String $m) {
		return base64_decode(strtr($m, '-_', '+/'));
	}


}