<?php

/**
 * Простой механизм доступа к API CDN Now через базовые средства.
 *
 */

namespace Arris\Toolkit;

use Exception;

interface CDNNowToolkitInterface
{
    /**
     * Инициализация конфигурации
     *
     * @param $username
     * @param $password
     * @param $client_token
     * @param $project_token
     * @return mixed
     */
    public static function init($username, $password, $client_token, $project_token);

    /**
     * Авторизация. Возвращает токен
     *
     * @return mixed
     * @throws Exception
     */
    public static function makeAuth();

    /**
     *
     * @param $year
     * @param $month
     * @param null $project_id
     * @return array
     */
    public static function getStatistic($year, $month, $project_id = null):array ;

    /**
     *
     * @param $url_list
     * @return mixed
     */
    public static function clearCache($url_list);
}

class CDNNowToolkit implements CDNNowToolkitInterface
{
    const URL_BASE = 'https://api.cdnnow.ru/api/v1/';
    const URL_AUTH = 'https://api.cdnnow.ru/api/v1/auth/login';

    private static $options = [
        'username'      => NULL,
        'password'      => NULL,
        'project_name'  => NULL,
        'project_token' => NULL,
        'client_token'  => NULL,
        'auth_token'    => NULL
    ];

    private static $curl;

    /**
     *
     * @param $method
     * @param $path
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    private static function request($method = 'GET', $path = '', array $data = [])
    {
        $url = self::URL_BASE . $path;

        //@todo: переписать, потому что stream_context_create поддерживает любые методы
        switch ($method) {
            case 'POST':
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                        'content' => http_build_query($data)
                    ],
                ]);
                $response = json_decode(@file_get_contents($url, false, $context), true);
                break;
            case 'GET':
                $response = json_decode(@file_get_contents($url . '?' . http_build_query($data)), true);
                break;
            default:
                throw new Exception('Invalid request method: ' . $method);
        }

        if (null === $response || !$response || !array_key_exists('status', $response) || !array_key_exists('data', $response)) {
            throw new Exception('Response is not valid');
        }

        if ('ok' !== $response['status']) {
            throw new Exception('Response status is not valid');
        }

        return $response;
    }

    public static function init($username, $password, $client_token, $project_token)
    {
        self::$options['username'] = $username;
        self::$options['password'] = $password;
        self::$options['client_token'] = $client_token;
        self::$options['project_token'] = $project_token;
    }

    /**
     * Очищает кэш CDN с автоматической авторизацией
     *
     * @param $url_list - список файлов
     * @throws Exception
     */
    public static function clearCache($url_list)
    {
        if (!is_array($url_list)) {
            $url_list[] = $url_list;
        }

        $auth_token = self::makeAuth()['token'];

        self::request(
            'GET',
            sprintf('clients/%s/projects/%s/cache-clear', self::$options['client_token'], self::$options['project_token']),
            [
                'token' => $auth_token,
                'masks' => $url_list
            ]
        );
    }

    /**
     * @throws Exception
     */
    public static function getStatistic($year, $month, $project_id = null):array
    {
        $auth_token = self::makeAuth()['token'];

        $response = self::request('GET', 'statistic/traffic/projects', [
            'token' => $auth_token,
            'year' => $year,
            'month' => $month,
            'client' => self::$options['client_token'],
            'project' => $project_id
        ]);

        if (empty($response['data'])) return [];

        return $response;
    }

    public static function makeAuth()
    {
        $response = self::request(
            'GET',
            'auth/login', [
                'username' => self::$options['username'],
                'password' => self::$options['password']
            ]
        );

        self::$options['auth_token'] = $response['data']['token'];

        return $response['data'];
    }


}