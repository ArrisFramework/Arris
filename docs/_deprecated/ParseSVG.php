<?php
/**
 * User: Karel Wintersky
 *
 * Class ParseSVG
 * Namespace: Arris\SVG
 *
 * Date: 10.04.2018, time: 6:17
 */

namespace Arris\SVG;

/**
 * Работа с SVG - преобразование путей в полигоны, экспорт изображений, преобразование систем координат,
 * подготовка данных для шаблона
 * v 1.4
 *
 * Class ParseSVG
 */
class ParseSVG
{
    const VERSION = '1.4';

    /**
     * Constants for convert_SVGPath_to_Polygon()
     * see : https://www.w3.org/TR/SVG11/paths.html#InterfaceSVGPathSeg
     */
    const PATHSEG_UNDEFINED             = 0;
    const PATHSEG_REGULAR_KNOT          = 1;

    const PATHSEG_MOVETO_ABS            = 2;
    const PATHSEG_MOVETO_REL            = 3;
    const PATHSEG_CLOSEPATH             = 4;

    const PATHSEG_LINETO_HORIZONTAL_REL = 5;
    const PATHSEG_LINETO_HORIZONTAL_ABS = 6;

    const PATHSEG_LINETO_VERTICAL_REL   = 7;
    const PATHSEG_LINETO_VERTICAL_ABS   = 8;

    const PATHSEG_LINETO_REL            = 9;
    const PATHSEG_LINETO_ABS            = 10;

    const NAMESPACES = array(
        'svg'       =>  'http://www.w3.org/2000/svg',
        'xlink'     =>  'http://www.w3.org/1999/xlink',
        'inkscape'  =>  'http://www.inkscape.org/namespaces/inkscape',
        'sodipodi'  =>  'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
        'rdf'       =>  'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
    );

    /**
     * @var \SimpleXMLElement
     */
    private $svg;

    private $layer_paths = array();
    private $layer_images = array();

    // перенесенный ноль координат для слоя изображений
    private $layer_images_oxy = array(
        'ox'    =>  0,
        'oy'    =>  0
    );

    // перенесенный ноль координат для слоя разметки
    private $layer_paths_oxy = array(
        'ox'    =>  0,
        'oy'    =>  0
    );

    public $svg_parsing_error = FALSE;

    /**
     * Создает новый экземпляр класса SimpleXMLElement с переданным содержимым и регистрирует неймспейсы
     *
     * @param $svg_file_content
     */
    public function __construct( $svg_file_content )
    {
        libxml_use_internal_errors(true);

        try {
            $this->svg = new \SimpleXMLElement( $svg_file_content );
        } catch (\Exception $e) {
            $this->svg_parsing_error = array(
                'state'     =>  NULL,
                'code'      =>  $e->getCode(),
                'message'   =>  $e->getMessage()
            );
            return FALSE;
        }

        foreach (self::NAMESPACES as $ns => $definition) {
            try {
                $this->svg->registerXPathNamespace( $ns, $definition);
            } catch (\Exception $e) {
                $this->svg_parsing_error = array(
                    'state'     =>  NULL,
                    'code'      =>  $e->getCode(),
                    'message'   =>  $e->getMessage()
                );
                return FALSE;
            }
        }

        return TRUE;
    }

    public function is_error()
    {

    }

