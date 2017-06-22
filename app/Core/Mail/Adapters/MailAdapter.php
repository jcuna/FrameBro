<?php
/**
 * Author: Jon Garcia.
 * Date: 4/28/17
 * Time: 7:41 PM
 */

namespace App\Core\Mail\Adapters;


interface MailAdapter
{
    /**
     * MailAdapter constructor.
     * @param array $settings
     */
    public function __construct(array $settings);

    /**
     * @param string $recipients
     * @param string $subject
     * @param string $body
     * @param string $headers
     * @param string|null $from
     * @param string|null $fromName
     * @return bool
     */
    public function send(
        string $recipients,
        string $subject,
        string $body,
        string $headers,
        string $from = null,
        string $fromName = null): bool;
}