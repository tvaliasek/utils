<?php


namespace Tvaliasek\Utils\Validators;


class IC
{

    /**
     * @param string $ic
     * @return bool
     */
    public static function isIC(string $ic): bool
    {
        return self::verifyIC($ic);
    }

    /**
     * Verifies validity of IC number
     * credits to phpfashion.cz
     * @param string $ic
     * @return bool
     */
    public static function verifyIC(string $ic): bool
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
}