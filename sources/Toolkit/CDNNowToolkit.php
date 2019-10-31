<?php

/**
 * Простой механизм доступа к API CDN Now через базовые средства.
 *
 */

namespace Arris\Toolkit;

use Arris\CDNNowToolkitInterface;
use Curl\Curl;

class CDNNowToolkit implements CDNNowToolkitInterface
{
    const URL_BASE = 'https://api.cdnnow.ru/api/v1';
    const URL_AUTH = '/auth/login';
    const URL_STAT = '/statistic/traffic/projects';

    private static $options = [
        'username'      => NULL,
        'password'      => NULL,
        'project_name'  => NULL,
        'project_token' => NULL,
        'client_token'  => NULL,
        'auth_token'    => NULL
    ];

    /**
     * @var \Curl\Curl $curl;
     */
    private static $curl;

    public static function init($username, $password, $client_id, $project_id)
    {
        self::$options['username'] = $username;
        self::$options['password'] = $password;
        self::$options['client_token'] = $client_id;
        self::$options['project_token'] = $project_id;

        self::$curl = new Curl();
    }

    public static function makeAuth()
    {
        self::$curl->post(self::URL_BASE . self::URL_AUTH, [
            'username'  =>  self::$options['username'],
            'password'  =>  self::$options['password']
        ]);

        $response_auth = self::$curl->response;

        //@throw error state
        if (!isset($response_auth->data)) return false;
        if (!isset($response_auth->data->client)) return false;
        if (!isset($response_auth->data->token)) return false;

        self::$options['client_token'] = $response_auth->data->client;
        self::$options['auth_token'] = $response_auth->data->token;
        return true;
    }

    /**
     *
     * @param $year
     * @param $month
     * @param null $project_id
     * @return array
     */
    public static function getStatistic($year, $month, $project_id = null)
    {
        $dataset = [
            'year'      =>  $year,
            'month'     =>  $month,
            'client'    =>  self::$options['client_token'],
            'token'     =>  self::$options['auth_token']
        ];
        if (!is_null($project_id)) {
            $dataset['project'] = $project_id;
        }
        self::$curl->get( self::URL_BASE . self::URL_STAT, $dataset );

        return self::$curl->response;
    }

    /**
     *
     * @param array $url_list
     * @return mixed
     */
    public static function clearCache(array $url_list = [])
    {
        $url = self::URL_BASE . sprintf('/clients/%s/projects/%s/cache-clear', self::$options['client_token'], self::$options['project_token']);

        $dataset = [
            'token' =>  self::$options['auth_token'],
        ];
        if (!empty($url_list)) {
            $dataset['masks'] = $url_list;
        }

        self::$curl->post($url, $dataset);

        return self::$curl->response;
    }

    public static function getState()
    {
        return self::$curl;
    }
}

# -eof-
