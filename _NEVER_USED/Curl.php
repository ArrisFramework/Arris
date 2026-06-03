<?php

namespace Arris\Core;

use RuntimeException;

/**
 * An object-oriented wrapper of the PHP cURL extension.
 *
 * This library requires to have the php cURL extensions installed:
 * https://php.net/manual/curl.setup.php
 *
 * Example of making a get request with parameters:
 *
 * ```php
 * $curl = new Curl\Curl();
 * $curl->get('http://www.example.com/search', array(
 *     'q' => 'keyword',
 * ));
 * ```
 *
 * Example post request with post data:
 *
 * ```php
 * $curl = new Curl\Curl();
 * $curl->post('http://www.example.com/login/', array(
 *     'username' => 'myusername',
 *     'password' => 'mypassword',
 * ));
 * ```
 *
 * @see https://php.net/manual/curl.setup.php
 *
 * It is copy of https://github.com/php-mod/curl
 */
class Curl implements CurlInterface
{
    /**
     * The HTTP authentication method(s) to use.
     *
     * @var string Type AUTH_BASIC
     */
    const AUTH_BASIC = CURLAUTH_BASIC;
    
    /**
     * @var string Type AUTH_DIGEST
     */
    const AUTH_DIGEST = CURLAUTH_DIGEST;
    
    /**
     * @var string Type AUTH_GSSNEGOTIATE
     */
    const AUTH_GSSNEGOTIATE = CURLAUTH_GSSNEGOTIATE;
    
    /**
     * @var string Type AUTH_NTLM
     */
    const AUTH_NTLM = CURLAUTH_NTLM;
    
    /**
     * @var string Type AUTH_ANY
     */
    const AUTH_ANY = CURLAUTH_ANY;
    
    /**
     * @var string Type AUTH_ANYSAFE
     */
    const AUTH_ANYSAFE = CURLAUTH_ANYSAFE;
    
    /**
     * @var string The user agent name which is set when making a request
     */
    const USER_AGENT = 'PHP Curl/2.3 (+https://github.com/php-mod/curl)';
    
    private $_cookies = array();
    
    private $_headers = array();
    
    /**
     * @var resource Contains the curl resource created by `curl_init()` function
     */
    public $curl;
    
    /**
     * @var bool Whether an error occurred or not
     */
    public $error = false;
    
    /**
     * @var int Contains the error code of the current request, 0 means no error happened
     */
    public $error_code = 0;
    
    /**
     * @var string If the curl request failed, the error message is contained
     */
    public $error_message = null;
    
    /**
     * @var bool Whether an error occurred or not
     */
    public $curl_error = false;
    
    /**
     * @var int Contains the error code of the current request, 0 means no error happened.
     * @see https://curl.haxx.se/libcurl/c/libcurl-errors.html
     */
    public $curl_error_code = 0;
    
    /**
     * @var string If the curl request failed, the error message is contained
     */
    public $curl_error_message = null;
    
    /**
     * @var bool Whether an error occurred or not
     */
    public $http_error = false;
    
    /**
     * @var int Contains the status code of the current processed request.
     */
    public $http_status_code = 0;
    
    /**
     * @var string If the curl request failed, the error message is contained
     */
    public $http_error_message = null;
    
    /**
     * @var string|array TBD (ensure type) Contains the request header information
     */
    public $request_headers = null;
    
    /**
     * @var string|array TBD (ensure type) Contains the response header information
     */
    public $response_headers = array();
    
    /**
     * @var string|false|null Contains the response from the curl request
     */
    public $response = null;
    
