<?php
/**
 * User: Karel Wintersky
 *
 * Class Auth
 * Namespace: Arris
 *
 * Date: 09.04.2018, time: 4:08
 */

namespace Arris;

use PHPAuth\Config as PHPAuthConfig;
use PHPAuth\Auth as PHPAuth;
use DB;

/**
 *
 * Class Auth
 * @package Arris
 */
class Auth
{
    private static $instance = NULL;
    private static $phpauth = NULL;

    /**
     * Проверяет существование инстанса этого класса
     *
     * @return bool
     */
    public static function checkInstance()
    {
        return NULL !== self::$instance;
    }

    /**
     * Возвращает инстанс PHPAuth или создает инстанс этого класса
     *
     * @return PHPAuth
     */
    public static function getInstance():PHPAuth
    {
        if (!self::checkInstance()) {
            self::init();
        }

        return self::$phpauth;
    }

    /**
     *
     */
    public static function init()
    {
        self::$instance = new self();
    }

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        $phpauth_config = App::get('phpauth');
        $phpauth_db_section_prefix = App::get('phpauth/db_prefix', NULL);

        $db_connection = DB::getConnection( $phpauth_db_section_prefix );

        $phpauth_config_class = new PHPAuthConfig($db_connection, 'array', $phpauth_config);

        self::$phpauth = new PHPAuth( $db_connection , $phpauth_config_class );
    }


    /* === Arris\Auth implementations === */

    /**
     * Логинит пользователя и устанавливает необходимые куки.
     *
     * @param $email
     * @param $password
     * @param bool|false $remember
     * @param null $captcha_response
     * @return array
     */
    public static function login($email, $password, $remember = false, $captcha_response = NULL)
    {
        $instance = self::getInstance();

        $auth_result = $instance->login($email, $password, $remember, $captcha_response);

        if (!$auth_result['error']) {
            \setcookie($instance->config->cookie_name, $auth_result['hash'], time()+$auth_result['expire'], $instance->config->cookie_path);
            self::unsetcookie(App::get('phpauth_cookies/cookie_userlogin_new_registered'));
        }

        return $auth_result;
    }

    /**
     * Регистрирует пользователя и устанавливает куки
     *
     * @param $email
     * @param $password
     * @param $repeatpassword
     * @param array $params
     * @param null $captcha
     * @param null $sendmail
     * @return array
     */
    public static function register($email, $password, $repeatpassword, $params = Array(), $captcha = NULL, $sendmail = NULL)
    {
        $instance = self::getInstance();

        $auth_result = $instance->register($email, $password, $repeatpassword, $params, $captcha, $sendmail);

        if (!$auth_result['error']) {
            setcookie(App::get('phpauth_cookies/cookie_userlogin_new_registered'), $email, time()+60*60, $instance->config->cookie_path);
        }
        return $auth_result;
    }

    /**
     *
     * @return mixed
     */
    public static function logout()
    {
        /**
         * @var \PHPAuth\Auth;
         */
        $instance = self::getInstance();

        $return['error'] = true;

        $hash = $instance->getSessionHash();
        $userinfo = $instance->getUser($instance->getSessionUID($hash));

        if ($hash === NULL) {
            return $return;
        }

        $auth_result = $instance->logout($hash);

        if ($auth_result) {
            self::unsetcookie($instance->config->cookie_name);
            setcookie(App::get('phpauth_cookies/cookie_userlogin_last_logged'), $userinfo['email'], time()+60*60*24, $instance->config->cookie_path);
            $return['error'] = false;
        }
        return $return;
    }

    /**
     * Проверяет, залогинен ли пользователь
     *
     * @return bool
     */
    public static function isLogged()
    {
        return self::getInstance()->isLogged();
    }


    /**
     * Удаляет куку
     * @param $cookie_name
     * @param string $cookie_path
     */
    private static function unsetcookie($cookie_name, $cookie_path = '/')
    {
        unset($_COOKIE[$cookie_name]);
        \setcookie($cookie_name, null, -1, $cookie_path);
    }


    /**
     * __callStatic method
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return self::getInstance()->$method(...$args);
    }

    /**
     *
     * @return array
     */
    public static function getCurrentUserInfo()
    {
        $instance = self::getInstance();
        return $instance->getUser($instance->getSessionUID($instance->getSessionHash()));
    }

}