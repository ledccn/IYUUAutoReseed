<?php

namespace Curl;

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
 */
class Curl
{
    // The HTTP authentication method(s) to use.

    /**
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
    const USER_AGENT = 'PHP Curl/1.9 (+https://github.com/php-mod/curl)';

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

    /**
     * Constructor ensures the available curl extension is loaded.
     *
     * @throws \RuntimeException
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The cURL extensions is not loaded, make sure you have installed the cURL extension: https://php.net/manual/curl.setup.php');
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
        $this->http_status_code = intval(curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
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
                // If a value is an instance of CurlFile skip the http_build_query
                // see issue https://github.com/php-mod/curl/issues/46
                // suggestion from: https://stackoverflow.com/a/36603038/4611030
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

    /**
     * @deprecated calling exec() directly is discouraged
     */
    public function _exec()
    {
        return $this->exec();
    }

    // functions

    /**
     * Make a get request with optional data.
     *
     * The get request has no body data, the data will be correctly added to the $url with the http_build_query() method.
     *
     * @param string $url  The url to make the get request for
     * @param array  $data Optional arguments who are part of the url
     * @return self
     */
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

    /**
     * Make a post request with optional post data.
     *
     * @param string $url  The url to make the post request
     * @param array  $data Post data to pass to the url
     * @param boolean $asJson Whether the data should be passed as json or not. {@insce 2.2.1}
     * @return self
     */
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

    /**
     * Make a delete request with optional data.
     *
     * @param string $url The url to make the delete request
     * @param array $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @return self
     */
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
    public function setBasicAuthentication($username, $password)
    {
        $this->setHttpAuth(self::AUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $username.':'.$password);
        return $this;
    }

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
    public function setHeader($key, $value)
    {
        $this->_headers[$key] = $key.': '.$value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

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
    public function setUserAgent($useragent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $useragent);
        return $this;
    }

    /**
     * @deprecated Call setReferer() instead
     *
     * @param $referrer
     * @return self
     */
    public function setReferrer($referrer)
    {
        $this->setReferer($referrer);
        return $this;
    }

    /**
     * Set the HTTP referer header.
     *
     * The $referer Information can help identify the requested client where the requested was made.
     *
     * @param string $referer An url to pass and will be set as referer header
     * @return self
     */
    public function setReferer($referer)
    {
        $this->setOpt(CURLOPT_REFERER, $referer);
        return $this;
    }

    /**
     * Set contents of HTTP Cookie header.
     *
     * @param string $key   The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return self
     */
    public function setCookie($key, $value)
    {
        $this->_cookies[$key] = $value;
        $this->setOpt(CURLOPT_COOKIE, http_build_query($this->_cookies, '', '; '));
        return $this;
    }

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
    public function setOpt($option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }

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
    public function getOpt($option)
    {
        return curl_getinfo($this->curl, $option);
    }
    
    /**
    * Return the endpoint set for curl
    *
    * @see http://php.net/curl_getinfo
    *
    * @return string of endpoint
    */
    public function getEndpoint()
    {
        return $this->getOpt(CURLINFO_EFFECTIVE_URL);
    }

    /**
     * Enable verbosity.
     *
     * @param bool $on
     * @return self
     */
    public function setVerbose($on = true)
    {
        $this->setOpt(CURLOPT_VERBOSE, $on);
        return $this;
    }

    /**
     * @deprecated Call setVerbose() instead
     *
     * @param bool $on
     * @return self
     */
    public function verbose($on = true)
    {
        return $this->setVerbose($on);
    }

    /**
     * Reset all curl options.
     *
     * In order to make multiple requests with the same curl object all settings requires to be reset.
     * @return self
     */
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

    /**
     * Closing the current open curl resource.
     * @return self
     */
    public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        return $this;
    }

    /**
     * Close the connection when the Curl object will be destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Was an 'info' header returned.
     * @return bool
     */
    public function isInfo()
    {
        return $this->getHttpStatus() >= 100 && $this->getHttpStatus() < 200;
    }

    /**
     * Was an 'OK' response returned.
     * @return bool
     */
    public function isSuccess()
    {
        return $this->getHttpStatus() >= 200 && $this->getHttpStatus() < 300;
    }

    /**
     * Was a 'redirect' returned.
     * @return bool
     */
    public function isRedirect()
    {
        return $this->getHttpStatus() >= 300 && $this->getHttpStatus() < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     * @return bool
     */
    public function isError()
    {
        return $this->getHttpStatus() >= 400 && $this->getHttpStatus() < 600;
    }

    /**
     * Was a 'client error' returned.
     * @return bool
     */
    public function isClientError()
    {
        return $this->getHttpStatus() >= 400 && $this->getHttpStatus() < 500;
    }

    /**
     * Was a 'server error' returned.
     * @return bool
     */
    public function isServerError()
    {
        return $this->getHttpStatus() >= 500 && $this->getHttpStatus() < 600;
    }
    
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

    /**
     * Get response from the curl request
     * @return string|false
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get curl error code
     * @return string
     */
    public function getErrorCode()
    {
        return $this->curl_error_code;
    }

    /**
     * Get curl error message
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->curl_error_message;
    }

    /**
     * Get http status code from the curl request
     * @return int
     */
    public function getHttpStatus()
    {
        return $this->http_status_code;
    }
}
