<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * set some default params (mainly utf8 as tiki is utf8) + use the mailCharset pref from a user
 */
class TikiMail
{
    /**
     * @var \Laminas\Mail\Message
     */
    private $mail;
    private $charset;
    public $errors;

    /**
     * @param string|null $user	to username
     * @param string|null $from	from email
     * @param string|null $fromName	from Name
     */
    public function __construct($user = null, $from = null, $fromName = null)
    {
        global $user_preferences, $prefs;

        require_once __DIR__ . '/../mail/maillib.php';

        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');

        $to = '';
        $this->errors = [];
        if (! empty($user)) {
            if ($userlib->user_exists($user)) {
                $to = $userlib->get_user_email($user);
                $tikilib->get_user_preferences($user, ['mailCharset']);
                $this->charset = $user_preferences[$user]['mailCharset'];
            } else {
                $str = tra('Mail to: User not found');
                trigger_error($str);
                $this->errors = [$str];

                return;
            }
        }

        if (! empty($from)) {
            $this->mail = tiki_get_basic_mail();

            try {
                $this->mail->setFrom($from, $fromName);
                $this->mail->setSender($from);
            } catch (Exception $e) {
                // was already set, then do nothing
            }
        } else {
            $this->mail = tiki_get_admin_mail($fromName);
        }
        if (! empty($to)) {
            $this->mail->addTo($to);
        }

        if (empty($this->charset)) {
            $this->charset = $prefs['users_prefs_mailCharset'];
        }
    }

    public function setUser($user)
    {
    }

    public function setFrom($email, $name = null)
    {
        if (! $name) {
            $name = null;	// zend now requires "Name must be a string" (or null, not false)
        }
        $this->mail->setFrom($email, $name);
    }

    public function setReplyTo($email, $name = null)
    {
        if (! $name) {
            $name = null;	// zend now requires "Name must be a string" (or null, not false)
        }
        $this->mail->setReplyTo($email, $name);
    }

    public function setSubject($subject)
    {
        $this->mail->setSubject($subject);
    }

    public function setHtml($html, $text = null, $images_dir = null)
    {
        global $prefs;
        if ($prefs['mail_apply_css'] != 'n') {
            $html = $this->applyStyle($html);
        }

        $body = $this->mail->getBody();
        if (! ($body instanceof \Laminas\Mime\Message) && ! empty($body)) {
            $this->convertBodyToMime($body);
            $body = $this->mail->getBody();
        }

        if (! $body instanceof Laminas\Mime\Message) {
            $body = new Laminas\Mime\Message();
        }

        $partHtml = false;
        $partText = false;

        $parts = [];
        foreach ($body->getParts() as $part) {
            /* @var $part Laminas\Mime\Part */
            if ($part->getType() == Laminas\Mime\Mime::TYPE_HTML) {
                $partHtml = $part;
                $part->setContent($html);
                if ($this->charset) {
                    $part->setCharset($this->charset);
                }
            } elseif ($part->getType() == Laminas\Mime\Mime::TYPE_TEXT) {
                $partText = $part;
                if ($text) {
                    $part->setContent($text);
                    if ($this->charset) {
                        $part->setCharset($this->charset);
                    }
                }
            } else {
                $parts[] = $part;
            }
        }

        if (! $partText && $text) {
            $partText = new Laminas\Mime\Part($text);
            $partText->setType(Laminas\Mime\Mime::TYPE_TEXT);
            if ($this->charset) {
                $partText->setCharset($this->charset);
            }
        }
        if ($partText) {
            $parts[] = $partText;
        }

        if (! $partHtml) {
            $partHtml = new Laminas\Mime\Part($html);
            $partHtml->setType(Laminas\Mime\Mime::TYPE_HTML);
            if ($this->charset) {
                $partHtml->setCharset($this->charset);
            }
        }
        $parts[] = $partHtml;

        $body->setParts($parts);
        $this->mail->setBody($body);
        // use multipart/alternative for mail clients to display html and fall back to plain text parts
        if ($text) {
            $this->mail->getHeaders()->get('content-type')->setType('multipart/alternative');
        }
    }

