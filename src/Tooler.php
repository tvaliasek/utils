<?php
namespace Tvaliasek\Utils;

/**
 * Various utility functions
 *
 * @author tvaliasek
 */
class Tooler {
    
     /**
     * Recursive delete folder and its content
     * @param string $path
     * @return boolean
     */
    public static function recurDelete($path) {
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
            return rmdir($path);
        } else {
            return self::unlinkIfExists($path);
        }
    }

    /**
     * Recursive move of folder and its content to new location
     * @param string $path
     * @param string $destination
     * @return boolean
     */
    public static function recurMove($path, $destination) {
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
        return $this->recurDelete($path);
    }
    
    /**
     * Delete file or folder (recursive) on specified path if it exists
     * @param string $path
     */
    public static function unlinkIfExists($path) {
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
    * @param directory $directory
    * @return integer
    */
    public static function getDirSize($directory) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
    
    /**
     * Transforms database selection to Array of Arrays (rowId => array rowData)
     * @param \Nette\Database\Table\Selection $selection
     * @return type
     */
    public static function selectionToArrays($selection) {
        $result = array();
        if ($selection instanceof \Nette\Database\Table\Selection) {
            foreach ($selection as $row) {
                if (!isset($primaryKeyName)) {
                    $primaryKeyName = $row->getPrimary();
                }
                $result[$row[$primaryKeyName]] = $row->toArray();
            }
        }
        return $result;
    }
    
    /**
     * Recursively creates folder
     * @param string $path
     * @param int $mode
     */
    public static function createFolder($path, $mode = 0754) {
        if (!is_dir($path)) {
            mkdir($path, $mode, true);
        }
    }
    
    /**
     * Converts object to array (object to json, json to array)
     * @param mixed $object
     * @return array || boolean false
     */
    public static function objectToArray($object) {
        return json_decode(json_encode($object), true);
    }

    /**
     * 
     * Generate v4 UUID
     * Version 4 UUIDs are pseudo-random.
     */
    public static function UUIDv4() {
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

    public static function is_valid($uuid) {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
                        '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }

/**
     * Verifies validity of RC number
     * credits to phpfashion.cz
     * @param mixed $rc
     * @return boolean
     */
    public static function verifyRC($rc) {
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
            if ($mod !== (int) $c) {
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
     * @param mixed $ic
     * @return boolean
     */
    public static function verifyIC($ic) {
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

        return (int) $ic[7] === $c;
    }

    /**
     * Get latte engine with installed macros
     * @return \Latte\Engine
     */
    public static function getLatteEngine(){
        $latte = new \Latte\Engine();
        \Latte\Macros\CoreMacros::install($latte->getCompiler());
        \Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());
        return $latte;
    }
    
    /**
     * Render latte template to string
     * @param string $templatePath
     * @param array $params
     * @return string || boolean false
     * @throws \Exception
     */
    public static function buildTemplate($templatePath, array $params = []){
        if(file_exists($templatePath)){
            $latte = self::getLatteEngine();
            return $latte->renderToString($templatePath, $params);
        } else {
            throw new \Exception('Invalid latte template path.');
        }
        return false;
    }
    
    /**
     * Simply send basic email with html body
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return boolean
     */
    public static function sendEmail($from, $to, $subject, $body){
        $message = new \Nette\Mail\Message();
        $message->setFrom($from)->addTo($to)->setSubject($subject)->setHtmlBody($body);
        $mailer = new \Nette\Mail\SendmailMailer();
        return $mailer->send($message);
    }
    

}
