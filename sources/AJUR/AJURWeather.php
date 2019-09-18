<?php

/*
Использовать:

AJURWeather::init( AppLogger::scope('weather') );

AJURWeather::load_weather_local()

 */

namespace Arris\AJUR;

use Arris\AppLogger;
use Monolog\Logger;
use Cmfcmf\OpenWeatherMap\CurrentWeather;

interface AJURWeatherInterface {
    public static function init($logger);
    public static function load_weather_local($district_id = 0, $source_file = null);

    public static function makeWeatherInfo($id, CurrentWeather $weather):array;
    public static function makeWeatherInfoJSON(int $id, CurrentWeather $region_weather):array;
}

class AJURWeather implements AJURWeatherInterface
{
    const VERSION = "1.14";

    /**
     * Error consts
     */
    const ERROR_SOURCE_FILE_NOT_DEFINED = 1;
    const ERROR_SOURCE_FILE_NOT_READABLE = 2;
    const ERROR_SOURCE_FILE_PARSING_ERROR = 3;
    const ERROR_SOURCE_FILE_HAVE_NO_DATA = 4;
    const ERROR_NO_SUCH_DISTRICT_ID = 5;
    const ERROR_SOURCE_FILE_READING_ERROR = 6;

    /**
     * список смежности регионов леобласти
     *
     * Ключ - внутренний идентификатор региона на сайте 47news.ru (0 - регион не выбран, СПб)
     * Значение - массив смежных регионов
     */
    const lo_adjacency_lists = [
        1   =>  [ 13, 4, 15, 6 ],
        2   =>  [ 14, 16, 7, 8  ],
        3   =>  [ 11, 16, 7, 12 ],
        4   =>  [ 5, 13, 14, 15 ],
        5   =>  [ 14, 15, 17, 4],
        6   =>  [ 9, 13, 15, 1 ],
        7   =>  [ 12, 16, 3, 2 ],
        8   =>  [ 2, 11, 14, 16],
        9   =>  [ 1, 6, 13, 15],
        10   =>  [ 17, 18, 5, 7],
        11   =>  [ 3, 8, 16, 7 ],
        12   =>  [ 7, 3, 16, 18 ],
        13   =>  [ 1, 4, 6, 15 ],
        14   =>  [ 2, 5, 4, 8 ],
        15   =>  [ 4, 5, 13, 6 ],
        16   =>  [ 2, 3, 7, 8 ],
        17   =>  [ 5, 10, 18, 14 ],
        18   =>  [ 12, 7, 10, 17 ],
    ];

    /**
     * таблица маппинга регионов ленобласти на таблицу регионов OWMID
     *
     * Ключ: внутренний код на сайте
     * Значение: OWM ID
     */
    const map_intid_to_owmid = [
        // субрайоны Санкт-Петербурга
        812 =>  [
            0   => 536203,
        ],
        // регионы ленобласти (внутренний ID на сайте 47news.ru => код региона OWM)
        813 =>  [
            0   =>  536203,             // Санкт-Петербург (центр)
            1   =>  575410,             // 'Бокситогорский район'
            2   =>  561887,             // 'Гатчинский район'
            3   =>  548602,             // 'Кингисеппский район'
            4   =>  548442,             // 'Киришский район'
            5   =>  548392,             // 'Кировский район'
            6   =>  534560,             // 'Лодейнопольский район'
            7   =>  534341,             // 'Ломоносовский район'
            8   =>  533690,             // 'Лужский район'
            9   =>  508034,             // 'Подпорожский район'
            10  =>  505230,             // 'Приозерский район'
            11  =>  492162,             // 'Сланцевский район'
            12  =>  490172,             // 'Сосновоборский округ'
            13  =>  483019,             // 'Тихвинский район'
            14  =>  481964,             // 'Тосненский район'
            15  =>  472722,             // 'Волховский район'
            16  =>  472357,             // 'Волосовский район'
            17  =>  471101,             // 'Всеволожский район'
            18  =>  470546              // 'Выборгский район'
        ]
    ];

