<?php

namespace Tvaliasek\Utils;

use Latte\Engine;
use Latte\Macros\CoreMacros;
use Nette\Application\LinkGenerator;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpMailer;
use Nette\Utils\Validators;

/**
 * Various utility functions
 *
 * @author tvaliasek
 */
class Tooler
{

    /**
     * Recursive delete folder and its content
     * @param string $path
     */
    public static function recurDelete(string $path): void
    {
        if (is_dir($path)) {
            $contents = array_diff(scandir($path), array('.', '..'));
            if (!empty($contents)) {
                foreach ($contents as $item) {
                    if (is_dir($path . '/' . $item)) {
                        self::recurDelete($path . '/' . $item);
                    } else {
                        self::unlinkIfExists($path . '/' . $item);
                    }
                }
            }
            rmdir($path);
        } else {
            self::unlinkIfExists($path);
        }
    }

    /**
     * Recursive move of folder and its content to new location
     * @param string $path
     * @param string $destination
     */
    public static function recurMove(string $path, string $destination) : void
    {
        if (is_dir($path)) {
            if (file_exists($destination)) {
                self::recurDelete($destination);
            }
            if (!is_dir($destination)) {
                mkdir($destination, 0754, true);
            }
            $contents = array_diff(scandir($path), array('.', '..'));
            if (!empty($contents)) {
                foreach ($contents as $item) {
                    if (is_dir($path . '/' . $item)) {
                        self::recurMove($path . '/' . $item, $destination . '/' . $item);
                    } else {
                        copy($path . '/' . $item, $destination . '/' . $item);
                        chmod($destination . '/' . $item, 0754);
                    }
                }
            }
        }
        self::recurDelete($path);
    }

    /**
     * Delete file or folder (recursive) on specified path if it exists
     * @param string $path
     */
    public static function unlinkIfExists(string $path)
    {
        if (file_exists($path)) {
            if (is_dir($path)) {
                self::recurDelete($path);
            } else {
                unlink($path);
            }
        }
    }