    /**
     * Парсит SVG-шку. Ищет все пути и все изображения на указанных слоях.
     * Если переданы пустые строки - ищет по всему SVG
     *
     * @param $name_layer_paths
     * @param $name_layer_images
     */
    public function parse($name_layer_paths , $name_layer_images)
    {
        // ИЗОБРАЖЕНИЯ (Images)
        if ($name_layer_images !== '') {
            // анализируем атрибуты слоя изображений
            $xpath_images_layer_attrs = '//svg:g[starts-with(@inkscape:label, "' . $name_layer_images . '")]';
            $images_layer_attrs = $this->svg->xpath($xpath_images_layer_attrs)[0];

            // получаем сдвиг всех объектов этого слоя
            if (!empty($images_layer_attrs->attributes()->{'transform'})) {
                $this->layer_images_oxy = $this->parseTransform( $images_layer_attrs->attributes()->{'transform'} );
            }

            // это XPath-определение всех изображений на слое
            $xpath_images   = '//svg:g[starts-with(@inkscape:label, "' . $name_layer_images . '")]/svg:image';
        } else {
            $xpath_images   = '//svg:image';
        }

        $this->layer_images = $this->svg->xpath($xpath_images);

        // ПОЛИГОНЫ (Paths)
        if ($name_layer_paths !== '') {
            // анализируем атрибуты слоя разметки регионов
            $xpath_paths_layer_attrs = '//svg:g[starts-with(@inkscape:label, "' . $name_layer_paths . '")]';
            $paths_layer_attrs = $this->svg->xpath($xpath_paths_layer_attrs)[0];

            // получаем сдвиг всех объектов этого слоя
            if (!empty($paths_layer_attrs->attributes()->{'transform'})) {
                $this->layer_paths_oxy = $this->parseTransform( $paths_layer_attrs->attributes()->{'transform'} );
            }

            // это XPath-определение всех путей на слое
            $xpath_paths    = '//svg:g[starts-with(@inkscape:label, "' . $name_layer_paths . '")]/svg:path';
        } else {
            $xpath_paths    = '//svg:path';
        }

        $this->layer_paths  = $this->svg->xpath($xpath_paths);

        // Можем добавить обработчик эллипсов (которые тоже могут быть на слое Paths)
        // echo '<pre>';
        // var_dump( $this->svg->xpath('//svg:g[starts-with(@inkscape:label, "' . $name_layer_paths . '")]') );
    }

    /**
     * * Анализирует строку трансформации и возвращает пару координат XY на которую сдвигают согласно описанию.
     * @param $transform_definition_string
     * @return array [ ox, oy ]
     */
    private function parseTransform( $transform_definition_string )
    {
        $result = array(
            'ox'    =>  0,
            'oy'    =>  0
        );

        if (1 == preg_match('/translate\(\s*([^\s,)]+)[\s,]([^\s,)]+)/', $transform_definition_string, $translate_matches)) {
            $result = array(
                'ox'    =>  $translate_matches[1],
                'oy'    =>  $translate_matches[2]
            );
        };

        return $result;
    }

