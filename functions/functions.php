<?php

namespace Arris;

interface ArrisFunctionsInterface {

    function setOptionEnv(array $options, $key, $env_key = null, $default_value = '');
    function setOption(array $options, $key, $default_value = null);

    function checkAllowedValue( $value, $allowed_values_array , $invalid_value = NULL );

    function mb_trim_text($input, $length, $ellipses = true, $strip_html = true, $ellipses_text = '...'):string;
    function mb_str_replace($search, $replace, $subject, &$count = 0):string;

    function array_map_to_integer(array $input): array;
    function array_fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL);
    function array_search_callback(array $a, callable $callback);
    function array_sort_in_given_order(array $array, array $order, $sort_key):array;

    function http_redirect($uri, $replace_prev_headers = false, $code = 302, $scheme = '');

    function pluralForm($number, $forms, string $glue = '|'):string;

    function GUID();

    function float_to_fixed_string($value, $separator = '.');

    function reArrangeFilesPOST($file_post);
}

if (!function_exists('Arris\checkAllowedValue')) {

    /**
     * @param $value
     * @param $allowed_values_array
     * @param null $invalid_value
     * @return mixed|null
     */
    function checkAllowedValue( $value, $allowed_values_array , $invalid_value = NULL )
    {
        if (empty($value)) {
            return $invalid_value;
        } else {
            $key = array_search( $value, $allowed_values_array);

            return ($key !== FALSE) ? $allowed_values_array[ $key ] : $invalid_value;
        }
    }
}

if (!function_exists('Arris\setOptionEnv')) {
    /**
     * use:
     *
     * use function ArrisFunctions\setOption as setOption;
     *
     * ($options, $key, $env_key, $default) =>  $options[ $key ]
     * ([], $key, $env_key, $default)       =>  get_env( $env_key )
     * ($arr, null, $env_key, $default)     =>  get_env( $env_key )
     * ([], null, null, $default)           =>  default
     * ([], null, null, null)               =>  null
     *
     * @param array $options
     * @param string $key
     * @param mixed $env_key
     * @param string $default_value
     * @return string
     */
    function setOptionEnv(array $options, $key, $env_key = null, $default_value = '')
    {
        if (empty($options) || is_null($key) || !array_key_exists($key, $options)) {
            if (is_null($env_key)) {
                return $default_value;
            }
            if (getenv($env_key) === false) {
                return $default_value;
            }
            return getenv($env_key);
        } else {
            return $options[$key];
        }
    }
}

if (!function_exists('Arris\setOption')) {
    function setOption(array $options, $key, $default_value = null)
    {
        return array_key_exists($key, $options) ? $options[$key] : $default_value;
    }
}

if (!function_exists('Arris\mb_trim_text')) {

    /**
     * trims text to a space then adds ellipses if desired
     * @param string $input text to trim
     * @param int $length in characters to trim to
     * @param bool $ellipses if ellipses (...) are to be added
     * @param bool $strip_html if html tags are to be stripped
     * @param string $ellipses_text text to be added as ellipses
     * @return string
     *
     * http://www.ebrueggeman.com/blog/abbreviate-text-without-cutting-words-in-half
     *
     * еще есть вариант: https://stackoverflow.com/questions/8286082/truncate-a-string-in-php-without-cutting-words (но без обработки тегов)
     * https://www.php.net/manual/ru/function.wordwrap.php - см комментарии
     */
    function mb_trim_text($input, $length, $ellipses = true, $strip_html = true, $ellipses_text = '...'):string
    {
        //strip tags, if desired
        if ($strip_html) {
            $input = strip_tags($input);
        }

        //no need to trim, already shorter than trim length
        if (mb_strlen($input) <= $length) {
            return $input;
        }

        //find last space within length
        $last_space = mb_strrpos(mb_substr($input, 0, $length), ' ');
        $trimmed_text = mb_substr($input, 0, $last_space);

        //add ellipses (...)
        if ($ellipses) {
            $trimmed_text .= $ellipses_text;
        }

        return $trimmed_text;
    }
}

if (!function_exists('Arris\mb_str_replace')) {

    /**
     * Multibyte string replace
     *
     * @param string|string[] $search  the string to be searched
     * @param string|string[] $replace the replacement string
     * @param string          $subject the source string
     * @param int             &$count  number of matches found
     *
     * @return string replaced string
     * @author Rodney Rehm, imported from Smarty
     *
     */
    function mb_str_replace($search, $replace, $subject, &$count = 0)
    {
        if (!is_array($search) && is_array($replace)) {
            return false;
        }
        if (is_array($subject)) {
            // call mb_replace for each single string in $subject
            foreach ($subject as &$string) {
                $string = \Arris\mb_str_replace($search, $replace, $string, $c);
                $count += $c;
            }
        } elseif (is_array($search)) {
            if (!is_array($replace)) {
                foreach ($search as &$string) {
                    $subject = \Arris\mb_str_replace($string, $replace, $subject, $c);
                    $count += $c;
                }
            } else {
                $n = max(count($search), count($replace));
                while ($n--) {
                    $subject = \Arris\mb_str_replace(current($search), current($replace), $subject, $c);
                    $count += $c;
                    next($search);
                    next($replace);
                }
            }
        } else {
            $parts = mb_split(preg_quote($search), $subject);
            $count = count($parts) - 1;
            $subject = implode($replace, $parts);
        }
        return $subject;

    }
}

