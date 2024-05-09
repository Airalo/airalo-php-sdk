<?php

namespace Airalo\Resources;

use Airalo\Config;
use Airalo\Constants\SdkConstants;
use Airalo\Exceptions\AiraloException;
use CurlHandle;

class CurlResource
{
    /**
     * @var resource|null
     */
    private $curl = null;

    public string $header = '';

    public int $code = 0;

    private Config $config;

    private bool $ignoreSSL = false;

    private int $rfc = PHP_QUERY_RFC1738;

    private bool $getHandler = false;

    private array $requestHeaders = [];

    private array $defaultHeaders = [
        'airalo-php-sdk: ' . SdkConstants::VERSION,
    ];

    /**
     * @param Config $config
     * @param boolean $getHandler
     */
    public function __construct(Config $config, bool $getHandler = false)
    {
        if (!extension_loaded('curl')) {
            throw new AiraloException('cURL library is not loaded');
        }

        $this->getHandler = $getHandler;
        $this->config = $config;

        $this->requestHeaders = array_merge($this->defaultHeaders, $this->config->getHttpHeaders());
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call(string $methodName, array $args)
    {
        if (!method_exists($this, $methodName)) {
            return false;
        }

        $this->initCurl();

        $result = call_user_func_array([$this, $methodName], $args);

        return $result;
    }

    /**
     * @param string $url
     * @return mixed
     */
    private function request(string $url = '')
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_VERBOSE, true);

        if ($this->ignoreSSL) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
            $this->ignoreSSL = false;
        }

        if ($this->getHandler) {
            return $this->curl;
        }

        $resp = curl_exec($this->curl);
        $info = curl_getinfo($this->curl);

        if ($info['http_code'] == 417) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, ["Expect:  "]);
            $resp = curl_exec($this->curl);
            $info = curl_getinfo($this->curl);
        }

        $header = substr($resp ?: '', 0, $info['header_size']);
        $response = substr($resp ?: '', strlen($header));

        if (is_string($resp)) {
            $header = substr($resp, 0, $info['header_size']);
            $response = substr($resp, strlen($header));
        } else {
            $header = '';
            $response = '';
        }

        preg_match('#HTTP.* (?P<code>\d+)#', $header, $matches);
        $this->header = $header;
        $this->code = (int)($matches['code'] ?? null);

        $this->reset();

        return $response;
    }

    /**
     * @param array $options
     * @return CurlResource
     */
    public function setopt(array $options): CurlResource
    {
        $this->initCurl();
        foreach ($options as $option => $value) {
            curl_setopt($this->curl, $option, $value);
        }

        return $this;
    }

    /**
     * @return CurlResource
     */
    public function ignoreSSL(): CurlResource
    {
        $this->ignoreSSL = true;

        return $this;
    }

    public function useRFC(int $rfc): CurlResource
    {
        $this->rfc = $rfc;

        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return CurlResource
     */
    public function setBasicAuthentication(string $username, string $password = ''): CurlResource
    {
        $this->initCurl();
        curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->curl, CURLOPT_USERPWD, $username . ':' . $password);

        return $this;
    }

    /**
     * @param array $array
     * @return CurlResource
     */
    public function setHeaders(array $array = []): CurlResource
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $array);

        $this->initCurl();

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array_unique($this->requestHeaders));

        return $this;
    }

    /**
     * @param integer $timeout
     * @return CurlResource
     */
    public function setTimeout(int $timeout = 30): CurlResource
    {
        $this->initCurl();
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);

        return $this;
    }

    /**
     * @return boolean
     */
    private function initCurl(): bool
    {
        if ($this->curl instanceof CurlHandle || is_resource($this->curl)) {
            return true;
        }

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 60);

        return true;
    }

    /**
     * @return void
     */
    private function reset(): void
    {
        if ($this->curl instanceof CurlHandle || is_resource($this->curl)) {
            curl_close($this->curl);
            // reset headers
            $this->requestHeaders = array_merge($this->defaultHeaders, $this->config->getHttpHeaders());
        }
    }

    /**
     * @param string $url
     * @param array $params
     * @return mixed
     */
    private function get(string $url = '', array $params = [])
    {
        if (is_array($params) && !empty($params)) {
            $url = rtrim($url, '?');
            $params = http_build_query($params, '', '&', $this->rfc);
            $url .= '?' . $params;
        }

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        return $this->request($url);
    }

    /**
     * @param string $url
     * @param mixed $params
     * @return mixed
     */
    private function post(string $url = '', $params = [])
    {
        if (is_array($params)) {
            $params = json_encode($params);
        }

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POST, true);

        return $this->request($url);
    }

    /**
     * @param string $url
     * @param array $params
     * @return mixed
     */
    private function head(string $url = '', array $params = [])
    {
        if (is_array($params)) {
            $params = http_build_query($params, '', '&', $this->rfc);
        }

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);

        return $this->request($url);
    }
}