    /**
     * Преобразует SVG-path в массив координат полигона.
     *
     * Возвращает массив пар координат ИЛИ false в случае невозможности преобразования.
     * Невозможно преобразовать кривые Безье любого вида. В таком случае возвращается пустой массив.
     *
     * Эта функция не выполняет сдвиг или преобразование координат! У неё нет для этого данных.
     *
     * @param $path
     * @return array|bool
     */
    public static function convert_SVGPath_to_Polygon( $path )
    {
        $xy = [];
        $is_debug = false;

        // пуст ли путь?
        if ($path === '') return array();

        // если путь не заканчивается на z/Z - это какая-то херня, а не путь. Отбрасываем
        //@todo: [УЛУЧШИТЬ] PARSE_SVG -- unfinished paths may be correct?
        if ( 'z' !== strtolower(substr($path, -1)) ) {
            return array();
        }

        // есть ли в пути управляющие последовательности кривых Безье любых видов?
        $charlist_unsupported_knots = 'CcSsQqTtAa'; // так быстрее, чем регулярка по '#(C|c|S|s|Q|q|T|t|A|a)#'
        if (strpbrk($path, $charlist_unsupported_knots)) {
            return array();
        }

        $path_fragments = explode(' ', $path);

        $polygon = array();             // массив узлов полигона
        $multipolygon = array();        // массив, содержащий все полигоны. Если в нём один элемент - то у фигуры один полигон.

        $polygon_is_relative = null;    // тип координат: TRUE - Относительные, FALSE - абсолютные, null - не определено
        $prev_knot_x = 0;               // X-координата предыдущего узла
        $prev_knot_y = 0;               // Y-координата предыдущего узла

        $path_start_x = 0;              // X-координата начала текущего пути
        $path_start_y = 0;              // Y-координата начала текущего пути

        $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

        do {
            $fragment = array_splice($path_fragments, 0, 1)[0];

            if ($is_debug) echo PHP_EOL, "Извлеченный фрагмент : ", $fragment, PHP_EOL;

            if ( $fragment === 'Z') $fragment = 'z';

            if ( strpbrk($fragment, 'MmZzHhVvLl') ) {    // faster than if (preg_match('/(M|m|Z|z|H|h|V|v|L|l)/', $fragment) > 0)
                switch ($fragment) {
                    case 'M' : {
                        $LOOKAHEAD_FLAG = self::PATHSEG_MOVETO_ABS;
                        break;
                    }
                    case 'm' : {
                        $LOOKAHEAD_FLAG = self::PATHSEG_MOVETO_REL;
                        break;
                    }
                    case 'z': {
                        // все гораздо интереснее. Это не конец всего пути. Это конец полигона. Реальный путь может состоять из нескольких полигонов.
                        // это надо обрабатывать.
                        $LOOKAHEAD_FLAG = self::PATHSEG_CLOSEPATH;
                        break;
                    }
                    case 'h': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_HORIZONTAL_REL;
                        break;
                    }
                    case 'H': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_HORIZONTAL_ABS;
                        break;
                    }
                    case 'v': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_VERTICAL_REL;
                        break;
                    }
                    case 'V': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_VERTICAL_ABS;
                        break;
                    }
                    case 'l': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_REL;
                        break;
                    }
                    case 'L': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_ABS;
                        break;
                    }
                } // switch

                // обработка управляющей последовательности Z
                if ($LOOKAHEAD_FLAG === self::PATHSEG_CLOSEPATH) {
                    $multipolygon[] = $polygon; // добавляем суб-полигон к полигону
                    $polygon = array();         // очищаем массив узлов суб-полигона
                }

                if ($is_debug) echo "Это управляющая последовательность. Параметры будут обработаны на следующей итерации.", PHP_EOL, PHP_EOL;
                continue;
            } else {
                if ($is_debug) echo "Это числовая последовательность, запускаем обработчик : ";

                /**
                 * Раньше этот блок данных обрабатывался внутри назначения обработчиков.
                 * Сейчас я его вынес наружу. Это может вызвать в перспективе некоторые глюки, нужны тесты
                 */
                if ($LOOKAHEAD_FLAG == self::PATHSEG_MOVETO_REL) {
                    if ($is_debug) echo "m : Начало полилинии с относительными координатами ", PHP_EOL;
                    $polygon_is_relative = true;

                    //@todo: Подумать над ускорением преобразования (ЧИСЛО,ЧИСЛО)

                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    // так как путь относительный, moveto делается относительно предыдущего положения "пера"
                    // вообще, скорее всего, нам не нужны совсем переменные $path_start_x и $path_start_y
                    $path_start_x = $prev_knot_x;
                    $path_start_y = $prev_knot_y;

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $path_start_x + $knot['X'],
                            'y' =>  $path_start_y + $knot['Y']
                        );
                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];

                        $path_start_x = $prev_knot_x;
                        $path_start_y = $prev_knot_y;
                    }

                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;
                    if ($is_debug) var_dump($xy);
                    continue; // ОБЯЗАТЕЛЬНО делаем continue, иначе управление получит следующий блок
                }

                if ($LOOKAHEAD_FLAG == self::PATHSEG_MOVETO_ABS) {
                    if ($is_debug) echo "M : Начало полилинии с абсолютными координатами ", PHP_EOL;
                    $polygon_is_relative = false;

                    //@todo: Подумать над ускорением преобразования (ЧИСЛО,ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    // вообще, скорее всего, нам не нужны совсем переменные $path_start_x и $path_start_y
                    $path_start_x = 0;
                    $path_start_y = 0;

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $path_start_x + $knot['X'],
                            'y' =>  $path_start_y + $knot['Y']
                        );
                        $polygon[] = $xy;

                        $prev_knot_x = 0;
                        $prev_knot_y = 0;
                    }

                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    if ($is_debug) var_dump($xy);

                    continue; // ОБЯЗАТЕЛЬНО делаем continue, иначе управление получит следующий блок
                }

                if ($LOOKAHEAD_FLAG == self::PATHSEG_UNDEFINED || $LOOKAHEAD_FLAG == self::PATHSEG_REGULAR_KNOT ) {
                    if ($is_debug) echo "Обычная числовая последовательность ", PHP_EOL;

                    // проверяем валидность пары координат
                    //@todo: Подумать над ускорением проверки (ЧИСЛО,ЧИСЛО)
                    //@todo: блядство в том, что формат с запятыми - это inkscape-friendly запись. Стандарт считает, что запятая не нужна и числа идут просто парами через пробел.

                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count == 0) continue; // Если это неправильная комбинация float-чисел - пропускаем обработку и идем на след. итерацию
                    // здесь я использую такую конструкцию чтобы не брать стену кода в IfTE-блок.

                    if (empty($polygon)) {
                        // возможно обработку первого узла следует перенести в другой блок (обработчик флага SVGPATH_START_ABSOULUTE или SVGPATH_START_RELATIVE)
                        // var_dump('Это первый узел. Он всегда задается в абсолютных координатах! ');

                        $xy = array(
                            'x' =>  $prev_knot_x + $knot['X'],
                            'y' =>  $prev_knot_y + $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    } else {
                        // var_dump('Это не первый узел в мультилинии');

                        if ($polygon_is_relative) {
                            // var_dump("его координаты относительные и даны относительно предыдущего узла полилинии ");

                            $xy = array(
                                'x' =>  $prev_knot_x + $knot['X'],
                                'y' =>  $prev_knot_y + $knot['Y']
                            );

                            $polygon[] = $xy;

                            $prev_knot_x = $xy['x'];
                            $prev_knot_y = $xy['y'];

                        } else {
                            // var_dump("Его координаты абсолютные");

                            $xy = array(
                                'x' =>  $knot['X'],
                                'y' =>  $knot['Y']
                            );

                            $polygon[] = $xy;

                            // "предыдущие" координаты все равно надо хранить.
                            $prev_knot_x = $xy['x'];
                            $prev_knot_y = $xy['y'];

                        } // if()
                    } // endif (polygon)
                    if ($is_debug) var_dump($xy);
                    unset($xy);
                } // if ($LOOKAHEAD_FLAG == SVGPATH_UNDEFINED || $LOOKAHEAD_FLAG == SVGPATH_NORMAL_KNOT )

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_HORIZONTAL_ABS) {
                    if ($is_debug) echo "Горизональная линия по абсолютным координатам ", PHP_EOL;

                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $knot['X'],
                            'y' =>  $prev_knot_y
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                }

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_HORIZONTAL_REL) {
                    if ($is_debug) echo "Горизональная линия по относительным координатам ", PHP_EOL;
                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x + $knot['X'],
                            'y' =>  $prev_knot_y
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                } // ($LOOKAHEAD_FLAG == SVGPATH_HORIZONTAL_RELATIVE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_VERTICAL_ABS) {
                    if ($is_debug) echo "Вертикальная линия по абсолютным координатам ", PHP_EOL;
                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<Y>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x,
                            'y' =>  $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                } // ($LOOKAHEAD_FLAG == SVGPATH_VERTICAL_ABSOLUTE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_VERTICAL_REL) {
                    if ($is_debug) echo "Вертикальная линия по относительным координатам ", PHP_EOL;
                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<Y>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x,
                            'y' =>  $prev_knot_y + $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }


                } // ($LOOKAHEAD_FLAG == SVGPATH_VERTICAL_RELATIVE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_ABS) {
                    if ($is_debug) echo "Линия по абсолютным координатам ", PHP_EOL;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $knot['X'],
                            'y' =>  $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }

                } // ($LOOKAHEAD_FLAG == SVGPATH_LINETO_ABSOLUTE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_REL) {
                    if ($is_debug) echo "Линия по относительным координатам ", PHP_EOL;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x + $knot['X'],
                            'y' =>  $prev_knot_y + $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                } // ($LOOKAHEAD_FLAG == SVGPATH_LINETO_ABSOLUTE)

                if ($is_debug && isset($xy)) var_dump($xy);
            } // endif (нет, это не управляющая последовательность)

        } while (!empty($path_fragments));

        // обработка мультиполигона

        if ($is_debug) var_dump($multipolygon);

        return $multipolygon;
    }

    /**
     * Извлекает из распарсенных данных информацию о связанном изображении по его индексу (по умолчанию 0)
     *
     * @param int $image_num
     * @return array|bool
     */
    public function getImageDefinition( $image_num = 0 )
    {
        /**
         * @var $an_image object
         */

        if (array_key_exists($image_num, $this->layer_images)) {
            $an_image = $this->layer_images[ $image_num ];

            return array(
                'width'     =>  round((float)$an_image->attributes()->{'width'}, 0),
                'height'    =>  round((float)$an_image->attributes()->{'height'}, 0),
                'ox'        =>  round((float)$an_image->attributes()->{'x'} + (float)$this->layer_images_oxy['ox'], 0),  // с модификацией переноса группы объектов
                'oy'        =>  round((float)$an_image->attributes()->{'y'} + (float)$this->layer_images_oxy['oy'], 0), // с модификацией переноса группы объектов
                'xhref'     =>       (string)$an_image->attributes('xlink', true)->{'href'}
            );
        } else {
            return false;
        }
    }

    /**
     * Преобразует информацию о полигонах, заданных в "путях" (<path d="">) в массив полигонов в формате JS-координат.
     * Выполняется конверсия SVG->JS, сдвиг координат из системы отсчета OXY в Leaflet.CRS
     *
     * @param $image_properties
     * @return array
     */
    public function getPathsDefinition( $image_properties )
    {
        /**
         * @var $path object
         */

        $all_paths = array();

        foreach ($this->layer_paths as $path) {
            $polygon_data = array();   // блок данных о пути

            $path_d     = (string)$path->attributes()->{'d'};
            $path_id    = (string)$path->attributes()->{'id'};
            $path_style = (string)$path->attributes()->{'style'};

            // преобразовываем <path d=""> в полигон (массив координат)
            $polygon_original = self::convert_SVGPath_to_Polygon( $path_d );

            // транслируем систему координат
            // $polygon_translated = self::translate_XY_to_CRS_js( $polygon_original, $image_properties['ox'], $image_properties['oy'], $image_properties['height'] );

            $polygon_translated = self::translate_polygone_from_XY_to_CRSjs( $polygon_original, $image_properties['ox'], $image_properties['oy'], $image_properties['height'] );

            // преобразовываем в JS-строку
            // $polygon_data['path'] = self::convert_CoordsArray_to_JSString( $polygon_translated );

            $polygon_data['path'] = self::convert_CRS_to_JSString( $polygon_translated );

            // получаем атрибут fillColor
            if (preg_match('#fill:([\#\d\w]{7})#', $path_style, $path_style_fillColor) ) {
                $polygon_data['fillColor'] = $path_style_fillColor[1];
            };

            // получаем атрибут fillOpacity
            if (preg_match('#fill-opacity:([\d]?\.[\d]{0,8})#', $path_style, $path_style_fillOpacity) ) {
                $polygon_data['fillOpacity'] = round($path_style_fillOpacity[1] , 2);
            };

            // получаем атрибут fillRule
            if (preg_match('#fill-rule:(evenodd|nonzero)#', $path_style, $path_style_fillRule) ) {
                if ($path_style_fillRule[1] !== 'evenodd') {
                    $polygon_data['fillRule'] = $path_style_fillRule[1];
                }
            };

            // получаем title узла
            $path_title = (string)$path->{'title'}[0];
            if ($path_title) {
                $polygon_data['title'] = htmlspecialchars($path_title, ENT_QUOTES | ENT_HTML5);
            }

            // получаем description узла
            $path_desc = (string)$path->{'desc'}[0];
            if ($path_desc) {
                $polygon_data['desc'] = htmlspecialchars($path_desc, ENT_QUOTES | ENT_HTML5);
            }

            $all_paths[ $path_id ] = $polygon_data;
        }

        return $all_paths;
    }

    /**
     * Cдвигает систему координат из XY (экранной SVG) в Leaflet-CRS
     * (X, Y) => (Height - (Y-oY) , (X-oX)
     *
     * @param $polyline
     * @param $ox
     * @param $oy
     * @param $height
     * @return array
     */
    public static function translate_XY_to_CRS_js( $polyline, $ox, $oy, $height ) // устарела
    {
        // (X, Y) => (Height - (Y-oY) , (X-oX)
        return array_map( function($knot) use ($ox, $oy, $height) {
            return array(
                'x'     =>  round( $height - ($knot['y'] - $oy) , 2 ),
                'y'     =>  round( $knot['x'] - $ox, 2)
            );
        }, $polyline);
    }

    // более правильное название этого метода - преобразует в CRS-представление только один суб-путь (subpath)
    public static function translate_subpath_from_XY_to_CRSjs( $subpolyline, $ox, $oy, $height )
    {
        // (X, Y) => (Height - (Y-oY) , (X-oX)
        return array_map( function($knot) use ($ox, $oy, $height) {
            return array(
                'x'     =>  round( $height - ($knot['y'] - $oy) , 2 ),
                'y'     =>  round( $knot['x'] - $ox, 2)
            );
        }, $subpolyline);
    }

    // преобразует все субпути
    public static function translate_polygone_from_XY_to_CRSjs( $polygone, $ox, $oy, $height)
    {
        /*
         [0] => массив вершин (XY)
         [1] => массив вершин (XY)
         */
        if ( empty($polygone) ) return array();

        return
            ( count($polygone) > 1 )    // если суб-полигонов больше одного
                ?                           // проходим по всем
                array_map( function($subpath) use ($ox, $oy, $height) {
                    return self::translate_subpath_from_XY_to_CRSjs( $subpath, $ox, $oy, $height );
                }, $polygone )
                :   array(                  // возвращаем первый элемент массива субполигонов, но как единственный элемент массива!
                self::translate_subpath_from_XY_to_CRSjs( array_shift($polygone), $ox, $oy, $height )
            );
    }

    /**
     * Преобразовывает узлы в JS-представление
     * array( array(x,y), array(x,y) ... ) => [ [x,y], [x,y], [x,y] ... [x,y] ]

     * @param $coords
     * @return array
     */
    public static function convert_CoordsArray_to_JSString( $coords ) // устарела
    {
        $js_coords_array = array();

        if (empty($coords)) return '[]';

        array_walk( $coords, function($knot) use (&$js_coords_array) {
            $js_coords_array[] = '[' . implode(',', array(
                    $knot['x'],
                    $knot['y']
                )) . ']';
        });
        return '[ ' . implode(', ' , $js_coords_array) . ' ]';
    }

    /**
     * @param $coords
     * @return string
     */
    public static function convert_subCRS_to_JSstring( $coords )
    {
        $js_coords_string = array();

        array_walk( $coords, function($knot) use (&$js_coords_string) {
            $js_coords_string[] = '[' . implode(',', array(
                    $knot['x'],
                    $knot['y']
                )) . ']';
        });
        return '[ ' . implode(', ' , $js_coords_string) . ' ]';
    }

    /**
     * @param $multicoords
     * @return string
     */
    public static function convert_multiCRS_to_JSString( $multicoords )
    {
        $js_coords_string = array();
        if (empty($multicoords)) return '[]';

        array_walk( $multicoords, function($sub_coords) use (&$js_coords_string) {
            $js_coords_string[] = self::convert_subCRS_to_JSstring( $sub_coords );
        });

        return '[ ' . implode(', ' , $js_coords_string) . ' ]';
    }

    /**
     * @param $multicoords
     * @return string
     */
    public static function convert_CRS_to_JSString( $multicoords )
    {
        if (empty($multicoords)) return '[]';

        // вариант для составных функций (вероятно второй вариант лучше)
        return
            (count($multicoords) > 1)
                ?   self::convert_multiCRS_to_JSString( $multicoords )
                :   self::convert_subCRS_to_JSstring( array_shift($multicoords));


        /*// вариант для одной функции
        $js_coords_string = array();
        if (count($multicoords) > 1) {
            array_walk( $multicoords, function($sub_coords) use (&$js_coords_string) {
                $js_coords_string[] = self::convert_subCRS_to_JSstring( $sub_coords );
            });
            return '[ ' . implode(', ' , $js_coords_string) . ' ]';

        } else {
            $js_coords_string = self::convert_subCRS_to_JSstring( array_shift($multicoords));
            return $js_coords_string;

        }*/

    }

    /**
     * Подготавливает данные для экспорта в шаблон
     * @param $all_paths
     * @return string
     */
    public function exportSPaths( $all_paths )
    {
        $all_paths_text = array();

        foreach($all_paths as $path_id => $path_data ) {
            $path_data_text = '';

            $path_data_text .= <<<APAT
        '{$path_id}': {
            'path'  : {$path_data['path']}
APAT;

            if (array_key_exists('fillColor', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'fillColor' : '{$path_data['fillColor']}'";
            }
            if (array_key_exists('fillOpacity', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'fillOpacity' : '{$path_data['fillOpacity']}'";
            }
            if (array_key_exists('fillRule', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'fillRule' : '{$path_data['fillRule']}'";
            }
            if (array_key_exists('title', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'title' : '{$path_data['title']}'";
            }
            if (array_key_exists('desc', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'desc' : '{$path_data['desc']}'";
            }
            $path_data_text .= PHP_EOL.'        }';

            $all_paths_text[] = $path_data_text;
        }

        // массив строк оборачиваем запятой если нужно
        $all_path_as_text = implode(','.PHP_EOL, $all_paths_text);
        return $all_path_as_text;
    }


}