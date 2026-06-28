<?php
namespace LoginApp\Application\Controllers;
use LoginApp\Application\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController{

	private static function sendEmail(String $to, String $toName, String $subj, String $msg):bool {
		$mail = new PHPMailer(true);
        $config = Config::getInstance();
		try {
	    //Server settings
	    $mail->isSMTP();
	    $mail->Host       = $config->get('SMTP_HOST');
	    $mail->SMTPAuth   = true;
	    $mail->Username   = $config->get('SMTP_USERNAME');
	    $mail->Password   = $config->get('SMTP_PASSWORD');
	    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	    $mail->Port       = $config->get('SMTP_PORT');




	    //Recipients
	    $mail->setFrom($config->get('SMTP_FROM'), $config->get('SMTP_FROM_NAME'));
	    $mail->addAddress($to, $toName);

	    // Content
	    $mail->isHTML(true);
	    $mail->Subject = $subj;
	    $mail->Body    = $msg;

	    $mail->send();
	    return true;
		} 
		catch(Exception $e) {
            echo $e->getMessage();
			return false;
		}
	}

    public static function sendValidationEmail(String $email): int{
        echo "sendVali";
		$connection = new LoginController();
        $config = Config::getInstance();
		if($connection) {
			$oneDayAgo = time() - 60 * 60 * 24;
			$res = $connection->sqlSelect('SELECT users.id,name,verified,COUNT(requests.id) FROM users LEFT JOIN requests ON users.id = requests.user AND type=0 AND timestamp>? WHERE email=? GROUP BY users.id ', 'is', $oneDayAgo, $email);
			if($res && $res->num_rows === 1) {
                
				$user = $res->fetch_assoc();
				if($user['verified'] === 0) {
                   	if($user['COUNT(requests.id)'] <= $config->get('MAX_EMAIL_VERIFICATION_REQUESTS_PER_DAY')) {
						//Send validation request
						
                        $verifyCode = random_bytes(32);
						$hash = password_hash($verifyCode, $config->get('PASSWORD_DEFAULT'));
						$requestID = $connection->sqlInsert('INSERT INTO requests VALUES (NULL, ?, ?, ?, 0)', 'isi', $user['id'], $hash, time());
						if($requestID !== -1) {

try {
    self::sendEmail(
        $email, 
        $user['name'], 
        'Email Verification', 
        '<a href="' . $config->get('VALIDATE_EMAIL_ENDPOINT') . '/' . $requestID . '/' . TokenController::urlSafeEncode($verifyCode) . '">Click this link to verify your email</a>'
    );
} catch (Exception $e) {
    echo $e->getMessage();
    error_log("E-Mail Fehler: " . $e->getMessage());
    // Optional: Fehler an Benutzer zurückgeben
    throw new Exception("E-Mail konnte nicht versendet werden: " . $e->getMessage());
}

							if(self::sendEmail($email, $user['name'], 'Email Verification', '<a href="' . $config->get('VALIDATE_EMAIL_ENDPOINT') . '/' . $requestID . '/' . TokenController::urlSafeEncode($verifyCode). '" />Click this link to verify your email</a>')) {
							echo "sendV 5";	
                            return 0;
							}
							else {
								echo 'failed to send email';
								return 1;
							}
						}
						else {
							// return 'failed to insert request';
							return 2;
						}
					}
					else {
						return 3;
					}
				}
				else {
					return 4;
				}
				$res->free_result();
			}
			else {
				return 5;
			}
			$C->close();
		}
		else {
			return 6;
		}
		return -1;
	}
	
    

}