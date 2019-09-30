<?php


namespace Arris\AJUR;

use Arris\AppLogger;
use Exception;
use Monolog\Logger;
use Throwable;

class AJURCurrency
{
    const credentials = [
        'currencies'    =>  [ 'R01235', 'R01239' ], // usd, euro
        'API_key'       =>  '',
        'URL'           =>  'http://www.cbr.ru/scripts/XML_daily.asp'
    ];

    /**
     * @var Logger
     */
    public static $_logger;

    public static $_currency_file;

    public static function init($source, Logger $logger)
    {
        self::$_currency_file = $source;

        self::$_logger
            = $logger instanceof Logger
            ? $logger
            : AppLogger::addNullLogger();
    }

    public static function formatCurrencyValue($value)
    {
        //@todo: at PHP 7.4 must use NumberFormatter::formatCurrency() and move it to Arris\AJUR\Currency

        return money_format('%i', str_replace(',', '.', $value));
    }

    public static function getCurrencyData($fetch_date = null)
    {
        $fetch_date = $fetch_date ?? (new \DateTime())->format('d/m/Y');
        $url = self::credentials['URL'] . '?' . http_build_query(['date_req' => $fetch_date]);

        try {
            $ch = curl_init($url);
            curl_setopt ($ch, CURLOPT_COOKIE, "stay_here=1");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_MAXREDIRS,10);

            $output = curl_exec ($ch);
            curl_close($ch);

            $xml = simplexml_load_string($output);


            $json = json_encode( $xml );
            return json_decode( $json , true );

        } catch (Throwable $e) {
            self::$_logger->notice("[EXCEPTION] Error code: ", [ $e->getCode(), $e->getMessage() ]);
            return null;
        }
    }

    public static function loadCurrencies(): array
    {
        $MAX_CURRENCY_STRING_LENGTH = 5;

        $file_currencies = self::$_currency_file;

        $current_currency = [];

        try {
            $file_content = file_get_contents($file_currencies);
            if ($file_content === FALSE) throw new Exception("Currency file `{$file_currencies}` not found", 1);

            $file_content = json_decode($file_content, true);
            if (($file_content === NULL) || !is_array($file_content)) throw new Exception("Currency data can't be parsed", 2);

            if (!array_key_exists('data', $file_content)) throw new Exception("Currency file does not contain DATA section", 3);

            // добиваем валюту до $MAX_CURRENCY_STRING_LENGTH нулями (то есть 55.4 (4 десятых) добивается до 55.40 (40 копеек)
            foreach ($file_content['data'] as $currency_code => $currency_data) {
                $current_currency[$currency_code] = str_pad($currency_data, $MAX_CURRENCY_STRING_LENGTH, '0');
            }

        } catch (Exception $e) {
            self::$_logger->error('[ERROR] Load Currency ', [$e->getMessage()]);
        }

        return $current_currency;
    }

}