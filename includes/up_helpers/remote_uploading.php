<?php
/**
*
* @package Kleeja_up_helpers
* @copyright (c) 2007-2012 Kleeja.com
* @license ./docs/license.txt
*
*/

//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}

//
// This helper is used to help in remote uploading
//

/**
 * bring the file size from remote file; aka url 
 * @param mixed $url
 * @param mixed $method
 * @param mixed $data
 * @param mixed $redirect
 */
function get_remote_file_size($url, $method = 'GET', $data = '', $redirect = 10)
{
    $url = parse_url($url);
    $fp  = @fsockopen($url['host'], (! empty($url['port']) ? (int) $url['port'] : 80), $errno, $errstr, 30);

    if ($fp)
    {
        $path   = (! empty($url['path']) ? $url['path'] : '/') . (! empty($url['query']) ? '?' . $url['query'] : '');
        $header = "\r\nHost: " . $url['host'];

        if ('post' == strtolower($method))
        {
            $header .= "\r\nContent-Length: " . strlen($data);
        }

        fputs($fp, $method . ' ' . $path . ' HTTP/1.0' . $header . "\r\n\r\n" . ('post' == strtolower($method) ? $data : ''));

        if (! feof($fp))
        {
            $scheme        = fgets($fp);
            list(, $code)  = explode(' ', $scheme);
            $headers       = ['Scheme' => $scheme];
        }

        while (! feof($fp))
        {
            $h = fgets($fp);

            if ($h == "\r\n" OR $h == "\n")
            {
                break;
            }
            list($key, $value) = explode(':', $h, 2);
            $headers[$key]     = trim($value);

            if ($code >= 300 AND $code < 400 AND strtolower($key) == 'location' AND $redirect > 0)
            {
                return get_remote_file_size($headers[$key], $method, $data, --$redirect);
            }
        }

        $body = '';
        // while ( !feof($fp) ) $body .= fgets($fp);
        fclose($fp);
    }
    else
    {
        return (['error' => ['errno' => $errno, 'errstr' => $errstr]]);
    }

    return (string) $headers['Content-Length'];
}
