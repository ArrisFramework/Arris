<?php


namespace Arris\Helpers;


class FS
{
    /**
     *
     * @param $dir
     * @return bool
     */
    public static function rmdir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $target = "{$dir}/{$file}";
            (is_dir($target)) ? self::rmdir($target) : unlink($target);
        }
        return rmdir($dir);
    }

}