<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private string $smtpHost;
    private string $smtpUsername;
    private string $smtpPassword;
    private int $smtpPort;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        string $smtpHost,
        string $smtpUsername,
        string $smtpPassword,
        int $smtpPort,
        string $fromEmail,
        string $fromName
    ) {
        $this->smtpHost = $smtpHost;
        $this->smtpUsername = $smtpUsername;
        $this->smtpPassword = $smtpPassword;
        $this->smtpPort = $smtpPort;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * @throws Exception
     */
    public function send(string $to, string $toName, string $subject, string $htmlBody): void {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $this->smtpPort;

        $mail->setFrom($this->fromEmail, $this->fromName);
        $mail->addAddress($to, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
    }
}