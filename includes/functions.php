<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}




/**
 *  Detect a bot activity an record it
*/
function kleeja_detecting_bots()
{
    global $SQL, $usrcp, $dbprefix, $config, $klj_session;

    // get information ..
    $agent	= $SQL->escape($_SERVER['HTTP_USER_AGENT']);
    $time	 = time();

    //for stats
    if (strpos($agent, 'Google') !== false)
    {
        $update_query = [
            'UPDATE'	=> "{$dbprefix}stats",
            'SET'		  => "last_google=$time, google_num=google_num+1"
        ];
        is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_google_lst_num', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        $SQL->build($update_query);
    }
    elseif (strpos($agent, 'Bing') !== false)
    {
        $update_query = [
            'UPDATE'	=> "{$dbprefix}stats",
            'SET'		  => "last_bing=$time, bing_num=bing_num+1"
        ];
        is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_bing_lst_num', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        $SQL->build($update_query);
    }

    //put another bots as a hook if you want !
    is_array($plugin_run_result = Plugins::getInstance()->run('anotherbots_onlline_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //clean online table
    if ((time() - $config['last_online_time_update']) >= 3600)
    {
        //what to add here ?
        //update last_online_time_update
        update_config('last_online_time_update', time());
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('KleejaOnline_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
}


/**
 * Ban system
*/
function get_ban()
{
    global $banss, $lang, $tpl, $text, $SQL;

    //visitor ip now
    $ip	= get_ip();

    //now .. loop for banned ips
    if (is_array($banss) && ! empty($ip))
    {
        foreach ($banss as $ip2)
        {
            $ip2 = trim($ip2);

            if (empty($ip2))
            {
                continue;
            }

            //first .. replace all * with something good .
            $replace_it = str_replace('*', '([0-9]{1,3})', $ip2);
            $replace_it = str_replace('.', '\.', $replace_it);

            if ($ip == $ip2 || @preg_match('/' . preg_quote($replace_it, '/') . '/i', $ip))
            {
                is_array($plugin_run_result = Plugins::getInstance()->run('banned_get_ban_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                //
                // if the request is an image
                //
                if (
                    ( defined('IN_DOWNLOAD') && (ig('img') || ig('thmb') || ig('thmbf') || ig('imgf')) )
                    || g('go', 'str', '') == 'queue'
                ) {
                    @$SQL->close();
                    $fullname = 'images/banned_user.jpg';
                    $filesize = filesize($fullname);
                    header("Content-length: $filesize");
                    header('Content-type: image/jpg');
                    readfile($fullname);

                    exit;
                }
                else
                {
                    kleeja_info($lang['U_R_BANNED'], $lang['U_R_BANNED'], true);
                }
            }
        }
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('get_ban_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
}


/**
 * Check if the given plugin installed ?
 * @param $plugin_name
 * @return bool
 */
function kleeja_plugin_exists($plugin_name)
{
    global $SQL, $dbprefix;

    $query = [
        'SELECT'	=> 'p.plg_id',
        'FROM'		 => "{$dbprefix}plugins p",
        'WHERE'		=> "p.plg_name = '" . $SQL->escape($plugin_name) . "'",
    ];

    $result	= $SQL->build($query);
    $num    = $SQL->num_rows($result);

    if ($num)
    {
        $d = $SQL->fetch($result);
        $SQL->freeresult();
        return $d['plg_id'];
    }

    return false;
}

/**
* Return current page url
*/
function kleeja_get_page()
{
    if (isset($_SERVER['REQUEST_URI']))
    {
        $location = $_SERVER['REQUEST_URI'];
    }
    elseif (isset($_ENV['REQUEST_URI']))
    {
        $location = $_ENV['REQUEST_URI'];
    }
    else
    {
        if (isset($_SERVER['PATH_INFO']))
        {
            $location = $_SERVER['PATH_INFO'];
        }
        elseif (isset($_ENV['PATH_INFO']))
        {
            $location = $_SERVER['PATH_INFO'];
        }
        elseif (isset($_ENV['PHP_SELF']))
        {
            $location = $_ENV['PHP_SELF'];
        }
        else
        {
            $location = $_SERVER['PHP_SELF'];
        }

        if (isset($_SERVER['QUERY_STRING']))
        {
            $location .= '?' . $_SERVER['QUERY_STRING'];
        }
        elseif (isset($_ENV['QUERY_STRING']))
        {
            $location = '?' . $_ENV['QUERY_STRING'];
        }
    }

    $return = str_replace(['&amp;'], ['&'], htmlspecialchars($location));
    return $return;
}

/**
 * Fix email string to be UTF8
 * @param $text
 * @return string
 */
function _sm_mk_utf8($text)
{
    return '=?UTF-8?B?' . kleeja_base64_encode($text) . '?=';
}

/**
 * Send an email message
 * @param  string $to
 * @param  string $body
 * @param  string $subject
 * @param  string $fromAddress
 * @param  string $fromName
 * @param  string $bcc
 * @return bool
 */
function send_mail($to, $body, $subject, $fromAddress, $fromName, $bcc = '')
{
    $eol     = "\r\n";
    $headers = '';
    $headers .= 'From: ' . _sm_mk_utf8(trim(preg_replace('#[\n\r:]+#s', '', $fromName))) . ' <' . trim(preg_replace('#[\n\r:]+#s', '', $fromAddress)) . '>' . $eol;
    $headers .= 'MIME-Version: 1.0' . $eol;
    $headers .= 'Content-transfer-encoding: 8bit' . $eol; // 7bit
    $headers .= 'Content-Type: text/plain; charset=utf-8' . $eol; // format=flowed
    $headers .= 'X-Mailer: Kleeja Mailer' . $eol;
    $headers .= 'Reply-To: ' . _sm_mk_utf8(trim(preg_replace('#[\n\r:]+#s', '', $fromName))) . ' <' . trim(preg_replace('#[\n\r:]+#s', '', $fromAddress)) . '>' . $eol;

    if (! empty($bcc))
    {
        $headers .= 'Bcc: ' . trim(preg_replace('#[\n\r:]+#s', '', $bcc)) . $eol;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_send_mail', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $body = str_replace(["\n", "\0"], ["\r\n", ''], $body);

    // Change the line breaks used in the headers according to OS
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'MAC')
    {
        $headers = str_replace("\r\n", "\r", $headers);
    }
    elseif (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
    {
        $headers = str_replace("\r\n", "\n", $headers);
    }

    $mail_sent = @mail(trim(preg_replace('#[\n\r]+#s', '', $to)), _sm_mk_utf8(trim(preg_replace('#[\n\r]+#s', '', $subject))), $body, $headers);

    return $mail_sent;
}


/**
 * Get remote files
 * (c) punbb + Kleeja team
 * @param $url
 * @param  bool              $save_in
 * @param  int               $timeout
 * @param  bool              $head_only
 * @param  int               $max_redirects
 * @param  bool              $binary
 * @return bool|string|array
 */
function fetch_remote_file($url, $save_in = false, $timeout = 20, $head_only = false, $max_redirects = 10, $binary = false)
{
    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_fetch_remote_file_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    // Quite unlikely that this will be allowed on a shared host, but it can't hurt
    if (function_exists('ini_set'))
    {
        @ini_set('default_socket_timeout', $timeout);
    }
    $allow_url_fopen = function_exists('ini_get') 
                    ? strtolower(@ini_get('allow_url_fopen')) 
                    : strtolower(@get_cfg_var('allow_url_fopen'));

    if (function_exists('curl_init'))
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, $head_only);
        curl_setopt($ch, CURLOPT_NOBODY, $head_only);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        if ($binary)
        {
            curl_setopt($ch, CURLOPT_ENCODING, '');
        }

        //let's open new file to save it in.
        if ($save_in)
        {
            $out = @fopen($save_in, 'w');
            curl_setopt($ch, CURLOPT_FILE, $out);
            @curl_exec($ch);
            curl_close($ch);
            fclose($out);
        } 

        if ($head_only)
        {
            // Grab the page
            $data          = @curl_exec($ch);
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close();

            if ($data !== false && $response_code == '200')
            {
                return explode("\r\n", str_replace("\r\n\r\n", "\r\n", trim($data)));
            }
        }
        else
        {
            if (! $save_in)
            {
                $data = @curl_exec($ch);
                curl_close();
            }

            return $save_in ? true : $data;
        }
    }
    // fsockopen() is the second best thing
    elseif (function_exists('fsockopen'))
    {
        $url_parsed = parse_url($url);
        $host       = $url_parsed['host'];
        $port       = empty($url_parsed['port']) || $url_parsed['port'] == 0 ? 80 : $url_parsed['port'];
        $path       = $url_parsed['path'];

        if (isset($url_parsed['query']) && $url_parsed['query'] != '')
        {
            $path .= '?' . $url_parsed['query'];
        }

        if (! $fp = @fsockopen($host, $port, $errno, $errstr, $timeout))
        {
            return false;
        }

        // Send a standard HTTP 1.0 request for the page
        fwrite($fp, ($head_only ? 'HEAD' : 'GET') . " $path HTTP/1.0\r\n");
        fwrite($fp, "Host: $host\r\n");
        fwrite($fp, 'User-Agent: Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)' . "\r\n");
        fwrite($fp, 'Connection: Close' . "\r\n\r\n");

        stream_set_timeout($fp, $timeout);
        $stream_meta = stream_get_meta_data($fp);

        $fp2 = null;

        //let's open new file to save it in.
        if ($save_in)
        {
            $fp2 = @fopen($save_in, 'w' . ($binary ? '' : ''));
        }

        // Fetch the response 1024 bytes at a time and watch out for a timeout
        $in = false;
        $h  = false;

        while (! feof($fp) && ! $stream_meta['timed_out'])
        {
            $s = fgets($fp, 1024);

            if ($save_in)
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

        if ($save_in)
        {
            unset($in);
            @fclose($fp2);
            return true;
        }

        // Process 301/302 redirect
        if ($in !== false && $max_redirects > 0 && preg_match('#^HTTP/1.[01] 30[12]#', $in))
        {
            $headers = explode("\r\n", trim($in));

            foreach ($headers as $header)
            {
                if (substr($header, 0, 10) == 'Location: ')
                {
                    $response = fetch_remote_file(substr($header, 10), $save_in, $timeout, $head_only, $max_redirects - 1);

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
            if ($head_only)
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
    // Last case scenario, we use file_get_contents provided allow_url_fopen is enabled (any non 200 response results in a failure)
    elseif (in_array($allow_url_fopen, ['on', 'true', '1']))
    {
        // PHP5's version of file_get_contents() supports stream options
        if (version_compare(PHP_VERSION, '5.0.0', '>='))
        {
            // Setup a stream context
            $stream_context = stream_context_create(
                [
                    'http' => [
                        'method'		      => $head_only ? 'HEAD' : 'GET',
                        'user_agent'	   => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0; Kleeja)',
                        'max_redirects'	=> $max_redirects + 1,	// PHP >=5.1.0 only
                        'timeout'		     => $timeout	// PHP >=5.2.1 only
                    ]
                ]
            );

            $content = @file_get_contents($url, false, $stream_context);
        }
        else
        {
            $content = @file_get_contents($url);
        }

        // Did we get anything?
        if ($content !== false)
        {
            // Gotta love the fact that $http_response_header just appears in the global scope (*cough* hack! *cough*)
            if ($head_only)
            {
                return $http_response_header;
            }

            if ($save_in)
            {
                $fp2 = fopen($save_in, 'w' . ($binary ? 'b' : ''));
                @fwrite($fp2, $content);
                @fclose($fp2);
                unset($content);
                return true;
            }

            return $content;
        }
    }

    return false;
}


/**
 * Delete cache
 * @param  string $name
 * @param  bool   $all  if true, all cache in cache folder will be deleted
 * @return bool
 */
function delete_cache($name, $all=false)
{

    //Those files are exceptions and not for deletion
    $exceptions = ['.htaccess', 'index.html', 'php.ini', 'web.config'];

    //ignore kleeja_log in dev stage.
    if (defined('DEV_STAGE'))
    {
        array_push($exceptions, 'kleeja_log.log');
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('delete_cache_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //handle array of cached files
    if (is_array($name))
    {
        foreach ($name as $n)
        {
            delete_cache($n, false);
        }
        return true;
    }

    $path_to_cache = PATH . 'cache';

    if ($all)
    {
        $del = true;

        if ($dh = @opendir($path_to_cache))
        {
            while (($file = @readdir($dh)) !== false)
            {
                if ($file != '.' && $file != '..' && ! in_array($file, $exceptions))
                {
                    kleeja_unlink($path_to_cache . '/' . $file, true);
                }
            }
            @closedir($dh);
        }
    }
    else
    {
        if (strpos($name, 'tpl_') !== false && strpos($name, '.html') !== false)
        {
            $name = str_replace('.html', '', $name);
        }

        $del  = true;
        $name = str_replace('.php', '', $name) . '.php';

        if (file_exists($path_to_cache . '/' . $name))
        {
            $del = kleeja_unlink ($path_to_cache . '/' . $name, true);
        }
    }

    return $del;
}

/**
 * Try delete files or at least change its name.
 * for those who have dirty hosting
 * @param  string $filePath
 * @param  bool   $cache_file
 * @return bool
 */
function kleeja_unlink($filePath, $cache_file = false)
{
    $return = false;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_unlink_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    if ($return)
    {
        return true;
    }

    //99.9% who use this
    if (function_exists('unlink'))
    {
        return unlink($filePath);
    }
    //5% only who use this
    //else if (function_exists('exec'))
    //{
    //	$out = array();
    //	$return = null;
    //	exec('del ' . escapeshellarg(realpath($filepath)) . ' /q', $out, $return);
    //	return $return;
    //}
    //5% only who use this
    //else if (function_exists('system'))
    //{
    //	$return = null;
    //	system ('del ' . escapeshellarg(realpath($filepath)) . ' /q', $return);
    //	return $return;
    //}
    //just rename cache file if there is new thing
    elseif (function_exists('rename') && $cache_file)
    {
        $new_name = substr($filePath, 0, strrpos($filePath, '/') + 1) . 'old_' . md5($filePath . time()) . '.php';
        return rename($filePath, $new_name);
    }

    return false;
}

/**
 * Get mime header
 * @param  string $ext file extension
 * @return string mime
 */
function get_mime_for_header($ext)
{
    $mime_types = [
        '323'   => 'text/h323',
        'rar'   => 'application/x-rar-compressed',
        'acx'   => 'application/internet-property-stream',
        'ai'    => 'application/postscript',
        'aif'   => 'audio/x-aiff',
        'aifc'  => 'audio/x-aiff',
        'aiff'  => 'audio/x-aiff',
        'asf'   => 'video/x-ms-asf',
        'asr'   => 'video/x-ms-asf',
        'asx'   => 'video/x-ms-asf',
        'au'    => 'audio/basic',
        'avi'   => 'video/x-msvideo',
        'axs'   => 'application/olescript',
        'bas'   => 'text/plain',
        'bcpio' => 'application/x-bcpio',
        'bin'   => 'application/octet-stream',
        'bmp'   => 'image/bmp', // this is not a good mime, but it work anyway
        //"bmp"	=> "image/x-ms-bmp", # @see bugs.php.net/47359
        'c'       => 'text/plain',
        'cat'     => 'application/vnd.ms-pkiseccat',
        'cdf'     => 'application/x-cdf',
        'cer'     => 'application/x-x509-ca-cert',
        'class'   => 'application/octet-stream',
        'clp'     => 'application/x-msclip',
        'cmx'     => 'image/x-cmx',
        'cod'     => 'image/cis-cod',
        'psd'     => 'image/psd',
        'cpio'    => 'application/x-cpio',
        'crd'     => 'application/x-mscardfile',
        'crl'     => 'application/pkix-crl',
        'crt'     => 'application/x-x509-ca-cert',
        'csh'     => 'application/x-csh',
        'css'     => 'text/css',
        'dcr'     => 'application/x-director',
        'der'     => 'application/x-x509-ca-cert',
        'dir'     => 'application/x-director',
        'dll'     => 'application/x-msdownload',
        'dms'     => 'application/octet-stream',
        'doc'     => 'application/msword',
        'dot'     => 'application/msword',
        'dvi'     => 'application/x-dvi',
        'dxr'     => 'application/x-director',
        'eps'     => 'application/postscript',
        'etx'     => 'text/x-setext',
        'evy'     => 'application/envoy',
        'exe'     => 'application/octet-stream',
        'fif'     => 'application/fractals',
        'flr'     => 'x-world/x-vrml',
        'gif'     => 'image/gif',
        'gtar'    => 'application/x-gtar',
        'gz'      => 'application/x-gzip',
        'h'       => 'text/plain',
        'hdf'     => 'application/x-hdf',
        'hlp'     => 'application/winhlp',
        'hqx'     => 'application/mac-binhex40',
        'hta'     => 'application/hta',
        'htc'     => 'text/x-component',
        'htm'     => 'text/html',
        'html'    => 'text/html',
        'htt'     => 'text/webviewhtml',
        'ico'     => 'image/x-icon',
        'ief'     => 'image/ief',
        'iii'     => 'application/x-iphone',
        'ins'     => 'application/x-internet-signup',
        'isp'     => 'application/x-internet-signup',
        'jfif'    => 'image/pipeg',
        'jpe'     => 'image/jpeg',
        'jpeg'    => 'image/jpeg',
        'jpg'     => 'image/jpeg',
        'png'     => 'image/png',
        'js'      => 'application/x-javascript',
        'latex'   => 'application/x-latex',
        'lha'     => 'application/octet-stream',
        'lsf'     => 'video/x-la-asf',
        'lsx'     => 'video/x-la-asf',
        'lzh'     => 'application/octet-stream',
        'm13'     => 'application/x-msmediaview',
        'm14'     => 'application/x-msmediaview',
        'm3u'     => 'audio/x-mpegurl',
        'man'     => 'application/x-troff-man',
        'mdb'     => 'application/x-msaccess',
        'me'      => 'application/x-troff-me',
        'mht'     => 'message/rfc822',
        'mhtml'   => 'message/rfc822',
        'mid'     => 'audio/mid',
        'mny'     => 'application/x-msmoney',
        'mov'     => 'video/quicktime',
        'movie'   => 'video/x-sgi-movie',
        'mp2'     => 'video/mpeg',
        'mp3'     => 'audio/mpeg',
        'mp4'     => 'video/mp4',
        'm4a'     => 'audio/mp4',
        'mpa'     => 'video/mpeg',
        'mpe'     => 'video/mpeg',
        'mpeg'    => 'video/mpeg',
        'mpg'     => 'video/mpeg',
        'amr'     => 'audio/3gpp',
        'mpp'     => 'application/vnd.ms-project',
        'mpv2'    => 'video/mpeg',
        'ms'      => 'application/x-troff-ms',
        'mvb'     => 'application/x-msmediaview',
        'nws'     => 'message/rfc822',
        'oda'     => 'application/oda',
        'p10'     => 'application/pkcs10',
        'p12'     => 'application/x-pkcs12',
        'p7b'     => 'application/x-pkcs7-certificates',
        'p7c'     => 'application/x-pkcs7-mime',
        'p7m'     => 'application/x-pkcs7-mime',
        'p7r'     => 'application/x-pkcs7-certreqresp',
        'p7s'     => 'application/x-pkcs7-signature',
        'pbm'     => 'image/x-portable-bitmap',
        'pdf'     => 'application/pdf',
        'pfx'     => 'application/x-pkcs12',
        'pgm'     => 'image/x-portable-graymap',
        'pko'     => 'application/ynd.ms-pkipko',
        'pma'     => 'application/x-perfmon',
        'pmc'     => 'application/x-perfmon',
        'pml'     => 'application/x-perfmon',
        'pmr'     => 'application/x-perfmon',
        'pmw'     => 'application/x-perfmon',
        'pnm'     => 'image/x-portable-anymap',
        'pot'     => 'application/vnd.ms-powerpoint',
        'ppm'     => 'image/x-portable-pixmap',
        'pps'     => 'application/vnd.ms-powerpoint',
        'ppt'     => 'application/vnd.ms-powerpoint',
        'prf'     => 'application/pics-rules',
        'ps'      => 'application/postscript',
        'pub'     => 'application/x-mspublisher',
        'qt'      => 'video/quicktime',
        'ra'      => 'audio/x-pn-realaudio',
        'ram'     => 'audio/x-pn-realaudio',
        'ras'     => 'image/x-cmu-raster',
        'rgb'     => 'image/x-rgb',
        'rmi'     => 'audio/mid',
        'roff'    => 'application/x-troff',
        'rtf'     => 'application/rtf',
        'rtx'     => 'text/richtext',
        'swf'     => 'application/x-shockwave-flash',
        'scd'     => 'application/x-msschedule',
        'sct'     => 'text/scriptlet',
        'setpay'  => 'application/set-payment-initiation',
        'setreg'  => 'application/set-registration-initiation',
        'sh'      => 'application/x-sh',
        'shar'    => 'application/x-shar',
        'sit'     => 'application/x-stuffit',
        'snd'     => 'audio/basic',
        'spc'     => 'application/x-pkcs7-certificates',
        'spl'     => 'application/futuresplash',
        'src'     => 'application/x-wais-source',
        'sst'     => 'application/vnd.ms-pkicertstore',
        'stl'     => 'application/vnd.ms-pkistl',
        'stm'     => 'text/html',
        'svg'     => 'image/svg+xml',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc'  => 'application/x-sv4crc',
        't'       => 'application/x-troff',
        'tar'     => 'application/x-tar',
        'tcl'     => 'application/x-tcl',
        'tex'     => 'application/x-tex',
        'texi'    => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tgz'     => 'application/x-compressed',
        'tif'     => 'image/tiff',
        'tiff'    => 'image/tiff',
        'tr'      => 'application/x-troff',
        'trm'     => 'application/x-msterminal',
        'tsv'     => 'text/tab-separated-values',
        'txt'     => 'text/plain',
        'uls'     => 'text/iuls',
        'ustar'   => 'application/x-ustar',
        'vcf'     => 'text/x-vcard',
        'vrml'    => 'x-world/x-vrml',
        'wav'     => 'audio/x-wav',
        'wcm'     => 'application/vnd.ms-works',
        'wdb'     => 'application/vnd.ms-works',
        'wks'     => 'application/vnd.ms-works',
        'wmf'     => 'application/x-msmetafile',
        'wps'     => 'application/vnd.ms-works',
        'wri'     => 'application/x-mswrite',
        'wrl'     => 'x-world/x-vrml',
        'wrz'     => 'x-world/x-vrml',
        'xaf'     => 'x-world/x-vrml',
        'xbm'     => 'image/x-xbitmap',
        'xla'     => 'application/vnd.ms-excel',
        'xlc'     => 'application/vnd.ms-excel',
        'xlm'     => 'application/vnd.ms-excel',
        'xls'     => 'application/vnd.ms-excel',
        'xlt'     => 'application/vnd.ms-excel',
        'xlw'     => 'application/vnd.ms-excel',
        'xof'     => 'x-world/x-vrml',
        'xpm'     => 'image/x-xpixmap',
        'xwd'     => 'image/x-xwindowdump',
        'z'       => 'application/x-compress',
        'zip'     => 'application/zip',
        '3gpp'    => 'video/3gpp',
        '3gp'     => 'video/3gpp',
        '3gpp2'   => 'video/3gpp2',
        '3g2'     => 'video/3gpp2',
        'midi'    => 'audio/midi',
        'pmd'     => 'application/x-pmd',
        'jar'     => 'application/java-archive',
        'jad'     => 'text/vnd.sun.j2me.app-descriptor',
        'apk'     => 'application/vnd.android.package-archive',
        'mkv'     => 'video/x-matroska' ,
        //add more mime here
    ];

    //return mime
    $ext = strtolower($ext);

    if (in_array($ext, array_keys($mime_types)))
    {
        $return = $mime_types[$ext];
    }
    else
    {
        $return = 'application/force-download';
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('get_mime_for_header_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}


/**
 * Include language file
 * @param  string $name   language filename, 'acp, common..'
 * @param  string $folder
 * @return bool
 */
function get_lang($name, $folder = '')
{
    global $config, $lang;

    if (is_null($lang) || ! is_array($lang))
    {
        $lang = [];
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('get_lang_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $name = str_replace('..', '', $name);

    if ($folder != '')
    {
        $folder = str_replace(['..', '/'], '', $folder);
        $name   = $folder . '/' . $name;
    }

    $path = PATH . 'lang/' . $config['language'] . '/' . str_replace('.php', '', $name) . '.php';

    $lang_to_add = @include_once $path;

    if ($lang_to_add === false)
    {
        //fallback to English
        $path_en     = PATH . 'lang/en/' . str_replace('.php', '', $name) . '.php';
        $lang_to_add = @include_once $path_en;

        if ($lang_to_add === false)
        {
            big_error('There is no language file in the current path', 'lang/' . $config['language'] . '/' . str_replace('.php', '', $name) . '.php  not found');
        }
    }

    if (is_array($lang_to_add))
    {
        $lang = array_merge($lang, $lang_to_add);
    }


    return true;
}


/*
* Get fresh config value
* some time cache doesn't not work as well, so some important
* events need fresh version of config values ...
*/
function get_config($name)
{
    global $dbprefix, $SQL, $d_groups, $userinfo;

    $table = "{$dbprefix}config c";

    //what if this config is a group-configs related ?
    $group_id_sql = '';

    if (array_key_exists($name, $d_groups[$userinfo['group_id']]['configs']))
    {
        $table        = "{$dbprefix}groups_data c";
        $group_id_sql = ' AND c.group_id=' . $userinfo['group_id'];
    }

    $query = [
        'SELECT'	=> 'c.value',
        'FROM'		 => $table,
        'WHERE'		=> "c.name = '" . $SQL->escape($name) . "'" . $group_id_sql
    ];

    $result	= $SQL->build($query);
    $v		    = $SQL->fetch($result);
    $return	= $v['value'];

    is_array($plugin_run_result = Plugins::getInstance()->run('get_config_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}

/*
* Add new config option
* type: where does your config belone, 0 = system, genetal = has no specifc cat., other = other items.
* html: the input or radio to let the user type or choose from them, see the database:configs to understand.
* dynamic: every refresh of the page, the config data will be brought from db, not from the cache !
* plg_id: if this config belong to plugin .. see devKit.
*/
function add_config($name, $value, $order = '0', $html = '', $type = '0', $plg_id = '0', $dynamic = false)
{
    global $dbprefix, $SQL, $config, $d_groups;

    if (get_config($name))
    {
        return true;
    }

    if ($html != '' && $type == '0')
    {
        $type = 'other';
    }

    if ($type == 'groups')
    {
        //add this option to all groups
        $group_ids = array_keys($d_groups);

        foreach ($group_ids as $g_id)
        {
            $insert_query	= [
                'INSERT'	=> '`name`, `value`, `group_id`',
                'INTO'		 => "{$dbprefix}groups_data",
                'VALUES' => "'" . $SQL->escape($name) . "','" . $SQL->escape($value) . "', " . $g_id,
            ];

            is_array($plugin_run_result = Plugins::getInstance()->run('insert_sql_add_config_func_groups_data', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $SQL->build($insert_query);
        }
    }

    $insert_query	= [
        'INSERT'	=> '`name` ,`value` ,`option` ,`display_order`, `type`, `plg_id`, `dynamic`',
        'INTO'		 => "{$dbprefix}config",
        'VALUES'	=> "'" . $SQL->escape($name) . "','" . $SQL->escape($value) . "', '" . $SQL->real_escape($html) . "','" . intval($order) . "','" . $SQL->escape($type) . "','" . intval($plg_id) . "','" . ($dynamic ? '1' : '0') . "'",
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('insert_sql_add_config_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($insert_query);

    if ($SQL->affected())
    {
        delete_cache('data_config');
        $config[$name] = $value;
        return true;
    }

    return false;
}

/**
 * add an array of new configs
 * @param $configs
 * @return bool
 */
function add_config_r($configs)
{
    if (! is_array($configs))
    {
        return false;
    }

    //array(name=>array(value=>,order=>,html=>),...);
    foreach ($configs as $n=>$m)
    {
        add_config(
            $n,
            empty($m['value']) ? '' : $m['value'],
            empty($m['order']) ? 0 : $m['order'],
            empty($m['html']) ? '' : $m['html'],
            empty($m['type']) ? 'other' : $m['type'],
            empty($m['plg_id']) ? 0 : $m['plg_id'],
            empty($m['dynamic']) ? false : $m['dynamic']
        );
    }

    return true;
}

function update_config($name, $value, $escape = true, $group = false)
{
    global $SQL, $dbprefix, $d_groups, $userinfo;

    $value = ($escape) ? $SQL->escape($value) : $value;
    $table = "{$dbprefix}config";

    //what if this config is a group-configs related ?
    $group_id_sql = '';

    if (array_key_exists($name, $d_groups[$userinfo['group_id']]['configs']) && $group != false)
    {
        $table = "{$dbprefix}groups_data";

        if ($group == -1)
        {
            $group_id_sql = ' AND group_id=' . $userinfo['group_id'];
        }
        elseif ($group)
        {
            $group_id_sql = ' AND group_id=' . intval($group);
        }
    }

    $update_query	= [
        'UPDATE'	=> $table,
        'SET'		  => "value='" . ($escape ? $SQL->escape($value) : $value) . "'",
        'WHERE'		=> 'name = "' . $SQL->escape($name) . '"' . $group_id_sql
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('update_sql_update_config_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($update_query);

    if ($SQL->affected())
    {
        if ($table == "{$dbprefix}groups_data")
        {
            $d_groups[$userinfo['group_id']]['configs'][$name] = $value;
            delete_cache('data_groups');
            return true;
        }

        $config[$name] = $value;
        delete_cache('data_config');
        return true;
    }

    return false;
}

// Delete config
function delete_config($name)
{
    if (is_array($name))
    {
        foreach ($name as $n)
        {
            delete_config($n);
        }
    }

    global $dbprefix, $SQL, $d_groups, $userinfo;

    //
    // 'IN' doesnt work here with delete, i dont know why ?
    //
    $delete_query	= [
        'DELETE'	=> "{$dbprefix}config",
        'WHERE'		=> "name  = '" . $SQL->escape($name) . "'"
    ];
    is_array($plugin_run_result = Plugins::getInstance()->run('del_sql_delete_config_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($delete_query);

    if (array_key_exists($name, $d_groups[$userinfo['group_id']]['configs']))
    {
        $delete_query	= [
            'DELETE'	=> "{$dbprefix}groups_data",
            'WHERE'		=> "name  = '" . $SQL->escape($name) . "'"
        ];
        is_array($plugin_run_result = Plugins::getInstance()->run('del_sql_delete_config_func2', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        $SQL->build($delete_query);
    }

    if ($SQL->affected())
    {
        return true;
    }

    return false;
}

//
//update words to lang
//
function update_olang($name, $lang = 'en', $value)
{
    global $SQL, $dbprefix;


    $update_query	= [
        'UPDATE'	=> "{$dbprefix}lang",
        'SET'		  => "trans='" . $SQL->escape($value) . "'",
        'WHERE'		=> 'word = "' . $SQL->escape($name) . '", lang_id = "' . $SQL->escape($lang) . '"'
    ];
    is_array($plugin_run_result = Plugins::getInstance()->run('update_sql_update_olang_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($update_query);

    if ($SQL->affected())
    {
        delete_cache('data_lang' . $lang);
        $olang[$name] = htmlspecialchars($value);
        return true;
    }

    return false;
}

//
//add words to lang
//
function add_olang($words = [], $lang = 'en', $plg_id = '0')
{
    global $dbprefix, $SQL;

    foreach ($words as $w=> $t)
    {
        $insert_query = [
            'INSERT'	=> 'word ,trans ,lang_id, plg_id',
            'INTO'		 => "{$dbprefix}lang",
            'VALUES'	=> "'" . $SQL->escape($w) . "','" . $SQL->real_escape($t) . "', '" . $SQL->escape($lang) . "','" . intval($plg_id) . "'",
        ];
        is_array($plugin_run_result = Plugins::getInstance()->run('insert_sql_add_olang_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        $SQL->build($insert_query);
    }

    delete_cache('data_lang' . $lang);
}

//
//delete words from lang
//
/**
 * @param  string|array $words  language terms to use a in $olang[word] or olang.word
 * @param  string       $lang   langauge of given word
 * @param  string       $plg_id plugin id associated with these words, optional
 * @return bool
 */
function delete_olang($words = '', $lang = 'en', $plg_id = 0)
{
    global $dbprefix, $SQL;

    if (is_array($words))
    {
        foreach ($words as $w)
        {
            delete_olang($w, $lang);
        }

        return true;
    }

    $delete_query	= [
        'DELETE'	=> "{$dbprefix}lang",
        'WHERE'		=> "word = '" . $SQL->escape($words) . "' AND lang_id = '" . $SQL->escape($lang) . "'"
    ];

    if (! empty($plg_id))
    {
        $delete_query['WHERE'] = 'plg_id = ' . intval($plg_id);
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('del_sql_delete_olang_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($delete_query);

    return $SQL->affected();
}


/**
 *  Administrator sometime needs some files and delete other ..
 *  we do that for him .. because he has no time .. :)
 * last_down - $config[del_f_day]
 * @param int $from
 */
function klj_clean_old_files($from = 0)
{
    global $config, $SQL, $stat_last_f_del, $dbprefix;

    $return = false;
    is_array($plugin_run_result = Plugins::getInstance()->run('klj_clean_old_files_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    if ((int) $config['del_f_day'] <= 0 || $return)
    {
        return;
    }

    if (! $stat_last_f_del || empty($stat_last_f_del))
    {
        $stat_last_f_del = time();
    }

    if ((time() - $stat_last_f_del) >= 86400)
    {
        $totaldays	= (time() - ($config['del_f_day']*86400));
        $not_today	= time() - 86400;

        //This feature will work only if id_form is not empty or direct !
        $query = [
            'SELECT'	  => 'f.id, f.last_down, f.name, f.type, f.folder, f.time, f.size, f.id_form',
            'FROM'		   => "{$dbprefix}files f",
            'WHERE'		  => "f.last_down < $totaldays AND f.time < $not_today AND f.id > $from AND f.id_form <> '' AND f.id_form <> 'direct'",
            'ORDER BY'	=> 'f.id ASC',
            'LIMIT'		  => '20',
        ];

        is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_klj_clean_old_files_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        $result	= $SQL->build($query);

        $num_of_files_to_delete = $SQL->num_rows($result);

        if ($num_of_files_to_delete == 0)
        {
            //update $stat_last_f_del !!
            $update_query = [
                'UPDATE'	=> "{$dbprefix}stats",
                'SET'		  => "last_f_del ='" . time() . "'",
            ];

            is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_lstf_del_date_kcof', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $SQL->build($update_query);
            //delete stats cache
            delete_cache('data_stats');
            update_config('klj_clean_files_from', '0');
            $SQL->freeresult($result);
            return;
        }

        $last_id_from = $files_num = $imgs_num = $real_num = $sizes = 0;
        $ids          = [];
        $ex_ids       =  [];
        //$ex_types = explode(',', $config['livexts']);


        is_array($plugin_run_result = Plugins::getInstance()->run('beforewhile_klj_clean_old_files_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


        //phpfalcon plugin
        $exlive_types = explode(',', $config['imagefolderexts']);

        //delete files
        while ($row=$SQL->fetch_array($result))
        {
            $continue = true;
            $real_num++;
            $last_id_from = $row['id'];
            $is_image     = in_array(strtolower(trim($row['type'])), ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;

            /*
            //exceptions
            if(in_array($row['type'], $ex_types) || $config['id_form'] == 'direct')
            {
                $ex_ids[] = $row['id'];
                continue;
            }
            */

            //exceptions
            //if($config['id_form'] == 'direct')
            //{
            //$ex_ids[] = $row['id'];
            //move on
            //continue;
            //}

            //your exepctions
            is_array($plugin_run_result = Plugins::getInstance()->run('while_klj_clean_old_files_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook



            if ($continue)
            {
                //delete from folder ..
                if (file_exists($row['folder'] . '/' . $row['name']))
                {
                    @kleeja_unlink ($row['folder'] . '/' . $row['name']);
                }
                //delete thumb
                if (file_exists($row['folder'] . '/thumbs/' . $row['name'] ))
                {
                    @kleeja_unlink ($row['folder'] . '/thumbs/' . $row['name'] );
                }

                $ids[] = $row['id'];

                if ($is_image)
                {
                    $imgs_num++;
                }
                else
                {
                    $files_num++;
                }
                $sizes += $row['size'];
            }
        }//END WHILE

        $SQL->freeresult($result);

        if (sizeof($ex_ids))
        {
            $update_query	= [
                'UPDATE'	=> "{$dbprefix}files",
                'SET'		  => "last_down = '" . (time() + 2*86400) . "'",
                'WHERE'		=> 'id IN (' . implode(',', $ex_ids) . ')'
            ];
            is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_lstdown_old_files', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            $SQL->build($update_query);
        }

        if (sizeof($ids))
        {
            $query_del	= [
                'DELETE'	=> "{$dbprefix}files",
                'WHERE'	 => 'id IN (' . implode(',', $ids) . ')'
            ];

            //update number of stats
            $update_query	= [
                'UPDATE'	=> "{$dbprefix}stats",
                'SET'		  => "sizes=sizes-$sizes,files=files-$files_num, imgs=imgs-$imgs_num",
            ];

            is_array($plugin_run_result = Plugins::getInstance()->run('qr_del_delf_old_files', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $SQL->build($query_del);
            $SQL->build($update_query);
        }

        update_config('klj_clean_files_from', $last_id_from);
    } //stat_del
}

/**
 * klj_clean_old
 * @param string         $table database table
 * @param string|integer $for   can be 'all, or a number of days like 30'
 */
function klj_clean_old($table, $for = 'all')
{
    global $SQL, $config, $dbprefix;

    $days = time() - (3600 * 24 * intval($for));

    $query = [
        'SELECT'	  => 'f.id, f.time',
        'DELETE'		 => "`{$dbprefix}" . $table . '` f',
        'ORDER BY'	=> 'f.id ASC',
        'LIMIT'		  => '30',
    ];

    if ($for != 'all')
    {
        $query['WHERE']	= "f.time < $days";
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_klj_clean_old_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $result	       = $SQL->build($query);
    $num_to_delete = $SQL->num_rows($result);

    if ($num_to_delete == 0)
    {
        $t = $table == 'call' ? 'calls' : $table;
        update_config('queue', preg_match('/:del_' . $for . $t . ':/i', '', $config['queue']));
        $SQL->freeresult($result);
        return;
    }

    $ids = [];
    while ($row=$SQL->fetch_array($result))
    {
        $ids[] = $row['id'];
    }

    $SQL->freeresult($result);

    $query_del	= [
        'DELETE'	=> '`' . $dbprefix . $table . '`',
        'WHERE'	 => 'id IN (' . implode(',', $ids) . ')'
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_del_delf_old_table', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($query_del);
}

/**
* get_ip() for the user
*/
function get_ip()
{
    $ip = '';

    if (! empty($_SERVER['HTTP_CF_CONNECTING_IP']))
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    elseif (! empty($_SERVER['REMOTE_ADDR']))
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    //if IP chain
    if (strpos($ip, ',') !== false)
    {
        $ip = explode(',', $ip);
        $ip = trim($ip[0]);
    }

    //is it IPv6?
    $ip_v6 = preg_match('/^[0-9a-f]{1,4}:([0-9a-f]{0,4}:){1,6}[0-9a-f]{1,4}$/', $ip);

    if ($ip_v6)
    {
        //does it IPv4 hide in a IPv6 style
        if (stripos($ip, '::ffff:') === 0)
        {
            $ip = substr($ip, 7);
        }
    }


    $return = preg_replace('/[^0-9a-z.:]/i', '', $ip);
    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_get_ip_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}


/**
 * Check and verify captcha field after submit
 * @return bool
 */
function kleeja_check_captcha()
{
    global $config;

    if ((int) $config['enable_captcha'] == 0 && ! defined('IN_REAL_INDEX') && ! defined('IN_ADMIN'))
    {
        return true;
    }


    $return = false;

    if (! empty($_SESSION['klj_sec_code']) && ip('kleeja_code_answer'))
    {
        if ($_SESSION['klj_sec_code'] == trim(p('kleeja_code_answer')))
        {
            unset($_SESSION['klj_sec_code']);
            $return = true;
        }
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_check_captcha_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}


/**
 * For logging and testing, enabled only for DEV_STAGE!
 * @param string $text a string to log
 */
function kleeja_log($text)
{
    if (! defined('DEV_STAGE'))
    {
        return;
    }

    $log_file = PATH . 'cache/kleeja_log.log';
    $l_c      = @file_get_contents($log_file);
    $fp       = @fopen($log_file, 'w');
    @fwrite($fp, $text . ' [time : ' . date('H:i a, d-m-Y') . "] \r\n" . $l_c);
    @fclose($fp);
}


/**
 * Return the first and last seek of range to be flushed.
 * @param string $range
 * @param $fileSize
 * @return array
 */
function kleeja_set_range($range, $fileSize)
{
    $dash	 = strpos($range, '-');
    $first	= trim(substr($range, 0, $dash));
    $last	 = trim(substr($range, $dash+1));

    if (! $first)
    {
        $suffix	= $last;
        $last   = $fileSize - 1;
        $first  = $fileSize - $suffix;

        if ($first < 0)
        {
            $first = 0;
        }
    }
    else
    {
        if (! $last || $last > $fileSize - 1)
        {
            $last = $fileSize - 1;
        }
    }

    if ($first > $last)
    {
        //unsatisfiable range
        header('Status: 416 Requested range not satisfiable');
        header("Content-Range: */$fileSize");

        exit;
    }

    return [$first, $last];
}

/**
 * Outputs up to $bytes from the file $file to standard output,
 * $buffer_size bytes at a time.
 * @param resource $file
 * @param integer  $bytes
 * @param integer  $buffer_size
 */
function kleeja_buffered_range($file, $bytes, $buffer_size = 1024)
{
    $bytes_left = $bytes;
    while ($bytes_left > 0 && ! feof($file))
    {
        if ($bytes_left > $buffer_size)
        {
            $bytes_to_read = $buffer_size;
        }
        else
        {
            $bytes_to_read = $bytes_left;
        }

        $bytes_left	-= $bytes_to_read;
        $contents	= fread($file, $bytes_to_read);
        echo $contents;
        @flush();
        @ob_flush();
    }
}

/**
 * user_can, used for checking the acl for the current user
 * @param  string $acl_name
 * @param  int    $group_id
 * @return bool
 */
function user_can($acl_name, $group_id = 0)
{
    global $d_groups, $userinfo;

    if ($group_id == 0)
    {
        $group_id = $userinfo['group_id'];
    }

    return (bool) $d_groups[$group_id]['acls'][$acl_name];
}


function ig($name)
{
    return isset($_GET[$name]) ? true : false;
}

function ip($name)
{
    return isset($_POST[$name]) ? true : false;
}

function g($name, $type = 'str', $default = '')
{
    if (isset($_GET[$name]))
    {
        return $type == 'str' ? htmlspecialchars($_GET[$name]) : intval($_GET[$name]);
    }

    return $type == 'str' ? htmlspecialchars($default) : intval($default);
}

function p($name, $type = 'str', $default = '')
{
    if (isset($_POST[$name]))
    {
        return $type == 'str'
            ? str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], htmlspecialchars(trim($_POST[$name])))
            : intval($_POST[$name]);
    }


    return $type == 'str' ? htmlspecialchars($default) : intval($default);
}

/**
 * add rewrite rules to the serve.php file
 * @param  array|string $rules
 * @param  string       $unique_id useful for the deletion later
 * @return bool
 */
function add_to_serve_rules($rules, $unique_id = '')
{
    $current_serve_content = file_get_contents(PATH . 'serve.php');

    $rules = is_array($rules) ? implode(PHP_EOL, $rules) : $rules;

    if (! empty($unique_id))
    {
        $rules = '#start_' . $unique_id . PHP_EOL . $rules . PHP_EOL . '#end_' . $unique_id;
    }

    if (strpos($current_serve_content, '#end_kleeja_rewrites_rules#') !== false)
    {
        $current_serve_content = str_replace(
                                '#end_kleeja_rewrites_rules#',
                                '#end_kleeja_rewrites_rules#' . PHP_EOL . $rules,
                                $current_serve_content
                        );
    }
    else
    {
        $current_serve_content = preg_replace(
                            '/\$rules\s{0,4}=\s{0,4}array\(/',
                            '$rules = array(' . PHP_EOL . $rules,
                            $current_serve_content
                        );
    }

    file_put_contents(PATH . 'serve.php', $current_serve_content);

    return true;
}


/**
 * remove rewrite rules by previously set unique id
 * @param  string $unique_id
 * @return bool
 */
function remove_from_serve_rules($unique_id)
{
    $file = PATH . 'serve.php';

    $current_serve_content = file_get_contents($file);

    $new_serve_content = preg_replace(
        '/^#start_' . preg_quote($unique_id) . '.*' . '#end_' . preg_quote($unique_id) . '$/sm',
        '',
        $current_serve_content
        );

    if ($new_serve_content === $current_serve_content)
    {
        return false;
    }

    file_put_contents($file, $new_serve_content);

    return true;
}

/**
 * parse rewrite rule. currently added separately for plugins
 * @param  string $regex
 * @param  array  $args
 * @param  bool   $is_unicode
 * @return bool
 */
function parse_serve_rule($regex, $args, $is_unicode = false)
{
    $request_uri = urldecode(
        trim(strtok($_SERVER['REQUEST_URI'], '?'), '/')
    );


    if (preg_match("/{$regex}/" . ($is_unicode ? 'u' : ''), $request_uri, $matches))
    {
        if (! empty($args))
        {
            parse_str($args, $parsed_args);

            foreach ($parsed_args as $arg_key => $arg_value)
            {
                if ( preg_match('/^\$/', $arg_value))
                {
                    $match_number = ltrim($arg_value, '$');

                    if ( isset($matches[$match_number]))
                    {
                        $_GET[$arg_key] = $matches[$match_number];
                    }
                }
                else
                {
                    $_GET[$arg_key] = $arg_value;
                }
            }
        }

        return true;
    }

    return false;
}
