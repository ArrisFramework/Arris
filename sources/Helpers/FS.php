<?php

namespace Arris\Helpers;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use UnexpectedValueException;

class FS implements FSInterface
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

    /**
     * Ищет файлы в директории с фильтрацией.
     *
     * Примеры:
     *  - findFiles('/src')                              => все файлы рекурсивно
     *  - findFiles('/src', extension: 'php')            => только .php
     *  - findFiles('/src', extension: ['php', 'json'])  => .php и .json
     *  - findFiles('/src', startWith: 'Controller')     => файлы начинаются на Controller
     *  - findFiles('/src', endWith: 'Test')             => файлы заканчиваются на Test
     *  - findFiles('/src', recursive: false)            => только текущая директория
     *
     * @param string $directory Путь к директории
     * @param string|array|null $extension Расширение(я) для фильтрации (без точки)
     * @param string|array|null $startWith Начало имени файла (строка или массив)
     * @param string|array|null $endWith Конец имени файла (строка или массив)
     * @param bool $recursive Рекурсивный обход
     * @return array<int, string> Массив абсолютных путей к файлам
     * @throws RuntimeException Если директория не существует
     */
    public static function findFiles(
        string $directory,
        string|array|null $extension = null,
        string|array|null $startWith = null,
        string|array|null $endWith = null,
        bool $recursive = true
    ): array {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (!is_dir($directory)) {
            throw new RuntimeException("Directory does not exist: {$directory}");
        }

        $extension = $extension !== null ? (array) $extension : null;
        $startWith = $startWith !== null ? (array) $startWith : null;
        $endWith = $endWith !== null ? (array) $endWith : null;

        $iterator = $recursive
            ? new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            )
            : new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        $results = [];

        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if (!$fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            $info = pathinfo($filename);

            // Фильтр по расширению
            if ($extension !== null) {
                if (!isset($info['extension']) || !in_array($info['extension'], $extension, true)) {
                    continue;
                }
            }

            // Фильтр по началу имени
            if ($startWith !== null) {
                $matched = false;
                foreach ($startWith as $prefix) {
                    if (str_starts_with($info['filename'], $prefix)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            // Фильтр по концу имени
            if ($endWith !== null) {
                $matched = false;
                foreach ($endWith as $suffix) {
                    if (str_ends_with($info['filename'], $suffix)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            $results[] = $fileInfo->getPathname();
        }

        return $results;
    }

}