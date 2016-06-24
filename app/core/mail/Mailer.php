<?php
/**
 * Author: Jon Garcia.
 * Date: 6/24/16
 * Time: 5:55 PM
 */

namespace App\Core\Mail;


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
    static public function send(Email $email)
    {
        self::$email = $email;

        $recipients = $email->getRecipients();

        if (!empty($recipients)) {
            return (bool)mail($recipients, $email->subject, $email->body, self::getHeaders());
        } else {
            throw new \Exception('You didn\'t specify a recipient');
        }
    }

    /**
     * @return string
     */
    public static function getHeaders()
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
    private static function configureHeaders()
    {
        $headers = [];
        $headers['Mime-Version'] = '1.0';
        $headers['X-Mailer'] = 'FrameBro';
        $headers['Content-type'] = 'text/html; ' . self::$email->charset;
        $headers['Date'] = date('r');
        $headers['From'] = self::$email->sender;

        if (!is_null(self::$email->replyTo)) {
            $headers['Reply-To'] = self::$email->replyTo;
        }

        if (!empty(self::$email->returnPath)) {
            $headers['Return-Path'] = self::$email->returnPath;
        }

        if (!empty(self::$email->Cc)) {
            $headers['Cc'] = self::$email->concatAddresses(self::$email->Cc);
        }
        if (!empty(self::$email->Bcc)) {
            $headers['Bcc'] = self::$email->concatAddresses(self::$email->Bcc);
        }
        return $headers;
    }


}