    /**
     * owm_id       -- код региона в таблицах OpenWeatherMap
     * geoname_en   -- гео название на англ. языке
     * geoname_ru   -- гео название на русском
     * lon          -- координаты центра региона
     * lat
     * group_code   -- какой группе принадлежит регион (используется телефонный код)
     */
    const outer_regions = [
        536203 => [
            'owm_id' => 536203,
            'geoname_en' => 'Sankt-Peterburg',
            'geoname_ru' => 'Санкт-Петербург',
            'lon' => 30.25,
            'lat' => 59.916668,
            'group_code' => 812,
        ],
        // Ленобласть
        575410 => [
            'owm_id' => 575410,
            'geoname_en' => 'Boksitogorsk',
            'geoname_ru' => 'Бокситогорский район',
            'lon' => 33.84853,
            'lat' => 59.474049,
            'group_code' => 813,
        ],
        561887 => [
            'owm_id' => 561887,
            'geoname_en' => 'Gatchina',
            'geoname_ru' => 'Гатчинский район',
            'lon' => 30.12833,
            'lat' => 59.576389,
            'group_code' => 813,
        ],
        548602 => [
            'owm_id' => 548602,
            'geoname_en' => 'Kingisepp',
            'geoname_ru' => 'Кингисеппский район',
            'lon' => 28.61343,
            'lat' => 59.37331,
            'group_code' => 813,
        ],
        548442 => [
            'owm_id' => 548442,
            'geoname_en' => 'Kirishi',
            'geoname_ru' => 'Киришский район',
            'lon' => 32.020489,
            'lat' => 59.447121
        ],
        548392 => [
            'owm_id' => 548392,
            'geoname_en' => 'Kirovsk',
            'geoname_ru' => 'Кировский район',
            'lon' => 30.99507,
            'lat' => 59.881008
        ],
        534560 => [
            'owm_id' => 534560,
            'geoname_en' => 'Lodeynoye Pole',
            'geoname_ru' => 'Лодейнопольский район',
            'lon' => 33.553059,
            'lat' => 60.726002
        ],
        534341 => [
            'owm_id' => 534341,
            'geoname_en' => 'Lomonosov',
            'geoname_ru' => 'Ломоносовский район',
            'lon' => 29.77253,
            'lat' => 59.90612
        ],
        533690 => [
            'owm_id' => 533690,
            'geoname_en' => 'Luga',
            'geoname_ru' => 'Лужский район',
            'lon' => 29.84528,
            'lat' => 58.737221
        ],
        508034 => [
            'owm_id' => 508034,
            'geoname_en' => 'Podporozhye',
            'geoname_ru' => 'Подпорожский район',
            'lon' => 34.170639,
            'lat' => 60.91124
        ],
        505230 => [
            'owm_id' => 505230,
            'geoname_en' => 'Priozersk',
            'geoname_ru' => 'Приозерский район',
            'lon' => 30.12907,
            'lat' => 61.03928
        ],
        492162 => [
            'owm_id' => 492162,
            'geoname_en' => 'Slantsy',
            'geoname_ru' => 'Сланцевский район',
            'lon' => 28.09137,
            'lat' => 59.118172
        ],
        490172 => [
            'owm_id' => 490172,
            'geoname_en' => 'Sosnovyy Bor',
            'geoname_ru' => 'Сосновоборский округ',
            'lon' => 29.116671,
            'lat' => 59.900002
        ],
        483019 => [
            'owm_id' => 483019,
            'geoname_en' => 'Tikhvin',
            'geoname_ru' => 'Тихвинский район',
            'lon' => 33.599369,
            'lat' => 59.645111
        ],
        481964 => [
            'owm_id' => 481964,
            'geoname_en' => 'Tosno',
            'geoname_ru' => 'Тосненский район',
            'lon' => 30.877501,
            'lat' => 59.540001
        ],
        472722 => [
            'owm_id' => 472722,
            'geoname_en' => 'Volhov',
            'geoname_ru' => 'Волховский район',
            'lon' => 32.338188,
            'lat' => 59.9258
        ],
        471101 => [
            'owm_id' => 471101,
            'geoname_en' => 'Vsevolozhsk',
            'geoname_ru' => 'Всеволожский район',
            'lon' => 30.637159,
            'lat' => 60.020432
        ],
        472357 => [
            'owm_id' => 472357,
            'geoname_en' => 'Volosovo',
            'geoname_ru' => 'Волосовский район',
            'lon' => 59.45,
            'lat' => 29.48
        ],
        470546 => [
            'owm_id' => 470546,
            'geoname' => 'Vyborg',
            'geoname_ru' => 'Выборгский район',
            'lon' => 28.752831,
            'lat' => 60.70763
        ],
    ];

