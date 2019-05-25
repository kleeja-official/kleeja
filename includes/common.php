<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

//not for directly open
if (! defined('IN_KLEEJA'))
{
    exit();
}

//we are in the common file
define('IN_COMMON', true);


//filename of config.php
define('KLEEJA_CONFIG_FILE', 'config.php');

//plugins folder
define('KLEEJA_PLUGINS_FOLDER', 'plugins');



if (@extension_loaded('apc'))
{
    define('APC_CACHE', true);
}

//path
if (! defined('PATH'))
{
    define('PATH', str_replace('/includes', '', __DIR__) . '/');
}

//no config
if (! file_exists(PATH . KLEEJA_CONFIG_FILE))
{
    header('Location: ./install/index.php');

    exit;
}


//there is a config
require_once PATH . KLEEJA_CONFIG_FILE;


//admin files path
define('ADM_FILES_PATH', PATH . 'includes/adm');

//Report all errors, except notices
error_reporting(defined('DEV_STAGE') ? E_ALL : E_ALL ^ E_NOTICE);


/**
* functions for start
* @param mixed $error_number
* @param mixed $error_string
* @param mixed $error_file
* @param mixed $error_line
*/
function kleeja_show_error($error_number, $error_string = '', $error_file = '', $error_line = '')
{
    switch ($error_number)
    {
        case E_NOTICE: case E_WARNING: case E_USER_WARNING: case E_USER_NOTICE: case E_STRICT:
            if (function_exists('kleeja_log'))
            {
                $error_name = [
                    2 => 'Warning', 8 => 'Notice', 512 => 'U_Warning', 1024 => 'U_Notice', 2048 => 'Strict'
                ][$error_number];
                kleeja_log('[' . $error_name . '] ' . basename($error_file) . ':' . $error_line . ' ' . $error_string);
            }

        break;

        default:
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">' . "\n<head>\n";
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
            echo '<title>Kleeja Error</title>' . "\n" . '<style type="text/css">' . "\n\t";
            echo '.error {color: #333;background:#ffebe8;float:left;width:73%;text-align:left;margin-top:10px;border: 1px solid #dd3c10; padding: 10px;font-family:tahoma,arial;font-size: 12px;}' . "\n";
            echo "</style>\n</head>\n<body>\n\t" . '<div class="error">' . "\n\n\t\t<h2>Kleeja error  : </h2><br />" . "\n";
            echo "\n\t\t<strong> [ " . $error_number . ':' . basename($error_file) . ':' . $error_line . ' ] </strong><br /><br />' . "\n\t\t" . $error_string . "\n\t";
            echo "\n\t\t" . '<br /><br /><small>Visit <a href="http://www.kleeja.com/" title="kleeja">Kleeja</a> Website for more details.</small>' . "\n\t";
            echo "</div>\n</body>\n</html>";
            global $SQL;

            if (isset($SQL))
            {
                @$SQL->close();
            }

            exit;

        break;
    }
}
set_error_handler('kleeja_show_error');

//time of start and end and whatever
function get_microtime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}

//is bot ?
function is_bot($bots = ['googlebot', 'bing' ,'msnbot'])
{
    if (isset($_SERVER['HTTP_USER_AGENT']))
    {
        return preg_match('/(' . implode('|', $bots) . ')/i', ($_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : @getenv('HTTP_USER_AGENT'))) ? true : false;
    }
    return false;
}

$starttm = get_microtime();


if (! is_bot() && ! isset($_SESSION))
{
    session_start();
}


//no enough data
if (empty($dbname) || empty($dbuser))
{
    header('Location: ./install/index.php');

    exit;
}

// solutions for hosts running under suexec, add define('HAS_SUEXEC', true) to config.php.
define('K_FILE_CHMOD', defined('HAS_SUEXEC') ? (0644 & ~umask()) : 0644);
define('K_DIR_CHMOD', defined('HAS_SUEXEC') ? (0755 & ~umask()) : 0755);

include PATH . 'includes/functions_alternative.php';
include PATH . 'includes/version.php';
include PATH . 'includes/mysqli.php';
include PATH . 'includes/style.php';
include PATH . 'includes/usr.php';
include PATH . 'includes/pager.php';
include PATH . 'includes/functions.php';
include PATH . 'includes/functions_display.php';
include PATH . 'includes/plugins.php';
include PATH . 'includes/FetchFile.php';


if (defined('IN_ADMIN'))
{
    include PATH . 'includes/functions_adm.php';
}


//fix integration problems
if (empty($script_encoding))
{
    $script_encoding = 'utf-8';
}

//start classes ..
$SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname, $dbprefix);
//no need after now
unset($dbpass);



$tpl      = new kleeja_style;
$usrcp    = new usrcp;

//then get caches
include PATH . 'includes/cache.php';

//getting dynamic configs
$query = [
    'SELECT'       => 'c.name, c.value',
    'FROM'         => "{$dbprefix}config c",
    'WHERE'        => 'c.dynamic = 1',
];

$result = $SQL->build($query);

while ($row=$SQL->fetch_array($result))
{
    $config[$row['name']] = $row['value'];
}


$SQL->freeresult($result);

//check user or guest
$usrcp->kleeja_check_user();

//+ configs of the current group
$config = array_merge($config, (array) $d_groups[$usrcp->group_id()]['configs']);


//admin path
define('ADMIN_PATH', rtrim($config['siteurl'], '/') . '/admin/index.php');


//no tpl caching in dev stage
if (defined('DEV_STAGE') || defined('STOP_TPL_CACHE'))
{
    $tpl->caching = false;
}


