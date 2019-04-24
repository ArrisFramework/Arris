<?php
/**
 * User: Karel Wintersky
 *
 * Class CLIConsole
 * Namespace: Arris
 *
 * Date: 04.03.2018, time: 19:27
 */

namespace Arris;

/**
 * Class CLIConsole
 * v 1.0 : + readline()
 * v 1.1 : + echo_status_cli(), +echo_status()
 * v 1.2 : + echo_status_setmode() и обработка флагов преобразования сущностей
 * v 1.3 : + обработка <BR> в echo_status_cli()
 * v 1.4 : add to AFW
 *
 */
class CLIConsole
{
    const VERSION = '1.4';

    const FOREGROUND_COLORS = [
        'black'         => '0;30',
        'dark gray'     => '1;30',
        'dgray'         => '1;30',
        'blue'          => '0;34',
        'light blue'    => '1;34',
        'lblue'         => '1;34',
        'green'         => '0;32',
        'light green'   => '1;32',
        'lgreen'        => '1;32',
        'cyan'          => '0;36',
        'light cyan'    => '1;36',
        'lcyan'         => '1;36',
        'red'           => '0;31',
        'light red'     => '1;31',
        'lred'          => '1;31',
        'purple'        => '0;35',
        'light purple'  => '1;35',
        'lpurple'       => '1;35',
        'brown'         => '0;33',
        'yellow'        => '1;33',
        'light gray'    => '0;37',
        'lgray'         => '0;37',
        'white'         => '1;37'
    ];

    const BACKGROUND_COLORS = [
        'black'     => '40',
        'red'       => '41',
        'green'     => '42',
        'yellow'    => '43',
        'blue'      => '44',
        'magenta'   => '45',
        'cyan'      => '46',
        'light gray'=> '47'
    ];

    private static $echo_status_cli_flags = [
        'strip_tags'        => false,
        'decode_entities'   => false
    ];

    /**
     * ConsoleReadline::readline('Введите число от 1 до 999: ', '/^\d{1,3}$/');
     * ConsoleReadline::readline('Введите число от 100 до 999: ', '/^\d{3}$/');
     *
     * @param $prompt -
     * @param $allowed_pattern
     * @param bool|FALSE $strict_mode
     * @return bool|string
     */
    public static function readline($prompt, $allowed_pattern = '/.*/', $strict_mode = FALSE)
    {
        if ($strict_mode) {
            if ((substr($allowed_pattern, 0, 1) !== '/') || (substr($allowed_pattern, -1, 1) !== '/')) {
                return FALSE;
            }
        } else {
            if (substr($allowed_pattern, 0, 1) !== '/')
                $allowed_pattern = '/' . $allowed_pattern;
            if (substr($allowed_pattern, -1, 1) !== '/')
                $allowed_pattern .= '/';
        }

        do {
            $result = readline($prompt);

        } while (preg_match($allowed_pattern, $result) !== 1);
        return $result;
    }

    /**
     * Печатает в консоли цветное сообщение.
     * Допустимые форматтеры:
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     * @param string $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status_cli($message = "", $breakline = TRUE)
    {
        $fgcolors = self::FOREGROUND_COLORS;

        // replace <br>
        $pattern_br = '#(?<br>\<br\s?\/?\>)#U';
        $message = preg_replace_callback($pattern_br, function ($matches) {
            return PHP_EOL;
        }, $message);

        // replace <hr>
        $pattern_hr = '#(?<hr>\<hr\s?\/?\>)#U';
        $message = preg_replace_callback($pattern_hr, function ($matches) {
            return PHP_EOL . str_repeat('-', 80) . PHP_EOL;
        }, $message);

        // replace <font>
        $pattern_font = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
        $message = preg_replace_callback($pattern_font, function ($matches) use ($fgcolors) {
            $color = (PHP_VERSION_ID < 70000)
                ? isset($fgcolors[$matches['Color']]) ? $fgcolors[$matches['Color']] : $fgcolors['white']    // php below 7.0
                : $fgcolors[$matches['Color']] ?? $fgcolors['white '];                                           // php 7.0+
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // replace <strong>
        $pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
        $message = preg_replace_callback($pattern_strong, function ($matches) use ($fgcolors) {
            $color = $fgcolors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // вырезает все лишние таги (если установлен флаг)
        if (self::$echo_status_cli_flags['strip_tags'])
            $message = strip_tags($message);

        // преобразует html entity-сущности (если установлен флаг)
        if (self::$echo_status_cli_flags['decode_entities'])
            $message = htmlspecialchars_decode($message, ENT_QUOTES | ENT_HTML5);

        if ($breakline === TRUE) $message .= PHP_EOL;
        echo $message;
    }

    /**
     * Wrapper around echo/echo_status_cli
     * Выводит сообщение на экран. Если мы вызваны из командной строки - заменяет теги на управляющие последовательности.
     * @param $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status($message = "", $breakline = TRUE)
    {
        if (php_sapi_name() === "cli") {
            self::echo_status_cli($message, $breakline);
        } else {
            if ($breakline === TRUE) $message .= PHP_EOL . "<br/>\r\n";
            echo $message;
        }
    }

    /**
     * Устанавливает флаги обработки разных тегов в функции echo_status()
     * @param bool|FALSE $will_strip - вырезать ли все лишние теги после обработки заменяемых?
     * @param bool|FALSE $will_decode - преобразовывать ли html entities в их html-представление?
     */
    public static function echo_status_setmode($will_strip = FALSE, $will_decode = FALSE)
    {
        self::$echo_status_cli_flags = array(
            'strip_tags'        => $will_strip,
            'decode_entities'   => $will_decode
        );
    }

}