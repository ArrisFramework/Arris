<?php

namespace Arris\Helpers;

class Misc implements MiscInterface
{
    /**
     * Максимальный размер закачиваемого файла
     *
     * @return int
     */
    public static function getMaxUploadFilesize():int
    {
        return min(
            self::get_ini_value('post_max_size'),
            self::get_ini_value('upload_max_filesize'),
            self::get_ini_value('memory_limit')
        );
    }

    /**
     * Получаем значение из php.ini файла
     * @param $key
     *
     * @return int
     */
    public static function get_ini_value($key): int
    {
        return Strings::returnBytes(ini_get($key));
    }



}