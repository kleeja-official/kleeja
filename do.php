<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/



/**
 * @ignore
 */
define('IN_KLEEJA', true);
define('IN_DOWNLOAD', true);
require_once 'includes/common.php';



is_array($plugin_run_result = Plugins::getInstance()->run('begin_download_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook




//
//page of wait downloading files
//
if (ig('id') || ig('filename'))
{
    is_array($plugin_run_result = Plugins::getInstance()->run('begin_download_id_filename', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $query = [
        'SELECT'       => 'f.id, f.real_filename, f.name, f.folder, f.size, f.time, f.uploads, f.type',
        'FROM'         => "{$dbprefix}files f",
        'LIMIT'        => '1',
    ];

    //if user system is default, we use users table
    if ((int) $config['user_system'] == 1)
    {
        $query['SELECT'] .= ', u.name AS fusername, u.id AS fuserid';
        $query['JOINS']    =    [
            [
                'LEFT JOIN'       => "{$dbprefix}users u",
                'ON'              => 'u.id=f.user'
            ]
        ];
    }

    if (ig('filename'))
    {
        $filename_l  = (string) $SQL->escape(g('filename'));

        if (ig('x'))
        {
            $query['WHERE']    = "f.name='" . $filename_l . '.' . $SQL->escape(g('x')) . "'";
        }
        else
        {
            $query['WHERE']    = "f.name='" . $filename_l . "'";
        }
    }
    else
    {
        $id_l              = g('id', 'int');
        $query['WHERE']    = 'f.id=' . $id_l;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_download_id_filename', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    $result    = $SQL->build($query);

    if ($SQL->num_rows($result) != 0)
    {
        $file_info = $SQL->fetch_array($result);

        $SQL->freeresult($result);

        // some vars
        $id            = $file_info['id'];
        $name          = $fname         = $file_info['name'];
        $real_filename = $file_info['real_filename'];
        $type          = $file_info['type'];
        $size          = $file_info['size'];
        $time          = $file_info['time'];
        $uploads       = $file_info['uploads'];


        $fname2           = str_replace('.', '-', htmlspecialchars($name));
        $name             = $real_filename != '' ? str_replace('.' . $type, '', htmlspecialchars($real_filename)) : $name;
        $name             = strlen($name)                                        > 70 ? substr($name, 0, 70) . '...' : $name;
        $fusername        = $config['user_system'] == 1 && $file_info['fuserid'] > -1 ? $file_info['fusername'] : false;
        $userfolder       = $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $file_info['fuserid'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $file_info['fuserid']);

        if (ig('filename'))
        {
            $url_file    = $config['mod_writer'] ? $config['siteurl'] . 'downf-' . $fname2 . '.html' : $config['siteurl'] . 'do.php?downf=' . $fname;
        }
        else
        {
            $url_file    = $config['mod_writer'] ? $config['siteurl'] . 'down-' . $file_info['id'] . '.html' : $config['siteurl'] . 'do.php?down=' . $file_info['id'];
        }

        if (! empty($config['livexts']))
        {
            $livexts = explode(',', $config['livexts']);

            if (in_array($type, $livexts))
            {
                if (ig('filename'))
                {
                    $url_filex    = $config['mod_writer'] ? $config['siteurl'] . 'downexf-' . $fname2 . '.html' : $config['siteurl'] . 'do.php?downexf=' . $fname;
                }
                else
                {
                    $url_filex    = $config['mod_writer'] ? $config['siteurl'] . 'downex-' . $file_info['id'] . '.html' : $config['siteurl'] . 'do.php?downex=' . $file_info['id'];
                }

                redirect($url_filex, false);
            }
        }

        $REPORT          = ($config['mod_writer']) ?  $config['siteurl'] . 'report-' . $file_info['id'] . '.html' :  $config['siteurl'] . 'go.php?go=report&amp;id=' . $file_info['id'];
        $seconds_w       = user_can('enter_acp') ? 0 : $config['sec_down'];
        $time            = kleeja_date($time);
        $size            = readable_size($size);

        $file_ext_icon       = file_exists('images/filetypes/' . $type . '.png') ? 'images/filetypes/' . $type . '.png' : 'images/filetypes/file.png';
        $sty                 = 'download';
        $title               =  $name . ' - ' . $lang['DOWNLAOD'];
    }
    else
    {
        //file not exists
        is_array($plugin_run_result = Plugins::getInstance()->run('not_exists_qr_downlaod_file', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        kleeja_err($lang['FILE_NO_FOUNDED']);
    }

    $show_style = true;

    is_array($plugin_run_result = Plugins::getInstance()->run('b4_showsty_downlaod_id_filename', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //add http reffer to session to prevent errors with some browsers !
    $_SESSION['HTTP_REFERER'] =  $file_info['id'];

    // show style
    if ($show_style)
    {
        Saaheader($title);
        echo $tpl->display($sty);
        Saafooter();
    }
}




//
//download file
//
// guidelines for _get variable names
//
// down: [0-9], default, came from do.php?id=[0-9]
// downf: [a-z0-9].[ext], came from do.php?filename=[a-z0-9].[ext]
//
// img: [0-9], default, direct from do.php?img=[0-9]
// imgf: [a-z0-9].[ext], direct from do.php?imgf=[a-z0-9].[ext]
//
// thmb: [0-9], default, direct from do.php?thmb=[0-9]
// thmbf: [a-z0-9].[ext], direct from do.php?thmbf=[a-z0-9].[ext]
//
// live extensions feature uses downex, downexf as in down & downf
//
// x : used only for html links, where x = extension, downf is filename without extension

elseif (ig('down') || ig('downf') ||
        ig('img') || ig('imgf') ||
        ig('thmb') || ig('thmbf') ||
    ig('downex') || ig('downexf'))
{
    is_array($plugin_run_result = Plugins::getInstance()->run('begin_down_go_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    //kleeja_log('downloading file start -  (' . var_dump($_GET) . ') -> ' . $_SERVER['HTTP_REFERER']);

    //must know from where he came ! and stop him if not image
    //todo: if it's download manger, let's pass this
    if (ig('down') || ig('downf'))
    {
        //if not from our site and the waiting page
        $not_reffer = true;


        $isset_down_h = ig('downf') && ig('x') ? 'downloadf-' . g('downf') . '-' . g('x') . '.html' : (ig('down') ? 'download' . g('down') . '.html' : '');

        if (! empty($_SERVER['HTTP_REFERER'])
            && strpos($_SERVER['HTTP_REFERER'], $isset_down_h) !== false)
        {
            $not_reffer = false;
        }

        $isset_down = ig('downf') ? 'do.php?filename=' . g('downf') : (ig('down') ? 'do.php?id=' . g('down') : '');

        if (! empty($_SERVER['HTTP_REFERER'])
            && strpos($_SERVER['HTTP_REFERER'], $isset_down) !== false)
        {
            $not_reffer = false;
        }

        if (! empty($_SERVER['HTTP_REFERER'])
            && strpos($config['siteurl'], str_replace(['http://', 'www.', 'https://'], '', htmlspecialchars($_SERVER['HTTP_REFERER']))))
        {
            $not_reffer = false;
        }

        if (isset($_SERVER['HTTP_RANGE']))
        {
            $not_reffer = false;
        }

        if (isset($_SESSION['HTTP_REFERER']))
        {
            $not_reffer = false;

            unset($_SESSION['HTTP_REFERER']);
        }



        if ($not_reffer)
        {
            if (ig('downf'))
            {
                $go_to = $config['siteurl'] . ($config['mod_writer'] && ig('x') ? 'downloadf-' . g('downf') . '-' . g('x') . '.html' : 'do.php?filename=' . g('downf'));
            }
            else
            {
                $go_to = $config['siteurl'] . ($config['mod_writer'] ? 'download' . g('down') . '.html' : 'do.php?id=' . g('down'));
            }

            redirect($go_to);
            $SQL->close();

            exit;
        }
    }

    //download by id or filename
    //is the requested variable is filename(filename123.gif) or id (123) ?
    $is_id_filename = ig('downf') || ig('imgf') || ig('thmbf') || ig('downexf') ? true : false;


    $filename = $id = null;


    if ($is_id_filename)
    {
        $var = ig('downf') ? 'downf' : (ig('imgf') ? 'imgf' : (ig('thmbf') ? 'thmbf' : (ig('downexf') ? 'downexf' : false)));

        //x, represent the extension, came from html links
        if (ig('x') && $var)
        {
            $filename = $SQL->escape(g($var)) . '.' . $SQL->escape(g('x'));
        }
        else
        {
            $filename = $SQL->escape(g($var));
        }
    }
    else
    {
        $id = ig('down') ? g('down', 'int') : (ig('img') ? g('img', 'int') : (ig('thmb') ? g('thmb', 'int') : (ig('downex') ? g('downex', 'int') : null)));
    }

    //is internet explore 8 ?
    $is_ie8 = is_browser('ie8');
    //is internet explore 6 ?
    // $is_ie6 = is_browser('ie6');

    $livexts = explode(',', $config['livexts']);

    //get info file
    $query = ['SELECT' => 'f.id, f.name, f.real_filename, f.folder, f.type, f.size, f.time',
        'FROM'         => "{$dbprefix}files f",
        'WHERE'        => $is_id_filename ? "f.name='" . $filename . "'" . (ig('downexf') ? " AND f.type IN ('" . implode("', '", $livexts) . "')" : '') :
            'f.id=' . $id . (ig('downex') ? " AND f.type IN ('" . implode("', '", $livexts) . "')" : ''),
        'LIMIT' => '1'
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_down_go_page_filename', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    $result = $SQL->build($query);

    $is_live = false;
    $pre_ext = ! empty($filename) && strpos($filename, '.') !== false ? explode('.', $filename) : [];
    $pre_ext = array_pop($pre_ext);


    $is_image = in_array(strtolower(trim($pre_ext)), ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;

    //initiate variables
    $ii = $n = $rn = $t = $f = $ftime = $d_size = null;


    if ($SQL->num_rows($result))
    {
        $row = $SQL->fetch($result);

        $ii     = $row['id'];
        $n      = $row['name'];
        $rn     = $row['real_filename'];
        $t      = strtolower(trim($row['type']));
        $f      = $row['folder'];
        $ftime  = $row['time'];
        $d_size = $row['size'];


        //img or not
        $is_image = in_array($t, ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;
        //live url
        $is_live = in_array($t, $livexts) ? true : false;


        $SQL->freeresult($result);

        //fix bug where a user can override files wait counter
        if (! $is_image && (ig('img') || ig('thmb')))
        {
            $go_to = $config['siteurl'] . ($config['mod_writer'] ? 'download' . $ii . '.html' : 'do.php?id=' . $ii);
            redirect($go_to);
        }


        //check if the vistor is new in this page before updating kleeja counter
        if (! preg_match('/,' . $ii . ',/i', $usrcp->kleeja_get_cookie('oldvistor')) && ! isset($_SERVER['HTTP_RANGE']))
        {
            if ($usrcp->group_id() != 1)
            {
                //updates number of uploads ..
                $update_query = [
                    'UPDATE' => "{$dbprefix}files",
                    'SET'    => 'uploads=uploads+1, last_down=' . time(),
                    'WHERE'  => $is_id_filename ? "name='" . $filename . "'" : 'id=' . $id,
                ];

                is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_no_uploads_down', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
                $SQL->build($update_query);

                //
                //Define as old vistor
                //if this vistor has other views then add this view too
                //old vistor just for 1 day
                //
                if ($usrcp->kleeja_get_cookie('oldvistor'))
                {
                    $usrcp->kleeja_set_cookie('oldvistor', $usrcp->kleeja_get_cookie('oldvistor') . $ii . ',', time() + 86400);
                }
                else
                {
                    //first time
                    $usrcp->kleeja_set_cookie('oldvistor', ',' . $ii . ',', time() + 86400);
                }
            }
        }
    }
    else
    {
        //not exists img or thumb
        if (ig('img') || ig('thmb') || ig('thmbf') || ig('imgf'))
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('not_exists_qr_down_img', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $f = 'images';
            $n = 'not_exists.jpg';

            //set image condition on
            $is_image = true;
        }
        else
        {
            //not exists file
            is_array($plugin_run_result = Plugins::getInstance()->run('not_exists_qr_down_file', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            kleeja_err($lang['FILE_NO_FOUNDED']);
        }
    }

    //download process
    $path_file   = ig('thmb') || ig('thmbf') ? "./{$f}/thumbs/{$n}" : "./{$f}/{$n}";
    $chunksize   = 8192;
    $resuming_on = true;

    is_array($plugin_run_result = Plugins::getInstance()->run('down_go_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    // this is a solution to ignore downloading through the file, redirect to the actual file
    // where you can add 'define("MAKE_DOPHP_301_HEADER", true);' in config.php to stop the load
    // if there is any.ead
    if (defined('MAKE_DOPHP_301_HEADER'))
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $path_file);
        $SQL->close();

        exit;
    }

    //start download ,,
    if (! is_readable($path_file))
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('down_file_not_exists', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        if ($is_image)
        {
            $path_file = 'images/not_exists.jpg';
        }
        else
        {
            big_error($lang['FILE_NO_FOUNDED'], $lang['NOT_FOUND']);
        }
    }

    if (! ($size = @filesize($path_file)))
    {
        $size = $d_size;
    }

    $name = empty($rn) ? $n : $rn;

    $dots_in_name = substr_count($name, '.') - 1;

    if ($dots_in_name > 0)
    {
        $name      =  preg_replace('/\./', '_', $name, $dots_in_name);
    }

    if (is_browser('mozilla'))
    {
        $h_name = "filename*=UTF-8''" . rawurlencode(htmlspecialchars_decode($name));
    }
    elseif (is_browser('opera, safari, konqueror'))
    {
        $h_name = 'filename="' . str_replace('"', '', htmlspecialchars_decode($name)) . '"';
    }
    else
    {
        $h_name = 'filename="' . rawurlencode(htmlspecialchars_decode($name)) . '"';
    }

    //Figure out the MIME type (if not specified)
    $ext = explode('.', $path_file);
    $ext = array_pop($ext);

    $mime_type = get_mime_for_header($ext);


    //disable execution time limit
    @set_time_limit(0);

    //disable output buffering
    //TODO check effectiveness
    $level = ob_get_level();
    while ($level > 0)
    {
        ob_end_clean();
        $level--;
    }

    if (! is_null($SQL))
    {
        $SQL->close();
    }

    session_write_close();


    // required for IE, otherwise Content-Disposition may be ignored
    if (@ini_get('zlib.output_compression'))
    {
        @ini_set('zlib.output_compression', 'Off');
    }


    //open the file
    if (($fp = @fopen($path_file, 'rb')) === false)
    {
        //so ... it's failed to open !
        header('HTTP/1.0 404 Not Found');
        @fclose($fp);
        big_error($lang['FILE_NO_FOUNDED'], $lang['NOT_FOUND']);
    }

    //Unsetting all previously set headers.
    header_remove();

    is_array($plugin_run_result = Plugins::getInstance()->run('do_page_before_headers_set', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    //send file headers
    header('Pragma: public');
    header('Accept-Ranges: bytes');
    header('Content-Description: File Transfer');

    //dirty fix
    if ($ext != 'apk')
    {
        header("Content-Type: $mime_type");
    }
    header('Date: ' . gmdate('D, d M Y H:i:s', empty($ftime) ? time() : $ftime) . ' GMT');
    //header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $ftime) . ' GMT');
    //header('Content-Encoding: none');
    header('Content-Disposition: ' . ($is_image || $is_live ? 'inline' : 'attachment') . '; ' . $h_name);


    is_array($plugin_run_result = Plugins::getInstance()->run('do_page_headers_set', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    //if(!$is_image && !$is_live && $is_ie8)
    //{
    //    header('X-Download-Options: noopen');
    //}

    //add multipart download and resume support
    if (isset($_SERVER['HTTP_RANGE']) && $resuming_on)
    {
        list($a, $range)         = explode('=', $_SERVER['HTTP_RANGE'], 2);
        list($range)             = explode(',', $range, 2);
        list($range, $range_end) = explode('=', $range);
        $range                   = round(floatval($range), 0);
        $range_end               = ! $range_end ? $size - 1 : round(floatval($range_end), 0);

        $partial_length = $range_end - $range + 1;
        header('HTTP/1.1 206 Partial Content');
        header("Content-Length: $partial_length");
        header('Content-Range: bytes ' . ($range - $range_end / $size));

        fseek($fp, $range);
    }
    else
    {
        header('HTTP/1.1 200 OK');
        $partial_length = $size;
        header("Content-Length: $partial_length");
    }

    //output file
    $bytes_sent = 0;

    //read and output the file in chunks
    while (! feof($fp) && (! connection_aborted()) && ($bytes_sent < $partial_length))
    {
        $buffer = fread($fp, $chunksize);
        print($buffer);
        flush();
        $bytes_sent += strlen($buffer);

        if (defined('TrottleLimit'))
        {
            usleep(1000000 * 0.3);
        }
    }

    fclose($fp);


    if (function_exists('fastcgi_finish_request'))
    {
        fastcgi_finish_request();
    }

    exit;
}

//
//no one of above are there, you can use this hook to get more actions here
//
else
{
    $error = true;

    is_array($plugin_run_result = Plugins::getInstance()->run('err_navig_download_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    if ($error)
    {
        kleeja_err($lang['ERROR_NAVIGATATION']);
    }
}

is_array($plugin_run_result = Plugins::getInstance()->run('end_download_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


//<-- EOF