    public function setText($text = '')
    {
        $body = $this->mail->getBody();
        if ($body instanceof \Laminas\Mime\Message) {
            $parts = $body->getParts();
            $textPartFound = false;
            foreach ($parts as $part) {
                /* @var $part Laminas\Mime\Part */
                if ($part->getType() == Laminas\Mime\Mime::TYPE_TEXT) {
                    $part->setContent($text);
                    if ($this->charset) {
                        $part->setCharset($this->charset);
                    }
                    $textPartFound = true;

                    break;
                }
            }
            if (! $textPartFound) {
                $part = new Laminas\Mime\Part($text);
                $part->setType(Laminas\Mime\Mime::TYPE_TEXT);
                if ($this->charset) {
                    $part->setCharset($this->charset);
                }
                $parts[] = $part;
            }
            $body->setParts($parts);
        } else {
            $this->mail->setBody($text);
            if ($this->charset) {
                $headers = $this->mail->getHeaders();
                $headers->removeHeader($headers->get('Content-type'));
                $headers->addHeaderLine(
                    'Content-type: text/plain; charset=' . $this->charset
                );
            }
        }
    }

    public function setCc($address)
    {
        foreach ((array) $address as $cc) {
            $this->mail->addCc($cc);
        }
    }

    public function setBcc($address)
    {
        foreach ((array) $address as $bcc) {
            $this->mail->addBcc($bcc);
        }
    }

    public function setHeader($name, $value)
    {
        $headers = $this->mail->getHeaders();
        switch ($name) {
            case 'Message-Id':
                $headers->addHeader(Laminas\Mail\Header\MessageId::fromString('Message-ID: ' . trim($value)));

                break;
            case 'In-Reply-To':
                $headers->addHeader(Laminas\Mail\Header\InReplyTo::fromString('In-Reply-To: ' . trim($value)));

                break;
            case 'References':
                $headers->addHeader(Laminas\Mail\Header\References::fromString('References: ' . trim($value)));

                break;
            default:
                $this->mail->getHeaders()->addHeaderLine($name, $value);

                break;
        }
    }

    public function addPart($content, $type)
    {
        $body = $this->mail->getBody();
        if (! ($body instanceof \Laminas\Mime\Message)) {
            $this->convertBodyToMime($body);
            $body = $this->mail->getBody();
        }
        $part = new Laminas\Mime\Part($content);
        $part->setType($type);
        $part->setCharset($this->charset);
        $body->addPart($part);
        $headers = $this->mail->getHeaders();
        $headers->removeHeader('Content-type');
        $headers->addHeaderLine(
            'Content-type: multipart/mixed; boundary="' . $body->getMime()->boundary() . '"'
        );
    }

    /**
     * Get the Laminas Message object
     *
     * @return \Laminas\Mail\Message
     */
    public function getMessage()
    {
        return $this->mail;
    }

    public function send($recipients, $type = 'mail')
    {
        global $tikilib, $prefs;
        $logslib = TikiLib::lib('logs');

        $this->mail->getHeaders()->removeHeader('to');
        foreach ((array) $recipients as $to) {
            try {
                $this->mail->addTo($to);
            } catch (Laminas\Mail\Exception\InvalidArgumentException $e) {
                $title = 'mail error';
                $error = $e->getMessage();
                $this->errors[] = $error;
                $error = ' [' . $error . ']';
                $logslib->add_log($title, $to . '/' . $this->mail->getSubject() . $error);
            }
        }

        if ($prefs['zend_mail_handler'] == 'smtp' && $prefs['zend_mail_queue'] == 'y') {
            $query = "INSERT INTO `tiki_mail_queue` (message) VALUES (?)";
            $bindvars = [serialize($this->mail)];
            $tikilib->query($query, $bindvars, -1, 0);
            $title = 'mail';
        } else {
            try {
                tiki_send_email($this->mail);
                $title = 'mail';
                $error = '';
            } catch (Laminas\Mail\Exception\ExceptionInterface $e) {
                $title = 'mail error';
                $error = $e->getMessage();
                $this->errors[] = $error;
                $error = ' [' . $error . ']';
            }

            if ($title == 'mail error' || $prefs['log_mail'] == 'y') {
                foreach ($recipients as $u) {
                    $logslib->add_log($title, $u . '/' . $this->mail->getSubject() . $error);
                }
            }
        }

        return $title == 'mail';
    }

    protected function convertBodyToMime($text)
    {
        $textPart = new Laminas\Mime\Part($text);
        $textPart->setType(Laminas\Mime\Mime::TYPE_TEXT);
        $newBody = new Laminas\Mime\Message();
        $newBody->addPart($textPart);
        $this->mail->setBody($newBody);
    }