    const icons_conversion = [
        // clear sky - чистое небо
        '01d'   =>  '31d',
        '01n'   =>  '31n',

        // few clouds - малая облачность
        '02d'   =>  '30d',
        '02n'   =>  '30n',

        // scattered clouds - рассеянная облачность
        '03d'   =>  '26d',
        '03n'   =>  '26n',

        // broken clouds - облачно с прояснениями
        '04d'   =>  '27d',
        '04n'   =>  '27n',

        // shower rain  =- проливной дождь
        '09d'   =>  '10d',
        '09n'   =>  '10n',

        // rain - дождь
        '10d'   =>  '9d',
        '10n'   =>  '9n',

        // thunderstorm - гроза
        '11d'   =>  '0d',
        '11n'   =>  '0n',

        // snow - снег
        '13d'   =>  '6d',
        '13n'   =>  '6n',

        // mist - туман
        '50d'   =>  '22d',
        '50n'   =>  '22n',
    ];

    /**
     * @var Logger
     */
    private static $logger;

    public static function init($logger)
    {
        self::$logger = $logger;
    }

    /**
     *
     * @param $district_id
     * @param $source_file
     * @return array
     * @throws \Exception
     */
    public static function load_weather_local($district_id = 0, $source_file = null)
    {
        $current_weather = [];

        try {
            if (is_null($source_file))
                throw new \Exception("Weather file not defined", self::ERROR_SOURCE_FILE_NOT_DEFINED);

            if (!is_readable($source_file))
                throw new \Exception("Weather file `{$source_file}` not found", self::ERROR_SOURCE_FILE_NOT_READABLE);

            $file_content = \file_get_contents($source_file);
            if ($file_content === FALSE)
                throw new \Exception("Error reading weather file `{$source_file}`", self::ERROR_SOURCE_FILE_READING_ERROR);

            $file_content = \json_decode($file_content, true);

            if (($file_content === NULL) || !\is_array($file_content))
                throw new \Exception("Weather data can't be parsed", self::ERROR_SOURCE_FILE_PARSING_ERROR);

            if (!\array_key_exists('data', $file_content))
                throw new \Exception("Weather file does not contain DATA section", self::ERROR_SOURCE_FILE_HAVE_NO_DATA);

            $current_weather = $file_content['data'];

            // Погода загружена. Перемешаем массив.
            \shuffle($current_weather);

            // Район - 0 (все) ?
            if ($district_id === 0) {
                return $current_weather; // возвращаем перемешанный массив с погодой
            }

            // Район не равен нулю, нужно построить массив с погодой для указанного района и ближайших:

            // проверим, есть ли такой идентификатор района вообще в массиве кодов районов.
            // Если нет - кидаем исключение (записываем ошибку), но возвращаем массив со случайной погодой
            if (!array_key_exists($district_id, self::map_intid_to_owmid[ 813 ]))
                throw new \Exception("Given district id ({$district_id}) does not exist in MAP_INTID_TO_OWMID set", self::ERROR_NO_SUCH_DISTRICT_ID);

            /**
             * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
             */
            $local_weather = [];

            // первый элемент - погода текущего региона
            $district_owmid = self::map_intid_to_owmid[ 813 ][ $district_id ];

            $local_weather[] = array_search_callback($current_weather, function ($item) use ($district_owmid){
                return ($item['id'] == $district_owmid);
            });

            // ближайшие регионы
            foreach (self::lo_adjacency_lists[ $district_id ] as $adjacency_district_id ) {

                $adjacency_district_owmid = self::map_intid_to_owmid[ 813 ][ $adjacency_district_id ];

                $local_weather[] = array_search_callback($current_weather, function ($item) use ($adjacency_district_owmid){
                    return ($item['id'] == $adjacency_district_owmid);
                });
            }

            return $local_weather;

        } catch (\Exception $e) {
            if (self::$logger instanceof AppLogger) {
                self::$logger->error('[ERROR] Load Weather ',
                    [
                        array_search($e->getCode(), (new \ReflectionClass(__CLASS__))->getConstants()),
                        $e->getMessage()
                    ]);
            }
        }

        return $current_weather;

    } // load_weather_local

