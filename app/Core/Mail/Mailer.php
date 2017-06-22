<?php
/**
 * Author: Jon Garcia.
 * Date: 6/24/16
 * Time: 5:55 PM
 */

namespace App\Core\Mail;


use App\Core\Mail\Adapters\MailAdapter;
use App\Core\Mail\Adapters\SendMailAdapter;
use App\Core\Mail\Adapters\SMTPAdapter;

class Mailer
{
    /**
     * @var Email
     */
    private static $email;

    /**
     * End of Line constant to avoid issues with some unix OSs
     */
    const EMAIL_EOL = "\r\n";

    /**
     * Send an email message
     *
     * @param Email $email
     * @return bool
     * @throws \Exception
     */
    public static function send(Email $email): bool
    {
        self::$email = $email;

        $recipients = $email->getRecipients();

        if (!empty($recipients)) {
            $adapter = self::getAdapter();
            return $adapter->send(
                $recipients,
                $email->subject,
                $email->body,
                self::getHeaders(),
                $email->sender,
                $email->senderName
            );
        } else {
            throw new \Exception('You did not specify any recipients');
        }
    }

    /**
     * @return MailAdapter
     */
    public static function getAdapter(): MailAdapter
    {
        $mailSettings = \App::getSettings("mail");
        switch ($mailSettings["driver"]) {
            case 'sendmail':
                return new SendMailAdapter($mailSettings);
                break;
            case 'smtp':
                return new SMTPAdapter($mailSettings);
        }
        throw new \RuntimeException("Invalid mail driver type");
    }

    /**
     * @return string
     */
    public static function getHeaders(): string
    {
        $headers = self::configureHeaders();
        $result = [];
        foreach ($headers as $name => $header) {
            $result[] = $name . ': ' . $header;
        }
        return implode(self::EMAIL_EOL, $result);

    }

    /**
     * @return array
     */
    private static function configureHeaders(): array
    {
        $email = self::$email;
        $headers = [
            'Mime-Version' => '1.0',
            'X-Mailer' => 'FrameBro',
            'Content-type' => "text/html;{$email->charset}",
            'Date' => date('r'),
        ];

        if (!is_null($email->replyTo)) {
            $headers['Reply-To'] = $email->replyTo;
        }

        if (!empty($email->returnPath)) {
            $headers['Return-Path'] = $email->returnPath;
        }

        if (!empty($email->Cc)) {
            $headers['Cc'] = $email->concatAddresses($email->Cc);
        }
        if (!empty($email->Bcc)) {
            $headers['Bcc'] = $email->concatAddresses($email->Bcc);
        }
        return $headers;
    }

}