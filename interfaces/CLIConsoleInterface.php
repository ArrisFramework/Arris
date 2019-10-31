<?php

namespace Arris;

interface CLIConsoleInterface {

    /**
     * CLIConsole::readline('Введите число от 1 до 999: ', '/^\d{1,3}$/');
     * CLIConsole::readline('Введите число от 100 до 999: ', '/^\d{3}$/');
     *
     * @param $prompt -
     * @param $allowed_pattern
     * @param bool|FALSE $strict_mode
     * @return bool|string
     */
    public static function readline($prompt, $allowed_pattern = '/.*/', $strict_mode = FALSE);

    /**
     * Устанавливает флаги обработки разных тегов в функции echo_status()
     * @param bool|FALSE $will_strip - вырезать ли все лишние теги после обработки заменяемых?
     * @param bool|FALSE $will_decode - преобразовывать ли html entities в их html-представление?
     */
    public static function echo_status_setmode($will_strip = FALSE, $will_decode = FALSE);

    /**
     * Печатает в консоли цветное сообщение. Рекомендуемый к использованию метод.
     *
     * Допустимые форматтеры:
     *
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     *
     * @param string $message
     * @param bool|TRUE $breakline
     */
    public static function say($message = "", $breakline = TRUE);

    /**
     * [OLD NAME] Печатает в консоли цветное сообщение.
     * Допустимые форматтеры:
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     *
     * @param string $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status_cli($message = "", $breakline = TRUE);

    /**
     * [OLD NAME] Wrapper around echo/echo_status_cli
     * Выводит сообщение на экран. Если мы вызваны из командной строки - заменяет теги на управляющие последовательности.
     *
     * @param $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status($message = "", $breakline = TRUE);
}