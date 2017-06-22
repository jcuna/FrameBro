<?php
/**
 * Author: Jon Garcia.
 * Date: 4/29/17
 * Time: 10:18 AM
 */

namespace App\Core\Mail\Adapters;


class SMTPAdapter implements MailAdapter
{

    /**
     * End of Line constant to avoid issues with some unix OSs
     */
    const EMAIL_EOL = "\r\n";

    /**
     * @var int
     */
    const TIMEOUT = 45;

    /*
     * int
     */
    const MESSAGE_LENGTH = 4096;

    /**
     * ip address of the mail server.  This can also be the local domain name
     *
     * @var string
     */
    private static $host;

    /**
     * port the mail server will be using for smtp
     *
     * @var string
     */
    private static $port;

    /**
     * the login for your smtp
     *
     * @var string
     */
    private static $username;

    /**
     * the password for your smtp
     *
     * @var string
     */
    private static $password;

    /**
     * Defined for the web server.  Since this is where we are gathering the details for the email
     *
     * @var string
     */
    private static $localhost = "127.0.0.1";

    /**
     * @var string
     */
    private static $encryption;

    /**
     * @var bool
     */
    private static $wasConfigured = false;

    /**
     * @var string
     */
    private static $from;

    /**
     * @var string
     */
    private static $fromName;

    /**
     *
     * @var bool
     */
    private static $insecure;

    /**
     * const array
     */
    const INSECURE_OPTIONS = [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ];

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var bool
     */
    private $connected;

    /**
     * @var string
     */
    private $error;

    /**
     * @var string
     */
    private $errorNumber;

    /**
     *
     * @var int
     */
    private $cryptoType;

    /**
     * @var resource
     */
    private $context;

    /**
     * @var array
     */
    private $SMTPResponses = [];

    /**
     * @var array
     */
    private $serverInfo;

    /**
     * SMTPAdapter constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {

        $this->setCrypto();
        $this->context = stream_context_create();

        if (! self::$wasConfigured) {
            self::$host = $settings['host'];
            self::$port = $settings['port'];
            self::$username = $settings['username'];
            self::$password = $settings['password'];
            self::$encryption = $settings['encryption'];
            self::$from = $settings["from"]["address"];
            self::$fromName = $settings["from"]["name"];
            self::$localhost = \App::getInstance("request")->server["HTTP_HOST"] ?? self::$localhost;
            self::$insecure = $settings["insecure"];
            self::$wasConfigured = true;
        }
    }

    private function setCrypto()
    {
        //Allow the best TLS version(s) we can
        $this->cryptoType = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        //PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
        //so add them back in manually if we can
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $this->cryptoType |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $this->cryptoType |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
    }

    /**
     * @param string $from
     * @param string $fromName
     * @param string $recipients
     * @param string $subject
     * @param string $body
     * @param string $headers
     * @return bool
     * @throws MailAdapterException
     */
    public function send(
        string $recipients,
        string $subject,
        string $body,
        string $headers,
        string $from = null,
        string $fromName = null): bool {

            $this->setResource();

            if ($this->connected) {
                $this->shakeHands();
                $this->requestAuthorization();
                $this->sendMessage($from, $recipients);
                $this->quit($recipients, $subject, $headers, $body, $from, $fromName);
            } else {
                throw new MailAdapterException("Please verify your mail settings \n\r{$this->error}", $this->errorNumber);
            }
            return $this->SMTPResponses['quit'] !== false || ! is_null($this->SMTPResponses['quit']);
    }

    /**
     * @param $from
     * @param $to
     */
    private function sendMessage($from, $to)
    {
        $from = $from ?? self::$from;
        //email from
        $this->command("from", "MAIL FROM: <$from>");
        //email to
        $this->command("rcpt", "RCPT TO: {$to}");
        //the email
        $this->command("data", "DATA");
    }

    /**
     * @param $from
     * @param $name
     * @return string
     */
    private function getMailFrom($from, $name): string
    {
        $mailFrom = "<{$from}>";
        if (! is_null($name) && ! empty($name)) {
            $mailFrom = "{$name} <{$from}>";
        } elseif (! is_null(self::$fromName) && ! empty(self::$fromName)) {
            $mailFrom = self::$fromName. " <{$from}>";
        }
        return $mailFrom;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $headers
     * @param string $message
     */
    private function quit(string $to, string $subject, string $headers, string $message, string $from, string $fromName)
    {
        $from = $from ?? self::$from;
        $mailFrom = $this->getMailFrom($from, $fromName);
        $headers .= "From: {$mailFrom}";
        //observe the . after the newline, it signals the end of message
        $this->command(
            "headers",
            "To: {$to}\r\nFrom: {$from}\r\nSubject: {$subject}\r\n{$headers}\r\n\r\n{$message}\r\n.\r\n"
        );
        $this->command('quit', "QUIT");
        fclose($this->resource);
    }

    /**
     *
     */
    private function requestAuthorization()
    {
        //request for auth login
        $this->command("login", "AUTH LOGIN");
        //send the username
        $this->command('username', base64_encode(self::$username));
        //send the password
        $this->command('password', base64_encode(self::$password));
    }

    /**
     * connect to the host and port
     */
    private function setResource()
    {
        try {
            $this->setContext();
            $this->resource = stream_socket_client(
                self::$host . ":" . self::$port,
                $errno,
                $errstr,
                self::TIMEOUT,
                STREAM_CLIENT_CONNECT,
                $this->context
            );
            $this->serverInfo = stream_get_meta_data($this->resource);
            $this->SMTPResponses["initial"] = fgets($this->resource, 4096);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->errorNumber = $e->getCode();
        }
        $this->connected = is_resource($this->resource);
    }

    /**
     *
     */
    private function setContext()
    {
        stream_context_set_option($this->context, self::$encryption, 'verify_host', true);
        if (self::$insecure) {
            foreach (self::INSECURE_OPTIONS as $option => $value) {
                stream_context_set_option($this->context, 'ssl', $option, $value);
            }
        }
    }

    /**
     *
     */
    private function shakeHands()
    {
        $IAm = self::$localhost;
        if (! $this->command("helo", "HELO $IAm")) {
            $this->command("ehlo", "EHLO $IAm");
        }
        $this->command('starttls', "STARTTLS");
        stream_socket_enable_crypto($this->resource, true, $this->cryptoType);
        $this->command("ehlo2", "EHLO $IAm");
    }

    /**
     * @param string $command
     * @param string $strCommand
     * @return bool
     */
    public function command(string $command, string $strCommand): bool
    {
        fwrite($this->resource, $strCommand.self::EMAIL_EOL);
        $this->SMTPResponses[$command] = fgets($this->resource, self::MESSAGE_LENGTH);
        return strpos($this->SMTPResponses[$command], "500") !== 0;
    }

    /**
     * @param $command
     * @return string|null
     */
    public function getResponse($command)
    {
        return $this->SMTPResponses[$command] ?? null;
    }

}