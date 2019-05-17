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
 * print Kleeja header
 * @param string $title
 * @param string $extra append html code to head tag
 */
function Saaheader($title = '', $extra = '')
{
    global $tpl, $usrcp, $lang, $olang, $user_is, $username, $config;
    global $extras, $script_encoding, $errorpage, $userinfo, $charset;
    global $STYLE_PATH;

    //is user ? and username
    $user_is  = $usrcp->name() ? true : false;
    $username = $usrcp->name() ? $usrcp->name() : $lang['GUST'];

    //our default charset
    $charset = 'utf-8';

    $side_menu = [
        1 => ['name' => 'profile', 'title' => $lang['PROFILE'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'profile.html' : 'ucp.php?go=profile'), 'show' => $user_is],
        2 => ['name' => 'fileuser', 'title' => $lang['YOUR_FILEUSER'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'fileuser.html' : 'ucp.php?go=fileuser'), 'show' => $config['enable_userfile'] && user_can('access_fileuser')],
        3 => $user_is
            ? ['name' => 'logout', 'title' => $lang['LOGOUT'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'logout.html' : 'ucp.php?go=logout'), 'show' => true]
            : ['name' => 'login', 'title' => $lang['LOGIN'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'login.html' : 'ucp.php?go=login'), 'show' => true],
        4 => ['name' => 'register', 'title' => $lang['REGISTER'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'register.html' : 'ucp.php?go=register'), 'show' => ! $user_is && $config['register']],
    ];

    $top_menu = [
        1 => ['name' => 'index', 'title' => $lang['INDEX'], 'url' => $config['siteurl'], 'show' => true],
        2 => ['name' => 'rules', 'title' => $lang['RULES'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'rules.html' : 'go.php?go=rules'), 'show' => true],
        3 => ['name' => 'guide', 'title' => $lang['GUIDE'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'guide.html' : 'go.php?go=guide'), 'show' => true],
        4 => ['name' => 'stats', 'title' => $lang['STATS'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'stats.html' : 'go.php?go=stats'), 'show' => $config['allow_stat_pg'] && user_can('access_stats')],
        5 => ['name' => 'report', 'title' => $lang['REPORT'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'report.html' : 'go.php?go=report'), 'show' => user_can('access_report')],
        6 => ['name' => 'call', 'title' => $lang['CALL'], 'url' => $config['siteurl'] . ($config['mod_writer'] ? 'call.html' : 'go.php?go=call'), 'show' => user_can('access_call')],
    ];

    //check for extra header
    $extras['header'] = empty($extras['header']) ? false : $extras['header'];

    is_array($plugin_run_result = Plugins::getInstance()->run('Saaheader_links_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    //assign some variables
    $tpl->assign('dir', $lang['DIR']);
    $tpl->assign('title', $title);
    $tpl->assign('side_menu', $side_menu);
    $tpl->assign('top_menu', $top_menu);
    $tpl->assign('go_current', g('go', 'str', 'index'));
    $tpl->assign('go_back_browser', $lang['GO_BACK_BROWSER']);
    $tpl->assign('H_FORM_KEYS_LOGIN', kleeja_add_form_key('login'));
    $tpl->assign('action_login', $config['siteurl'] . 'ucp.php?go=login' . (ig('return') ? '&amp;return=' . g('return') : ''));
    $tpl->assign('EXTRA_CODE_META', $extra);
    $default_avatar = $STYLE_PATH . 'images/user_avater.png';

    if ($user_is)
    {
        $tpl->assign('user_avatar', 'https://www.gravatar.com/avatar/' .
            md5(strtolower(trim($userinfo['mail']))) . '?s=100&amp;d=' . urlencode($default_avatar));
    }
    else
    {
        $tpl->assign('user_avatar', $default_avatar);
    }


    $tpl->assign('is_embedded', ig('embedded'));

    $header = $tpl->display('header');


    if ($config['siteclose'] == '1' && user_can('enter_acp') && ! defined('IN_ADMIN'))
    {
        //add notification bar
        $header = preg_replace('/<body([^\>]*)>/i', "<body\\1>\n<!-- site is closed -->\n<p style=\"z-index:999;width: 100%; text-align:center; background:#FFFFA6; color:black; border:thin;top:0;left:0; position:absolute; clear:both;\">" . $lang['NOTICECLOSED'] . "</p>\n<!-- #site is closed -->", $header);
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('Saaheader_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    header('Content-type: text/html; charset=UTF-8');
    header('Cache-Control: private, no-cache="set-cookie"');
    header('Pragma: no-cache');
    header('x-frame-options: SAMEORIGIN');
    header('x-xss-protection: 1; mode=block');

    echo $header;
    flush();
}


/**
* print kleeja footer
*/
function Saafooter()
{
    global $tpl, $SQL, $starttm, $config, $usrcp, $lang, $olang;
    global $do_gzip_compress, $script_encoding, $errorpage, $extras, $userinfo;

    //show stats ..
    $page_stats = '';

    if ($config['statfooter'] != 0 || defined('DEV_STAGE'))
    {
        $gzip             = $config['gzip'] == '1' ?  'Enabled' : 'Disabled';
        $hksys            = ! defined('STOP_PLUGINS') ? 'Enabled' : 'Disabled';
        $endtime          = get_microtime();
        $loadtime         = number_format($endtime - $starttm, 4);
        $queries_num      = $SQL->query_num;
        $time_sql         = round($SQL->query_num / $loadtime);
        $page_url         = preg_replace(['/([\&\?]+)debug/i', '/&amp;/i'], ['', '&'], kleeja_get_page());
        $link_dbg         = user_can('enter_acp') && defined('DEV_STAGE') ? '[ <a href="' . str_replace('&', '&amp;', $page_url) . (strpos($page_url, '?') === false ? '?' : '&amp;') . 'debug">Debug Info ... </a> ]' : '';
        $page_stats       = "<strong>[</strong> GZIP : $gzip - Generation Time: $loadtime Sec  - Queries: $queries_num - Hook System:  $hksys <strong>]</strong>  " . $link_dbg;
    }

    $tpl->assign('page_stats', $page_stats);

    //if admin, show admin in the bottom of all page
    $tpl->assign('admin_page', (user_can('enter_acp') ? '<a href="' . ADMIN_PATH . '" class="admin_cp_link"><span>' . $lang['ADMINCP'] . '</span></a>' : ''));

    //assign cron
    $tpl->assign('run_queue', '<img src="' . $config['siteurl'] . 'go.php?go=queue" width="1" height="1" alt="queue" />');


    // if google analytics, new version
    //http://www.google.com/support/googleanalytics/bin/answer.py?answer=55488&topic=11126
    $googleanalytics = '';

    if (strlen($config['googleanalytics']) > 4)
    {
        $googleanalytics .= '<script type="text/javascript">' . "\n";
        $googleanalytics .= '<!--' . "\n";
        $googleanalytics .= 'var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");' . "\n";
        $googleanalytics .= 'document.write("\<script src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'>\<\/script>" );' . "\n";
        $googleanalytics .= '-->' . "\n";
        $googleanalytics .= '</script>' . "\n";
        $googleanalytics .= '<script type="text/javascript">' . "\n";
        $googleanalytics .= '<!--' . "\n";
        $googleanalytics .= 'var pageTracker = _gat._getTracker("' . $config['googleanalytics'] . '");' . "\n";
        $googleanalytics .= 'pageTracker._initData();' . "\n";
        $googleanalytics .= 'pageTracker._trackPageview();' . "\n";
        $googleanalytics .= '-->' . "\n";
        $googleanalytics .= '</script>' . "\n";
    }

    $tpl->assign('googleanalytics', $googleanalytics);

    $extras['footer'] = empty($extras['footer']) ? false : $extras['footer'];

    is_array($plugin_run_result = Plugins::getInstance()->run('Saafooter_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $footer = $tpl->display('footer');


    is_array($plugin_run_result = Plugins::getInstance()->run('print_Saafooter_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    echo $footer;

    //page analysis
    if (ig('debug') && user_can('enter_acp'))
    {
        kleeja_debug();
    }

    //at end, close sql connections
    $SQL->close();
}

/**
 * return file size in a readable format
 * @param  int    $size in bytes
 * @return string
 */
function readable_size($size)
{
    $sizes = [' B', ' KB', ' MB', ' GB', ' TB', 'PB', ' EB'];
    $ext   = $sizes[0];

    for ($i=1; (($i < count($sizes)) && ($size >= 1024)); $i++)
    {
        $size = $size / 1024;
        $ext  = $sizes[$i];
    }
    $result    =     round($size, 2) . $ext;
    is_array($plugin_run_result = Plugins::getInstance()->run('func_readable_size', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return  $result;
}

/**
 * show an error message
 *
 * @param $message
 * @param string      $title
 * @param bool        $exit
 * @param bool|string $redirect          a link to redirect after showing the message, or false
 * @param int         $rs                delay in seconds if redirect parameter is set
 * @param string      $extra_code_header to append a code to head tag
 * @param string      $style             is err or info, set by default, no need to fill
 */
function kleeja_err($message, $title = '', $exit = true, $redirect = false, $rs = 2, $extra_code_header = '', $style = 'err')
{
    global $text, $tpl, $SQL;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_err_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    // assign {text} in err template
    $text    = $message . ($redirect ? redirect($redirect, false, $exit, $rs, true) : '');
    //header
    Saaheader($title, $extra_code_header);
    //show tpl
    echo $tpl->display($style);
    //footer
    Saafooter();

    if ($exit)
    {
        $SQL->close();

        exit();
    }
}


/**
 * show an information message
 *
 * @param $message
 * @param string      $title
 * @param bool        $exit
 * @param bool|string $redirect          a link to redirect after showing the message, or false
 * @param int         $rs                delay in seconds if redirect parameter is set
 * @param string      $extra_code_header to append a code to head tag
 */
function kleeja_info($message, $title='', $exit = true, $redirect = false, $rs = 5, $extra_code_header = '')
{
    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_info_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    kleeja_err($message, $title, $exit, $redirect, $rs, $extra_code_header, 'info');
}


/**
* Show debug information
*/
function kleeja_debug()
{
    global $SQL,$do_gzip_compress, $all_plg_hooks;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_debug_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    $debug_output = '';

    //get memory usage ; code of phpbb
    if (function_exists('memory_get_usage'))
    {
        if ($memory_usage = memory_get_usage())
        {
            $base_memory_usage    =    0;
            $memory_usage -= $base_memory_usage;
            $memory_usage = ($memory_usage >= 1048576) ? round((round($memory_usage / 1048576 * 100) / 100), 2) . ' MB' : (($memory_usage >= 1024) ? round((round($memory_usage / 1024 * 100) / 100), 2) . ' KB' : $memory_usage . ' BYTES');
            $debug_output = 'Memory Usage : <em>' . $memory_usage . '</em>';
        }
    }

    //then show it
    echo '<div class="debug_kleeja">';
    echo '<fieldset  dir="ltr"><legend><br /><br /><em style="font-family: Tahoma,serif; color:red">[Page Analysis]</em></legend>';
    echo '<p>&nbsp;</p>';
    echo '<p><h2><strong>General Information :</strong></h2></p>';
    echo '<p>Gzip : <em>' . ($do_gzip_compress !=0 ?  'Enabled' : 'Disabled') . '</em></p>';
    echo '<p>Queries Number :<em> ' . $SQL->query_num . ' </i></p>';
    echo '<p>Hook System :<em> ' . ((! defined('STOP_PLUGINS')) ? 'Enabled' : 'Disabled') . ' </em></p>';
    echo '<p>' . $debug_output . '</p>';
    echo '<p>&nbsp;</p>';
    echo '<p><h2><strong><em>SQL</em> Information :</strong></h2></p> ';

    if (is_array($SQL->debugr))
    {
        foreach ($SQL->debugr as $key=>$val)
        {
            echo '<fieldset name="sql"  dir="ltr" style="background:white"><legend><em>Query # [' . ($key+1) . '</em>]</legend> ';
            echo '<textarea style="font-family:Courier New,monospace;width:99%; background:#F4F4F4" rows="5" cols="10">' . $val[0] . '';
            echo '</textarea>    <br />';
            echo 'Duration :' . $val[1] . '';
            echo '</fieldset>';
            echo '<br /><br />';
        }
    }
    else
    {
        echo '<p><strong>NO SQLs</strong></p>';
    }

    echo '<p>&nbsp;</p><p><h2><strong><em>Plugins</em> Information :</strong></h2></p> ';
    echo '<ul>';

    if (sizeof(Plugins::getInstance()->getDebugInfo()) > 0)
    {
        echo '<textarea style="font-family:\'Courier New\',monospace;width:99%; background:#F4F4F4" rows="20" cols="10">' . var_export(Plugins::getInstance()->getDebugInfo(), true) . '';
        echo '</textarea>    <br />';
    }
    else
    {
        echo '<p><strong>...</strong></p>';
    }

    echo '</ul>';
    echo '</div>';
}

/**
 * Show error of critical problem
 *
 * @param string $error_title title
 * @param string $msg_text    content
 * @param bool   $error       is it an error or an info message
 */
function big_error($error_title, $msg_text, $error = true)
{
    global $SQL;
    echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">' . "\n";
    echo '<head>' . "\n";
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
    echo '<title>' . htmlspecialchars($error_title) . '</title>' . "\n";
    echo '<style type="text/css">' . "\n\t";
    echo '* { margin: 0; padding: 0; direction:ltr}' . "\n\t";
    echo '.error {color: #333;background:#ffebe8;float:left;width:73%;text-align:left;margin-top:10px;border: 1px solid #dd3c10;} .info {color: #333;background:#fff9d7;border: 1px solid #e2c822;}' . "\n\t";
    echo '.error,.info {padding: 10px;font-family:"lucida grande", tahoma, verdana, arial, sans-serif;font-size: 12px;}' . "\n";
    echo '</style>' . "\n";
    echo '</head>' . "\n";
    echo '<body>' . "\n\t";
    echo '<div class="' . ($error ? 'error' : 'info') . '">' . "\n";
    echo "\n\t\t<h2>Kleeja " . ($error ? 'error' : 'information message') . ': </h2><br />' . "\n";
    echo "\n\t\t<strong> [ " . $error_title . ' ] </strong><br /><br />' . "\n\t\t" . $msg_text . "\n\t";
    echo "\n\t\t" . '<br /><br /><small>Visit <a href="http://www.kleeja.com/" title="kleeja">Kleeja</a> Website for more details.</small>' . "\n\t";
    echo '</div>' . "\n";
    echo '</body>' . "\n";
    echo '</html>';
    @$SQL->close();

    exit();
}


/**
 * Redirect to a url
 * @param  string $url
 * @param  bool   $header true for header location redirect or false for html meta
 * @param  bool   $exit   halt after echoing the redirect code
 * @param  int    $sec    delay in seconds
 * @param  bool   $return return the html code only
 * @return mixed
 *
 */
function redirect($url, $header = true, $exit = true, $sec = 0, $return = false)
{
    global $SQL;

    is_array($plugin_run_result = Plugins::getInstance()->run('redirect_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    if (! headers_sent() && $header && ! $return)
    {
        header('Location: ' . str_replace(['&amp;'], ['&'], $url));
    }
    else
    {
        $gre = '<script type="text/javascript"> setTimeout("window.location.href = \'' . str_replace(['&amp;'], ['&'], $url) . '\'", ' . $sec*1000 . '); </script>' .
            '<noscript><meta http-equiv="refresh" content="' . $sec . ';url=' . $url . '" /></noscript>';

        if ($return)
        {
            return $gre;
        }

        echo $gre;
    }

    if ($exit)
    {
        $SQL->close();

        exit;
    }

    return null;
}

/**
 *
 * Prevent CSRF,
 *
 * This will generate security token for GET request
 * @param  string $request_id
 * @return string
 */
function kleeja_add_form_key_get($request_id)
{
    global $config;

    $return = 'formkey=' . substr(sha1($config['h_key'] . date('H-d-m') . $request_id), 0, 20);

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_add_form_key_get_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}


function kleeja_check_form_key_get($request_id)
{
    global $config;

    $token = substr(sha1($config['h_key'] . date('H-d-m') . $request_id), 0, 20);

    $return = false;

    if ($token == g('formkey'))
    {
        $return = true;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_check_form_key_get_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}

/**
 * This will generate hidden fields for kleeja forms, csrf input
 * @param  string $form_name
 * @return string
 */
function kleeja_add_form_key($form_name)
{
    global $config;
    $now    = time();
    $return = '<input type="hidden" name="k_form_key" value="' . sha1($config['h_key'] . $form_name . $now) . '" /><input type="hidden" name="k_form_time" value="' . $now . '" />' . "\n";

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_add_form_key_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}

/**
 * This will check csrf hidden fields that came from kleeja forms
 * @param  string $form_name
 * @param  int    $require_time in seconds
 * @return bool
 */
function kleeja_check_form_key($form_name, $require_time = 300)
{
    global $config;

    if (defined('IN_ADMIN'))
    {
        //we increase it for admin to be a double
        $require_time *= 2;
    }

    $return = false;

    if (ip('k_form_key') && ip('k_form_time'))
    {
        $key_was   = trim(p('k_form_key'));
        $time_was  = p('k_form_time', 'int');
        $different = time() - $time_was;

        //check time that user spent in the form
        if ($different && (! $require_time || $require_time >= $different))
        {
            if (sha1($config['h_key'] . $form_name . $time_was) === $key_was)
            {
                $return = true;
            }
        }
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_check_form_key_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}

/**
 * Link generator
 * TODO to be edited
 * Files can be many links styles, so this will generate the current style of link
 * @param $pid
 * @param  array  $extra
 * @return string
 */
function kleeja_get_link ($pid, $extra = [])
{
    global $config;

    $links = [];

    //to avoid problems
    $config['id_form']     = empty($config['id_form']) ? 'id' : $config['id_form'];
    $config['id_form_img'] = empty($config['id_form_img']) ? 'id' : $config['id_form_img'];


    //to prevent bug with rewrite
    if ($config['mod_writer'] && ! empty($extra['::NAME::']))
    {
        if (
             (($pid == 'image' || $pid == 'thumb') && $config['id_form_img'] != 'direct') ||
             ($pid == 'file' && $config['id_form'] != 'direct')
        ) {
            $extra['::NAME::'] = str_replace('.', '-', $extra['::NAME::']);
        }
    }


    $file_link = [
        'id'       => $config['mod_writer'] ? 'download::ID::.html' : 'do.php?id=::ID::',
        'filename' => $config['mod_writer'] ?  'downloadf-::NAME::.html' : 'do.php?filename=::NAME::',
        'direct'   => '::DIR::/::NAME::',
    ];

    $image_link = [
        'id'       => $config['mod_writer'] ? 'image::ID::.html' : 'do.php?img=::ID::',
        'filename' => $config['mod_writer'] ?  'imagef-::NAME::.html' : 'do.php?imgf=::NAME::',
        'direct'   => '::DIR::/::NAME::',
    ];


    $thumb_link = [
        'id'       => $config['mod_writer'] ? 'thumb::ID::.html' : 'do.php?thmb=::ID::',
        'filename' => $config['mod_writer'] ?  'thumbf-::NAME::.html' : 'do.php?thmbf=::NAME::',
        'direct'   => '::DIR::/thumbs/::NAME::',
    ];

    $del_link = $config['mod_writer'] ?  'del::CODE::.html' : 'go.php?go=del&amp;cd=::CODE::';



    $links['file']  = $file_link[$config['id_form']];
    $links['image'] = $image_link[$config['id_form_img']];
    $links['thumb'] = $thumb_link[$config['id_form_img']];
    $links['del']   = $del_link;


    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_get_link_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    $return_link = $config['siteurl'] . str_replace(array_keys($extra), array_values($extra), $links[$pid]);

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_get_link_func2', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return $return_link;
}

/**
 *  Uploading boxes
 *
 * Parse template of boxes and print them
 * @param  string $box_name html block name from up_boxes.html file
 * @param  array  $extra    variables to pass to the html block
 * @return mixed
 */
function get_up_tpl_box($box_name, $extra = [])
{
    global $THIS_STYLE_PATH_ABS, $config;
    static $boxes;

    //prevent loads
    //also this must be cached in future
    if (empty($boxes))
    {
        $tpl_path = $THIS_STYLE_PATH_ABS . 'up_boxes.html';

        if (! file_exists($tpl_path))
        {
            $depend_on = false;

            if (trim($config['style_depend_on']) != '')
            {
                $depend_on = $config['style_depend_on'];
            }
            else
            {
                $depend_on = 'default';
            }

            $tpl_path = str_replace('/' . $config['style'] . '/', '/' . trim($depend_on) . '/', $tpl_path);
        }

        $tpl_code = file_get_contents($tpl_path);
        $tpl_code = preg_replace("/\n[\n\r\s\t]*/", '', $tpl_code);//remove extra spaces
        $matches  = preg_match_all('#<!-- BEGIN (.*?) -->(.*?)<!-- END (?:.*?) -->#', $tpl_code, $match);

        $boxes = [];

        for ($i = 0; $i < $matches; $i++)
        {
            if (empty($match[1][$i]))
            {
                continue;//it's empty , let's leave it
            }

            $boxes[$match[1][$i]] = $match[2][$i];
        }
    }

    //extra value
    $extra += [
        'siteurl'  => $config['siteurl'],
        'sitename' => $config['sitename'],
    ];

    //return compiled value
    $return = $boxes[$box_name];

    foreach ($extra as $var=>$val)
    {
        $return = preg_replace('/{' . $var . '}/', $val, $return);
    }

    /*
     * We add this hook here so you can substitute you own vars
     * and even add your own boxes to this template.
     */
    is_array($plugin_run_result = Plugins::getInstance()->run('get_up_tpl_box_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return $return;
}


/**
 * Extract info of a style
 * @param  string     $style_name
 * @return array|bool
 */
function kleeja_style_info($style_name)
{
    $inf_path = PATH . 'styles/' . $style_name . '/info.txt';

    //is info.txt exists or not
    if (! file_exists($inf_path))
    {
        return false;
    }

    $inf_c = file_get_contents($inf_path);
    //some ppl will edit this file with notepad or even with office word :)
    $inf_c = str_replace(["\r\n", "\r"], ["\n", "\n"], $inf_c);

    //as lines
    $inf_l = @explode("\n", $inf_c);
    $inf_l = array_map('trim', $inf_l);

    $inf_r = [];

    foreach ($inf_l as $m)
    {
        //comments
        if (isset($m[0]) && $m[0] == '#' || trim($m) == '')
        {
            continue;
        }

        $t = array_map('trim', @explode('=', $m, 2));
        // ':' mean something secondary as in sub-array
        if (strpos($t[0], ':') !== false)
        {
            $subInfo                   = explode(':', $t[0]);
            $t_t0                      = array_map('trim', $subInfo);
            $inf_r[$t_t0[0]][$t_t0[1]] = $t[1];
        }
        else
        {
            $inf_r[$t[0]] = $t[1];
        }
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_style_info_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return $inf_r;
}


/**
 * Browser detection
 * returns whether or not the visiting browser is the one specified [part of kleeja style system]
 * i.e. is_browser('ie6') -> true or false
 * i.e. is_browser('ie, opera') -> true or false
 * @param  string $b browser name, like mozilla
 * @return bool
 */
function is_browser($b)
{
    //is there , which mean -OR-
    if (strpos($b, ',') !== false)
    {
        $e = explode(',', $b);

        foreach ($e as $n)
        {
            if (is_browser(trim($n)))
            {
                return true;
            }
        }

        return false;
    }

    //if no agent, let's take the worst case
    $u_agent = (! empty($_SERVER['HTTP_USER_AGENT'])) ? htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']) : (function_exists('getenv') ? getenv('HTTP_USER_AGENT') : '');
    $t       = trim(preg_replace('/[^a-z]/', '', $b));
    $r       = trim(preg_replace('/[a-z]/', '', $b));

    $return = false;
    switch ($t)
    {
        case 'ie':
            $return = strpos(strtolower($u_agent), trim('msie ' . $r)) !== false ? true : false;

        break;

        case 'firefox':
            $return = strpos(str_replace('/', ' ', strtolower($u_agent)), trim('firefox ' . $r)) !== false ? true : false;

        break;

        case 'safari':
            $return = strpos(strtolower($u_agent), trim('safari/' . $r)) !== false ? true : false;

        break;

        case 'chrome':
            $return = strpos(strtolower($u_agent), trim('chrome ' . $r)) !== false ? true : false;

        break;

        case 'flock':
            $return = strpos(strtolower($u_agent), trim('flock ' . $r)) !== false ? true : false;

        break;

        case 'opera':
            $return = strpos(strtolower($u_agent), trim('opera ' . $r)) !== false ? true : false;

        break;

        case 'konqueror':
            $return = strpos(strtolower($u_agent), trim('konqueror/' . $r)) !== false ? true : false;

        break;

        case 'mozilla':
            $return = strpos(strtolower($u_agent), trim('gecko/' . $r)) !== false ? true : false;

        break;

        case 'webkit':
            $return = strpos(strtolower($u_agent), trim('applewebkit/' . $r)) !== false ? true : false;

        break;
        /**
         * Mobile Phones are so popular those days, so we have to support them ...
         * This is still in our test lab.
         * @see http://en.wikipedia.org/wiki/List_of_user_agents_for_mobile_phones
         **/
        case 'mobile':
            $mobile_agents = ['iPhone;', 'iPod;', 'blackberry', 'Android', 'HTC' , 'IEMobile', 'LG/', 'LG-',
                'LGE-', 'MOT-', 'Nokia', 'SymbianOS', 'nokia_', 'PalmSource', 'webOS', 'SAMSUNG-',
                'SEC-SGHU', 'SonyEricsson', 'BOLT/', 'Mobile Safari', 'Fennec/', 'Opera Mini'];
            $return = false;

            foreach ($mobile_agents as $agent)
            {
                if (strpos($u_agent, $agent) !== false)
                {
                    $return = true;

                    break;
                }
            }

        break;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('is_browser_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    return $return;
}


/**
 * Send an answer for ajax request
 * @param int    $code_number
 * @param string $content
 * @param string $menu
 */
function echo_ajax($code_number, $content, $menu = '')
{
    global $SQL;
    $SQL->close();

    exit(json_encode(['code' => $code_number, 'content' => $content, 'menu' => $menu]));
}


/**
 * Send an answer for ajax request [ARRAY]
 * @param array $array
 */
function echo_array_ajax($array)
{
    global $SQL;
    $SQL->close();

    exit(@json_encode($array));
}

/**
 * show date in a human-readable-text
 * @param  int    $time       timestamp
 * @param  bool   $human_time return a readable time, like today, 1 hour ago
 * @param  bool   $format     date format like d-m-y
 * @return string
 */
function kleeja_date($time, $human_time = true, $format = false)
{
    global $lang, $config;

    if (! defined('TIME_FORMAT'))
    {
        define('TIME_FORMAT', 'd-m-Y h:i a'); // to be moved to configs later
    }

    if (! empty($config['time_zone']) && strpos($config['time_zone'], '/') !== false)
    {
        $timezone_offset = timezone_offset_get(new DateTimeZone($config['time_zone']), new DateTime);
    }
    else
    {
        $timezone_offset = intval($config['time_zone']) * 60 * 60;
    }

    if ((time() - $time > (86400 * 9)) || $format || ! $human_time)
    {
        $format    = ! $format ? TIME_FORMAT : $format;
        $time      = $time + $timezone_offset;
        return str_replace(['am', 'pm'], [$lang['TIME_AM'], $lang['TIME_PM']], gmdate($format, $time));
    }

    $lengths    = ['60','60','24','7','4.35','12','10'];

    $timezone_diff       = (int) $config['time_zone'] * 60 * 60;
    $now                 = time() + $timezone_diff;
    $time                = $time  + $timezone_diff;
    $difference          = $now > $time ? $now - $time :  $time - $now;
    $tense               = $now > $time ? $lang['W_AGO'] : $lang['W_FROM'];

    for ($j = 0; $difference >= $lengths[$j] && $j < sizeof($lengths)-1; $j++)
    {
        $difference /= $lengths[$j];
    }

    $difference = round($difference);

    if ($difference != 1)
    {
        if ($difference == 2)
        {
            $return = $lang['W_PERIODS_DP_' . $j];
        }
        else
        {
            $return = $difference . ' ' . ($difference > 10 ? $lang['W_PERIODS_' . $j] :  $lang['W_PERIODS_P_' . $j]);
        }
    }
    else
    {
        $return = $lang['W_PERIODS_' . $j];
    }

    $return = $now > $time  ? $return . '  ' . $lang['W_AGO']: $lang['W_FROM'] . ' ' . $return;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_date_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    return $return;
}


/*
 * World Time Zones
 * @return array
 */
function time_zones()
{
    static $regions = [
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC,
    ];

    $timezones = [];

    foreach ($regions as $region)
    {
        foreach (timezone_identifiers_list($region) as $tz)
        {
            $timezones[$tz] = timezone_offset_get(new DateTimeZone($tz), new DateTime) / 3600;
        }
    }

    // for compatibility with earlier versions.
    $timezones['Asia/Buraydah'] = 3.01;

    asort($timezones);

    return $timezones;
}


/**
 * generate a config html field to insert to add as an acp option
 * @param  string $name           config name
 * @param  string $type           input type (text, yesno, select)
 * @param  array  $select_options in case of select type, provide options array ([[title=>value], [title=>value]]
 * @return string input html
 */
function configField($name, $type = 'text', $select_options = [])
{
    switch ($type) {
        default:
        case 'text':
            return '<input type="text" id="kj_meta_seo_home_meta_keywords" name="' . $name . '"' .
                ' value="{con.' . $name . '}" size="50" />';

        case 'yesno':
            return '<label>{lang.YES}<input type="radio" id="' . $name . '" name="' . $name . '" ' .
                'value="1"  <IF NAME="con.' . $name . '==1"> checked="checked"</IF> /></label><label>{lang.NO}' .
                '<input type="radio" id="' . $name . '" name="' . $name . '" value="0" ' .
                ' <IF NAME="con.' . $name . '==0"> checked="checked"</IF> /></label>';

        case 'select':
            $return_value = '<select id="' . $name . '" name="' . $name . '">' . "\n";

            foreach ($select_options as $title => $value)
            {
                $return_value .= '<option <IF NAME="con.' . $name . '==' . $value . '">selected="selected"</IF> value="' . $value . '">' . $title . '</option>' . "\n";
            }

            return $return_value . '</select>' . "\n";
    }
}

/**
 * Shorten A string
 *
 * @param  string $text  The strings to shorten
 * @param  int    $until
 * @return string Short string
 */
function shorten_text($text, $until = 30)
{
    $until = $until < 4 ? 4 : $until;

    $chars_len = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

    if ($chars_len >= $until)
    {
        $return = function_exists('mb_substr')
            ? (mb_substr($text, 0, $until-4, 'UTF-8') . ' ... ' . mb_substr($text, -4, null, 'UTF-8'))
            : substr($text, 0, $until-4) . ' ... ' . substr($text, -4);
    }
    else
    {
        $return = $text;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('shorten_text_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return $return;
}
