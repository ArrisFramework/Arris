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
    public static function readline($prompt, $allowed_pattern = '/.*/', $strict_mode = false);

    /**
     * Устанавливает флаги обработки разных тегов в функции echo_status()
     * @param bool|bool $will_strip - вырезать ли все лишние теги после обработки заменяемых?
     * @param bool|bool $will_decode - преобразовывать ли html entities в их html-представление?
     */
    public static function set_mode(bool $will_strip = false, bool $will_decode = false);

    /**
     * Генерирует сообщение - отформатированное ESCAPE-последовательностями для CLI
     * и не отформатированное (с тегами) для WEB
     *
     * @param string $message
     * @param bool $break_line
     * @return array|string|string[]|null
     */
    public static function get_message(string $message = "", bool $break_line = true);

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
     * @param bool|TRUE $break_line
     */
    public static function say($message = "", $break_line = true);

}