    /**
     * @var bool Whether the current section of response headers is after 'HTTP/1.1 100 Continue'
     */
    protected $response_header_continue = false;
    
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The cURL extensions is not loaded, make sure you have installed the cURL extension: https://php.net/manual/curl.setup.php');
        }
        
        $this->init();
    }
    
    // private methods
    
    /**
     * Initializer for the curl resource.
     *
     * Is called by the __construct() of the class or when the curl request is reset.
     * @return self
     */
    private function init()
    {
        $this->curl = curl_init();
        $this->setUserAgent(self::USER_AGENT);
        $this->setOpt(CURLINFO_HEADER_OUT, true);
        $this->setOpt(CURLOPT_HEADER, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_HEADERFUNCTION, array($this, 'addResponseHeaderLine'));
        return $this;
    }
    
    /**
     * Handle writing the response headers
     *
     * @param resource $curl The current curl resource
     * @param string $header_line A line from the list of response headers
     *
     * @return int Returns the length of the $header_line
     */
    public function addResponseHeaderLine($curl, $header_line)
    {
        $trimmed_header = trim($header_line, "\r\n");
        
        if ($trimmed_header === "") {
            $this->response_header_continue = false;
        } elseif (strtolower($trimmed_header) === 'http/1.1 100 continue') {
            $this->response_header_continue = true;
        } elseif (!$this->response_header_continue) {
            $this->response_headers[] = $trimmed_header;
        }
        
        return strlen($header_line);
    }
    
    // protected methods
    
    /**
     * Execute the curl request based on the respective settings.
     *
     * @return int Returns the error code for the current curl request
     */
    protected function exec()
    {
        $this->response_headers = array();
        $this->response = curl_exec($this->curl);
        $this->curl_error_code = curl_errno($this->curl);
        $this->curl_error_message = curl_error($this->curl);
        $this->curl_error = !($this->getErrorCode() === 0);
        $this->http_status_code = (int)curl_getinfo( $this->curl, CURLINFO_HTTP_CODE );
        $this->http_error = $this->isError();
        $this->error = $this->curl_error || $this->http_error;
        $this->error_code = $this->error ? ($this->curl_error ? $this->getErrorCode() : $this->getHttpStatus()) : 0;
        $this->request_headers = preg_split('/\r\n/', curl_getinfo($this->curl, CURLINFO_HEADER_OUT), null, PREG_SPLIT_NO_EMPTY);
        $this->http_error_message = $this->error ? (isset($this->response_headers['0']) ? $this->response_headers['0'] : '') : '';
        $this->error_message = $this->curl_error ? $this->getErrorMessage() : $this->http_error_message;
        
        return $this->error_code;
    }
    
    /**
     * @param array|object|string $data
     */
    protected function preparePayload($data)
    {
        $this->setOpt(CURLOPT_POST, true);
        
        if (is_array($data) || is_object($data)) {
            $skip = false;
            foreach ($data as $key => $value) {
                if ($value instanceof \CurlFile) {
                    $skip = true;
                }
            }
            
            if (!$skip) {
                $data = http_build_query($data);
            }
        }
        
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }
    
    /**
     * Set the json payload informations to the postfield curl option.
     *
     * @param array $data The data to be sent.
     * @return void
     */
    protected function prepareJsonPayload(array $data)
    {
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    /**
     * Set auth options for the current request.
     *
     * Available auth types are:
     *
     * + self::AUTH_BASIC
     * + self::AUTH_DIGEST
     * + self::AUTH_GSSNEGOTIATE
     * + self::AUTH_NTLM
     * + self::AUTH_ANY
     * + self::AUTH_ANYSAFE
     *
     * @param int $httpauth The type of authentication
     */
    protected function setHttpAuth($httpauth)
    {
        $this->setOpt(CURLOPT_HTTPAUTH, $httpauth);
    }
    
    // public methods
    
    public function _exec()
    {
        return $this->exec();
    }
    
    // functions
    
    public function get($url, $data = array())
    {
        if (count($data) > 0) {
            $this->setOpt(CURLOPT_URL, $url.'?'.http_build_query($data));
        } else {
            $this->setOpt(CURLOPT_URL, $url);
        }
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->exec();
        return $this;
    }
    
    public function post($url, $data = array(), $asJson = false)
    {
        $this->setOpt(CURLOPT_URL, $url);
        if ($asJson) {
            $this->prepareJsonPayload($data);
        } else {
            $this->preparePayload($data);
        }
        $this->exec();
        return $this;
    }
    
    public function put($url, $data = array(), $payload = false)
    {
        if (! empty($data)) {
            if ($payload === false) {
                $url .= '?'.http_build_query($data);
            } else {
                $this->preparePayload($data);
            }
        }
        
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->exec();
        return $this;
    }
    
    public function patch($url, $data = array(), $payload = false)
    {
        if (! empty($data)) {
            if ($payload === false) {
                $url .= '?'.http_build_query($data);
            } else {
                $this->preparePayload($data);
            }
        }
        
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->exec();
        return $this;
    }
    
    public function delete($url, $data = array(), $payload = false)
    {
        if (! empty($data)) {
            if ($payload === false) {
                $url .= '?'.http_build_query($data);
            } else {
                $this->preparePayload($data);
            }
        }
        
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->exec();
        return $this;
    }
    
    // setters
    
    public function setBasicAuthentication($username, $password)
    {
        $this->setHttpAuth(self::AUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $username.':'.$password);
        return $this;
    }
    
    public function setHeader($key, $value)
    {
        $this->_headers[$key] = $key.': '.$value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }
    
    public function setUserAgent($useragent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $useragent);
        return $this;
    }
    
    public function setReferrer($referrer)
    {
        $this->setReferer($referrer);
        return $this;
    }
    
    public function setReferer($referer)
    {
        $this->setOpt(CURLOPT_REFERER, $referer);
        return $this;
    }
    
    public function setCookie($key, $value)
    {
        $this->_cookies[$key] = $value;
        $this->setOpt(CURLOPT_COOKIE, http_build_query($this->_cookies, '', '; '));
        return $this;
    }
    
    public function setOpt($option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }
    
    public function getOpt($option = null)
    {
        if (is_null($option)) {
            $result = curl_getinfo($this->curl);
        } else {
            $result = curl_getinfo($this->curl, $option);
        }
        
        return $result;
    }
    
    public function getEndpoint()
    {
        return $this->getOpt(CURLINFO_EFFECTIVE_URL);
    }
    
    public function setVerbose($on = true)
    {
        $this->setOpt(CURLOPT_VERBOSE, $on);
        return $this;
    }
    
    public function verbose($on = true)
    {
        return $this->setVerbose($on);
    }
    
    public function reset()
    {
        $this->close();
        $this->_cookies = array();
        $this->_headers = array();
        $this->error = false;
        $this->error_code = 0;
        $this->error_message = null;
        $this->curl_error = false;
        $this->curl_error_code = 0;
        $this->curl_error_message = null;
        $this->http_error = false;
        $this->http_status_code = 0;
        $this->http_error_message = null;
        $this->request_headers = null;
        $this->response_headers = array();
        $this->response = false;
        $this->init();
        return $this;
    }
    
    public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        return $this;
    }
    
    public function __destruct()
    {
        $this->close();
    }
    
    public function isInfo()
    {
        return $this->getHttpStatus() >= 100 && $this->getHttpStatus() < 200;
    }
    
    public function isSuccess()
    {
        return $this->getHttpStatus() >= 200 && $this->getHttpStatus() < 300;
    }
    
    public function isRedirect()
    {
        return $this->getHttpStatus() >= 300 && $this->getHttpStatus() < 400;
    }
    
    public function isError()
    {
        return $this->getHttpStatus() >= 400 && $this->getHttpStatus() < 600;
    }
    
    public function isClientError()
    {
        return $this->getHttpStatus() >= 400 && $this->getHttpStatus() < 500;
    }
    
    public function isServerError()
    {
        return $this->getHttpStatus() >= 500 && $this->getHttpStatus() < 600;
    }
    
    public function getResponseHeaders($headerKey = null)
    {
        $headers = array();
        $headerKey = strtolower($headerKey);
        
        foreach ($this->response_headers as $header) {
            $parts = explode(":", $header, 2);
            
            $key = isset($parts[0]) ? $parts[0] : null;
            $value = isset($parts[1]) ? $parts[1] : null;
            
            $headers[trim(strtolower($key))] = trim($value);
        }
        
        if ($headerKey) {
            return isset($headers[$headerKey]) ? $headers[$headerKey] : false;
        }
        
        return $headers;
    }
    
    public function getResponse()
    {
        return $this->response;
    }
    
    public function getErrorCode()
    {
        return $this->curl_error_code;
    }
    
    public function getErrorMessage()
    {
        return $this->curl_error_message;
    }
    
    public function getHttpStatus()
    {
        return $this->http_status_code;
    }
}