if (isset($config['foldername']))
{
    $config['foldername'] = str_replace(
        [
            '{year}',
            '{month}',
            '{week}',
            '{day}',
            '{username}',
        ],
        [
            date('Y'),
            date('m'),
            date('W'),
            date('d'),
            $usrcp->name() ? preg_replace('/[^a-z0-9\._-]/', '', strtolower($usrcp->name())) : 'guest'
        ],
        $config['foldername']
    );
}


is_array($plugin_run_result = Plugins::getInstance()->run('boot_common', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


/**
 * Set default time zone
 * There is no time difference between Coordinated Universal Time (UTC) and Greenwich Mean Time (GMT).
 * Kleeja supports the changing of time zone through the admin panel, see functions_display.php/kleeja_date()
 */
date_default_timezone_set('GMT');


//kleeja session id
define('KJ_SESSION', preg_replace('/[^-,a-zA-Z0-9]/', '', session_id()));

//site url must end with /
$config['siteurl'] = rtrim($config['siteurl'], '/') . '/';


//check lang
if (! $config['language'] || empty($config['language']))
{
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) > 2)
    {
        $config['language'] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        if (! file_exists(PATH . 'lang/' . $config['language'] . '/common.php'))
        {
            $config['language'] = 'en';
        }
    }
}

//check style
if (is_null($config['style']) || empty($config['style']))
{
    $config['style'] = 'default';
}

//check h_kay, important for kleeja
if (empty($config['h_key']))
{
    $h_k = sha1(microtime() . rand(0, 100));

    if (! update_config('h_key', $h_k))
    {
        add_config('h_key', $h_k);
    }
}


//current Kleeja admin style
define('ACP_STYLE_NAME', 'Masmak');

//path variables for Kleeja
$STYLE_PATH                         = $config['siteurl'] . 'styles/' . (trim($config['style_depend_on']) == '' ? $config['style'] : $config['style_depend_on']) . '/';
$THIS_STYLE_PATH                    = $config['siteurl'] . 'styles/' . $config['style'] . '/';
$THIS_STYLE_PATH_ABS                = PATH . 'styles/' . $config['style'] . '/';
$STYLE_PATH_ADMIN                   = $config['siteurl'] . 'admin/' . (is_browser('mobile') || defined('IN_MOBILE') ? ACP_STYLE_NAME : ACP_STYLE_NAME) . '/';
$STYLE_PATH_ADMIN_ABS               = PATH . 'admin/' . (is_browser('mobile') || defined('IN_MOBILE') ? ACP_STYLE_NAME . '/' : ACP_STYLE_NAME . '/');
$DEFAULT_PATH_ADMIN_ABS             = PATH . 'admin/' . ACP_STYLE_NAME . '/';
$DEFAULT_PATH_ADMIN                 = $config['siteurl'] . 'admin/' . ACP_STYLE_NAME . '/';


//get languge of common
get_lang('common');

//run ban system
get_ban();

if (isset($_GET['go']) && $_GET['go'] == 'login')
{
    define('IN_LOGIN', true);
}

//install.php exists
if (
    file_exists(PATH . 'install')  &&
    ! defined('IN_ADMIN') &&
    ! defined('IN_LOGIN') &&
    ! defined('DEV_STAGE') &&
    ! (defined('IN_GO') && in_array(g('go'), ['queue'])) &&
    ! (defined('IN_UCP') && in_array(g('go'), ['captcha', 'login']))
) {
    //Different message for admins! delete install folder
    kleeja_info((user_can('enter_acp') ? $lang['DELETE_INSTALL_FOLDER'] : $lang['WE_UPDATING_KLEEJA_NOW']), $lang['SITE_CLOSED']);
}


//is site close
$login_page = '';

if (
    $config['siteclose'] == '1' &&
    ! user_can('enter_acp') &&
    ! defined('IN_LOGIN') &&
    ! defined('IN_ADMIN') &&
    ! (defined('IN_GO') && in_array(g('go'), ['queue'])) &&
    ! (defined('IN_UCP') && in_array(g('go'), ['captcha', 'login', 'register', 'logout']))
    ) {
    //if download, images ?
    if (
        (defined('IN_DOWNLOAD') && (ig('img') || ig('thmb') || ig('thmbf') || ig('imgf')))
        || g('go', 'str', '') == 'queue'
        ) {
        @$SQL->close();
        $fullname = 'images/site_closed.jpg';
        $filesize = filesize($fullname);
        header("Content-length: $filesize");
        header('Content-type: image/jpg');
        readfile($fullname);

        exit;
    }

    // Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    kleeja_info($config['closemsg'], $lang['SITE_CLOSED']);
}

//exceed total size
if (($stat_sizes >= ($config['total_size'] *(1048576))) && ! defined('IN_LOGIN') && ! defined('IN_ADMIN'))
{
    // convert megabytes to bytes
    // Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    kleeja_info($lang['SIZES_EXCCEDED'], $lang['STOP_FOR_SIZE']);
}

//detect bots and save stats
kleeja_detecting_bots();

//check for page number
if (empty($perpage) || intval($perpage) == 0)
{
    $perpage = 14;
}


//captcha file
$captcha_file_path = $config['siteurl'] . 'ucp.php?go=captcha';

if (defined('STOP_CAPTCHA'))
{
    $config['enable_captcha'] = 0;
}

is_array($plugin_run_result = Plugins::getInstance()->run('end_common', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


if (function_exists('session_register_shutdown'))
{
    session_register_shutdown();
}
else
{
    register_shutdown_function('session_write_close');
}
