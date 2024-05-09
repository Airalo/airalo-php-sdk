<?php

namespace Airalo\Resources;

use Airalo\Config;
use Airalo\Constants\SdkConstants;

class MultiCurlResource
{
    private const WINDOW = 5;

    private array $handlers = [];

    private static array $headers = [];

    private array $options = [];

    private bool $ignoreSSL = false;

    private bool $timeout = false;

    private $tag;

    private int $rfc = PHP_QUERY_RFC1738;

    private Config $config;

    private array $defaultHeaders = [
        'airalo-php-sdk: ' . SdkConstants::VERSION,
    ];

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        if (!extension_loaded('curl')) {
            throw new \Exception('cURL library is not loaded');
        }

        $this->config = $config;

        self::$headers = array_merge($this->defaultHeaders, $this->config->getHttpHeaders());
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return MultiCurlResource
     */
    public function add(string $methodName, array $args): MultiCurlResource
    {
        $curl = new CurlResource($this->config, true);

        if ($this->ignoreSSL) {
            $curl->ignoreSSL();
        }

        $curl->setRFC($this->rfc);

        if ($this->timeout) {
            $curl->setTimeout($this->timeout);
        }

        if (count(self::$headers) > 0) {
            $curl->setHeaders(self::$headers);
        }

        $handler = call_user_func_array([$curl, $methodName], $args);

        foreach ($this->options as $option => $value) {
            curl_setopt($handler, $option, $value);
        }

        !is_null($this->tag) ? $this->handlers[$this->tag] = $handler : $this->handlers[] = $handler;

        $this->tag = null;

        return $this;
    }

    /**
     * @param array $options
     * @return MultiCurlResource
     */
    public function setopt(array $options): MultiCurlResource
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $name
     * @return MultiCurlResource
     */
    public function tag(string $name = ''): MultiCurlResource
    {
        if ($name) {
            $this->tag = $name;
        }

        return $this;
    }

    /**
     * @param string $url
     * @param array $params
     * @return MultiCurlResource
     */
    public function get(string $url, array $params = []): MultiCurlResource
    {
        $params = array_merge([$url], [$params]);

        return $this->add('get', $params);
    }

    /**
     * @param string $url
     * @param array $params
     * @return MultiCurlResource
     */
    public function post(string $url, array $params = []): MultiCurlResource
    {
        $params = array_merge([$url], [$params]);

        return $this->add('post', $params);
    }

    /**
     * @param array $headers
     * @return MultiCurlResource
     */
    public function setHeaders(array $headers = []): MultiCurlResource
    {
        self::$headers = $headers;

        return $this;
    }

    /**
     * Ignore SSL certificate
     * @return MultiCurlResource
     */
    public function ignoreSSL(): MultiCurlResource
    {
        $this->ignoreSSL = true;

        return $this;
    }

    /**
     * @param int $rfc
     * @return MultiCurlResource
     */
    public function useRFC(int $rfc): MultiCurlResource
    {
        $this->rfc = $rfc;

        return $this;
    }

    /**
     * @param int $timeout
     * @return MultiCurlResource
     */
    public function setTimeout(int $timeout = 30): MultiCurlResource
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param mixed $window
     * @return mixed
     */
    public function exec($window = null)
    {
        if (is_null($window)) {
            $window = self::WINDOW;
        }

        if ($window < 0 || $window > 50) {
            $window = self::WINDOW;
        }

        $master = curl_multi_init();
        $responses = array_fill_keys(array_keys($this->handlers), false);

        $rolling_window = (sizeof($this->handlers) < $window) ? sizeof($this->handlers) : $window;

        reset($this->handlers);

        for ($i = 0; $i < $rolling_window; $i++) {
            $handler = $i == 0 ? current($this->handlers) : next($this->handlers);
            curl_multi_add_handle($master, $handler);
        }

        do {
            $status = curl_multi_exec($master, $active);

            while ($done = curl_multi_info_read($master)) {
                $info = curl_getinfo($done['handle']);

                $output = curl_multi_getcontent($done['handle']);

                $info   = curl_getinfo($done['handle']);
                $header = substr($output, 0, $info['header_size']);
                $output = substr($output, strlen($header));

                $tag = array_search($done['handle'], $this->handlers);

                $responses[$tag] = $output;

                $handler = next($this->handlers);

                if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                    $checkHandler = $handler instanceof \CurlHandle;
                } else {
                    $checkHandler = is_resource($handler);
                }

                if ($checkHandler) {
                    // start a new request (it's important to do this before removing the old one)
                    curl_multi_add_handle($master, $handler);

                    curl_multi_remove_handle($master, $done['handle']);
                }

                $status = curl_multi_exec($master, $active);

                // clean memory
                unset($this->handlers[$tag], $info, $output, $header, $tag, $handler);
            }

            if ($status != CURLM_OK) {
                break;
            }

            if (curl_multi_select($master, 0.05) == -1) {
                time_nanosleep(0, 500000000);
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        $this->handlers = [];

        curl_multi_close($master);

        return $responses;
    }
}