    public static function makeWeatherInfo($id, CurrentWeather $weather):array
    {
        $info = [
            'id'            =>  $id,
            'name'          =>  self::outer_regions[ $id ]['title'],
            'temperature'   =>  $weather->temperature->now->getValue()      ?? 0,
            'humidity'      =>  $weather->humidity->getFormatted()          ?? '0 %',     // форматированное, с %
            'pressure_hpa'  =>  $weather->pressure->getValue()              ?? 0,         // в гектопаскалях, сырое значение
            'pressure_mm'   =>  round(($weather->pressure->getValue()   ?? 0) * 0.75006375541921, 0),
            'wind_speed'    =>  $weather->wind->speed->getValue()           ?? 0,      // м/с, сырое
            'wind_dir_raw'  =>  $weather->wind->direction->getValue()       ?? 0,    //@todo: ЕСЛИ ПРИШЛО NULL то будет ошибка
            'wind_dir'      =>  $weather->wind->direction->getUnit()        ?? '',   // направление, аббревиатурой (NULL не ломает)
            'clouds_value'  =>  $weather->clouds->getValue()                ?? 0,            // облачность (% значение)
            'clouds_text'   =>  $weather->clouds->getDescription()          ?? '',      // облачность, текстом
            'precipitation' =>  $weather->precipitation->getValue()         ?? 0,    // осадки, сырое значение
            'weather_icon'  =>  $weather->weather->icon                     ?? '',                 // погодная иконка, название
            'weather_icon_url'  =>  $weather->weather->getIconUrl()         ?? '',     // погода, текстом
            't'         =>  round(($weather->temperature->now->getValue()   ?? 0), 0),
            's'         =>  array_key_exists($weather->weather->icon, self::icons_conversion)
                ? self::icons_conversion[ $weather->weather->icon ]
                : '44d',
        ];
        return $info;
    }

    public static function makeWeatherInfoJSON(int $id, CurrentWeather $region_weather):array {
        $info = [
            'id'            =>  $id,
            'name'          =>  self::outer_regions[ $id ]['title'],
            'temperature'   =>  round($region_weather['main']['temp'], 0),
            'humidity'      =>  ($region_weather['main']['humidity'] ?? '0')  . '%',     // форматированное, с %
            'pressure_hpa'  =>  round($region_weather['main']['pressure_hpa'] ?? 0, 0),         // в гектопаскалях, сырое значение
            'wind_speed'    =>  round($region_weather['wind']['speed'] ?? 0, 0),
            'wind_dir_raw'  =>  $region_weather['wind']['deg'] ?? 0,                    // направление, градусы

            'clouds_value'  =>  $region_weather->clouds->getValue()                ?? 0,            // облачность (% значение)
            'clouds_text'   =>  $region_weather->clouds->getDescription()          ?? '',      // облачность, текстом
            'precipitation' =>  $region_weather->precipitation->getValue()         ?? 0,    // осадки, сырое значение
            'weather_icon'  =>  $region_weather->weather->icon                     ?? '',                 // погодная иконка, название
            'weather_icon_url'  =>  $region_weather->weather->getIconUrl()         ?? '',     // погода, текстом
            't'         =>  round(($region_weather->temperature->now->getValue()   ?? 0), 0),
            's'         =>  array_key_exists($region_weather->weather->icon, self::icons_conversion)
                ? self::icons_conversion[ $region_weather->weather->icon ]
                : '44d',
        ];

        $info['pressure_mm'] = round(($region_weather['main']['pressure_hpa'] ?? 0) * 0.75006375541921, 0);

        return $info;
    }



}

# -eof-
