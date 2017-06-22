<?php
/**
 * Author: Jon Garcia.
 * Date: 4/29/17
 * Time: 10:18 AM
 */

namespace App\Core\Mail\Adapters;


use App;

class SendMailAdapter implements MailAdapter
{

    /**
     * @var bool
     */
    private static $wasConfigured = false;

    /**
     * SendMailAdapter constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        if (! self::$wasConfigured) {
            $from = "{$settings['from']['name']}<{$settings['from']['address']}}>";
            ini_set("sendmail_from", $from);
            ini_set("sendmail_path", $settings['sendmail']);
            self::$wasConfigured = true;
        }
    }

    /**
     * @param string $recipients
     * @param string $subject
     * @param string $body
     * @param string $headers
     * @return bool
     */
    public function send(
        string $recipients,
        string $subject,
        string $body,
        string $headers,
        string $from = null,
        string $fromName = null): bool
    {
        return (bool) mail($recipients, $subject, $body, $headers);
    }
}