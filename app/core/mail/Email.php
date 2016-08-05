<?php
/**
 * Author: Jon Garcia.
 * Date: 6/24/16
 * Time: 5:47 PM
 */

namespace App\Core\Mail;


class Email
{
    /**
     * Sender.
     *
     * @var string
     */
    public $sender;

    /**
     * Email subject.
     *
     * @var string
     */
    public $subject = '';

    /**
     * Reply to.
     *
     * @var string
     */
    public $replyTo;

    /**
     * Return path.
     *
     * @var string
     */
    public $returnPath = '';

    /**
     * Message and subject charsets.
     *
     * @var string
     */
    public $charset = 'utf8';

    /**
     * Message body. HTML.
     *
     * @var string
     */
    public $body = '';

    /**
     * Recipients are passed in to the constructor as string separated by the current separtor.
     *
     * @var array
     */
    private $recipientSeparators = [',', ';'];

    /**
     * TO
     *
     * @var array
     */
    public $recipients = [];

    /**
     * CC
     *
     * @var array
     */
    public $Cc = [];

    /**
     * BCC.
     *
     * @var array
     */
    public $Bcc = [];

    /**
     * New mail instance.
     *
     * @param string $subject
     * @param string $sender
     */
    public function __construct($sender, $subject = '')
    {
        $this->subject = $subject;
        $this->sender = $sender;
    }

    /**
     * Add public Cc.
     *
     * @param string $email
     */
    public function addReplyTo($email)
    {
        $this->replyTo[] = $email;
    }

    /**
     * New blind Cc.
     *
     * @param string $email
     */
    public function addBcc($emails)
    {
        $this->Bcc = $this->formatRecipients($emails);
    }

    /**
     * Add public Cc.
     *
     * @param string $email
     */
    public function addCc($emails)
    {
        $this->Cc = $this->formatRecipients($emails);
    }

    /**
     * Email recipient.
     *
     * @param string $email
     */
    public function addRecipients($recipients)
    {
        $this->recipients = $this->formatRecipients($recipients);
    }

    /**
     * Add body to message
     *
     * @param string $body
     */
    public function addBody($body)
    {
        $this->body = $body;
    }

    /**
     * @param array $addresses
     * @return string
     */
    public function concatAddresses(array $addresses)
    {
        return implode(', ', $addresses);
    }

    /**
     * @param $recipients
     * @return array
     */
    public function formatRecipients($recipients)
    {
        if (is_array($recipients)) {
            return $recipients;
        } else {
            foreach ($this->recipientSeparators as $separator) {
                if (strpos($recipients, $separator)) {
                    return explode($separator, str_replace(' ', '', $recipients));
                }
            }
        }
        return [$recipients];
    }

    /**
     * @return string
     */
    public function getRecipients()
    {
        return $this->concatAddresses($this->recipients);
    }

    /**
     * @param $returnPath
     */
    public function addReturnPath($returnPath)
    {
        $this->returnPath = $returnPath;
    }

}