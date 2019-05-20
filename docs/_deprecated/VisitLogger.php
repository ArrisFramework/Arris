<?php
/**
 * User: Karel Wintersky
 *
 * Class VisitLogger
 * Namespace: Arris
 *
 * Date: 27.02.2018, time: 1:11
 */

namespace Arris;

use Arris\App;
use Arris\DB;

/**
 *
 *
 * Class VisitLogger
 * @package Arris
 */
class VisitLogger
{
    const VERSION = '1.2';

    const QUERY_DEFINITION_UNIQS = <<<QUERY_DEFINITION_UNIQS
CREATE TABLE IF NOT EXISTS `%s` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `counter_id`      INT(11) DEFAULT NULL,
  `counter_alias`   CHAR(8) DEFAULT NULL,
  `dayvisit`        DATE DEFAULT NULL,
  `ipv4`            INT(10) UNSIGNED DEFAULT NULL,
  `hits`            INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE  KEY `date+ipv4` (`dayvisit`,`ipv4`),
                    KEY `ipv4` (`ipv4`),
                    KEY `counter_id` (`counter_id`)
) ENGINE=MYISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
QUERY_DEFINITION_UNIQS;

    const QUERY_DEFINITION_ALL = <<<QUERY_DEFINITION_ALL
CREATE TABLE IF NOT EXISTS `%s` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `counter_id`      INT(11) DEFAULT NULL,
  `counter_alias`   CHAR(8) DEFAULT NULL,
  `tsvisit`         DATETIME DEFAULT NULL,
  `ipv4`            INT(10) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id`),
                    KEY `ipv4` (`ipv4`)
) ENGINE=MYISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

QUERY_DEFINITION_ALL;

    const QUERY_CHECK_TABLE_EXIST = <<<QUERY_CHECK_TABLE_EXIST
SHOW TABLES LIKE '%s';
QUERY_CHECK_TABLE_EXIST;

    const QUERY_INSERT_UNIQ = <<<QUERY_INSERT_UNIQ
INSERT INTO `%s` (counter_alias, dayvisit, ipv4, hits) VALUES(:counter_alias, CURDATE(), INET_ATON(:ipv4), 1)
ON DUPLICATE KEY UPDATE hits = hits+1;
QUERY_INSERT_UNIQ;

    const QUERY_INSERT_ALL = <<<QUERY_INSERT_ALL
INSERT INTO `%s` (counter_alias, tsvisit, ipv4) VALUES(:counter_alias, NOW(), INET_ATON(:ipv4))
QUERY_INSERT_ALL;

    /**
     * Storage handler (file | database)
     * @var null
     */
    private static $handler = NULL;

    private static $channel = NULL;

    private static $is_correct_config = FALSE;

    private static $is_log_unuq     = NULL;
    private static $is_log_all      = NULL;

    private static $target_file_path = NULL;
    private static $target_file_name = NULL;

    private static $target_db_unuq = NULL;
    private static $target_db_all  = NULL;


    /**
     *
     * @return null|string
     */
    private static function getIP()
    {
        if (php_sapi_name() === 'cli') return '127.0.0.1';

        if (!isset ($_SERVER['REMOTE_ADDR'])) {
            return NULL;
        }

        if (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)) {
            $http_x_forwared_for = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
            $client_ip = trim(end($http_x_forwared_for));
            if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
                return $client_ip;
            }
        }

        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ? $_SERVER['REMOTE_ADDR'] : NULL;
    }

    /**
     *
     * @return bool
     */
    private static function checkValidConfig() {
        if (!App::get('visitlog')) return FALSE;

        self::$handler = App::get('visitlog/handler');
        if (self::$handler === NULL) return FALSE;

        self::$is_log_unuq = App::get('visitlog/log_unuque');
        if (self::$is_log_unuq === NULL) return FALSE;

        self::$is_log_all = App::get('visitlog/log_all');
        if (self::$is_log_all === NULL) return FALSE;

        self::$channel = App::get('visitlog/log_channel'); // может быть NULL

        switch (self::$handler) {
            case 'file': {
                if (!App::get('visitlog/handler:file')) return FALSE;

                self::$target_file_path = App::get('visitlog/handler:file/file_log_path');
                if (self::$target_file_path === NULL) return FALSE;

                self::$target_file_name = App::get('visitlog/handler:file/file_log_name');
                if (self::$target_file_name === NULL) return FALSE;

                break;
            }

            case 'database': {
                $section = App::get('visitlog/handler:database');
                if (!$section) return FALSE;

                self::$target_db_unuq = App::get('visitlog/handler:database/table_log_unuque');
                if (self::$target_db_unuq === NULL) return FALSE;

                self::$target_db_all = App::get('visitlog/handler:database/table_log_all');
                if (self::$target_db_all === NULL) return FALSE;

                break;
            }

            default: {
                self::$handler = NULL;
                return FALSE;
            }
        }

        self::$is_correct_config = TRUE;

        return TRUE;
     }


    /**
     *
     */
    private static function log_to_file() {
        if (self::$is_log_all) {

            $root = (php_sapi_name() === 'cli') ? getcwd() : $_SERVER['DOCUMENT_ROOT'];
            $path = str_replace('$', $root, self::$target_file_path);

            $filename = $path . self::$target_file_name;

            $append_csv_header = !file_exists($filename);

            $f = fopen($filename, 'a');
            if ($append_csv_header) {
                fputcsv($f, [
                    "channel",
                    "date_atom",
                    "ipv4"
                ], ';');
            }

            fputcsv($f, [
                self::$channel,
                date(DATE_ATOM),
                self::getIP()
            ], ';');

            fclose($f);
        }

    }

    /**
     * @param $db_prefix
     * @return bool|int|mixed
     */
    private static function log_to_database($db_prefix){
        $insert_state = TRUE;

        $connection = DB::getConnection($db_prefix);

        if (self::$is_log_unuq) {
            $query = sprintf(self::QUERY_CHECK_TABLE_EXIST, self::$target_db_unuq);

            if (!$connection->query($query)->rowCount()) {
                $query = sprintf(self::QUERY_DEFINITION_UNIQS, self::$target_db_unuq);

                $connection->query($query);
            }

            $query = sprintf(self::QUERY_INSERT_UNIQ, self::$target_db_unuq);
            $sth = $connection->prepare($query);

            try {
                $sth->execute([
                    'ipv4'          =>  self::getIP(),
                    'counter_alias' =>  self::$channel
                ]);
            } catch (\PDOException $e) {
                $insert_state = $e->getCode();
            }
        }

        if (self::$is_log_all) {
            $query = sprintf(self::QUERY_CHECK_TABLE_EXIST, self::$target_db_all);

            if (!$connection->query($query)->rowCount()) {
                $query = sprintf(self::QUERY_DEFINITION_ALL, self::$target_db_all);

                $connection->query($query);
            }

            $query = sprintf(self::QUERY_INSERT_ALL, self::$target_db_all);
            $sth = $connection->prepare($query);

            try {
                $sth->execute([
                    'ipv4'          =>  self::getIP(),
                    'counter_alias' =>  self::$channel
                ]);
            } catch (\PDOException $e) {
                $insert_state = $e->getCode();
            }
        }

        return $insert_state;
    }

    /**
     *
     */
    private static function log_to_monolog()
    {
    }



    /**
     * Ожидает наличие секции visitlog в глобальном конфиге
     *
     * @param null $db_prefix
     * @return bool
     */
    public static function log($db_prefix = NULL) {
        if (!self::checkValidConfig()) return FALSE;

        switch (self::$handler) {
            case 'database': {
                self::log_to_database($db_prefix);
                break;
            }

            case 'file': {
                self::log_to_file();
                break;
            }

            case 'monolog': {
                self::log_to_monolog();
                break;
            }
        }

        return TRUE;
    }



}