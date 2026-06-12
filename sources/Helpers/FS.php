<?php

namespace Arris\Helpers;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use UnexpectedValueException;

class FS
{
    /**
     * Рекурсивно удаляет директорию и всё её содержимое.
     * Использует SPL-итераторы для эффективности и безопасности.
     *
     * Примеры:
     *  - rmdir('/tmp/cache')                    => удаляет директорию полностью
     *  - rmdir('/tmp/cache', preserveRoot: true) => удаляет содержимое, но оставляет саму директорию
     *  - rmdir('/tmp/cache', followSymlinks: false) => не переходит по symlink (безопаснее)
     *
     * @param string $directory Путь к директории
     * @param bool $preserveRoot Сохранить корневую директорию (удалить только содержимое)
     * @param bool $followSymlinks Переходить по символическим ссылкам (осторожно: риск циклов)
     * @return bool true при успешном удалении
     * @throws RuntimeException Если директория не существует или не может быть удалена
     */
    public static function rmdir(
        string $directory,
        bool $preserveRoot = false,
        bool $followSymlinks = false
    ): bool {
        // Нормализация пути
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (!is_dir($directory)) {
            throw new RuntimeException("Directory does not exist: {$directory}");
        }

        // Проверяем, не является ли директория symlink
        if (!$followSymlinks && is_link($directory)) {
            return unlink($directory);
        }

        try {
            // CHILD_FIRST: сначала обрабатываем содержимое, потом саму директорию
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    FilesystemIterator::SKIP_DOTS | // Пропускаем . и ..
                    ($followSymlinks ? FilesystemIterator::FOLLOW_SYMLINKS : 0)
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                $pathname = $fileInfo->getPathname();

                // Защита от symlink-петель
                if (!$followSymlinks && is_link($pathname)) {
                    if (!unlink($pathname)) {
                        throw new RuntimeException("Failed to remove symlink: {$pathname}");
                    }
                    continue;
                }

                if ($fileInfo->isDir() && !$fileInfo->isLink()) {
                    if (!rmdir($pathname)) {
                        throw new RuntimeException("Failed to remove directory: {$pathname}");
                    }
                } else {
                    if (!unlink($pathname)) {
                        throw new RuntimeException("Failed to remove file: {$pathname}");
                    }
                }
            }

            // Удаляем корневую директорию, если не требуется её сохранить
            if (!$preserveRoot) {
                if (!rmdir($directory)) {
                    throw new RuntimeException("Failed to remove root directory: {$directory}");
                }
            }

            return true;
        } catch (UnexpectedValueException $e) {
            throw new RuntimeException("Cannot iterate directory: {$directory}", 0, $e);
        }
    }

}