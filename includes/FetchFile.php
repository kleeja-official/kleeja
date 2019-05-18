<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.com
 * @license http://www.kleeja.com/license
 *
 */

//no for directly open
if (! defined('IN_COMMON'))
{
    exit;
}

class FetchFile
{
    private $url;
    private $headOnly = false;
    private $timeout  = 60;
    private $destinationPath;
    private $maxRedirects = 3;
    private $binary       = false;


    public function __construct($url)
    {
        $this->url = $url;
    }

    public static function make($url)
    {
        return new static($url);
    }

    public function isHeadOnly($val)
    {
        $this->headOnly = $val;
        return $this;
    }

    public function setTimeOut($seconds)
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function setDestinationPath($path)
    {
        $this->destinationPath = $path;
        return $this;
    }

    public function setMaxRedirects($limit)
    {
        $this->maxRedirects = $limit;
        return $this;
    }

    public function isBinaryFile($val)
    {
        $this->binary = $val;
        return $this;
    }

    public function get()
    {
        $fetchType = '';

        $allow_url_fopen = function_exists('ini_get')
                            ? strtolower(@ini_get('allow_url_fopen'))
                            : strtolower(@get_cfg_var('allow_url_fopen'));

        if (function_exists('curl_init'))
        {
            $fetchType = 'curl';
        }
        // fsockopen() is the second best thing
        elseif (function_exists('fsockopen'))
        {
            $fetchType = 'fsocket';
        }
        // Last case scenario, we use file_get_contents provided allow_url_fopen is enabled (any non 200 response results in a failure)
        elseif (in_array($allow_url_fopen, ['on', 'true', '1']))
        {
            $fetchType = 'fopen';
        }

        session_write_close();

        $result = null;

        is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_fetch_file_start', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        if (! empty($fetchType))
        {
            $result =  $this->{$fetchType}();
        }

        $this->finishUp();

        return $result;
    }

    protected function finishUp()
    {
        global $klj_session;

        session_id($klj_session);

        session_start();
    }

    protected function curl()
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, $this->headOnly);
        curl_setopt($ch, CURLOPT_NOBODY, $this->headOnly);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        if ($this->binary)
        {
            curl_setopt($ch, CURLOPT_ENCODING, '');
        }

        //let's open new file to save it in.
        if (! empty($this->destinationPath))
        {
            $out = @fopen($this->destinationPath, 'w');
            curl_setopt($ch, CURLOPT_FILE, $out);
            @curl_exec($ch);
            curl_close($ch);
            fclose($out);
        }

        if ($this->headOnly)
        {
            // Grab the page
            $data          = @curl_exec($ch);
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($data !== false && $response_code == '200')
            {
                return explode("\r\n", str_replace("\r\n\r\n", "\r\n", trim($data)));
            }

            return false;
        }
        else
        {
            if (! empty($this->destinationPath))
            {
                $data = @curl_exec($ch);
                curl_close($ch);
            }

            return ! empty($this->destinationPath) ? true : $data;
        }
    }

    protected function fsocket()
    {
        $url_parsed = parse_url($this->url);
        $host       = $url_parsed['host'];
        $port       = empty($url_parsed['port']) || $url_parsed['port'] == 0 ? 80 : $url_parsed['port'];
        $path       = $url_parsed['path'];

        if (isset($url_parsed['query']) && $url_parsed['query'] != '')
        {
            $path .= '?' . $url_parsed['query'];
        }

        if (! $fp = @fsockopen($host, $port, $_, $_, $this->timeout))
        {
            return false;
        }

        // Send a standard HTTP 1.0 request for the page
        fwrite($fp, ($this->headOnly ? 'HEAD' : 'GET') . " $path HTTP/1.0\r\n");
        fwrite($fp, "Host: $host\r\n");
        fwrite($fp, 'User-Agent: Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)' . "\r\n");
        fwrite($fp, 'Connection: Close' . "\r\n\r\n");

        stream_set_timeout($fp, $this->timeout);
        $stream_meta = stream_get_meta_data($fp);

        $fp2 = null;

        //let's open new file to save it in.
        if (! empty($this->destinationPath))
        {
            $fp2 = @fopen($this->destinationPath, 'w' . ($this->binary ? '' : ''));
        }

        // Fetch the response 1024 bytes at a time and watch out for a timeout
        $in = false;
        $h  = false;

        while (! feof($fp) && ! $stream_meta['timed_out'])
        {
            $s = fgets($fp, 1024);

            if (! empty($this->destinationPath))
            {
                if ($s == "\r\n")
                { //|| $s == "\n")
                    $h = true;

                    continue;
                }

                if ($h)
                {
                    @fwrite($fp2, $s);
                }
            }

            $in .= $s;
            $stream_meta = stream_get_meta_data($fp);
        }

        fclose($fp);

        if (! empty($this->destinationPath))
        {
            unset($in);
            @fclose($fp2);
            return true;
        }

        // Process 301/302 redirect
        if ($in !== false && $this->maxRedirects > 0 && preg_match('#^HTTP/1.[01] 30[12]#', $in))
        {
            $headers = explode("\r\n", trim($in));

            foreach ($headers as $header)
            {
                if (substr($header, 0, 10) == 'Location: ')
                {
                    $response = static::make(substr($header, 10))
                        ->setDestinationPath($this->destinationPath)
                        ->setTimeOut($this->timeout)
                        ->isHeadOnly($this->headOnly)
                        ->setMaxRedirects($this->maxRedirects - 1)
                        ->get();

                    if ($response != false)
                    {
                        $headers[] = $response;
                    }
                    return $headers;
                }
            }
        }

        // Ignore everything except a 200 response code
        if ($in !== false && preg_match('#^HTTP/1.[01] 200 OK#', $in))
        {
            if ($this->headOnly)
            {
                return explode("\r\n", trim($in));
            }
            else
            {
                $content_start = strpos($in, "\r\n\r\n");

                if ($content_start !== false)
                {
                    return substr($in, $content_start + 4);
                }
            }
        }

        return $in;
    }

    protected function fopen()
    {
        // PHP5's version of file_get_contents() supports stream options
        if (version_compare(PHP_VERSION, '5.0.0', '>='))
        {
            // Setup a stream context
            $stream_context = stream_context_create(
            [
                'http' => [
                    'method'              => $this->headOnly ? 'HEAD' : 'GET',
                    'user_agent'          => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)',
                    'max_redirects'       => $this->maxRedirects + 1,    // PHP >=5.1.0 only
                    'timeout'             => $this->timeout    // PHP >=5.2.1 only
                ]
            ]
        );

            $content = @file_get_contents($this->url, false, $stream_context);
        }
        else
        {
            $content = @file_get_contents($this->url);
        }

        // Did we get anything?
        if ($content !== false)
        {
            // Gotta love the fact that $http_response_header just appears in the global scope (*cough* hack! *cough*)
            if ($this->headOnly)
            {
                return $http_response_header;
            }

            if (! empty($this->destinationPath))
            {
                $fp2 = fopen($this->destinationPath, 'w' . ($this->binary ? 'b' : ''));
                @fwrite($fp2, $content);
                @fclose($fp2);
                unset($content);
                return true;
            }
        }

        return false;
    }
}
