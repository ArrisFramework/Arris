<?php
/**
 * User: Karel Wintersky
 *
 * Class WebSunTemplate
 * Namespace: Arris
 *
 * Date: 26.02.2018, time: 23:26
 */

namespace Arris;

use Websun\websun as websun;

/**
 * WebSun Template Facade
 *
 * Class WebSunTemplate
 * @package Arris
 */
class WebSunTemplate
{
    const VERSION = '1.2';

    const ALLOWED_RENDERS = array('html', 'json', 'null');

    public $template_file;
    public $template_path;
    public $template_data;
    public $template_real_filepath;

    private $render_type;
    private $http_status;
    private $data = array();

    /**
     * @param $file
     * @param $path
     */
    public function __construct( $file , $path )
    {
        $this->template_file = $file;
        $this->template_path = $path;
        $this->template_real_filepath = preg_replace('/^\$/', __ROOT__, $path) . '/' . $file;
        $this->data = [];
        $this->http_status = 200;
        $this->render_type = 'html';
    }


    /**
     * @return mixed|null|string
     */
    public function render()
    {
        // проверка is_file, file_exists и Monolog

        if ($this->render_type === 'html') {

            if ($this->template_path === '' && $this->template_file === '') return false;

            if (!is_readable( $this->template_real_filepath )) {

                //@todo: write to LOG
                var_dump($this->template_real_filepath . " not readable ");

                return NULL;
            }

            return websun::websun_parse_template_path( $this->template_data, $this->template_file, $this->template_path );

        } elseif ($this->render_type === 'json') {
            return json_encode( $this->template_data );
        } else return null;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->template_path = $path;
    }


    /**
     * @param $file
     */
    public function setFile( $file )
    {
        $this->template_file = $file;
    }


    /**
     * @param string $type
     */
    public function setRender( $type = 'html' )
    {
        if ( in_array($type, $this::ALLOWED_RENDERS))
            $this->render_type = $type;
    }


    public function setTemplateOptions() {}

    /**
     * @param $path
     * @param $value
     * @return bool
     */
    public function set($path, $value)
    {
        $result = &$this->path_to_array( $path );

        if ($path != '/') {
            $result = $value;
        } else {
            if (!is_array($value)) {
                return false;
            } else {
                $result = array_merge_recursive($result, $value);
            }
        }
        return true;
    }


    /**
     * @param $path
     * @return array
     */
    public function get( $path )
    {
        return $this->path_to_array( $path );
    }

    /**
     *
     * @param $path
     * @return array
     */
    private function &path_to_array($path)
    {
        $path_array = explode('/', $path);
        $result = &$this->template_data;

        foreach ($path_array as $value) {
            if (!empty($value)) {
                if (!is_array($result)) {
                    $result[$value] = array();
                }
                $result =& $result[$value];
            }
        }
        return $result;
    }

    /**
     *
     *
     * @return mixed|null|string
     */
    public function content()
    {
        return $this->render();
    }


}