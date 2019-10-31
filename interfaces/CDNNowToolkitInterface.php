<?php

namespace Arris;

use Exception;

interface CDNNowToolkitInterface
{
    /**
     * Инициализация конфигурации
     *
     * @param $username
     * @param $password
     * @param $client_id
     * @param $project_id
     * @return mixed|void
     * @throws \ErrorException
     */
    public static function init($username, $password, $client_id, $project_id);

    /**
     * Авторизация. Возвращает true при успешной авторизации, false в противном случае.
     * Состояние
     *
     * @return bool
     * @throws Exception
     */
    public static function makeAuth();

    /**
     * Возвращает статистику по указанному проекту или всем проектам
     *
     * @param $year
     * @param $month
     * @param null $project_id
     * @return array
     */
    public static function getStatistic($year, $month, $project_id = null);

    /**
     * Очищает кэш CDN по маске.
     *
     * Варианты маски:
     * [] - очистить весь кэш
     * [ '/img/*' ] - файлы типа '/img/one.jpg', '/img/two.jpg' etc
     * [ '/img/*', '/video/*' ] - несколько масок, аналогично предыдущему случаю
     * [ '/img/my_super_image.jpg?id=8193737182253' ] - удалить по строгому соответствию
     *
     * @param $url_list
     * @return mixed
     */
    public static function clearCache(array $url_list = []);

    /**
     * @return mixed
     */
    public static function getState();
}