if (!function_exists('Arris\array_map_to_integer')) {
    /**
     * Хелпер преобразования всех элементов массива к типу integer
     *
     * @param array $input
     * @return array
     */
    function array_map_to_integer($input): array
    {
        if (!is_array($input) || empty($input)) return [];

        return array_map(function ($i) {
            return intval($i);
        }, $input);
    }
}

if (!function_exists('Arris\array_fill_like_list')) {
    /**
     *
     * Аналог list($dataset['a'], $dataset['b']) = explode(',', 'AAAAAA,BBBBBB');
     * только с учетом размерности массивов и с дефолтными значениями
     *
     * Example: array_fill_like_list($dataset, ['a', 'b', 'c'], explode(',', 'AAAAAA,BBBBBB'), 'ZZZZZ' );
     *
     * @package KarelWintersky/CoreFunctions
     *
     * @param array $target_array
     * @param array $indexes
     * @param array $source_array
     * @param null $default_value
     */
    function array_fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL)
    {
        foreach ($indexes as $i => $index) {
            $target_array[ $index ] = array_key_exists($i, $source_array) ? $source_array[ $i ] : $default_value;
        }
    }
}

if (!function_exists('Arris\array_search_callback')) {

    /**
     * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
     *
     * @param array $a
     * @param callable $callback
     * @return mixed|null
     */
    function array_search_callback(array $a, callable $callback)
    {
        foreach ($a as $item) {
            $v = \call_user_func($callback, $item);
            if ( $v === true ) return $item;
        }
        return null;
    }
}

if (!function_exists('Arris\array_sort_in_given_order')) {

    /**
     * Sort array in given order by key
     * Returns array
     *
     * @param $array - array for sort [ [ id, data...], [ id, data...], ...  ]
     * @param $order - order (as array of sortkey values) [ id1, id2, id3...]
     * @param $sort_key - sorting key (id)
     * @return mixed
     */
    function array_sort_in_given_order(array $array, array $order, $sort_key):array
    {
        usort($array, function ($home, $away) use ($order, $sort_key) {
            $pos_home = array_search($home[$sort_key], $order);
            $pos_away = array_search($away[$sort_key], $order);
            return $pos_home - $pos_away;
        });
        return $array;
    }
} // sort_array_in_given_order

if (!function_exists('Arris\http_redirect')) {

    /**
     * HTTP-редирект.
     * Scheme редиректа определяется так: ENV->HTTP.REDIRECT_SCHEME > $scheme > 'http'
     *
     * @param $uri
     * @param bool $replace_prev_headers
     * @param int $code
     * @param string $scheme
     */
    function http_redirect($uri, $replace_prev_headers = false, $code = 302, $scheme = '')
    {
        $default_scheme = $scheme ?: getenv('HTTP.REDIRECT_SCHEME') ?: 'http';

        $location
            = (strstr($uri, "http://") or strstr($uri, "https://"))
            ? "Location: " . $uri
            : "Location: {$default_scheme}://{$_SERVER['HTTP_HOST']}{$uri}";

        header($location, $replace_prev_headers, $code);
        exit(0);
    }
}

if (!function_exists('Arris\pluralForm')) {
    /**
     *
     * @param $number
     * @param array $forms (array or string with glues, x|y|z or [x,y,z]
     * @param string $glue
     * @return string
     */
    function pluralForm($number, $forms, string $glue = '|'):string
    {
        if (is_string($forms)) {
            $forms = explode($forms, $glue);
        } elseif (!is_array($forms)) {
            return '';
        }

        if (count($forms) != 3) return '';

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

if (!function_exists('Arris\GUID')) {
    function GUID()
    {
        if (function_exists('\com_create_guid') === true) {
            return trim(\com_create_guid(), '{}');
        }

        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            return strtoupper( vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)) );
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

    }
}

// template function
if (!function_exists('Arris\reArrangeFilesPOST')) {
    /**
     *
     * @param $file_post
     * @return array
     */
    function reArrangeFilesPOST($file_post)
    {
        $rearranged_files = [];
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $rearranged_files[$i][$key] = $file_post[$key][$i];
            }
        }

        return $rearranged_files;
    }
}

if (!function_exists('Arris\float_to_fixed_string')) {
    function float_to_fixed_string($value, $separator = '.')
    {
        return str_replace(',', $separator, (string)$value);
    }
}




// template function
if (!function_exists('Arris\__template__')) {
    function __template__()
    {

    }
}


# -eof-
