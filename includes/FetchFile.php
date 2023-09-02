<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license http://www.kleeja.net/license
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
    private $timeout         = 60;
    private $destinationPath = '';
    private $maxRedirects    = 3;
    private $binary          = false;


    public function __construct($url)
    {
        $this->url = $url;
    }

    public static function make($url)
    {
        return new static($url);
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
        if (defined('KJ_SESSION'))
        {
            session_id(constant('KJ_SESSION'));
        }

        session_start();
    }

    protected function curl()
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)');
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);


        if ($this->binary)
        {
            curl_setopt($ch, CURLOPT_ENCODING, '');
        }

        //let's open new file to save it in.
        if (! empty($this->destinationPath))
        {
            $out = fopen($this->destinationPath, 'w');
            curl_setopt($ch, CURLOPT_FILE, $out);
            $result = curl_exec($ch);

            if ($result === false)
            {
                $error = true;
                kleeja_log(sprintf("cUrl error (#%d): %s\n", curl_errno($ch), htmlspecialchars(curl_error($ch))));
            }
            
            curl_close($ch);
            fclose($out);

            return isset($error) ? false : true;
        }
        else
        {
            $data = curl_exec($ch);

            if ($data === false)
            {
                $error = true;
                kleeja_log(sprintf("FetchFile error (curl: #%d): %s\n", curl_errno($ch), htmlspecialchars(curl_error($ch))));
            }
            
            curl_close($ch);

            return isset($error) ? false : $data;
        }
    }

    protected function fopen()
    {
        // Setup a stream context
        $stream_context = stream_context_create(
            [
                'http' => [
                    'method'              => 'GET',
                    'user_agent'          => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)',
                    'max_redirects'       => $this->maxRedirects + 1,
                    'timeout'             => $this->timeout
                ]
            ]
        );

        $content = @file_get_contents($this->url, false, $stream_context);


        // Did we get anything?
        if ($content !== false)
        {
            if (! empty($this->destinationPath))
            {
                $fp2 = fopen($this->destinationPath, 'w' . ($this->binary ? 'b' : ''));
                @fwrite($fp2, $content);
                @fclose($fp2);
                unset($content);
                return true;
            }

            return $content;
        }
        else
        {
            $error = error_get_last();
            kleeja_log(sprintf("FetchFile error (stream: #%s): %s\n", $error['type'], $error['message']));
        }

        return false;
    }
}
