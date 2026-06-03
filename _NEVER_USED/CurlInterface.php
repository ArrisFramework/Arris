<?php

namespace Arris\Core;

use RuntimeException;

interface CurlInterface {
    
    /**
     * Constructor ensures the available curl extension is loaded.
     *
     * @throws RuntimeException
     */
    public function __construct();
    
    /**
     * @deprecated calling exec() directly is discouraged
     */
    public function _exec();
    
    /**
     * Make a get request with optional data.
     *
     * The get request has no body data, the data will be correctly added to the $url with the http_build_query() method.
     *
     * @param string $url  The url to make the get request for
     * @param array  $data Optional arguments who are part of the url
     * @return self
     */
    public function get($url, $data = array());
    
    /**
     * Make a post request with optional post data.
     *
     * @param string $url  The url to make the post request
     * @param array|object|string $data Post data to pass to the url
     * @param boolean $asJson Whether the data should be passed as json or not. {@insce 2.2.1}
     * @return self
     */
    public function post($url, $data = array(), $asJson = false);
    
    /**
     * Make a put request with optional data.
     *
     * The put request data can be either sent via payload or as get parameters of the string.
     *
     * @param string $url The url to make the put request
     * @param array $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @return self
     */
    public function put($url, $data = array(), $payload = false);
    
    /**
     * Make a patch request with optional data.
     *
     * The patch request data can be either sent via payload or as get parameters of the string.
     *
     * @param string $url The url to make the patch request
     * @param array $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @return self
     */
    public function patch($url, $data = array(), $payload = false);
    
    /**
     * Make a delete request with optional data.
     *
     * @param string $url The url to make the delete request
     * @param array $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @return self
     */
    public function delete($url, $data = array(), $payload = false);
    
    /**
     * Pass basic auth data.
     *
     * If the the requested url is secured by an htaccess basic auth mechanism you can use this method to provided the auth data.
     *
     * ```php
     * $curl = new Curl();
     * $curl->setBasicAuthentication('john', 'doe');
     * $curl->get('http://example.com/secure.php');
     * ```
     *
     * @param string $username The username for the authentication
     * @param string $password The password for the given username for the authentication
     * @return self
     */
    public function setBasicAuthentication($username, $password);
    
    /**
     * Provide optional header information.
     *
     * In order to pass optional headers by key value pairing:
     *
     * ```php
     * $curl = new Curl();
     * $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
     * $curl->get('http://example.com/request.php');
     * ```
     *
     * @param string $key   The header key
     * @param string $value The value for the given header key
     * @return self
     */
    public function setHeader($key, $value);
    
    /**
     * Provide a User Agent.
     *
     * In order to provide you customized user agent name you can use this method.
     *
     * ```php
     * $curl = new Curl();
     * $curl->setUserAgent('My John Doe Agent 1.0');
     * $curl->get('http://example.com/request.php');
     * ```
     *
     * @param string $useragent The name of the user agent to set for the current request
     * @return self
     */
    public function setUserAgent($useragent);
    
    /**
     * @deprecated Call setReferer() instead
     *
     * @param $referrer
     * @return self
     */
    public function setReferrer($referrer);
    
    /**
     * Set the HTTP referer header.
     *
     * The $referer Information can help identify the requested client where the requested was made.
     *
     * @param string $referer An url to pass and will be set as referer header
     * @return self
     */
    public function setReferer($referer);
    
    /**
     * Set contents of HTTP Cookie header.
     *
     * @param string $key   The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return self
     */
    public function setCookie($key, $value);
    
    /**
     * Set customized curl options.
     *
     * To see a full list of options: http://php.net/curl_setopt
     *
     * @see http://php.net/curl_setopt
     *
     * @param int $option The curl option constant e.g. `CURLOPT_AUTOREFERER`, `CURLOPT_COOKIESESSION`
     * @param mixed $value The value to pass for the given $option
     * @return bool
     */
    public function setOpt($option, $value);
    
    /**
     * Get customized curl options.
     *
     * To see a full list of options: http://php.net/curl_getinfo
     *
     * @see http://php.net/curl_getinfo
     *
     * @param int $option The curl option constant e.g. `CURLOPT_AUTOREFERER`, `CURLOPT_COOKIESESSION`
     * @param mixed The value to check for the given $option
     * @return mixed
     */
    public function getOpt($option = null);
    
    /**
     * Return the endpoint set for curl
     *
     * @see http://php.net/curl_getinfo
     *
     * @return string of endpoint
     */
    public function getEndpoint();
    
    /**
     * Enable verbosity.
     *
     * @param bool $on
     * @return self
     */
    public function setVerbose($on = true);
    
    /**
     * @deprecated Call setVerbose() instead
     *
     * @param bool $on
     * @return self
     */
    public function verbose($on = true);
    
    /**
     * Reset all curl options.
     *
     * In order to make multiple requests with the same curl object all settings requires to be reset.
     * @return self
     */
    public function reset();
    
    /**
     * Closing the current open curl resource.
     * @return self
     */
    public function close();
    
    /**
     * Close the connection when the Curl object will be destroyed.
     */
    public function __destruct();
    
    /**
     * Was an 'info' header returned.
     * @return bool
     */
    public function isInfo();
    
    /**
     * Was an 'OK' response returned.
     * @return bool
     */
    public function isSuccess();
    
    /**
     * Was a 'redirect' returned.
     * @return bool
     */
    public function isRedirect();
    
    /**
     * Was an 'error' returned (client error or server error).
     * @return bool
     */
    public function isError();
    
    /**
     * Was a 'client error' returned.
     * @return bool
     */
    public function isClientError();
    
    /**
     * Was a 'server error' returned.
     * @return bool
     */
    public function isServerError();
    
    /**
     * Get a specific response header key or all values from the response headers array.
     *
     * Usage example:
     *
     * ```php
     * $curl = (new Curl())->get('http://example.com');
     *
     * echo $curl->getResponseHeaders('Content-Type');
     * ```
     *
     * Or in order to dump all keys with the given values use:
     *
     * ```php
     * $curl = (new Curl())->get('http://example.com');
     *
     * var_dump($curl->getResponseHeaders());
     * ```
     *
     * @param string $headerKey Optional key to get from the array.
     * @return bool|string|array
     * @since 1.9
     */
    public function getResponseHeaders($headerKey = null);
    
    /**
     * Get response from the curl request
     * @return string|false
     */
    public function getResponse();
    
    /**
     * Get curl error code
     * @return string
     */
    public function getErrorCode();
    
    /**
     * Get curl error message
     * @return string
     */
    public function getErrorMessage();
    
    /**
     * Get http status code from the curl request
     * @return int
     */
    public function getHttpStatus();
    
}