    public function addAttachment($data, $filename, $mimetype)
    {
        $body = $this->mail->getBody();
        if (! ($body instanceof \Laminas\Mime\Message)) {
            $this->convertBodyToMime($body);
            $body = $this->mail->getBody();
        }

        $attachment = new Laminas\Mime\Part($data);
        $attachment->setFileName($filename);
        $attachment->setType($mimetype);
        $attachment->setEncoding(Laminas\Mime\Mime::ENCODING_BASE64);
        $attachment->setDisposition(Laminas\Mime\Mime::DISPOSITION_INLINE);
        $body->addPart($attachment);
    }

    /**
     *	scramble an email with a method
     *
     * @param string $email email address to be scrambled
     * @param string $method unicode or y: each character is replaced with the unicode value
     *                       strtr: mr@tw.org -> mr AT tw DOT org
     *                       x: mr@tw.org -> mr@xxxxxx
     *
     * @return string scrambled email
     */
    public static function scrambleEmail($email, $method = 'unicode')
    {
        switch ($method) {
            case 'strtr':
                $trans = [	"@" => tra("(AT)"),
                            "." => tra("(DOT)")
                ];

                return strtr($email, $trans);
            case 'x':
                $encoded = $email;
                for ($i = strpos($email, "@") + 1, $istrlen_email = strlen($email); $i < $istrlen_email; $i++) {
                    if ($encoded[$i] != ".") {
                        $encoded[$i] = 'x';
                    }
                }

                return $encoded;
            case 'unicode':
            case 'y':// for previous compatibility
                $encoded = '';
                for ($i = 0, $istrlen_email = strlen($email); $i < $istrlen_email; $i++) {
                    $encoded .= '&#' . ord($email[$i]) . ';';
                }

                return $encoded;
            case 'n':
            default:
                return $email;
        }
    }

    private function collectCss()
    {
        static $css;
        if ($css) {
            return $css;
        }

        $cachelib = TikiLib::lib('cache');
        if ($css = $cachelib->getCached('email_css')) {
            return $css;
        }

        $headerlib = TikiLib::lib('header');
        $files = $headerlib->get_css_files();
        $contents = array_map(function ($file) {
            if ($file[0] == '/') {
                return file_get_contents($file);
            } elseif (substr($file, 0, 4) == 'http') {
                return TikiLib::lib('tiki')->httprequest($file);
            }
            if (strpos($file, 'themes/') === 0) {   // only use the tiki base and current theme files
                return file_get_contents(TIKI_PATH . '/' . $file);
            }
        }, $files);

        $css = implode("\n\n", array_filter($contents));
        $cachelib->cacheItem('email_css', $css);

        return $css;
    }

    private function applyStyle($html)
    {
        $html = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $html;
        $css = $this->collectCss();
        $processor = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $html = $processor->convert($html, $css);

        return $html;
    }
}

/**
 * Format text, sender and date for a plain text email reply
 * - Split into 75 char long lines prepended with >
 *
 * @param $text		email text to be quoted
 * @param $from		email from name/address to be quoted
 * @param $date		date of mail to be quoted
 * @return string	text ready for replying in a plain text email
 */
function format_email_reply(&$text, $from, $date)
{
    $lines = preg_split('/[\n\r]+/', wordwrap($text));

    for ($i = 0, $icount_lines = count($lines); $i < $icount_lines; $i++) {
        $lines[$i] = '> ' . $lines[$i] . "\n";
    }
    $str = ! empty($from) ? $from . ' wrote' : '';
    $str .= ! empty($date) ? ' on ' . $date : '';
    $str = "\n\n\n" . $str . "\n" . implode($lines);

    return $str;
}

/**
 * Attempt to close any unclosed HTML tags
 * Needs to work with what's inside the BODY
 * originally from http://snipplr.com/view/3618/close-tags-in-a-htmlsnippet/
 *
 * @param $html			html input
 * @return string		corrected html out
 */
function closetags($html)
{
    #put all opened tags into an array
    preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
    $openedtags = $result[1];

    #put all closed tags into an array
    preg_match_all("#</([a-z]+)>#iU", $html, $result);
    $closedtags = $result[1];
    $len_opened = count($openedtags);

    # all tags are closed
    if (count($closedtags) == $len_opened) {
        return $html;
    }
    $openedtags = array_reverse($openedtags);

    # close tags
    for ($i = 0; $i < $len_opened; $i++) {
        if (! in_array($openedtags[$i], $closedtags)) {
            $html .= "</" . $openedtags[$i] . ">";
        } else {
            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
        }
    }

    return $html;
}
