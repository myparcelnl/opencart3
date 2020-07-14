<?php

class MyParcel_Curl
{
    protected $options;
    private $_curl;

    public function __construct()
    {
        if (!function_exists("curl_init")) {
            throw new Exception("cURL is not installed");
        }
    }

    public function getDefaultOptions()
    {
        $curl_version = curl_version();
        return array(
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_FAILONERROR     => false,
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FRESH_CONNECT   => true,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_HEADER          => true,
            CURLOPT_TIMEOUT         => 20,
            CURLOPT_CONNECTTIMEOUT  => 20,
            CURLOPT_ENCODING        => "",
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_USERAGENT       => 'Opencart/2.3.0.2',
            CURLOPT_CAINFO          => dirname(__FILE__) . '/ssl/ca-bundle.pem'
        );
    }
    /**
     * Set Opt
     *
     * @access public
     * @param  $option
     * @param  $value
     *
     * @return boolean
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function sendRequest($url, $method = 'GET', $post_data = null, $headers = array(), $raw = false)
    {
        $this->options = $this->getDefaultOptions();

        $this->setOption(CURLOPT_URL, $url);

        if ((ini_get('open_basedir') == '') AND (!ini_get('safe_mode'))) {
            $this->setOption(CURLOPT_FOLLOWLOCATION, true);
        }

        switch ($method) {
            case "PUT":
                $this->setOption(CURLOPT_PUT, true);
                break;

            case "POST":
                $this->setOption(CURLOPT_POST, true);
                break;

            case "DELETE":
                $this->setOption(CURLOPT_CUSTOMREQUEST, "DELETE");
                break;

            case "GET":
            default:
                break;
        }

        if (!empty($post_data)) {
            $this->setOption(CURLOPT_POSTFIELDS, $post_data);
        }

        if (!empty($headers)) {
            $this->setOption(CURLOPT_HTTPHEADER, $headers);
        }

        $this->_curl = curl_init();

        if (!is_resource($this->_curl) || !isset($this->_curl)) {
            throw new Exception("Unable to create cURL session");
        }

        $result_setopt = curl_setopt_array($this->_curl, $this->getOptions());
        if ($result_setopt !== TRUE) {
            throw new Exception(curl_error($this->_curl));
        }

        $response = curl_exec($this->_curl);
        $info = curl_getinfo($this->_curl);
        curl_close($this->_curl);

        $status = $info["http_code"];
        $header = substr($response, 0, $info["header_size"]);
        $body = substr( $response, $info["header_size"]);

        if ($raw !== true) {
            $body = json_decode($body, true);
        }

        if ($status > 400) {
            if (!empty($body['errors'])) {
                $error = $this->parseErrors($body['errors']);
            } elseif ( !empty($body["message"] ) ) {
                $error = $body["message"];
            } else {
                $error = "Unknown error";
            }

            $body = $error;
        }

        $response_headers = $this->getResponseHeader($header);

        return array("code" => $status, "body" => $body, "headers" => $response_headers);
    }

    function getResponseHeader($response)
    {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }

    function parseErrors($errors)
    {
        if (is_array($errors)) {
            $error_messages = array();
            foreach ($errors as $error) {
                if (isset($error['message'])) {
                    $error_messages[] = '<li>' . $error['message'] . '</li>';
                }
            }

            return sprintf('<ul>%s</ul>', implode("\r\n", $error_messages));
        }

        return 'Unknown error during the request';
    }

    public function get($url, $headers = array(), $raw = false) {
        return $this->sendRequest($url, "GET", null, $headers, $raw);
    }

    public function post($url, $post, $headers = array(), $raw = false) {
        return $this->sendRequest($url, "POST", $post, $headers, $raw);
    }

    public function delete($url, $headers = array(), $raw = false) {
        return $this->sendRequest($url, "GET", null, $headers, $raw);
    }
}