    /***
     * Get the directory size
     * @param string $directory
     * @return int
     */
    public static function getDirSize(string $directory)
    {
        $size = 0;
        foreach (new \RecursiveDirectoryIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Recursively creates folder
     * @param string $path
     * @param int $mode
     */
    public static function createFolder(string $path, int $mode = 0754) : void
    {
        if (!is_dir($path)) {
            mkdir($path, $mode, true);
        }
    }

    /**
     * @return string
     */
    public static function UUIDv4() : string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Verifies validity of RC number
     * credits to phpfashion.cz
     * @param string $rc
     * @return bool
     */
    public static function verifyRC(string $rc) : bool
    {
        // be liberal in what you receive
        if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
            return FALSE;
        }

        list(, $year, $month, $day, $ext, $c) = $matches;

        if ($c === '') {
            $year += $year < 54 ? 1900 : 1800;
        } else {
            // kontrolní číslice
            $mod = ($year . $month . $day . $ext) % 11;
            if ($mod === 10)
                $mod = 0;
            if ($mod !== (int)$c) {
                return FALSE;
            }

            $year += $year < 54 ? 2000 : 1900;
        }

        // k měsíci může být připočteno 20, 50 nebo 70
        if ($month > 70 && $year > 2003) {
            $month -= 70;
        } elseif ($month > 50) {
            $month -= 50;
        } elseif ($month > 20 && $year > 2003) {
            $month -= 20;
        }

        // kontrola data
        if (!checkdate($month, $day, $year)) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Verifies validity of IC number
     * credits to phpfashion.cz
     * @param string $ic
     * @return bool
     */
    public static function verifyIC(string $ic) : bool
    {
        // be liberal in what you receive
        $ic = preg_replace('#\s+#', '', $ic);

        // má požadovaný tvar?
        if (!preg_match('#^\d{8}$#', $ic)) {
            return FALSE;
        }

        // kontrolní součet
        $a = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += $ic[$i] * (8 - $i);
        }

        $a = $a % 11;
        if ($a === 0) {
            $c = 1;
        } elseif ($a === 1) {
            $c = 0;
        } else {
            $c = 11 - $a;
        }

        return (int)$ic[7] === $c;
    }

    /**
     * Get latte engine with installed macros
     * @return Engine
     */
    public static function getLatteEngine() : Engine
    {
        $latte = new Engine();
        CoreMacros::install($latte->getCompiler());
        UIMacros::install($latte->getCompiler());
        return $latte;
    }

    /**
     * Render latte template to string
     * @param string $templatePath
     * @param array $params
     * @param LinkGenerator|null $linkGenerator
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function buildTemplate(
        string $templatePath,
        array $params = [],
        LinkGenerator $linkGenerator = null)
    {
        if (file_exists($templatePath)) {
            $latte = self::getLatteEngine();
            if ($linkGenerator instanceof LinkGenerator) {
                $latte->addProvider('uiControl', $linkGenerator);
            }
            return $latte->renderToString($templatePath, $params);
        } else {
            throw new \InvalidArgumentException('Invalid latte template path.');
        }
    }

    public static function prepareEmail(
        string $from,
        string $subject,
        string $body,
        array $attachments = []
    ) : Message
    {
        if (!Validators::isEmail($from)) {
            throw new \InvalidArgumentException('Invalid email address: '.$from);
        }
        foreach ($attachments as $filepath) {
            if (!file_exists($filepath)) {
                throw new \InvalidArgumentException('Cannot find attachment at '.$filepath);
            }
        }
        $message = new Message();
        $message->setFrom($from);
        $message->setSubject($subject);
        $message->setHtmlBody($body);
        foreach ($attachments as $filepath) {
            $message->addAttachment($filepath);
        }
        return $message;
    }

    /**
     * Simply send basic email with html body
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $bcc
     * @param array $attachments
     * @return boolean
     */
    public static function sendEmail(
        string $from,
        string $to,
        string $subject,
        string $body,
        string $bcc = null,
        array $attachments = []
    )
    {
        if (!Validators::isEmail($from)) {
            throw new \InvalidArgumentException('Invalid email address: '.$from);
        }
        if (!Validators::isEmail($to)) {
            throw new \InvalidArgumentException('Invalid email address: '.$to);
        }
        if ($bcc !== null && !Validators::isEmail($bcc)) {
            throw new \InvalidArgumentException('Invalid email address: '.$bcc);
        }
        $message = self::prepareEmail($from, $subject, $body, $attachments);
        $message->addTo($to);
        if ($bcc !== null) {
            $message->addBcc($bcc);
        }
        $mailer = new SendmailMailer();
        try {
            $mailer->send($message);
        } catch (SendException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param array $smtpSettings
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string|null $bcc
     * @param array $attachments
     * @return bool
     */
    public static function sendSMTPEmail(
        array $smtpSettings,
        string $from,
        string $to,
        string $subject,
        string $body,
        string $bcc = null,
        array $attachments = []
    ) : bool
    {
        if (!Validators::isEmail($from)) {
            throw new \InvalidArgumentException('Invalid email address: '.$from);
        }
        if (!Validators::isEmail($to)) {
            throw new \InvalidArgumentException('Invalid email address: '.$to);
        }
        if ($bcc !== null && !Validators::isEmail($bcc)) {
            throw new \InvalidArgumentException('Invalid email address: '.$bcc);
        }
        $message = self::prepareEmail($from, $subject, $body, $attachments);
        $message->addTo($to);
        if ($bcc !== null) {
            $message->addBcc($bcc);
        }
        $mailer = new SmtpMailer($smtpSettings);
        try {
            $mailer->send($message);
        } catch (SendException $e) {
            return false;
        }
        return true;
    }

    /**
     * Check mime type of file
     * @param string $filepath
     * @param string $mimeType
     * @return boolean true on success
     */
    public static function validateMimeType(string $filepath, string $mimeType) : bool
    {
        if (file_exists($filepath)) {
            return strcasecmp(mime_content_type($filepath), $mimeType) === 0;
        }
        return false;
    }
}
