<?php

namespace Arris\Helpers;

class Strings
{
    /**
     * Plural form of number
     *
     * @param $number
     * @param mixed $forms (array or string with glues, x|y|z or [x,y,z]
     * @param string $glue
     * @return string
     */
    public static function pluralForm($number, $forms, string $glue = '|'):string
    {
        if (@empty($forms)) {
            return $number;
        }

        if (is_string($forms)) {
            $forms = explode($forms, $glue);
        } elseif (!is_array($forms)) {
            return $number;
        }

        switch (count($forms)) {
            case 1: {
                $forms[] = end($forms);
                $forms[] = end($forms);
                break;
            }
            case 2: {
                $forms[] = end($forms);
            }
        }

        return
            ($number % 10 == 1 && $number % 100 != 11)
                ? $forms[0]
                : (
            ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20))
                ? $forms[1]
                : $forms[2]
            );
    }

}

# -eof-