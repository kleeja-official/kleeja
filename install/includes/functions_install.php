<?php
/**
*
* @package install
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


// get version info and min requirement values
require PATH . 'includes/version.php';

//set mysql to show no errors
define('MYSQL_NO_ERRORS', true);
define('EVAL_IS_ON', is_eval_is_on());


// Detect choosing another lang while installing
if (ig('change_lang') && ip('lang'))
{
    header('Location: ' . $_SERVER['PHP_SELF'] . '?step=' . p('step_is') . '&lang=' . p('lang'));
}


// Including current language
$lang = require PATH . 'lang/' . getlang() . '/common.php';
$lang = array_merge($lang, require PATH . 'lang/' . getlang() . '/install.php');


// Exceptions for development
if (file_exists(PATH . '.git'))
{
    define('DEV_STAGE', true);
}


/**
 * Return current language of installing wizard
 * @param  bool         $link
 * @return mixed|string
 */
function getlang ($link = false)
{
    $ln    = 'en';

    if (ig('lang'))
    {
        $lang = preg_replace('/[^a-z0-9]/i', '', g('lang', 'str', 'en'));
        $ln	  = file_exists(PATH . 'lang/' . $lang . '/install.php') ? $lang : 'en';
    }

    return $link ? 'lang=' . $ln : $ln;
}

function getjquerylink()
{
    if (file_exists(PATH . 'admin/Masmak/js/jquery.min.js'))
    {
        return PATH . 'admin/Masmak/js/jquery.min.js';
    }

    return 'http://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js';
}

/**
* Parsing installing templates
* @param mixed $tplname
*/
function gettpl($tplname)
{
    global $lang;

    $tpl = preg_replace('/{{([^}]+)}}/', '<?php \\1 ?>', file_get_contents('style/' . $tplname));

    ob_start();

    if (EVAL_IS_ON)
    {
        eval('?> ' . $tpl . '<?php ');
    }
    else
    {
        include_once kleeja_eval($tpl);
    }

    $stpl = ob_get_contents();
    ob_end_clean();

    return $stpl;
}

function is_eval_is_on()
{
    $eval_on = false;
    eval('$eval_on = true;');

    return $eval_on;
}

function kleeja_eval($code)
{
    $path  = PATH . 'cache/' . md5($code) . '.php';
    file_put_contents($path, $code);
    return $path;
}


/**
* Export config
* @param mixed $srv
* @param mixed $usr
* @param mixed $pass
* @param mixed $nm
* @param mixed $prf
*/
function do_config_export($srv, $usr, $pass, $nm, $prf)
{
    $data = '<?php' . "\n\n" . '//fill these variables with your data' . "\n";
    $data	.= '$dbserver		= \'' . str_replace("'", "\'", $srv) . "'; //database server \n";
    $data	.= '$dbuser			= \'' . str_replace("'", "\'", $usr) . "' ; // database user \n";
    $data	.= '$dbpass			= \'' . str_replace("'", "\'", $pass) . "'; // database password \n";
    $data	.= '$dbname			= \'' . str_replace("'", "\'", $nm) . "'; // database name \n";
    $data .= '$dbprefix		= \'' . str_replace("'", "\'", $prf) . "'; // if you use prefix for tables , fill it \n";

    if (file_put_contents(PATH . 'config.php', $data, LOCK_EX) !== false)
    {
        return true;
    }

    if (defined('CLI') && CLI)
    {
        return true;
    }


    header('Content-Type: text/x-delimtext; name="config.php"');
    header('Content-disposition: attachment; filename=config.php');
    echo $data;

    exit;
}


/**
* Usefull to caluculte time of execution
*/
function get_microtime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}

/**
* Get config value from database directly, if not return false.
* @param mixed $name
*/
function inst_get_config($name)
{
    global $SQL, $dbprefix;

    if (empty($SQL))
    {
        global $dbserver, $dbuser, $dbpass, $dbname;

        if (! isset($dbserver))
        {
            return false;
        }

        $SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname);
    }

    if (empty($SQL))
    {
        return false;
    }

    $sql    = "SELECT value FROM `{$dbprefix}config` WHERE `name` = '" . $name . "'";
    $result	= $SQL->query($sql);

    if ($SQL->num_rows($result) == 0)
    {
        return false;
    }
    else
    {
        $current_ver  = $SQL->fetch_array($result);
        return $current_ver['value'];
    }
}



/**
* trying to detect cookies settings
*/
function get_cookies_settings()
{
    $server_port = ! empty($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : (int) @getenv('SERVER_PORT');
    $server_name = $server_name = (! empty($_SERVER['HTTP_HOST'])) ? strtolower($_SERVER['HTTP_HOST']) : ((! empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : @getenv('SERVER_NAME'));

    // HTTP HOST can carry a port number...
    if (strpos($server_name, ':') !== false)
    {
        $server_name = substr($server_name, 0, strpos($server_name, ':'));
    }


    $cookie_secure	= isset($_SERVER['HTTPS'])  && $_SERVER['HTTPS'] == 'on' ? true : false;
    $cookie_name	  = 'klj_' . strtolower(substr(str_replace('0', 'z', base_convert(md5(mt_rand()), 16, 35)), 0, 5));

    $name = (! empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');

    if (! $name)
    {
        $name = (! empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
    }

    $script_path = trim(dirname(str_replace(['\\', '//'], '/', $name)));


    if ($script_path !== '/')
    {
        if (substr($script_path, -1) == '/')
        {
            $script_path = substr($script_path, 0, -1);
        }

        $script_path = str_replace(['../', './'], '', $script_path);

        if ($script_path[0] != '/')
        {
            $script_path = '/' . $script_path;
        }
    }

    $cookie_domain = $server_name;

    if (strpos($cookie_domain, 'www.') === 0)
    {
        $cookie_domain = str_replace('www.', '.', $cookie_domain);
    }

    return [
        'server_name'	  => $server_name,
        'cookie_secure'	=> $cookie_secure,
        'cookie_name'	  => $cookie_name,
        'cookie_domain'	=> $cookie_domain,
        'cookie_path'	  => str_replace('/install', '', $script_path),
    ];
}
