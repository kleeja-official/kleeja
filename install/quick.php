<?php
/**
 *
 * @package install
 * @copyright (c) 2007 Kleeja.com
 * @license ./docs/license.txt
 *
 */


// Report all errors, except notices
@error_reporting(E_ALL ^ E_NOTICE);


/**
 * include important files
 */
define('IN_COMMON', true);
define('STOP_PLUGINS', true);
define('PATH', __DIR__ . '/../');
define('CLI', PHP_SAPI === 'cli');


include_once PATH . 'includes/plugins.php';
include_once PATH . 'includes/functions_display.php';
include_once PATH . 'includes/functions_alternative.php';
include_once PATH . 'includes/functions.php';
include_once PATH . 'includes/mysqli.php';

include_once 'includes/functions_install.php';

//cli options
$cli_options   = [];

if (CLI)
{
    $cli_options = getopt('', ['password::', 'link::']);
}


if (file_exists(PATH . 'config.php'))
{
    include_once PATH . 'config.php';
}
else
{
    do_config_export('localhost', 'root', '', 'kleeja', 'klj_');

    exit('`config.php` was missing! so we created one for you, kindly edit the file with database information.');
}

$SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname, $dbprefix);

if (! $SQL->is_connected())
{
    exit('Can not connect to database, please make sure the data in `config.php` is correct!');
}

if (! empty($SQL->mysql_version()) && version_compare($SQL->mysql_version(), MIN_MYSQL_VERSION, '<'))
{
    exit('The required MySQL version is `' . MIN_MYSQL_VERSION . '` and yours is `' . $SQL->mysql_version() . '`!');
}

foreach (['cache', 'uploads', 'uploads/thumbs'] as $folder)
{
    if (! is_writable(PATH . $folder))
    {
        @chmod(PATH . $folder, 0755);

        if (! is_writable(PATH . $folder))
        {
            exit('The folder `' . $folder . '` has to be writable!');
        }
    }
}


//install
$SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname, $dbprefix);

include_once PATH . 'includes/usr.php';
include_once PATH . 'includes/functions_alternative.php';

$usrcp                     = new usrcp;
$password                  = ! empty($cli_options['password']) ? $cli_options['password'] : mt_rand();
$user_salt                 = substr(base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
$user_pass                 = $usrcp->kleeja_hash_password($password . $user_salt);
$user_name                 = $clean_name = 'admin';
$user_mail                 = $config_sitemail = 'admin@example.com';
$config_urls_type          = 'id';
$config_sitename           = 'Yet Another Kleeja';
$config_siteurl            = ! empty($cli_options['link'])
                                ? $cli_options['link']
                                : 'http://' . $_SERVER['HTTP_HOST'] . str_replace('install', '', dirname($_SERVER['PHP_SELF']));
$config_time_zone          = 'Asia/Buraydah';

// Queries
include 'includes/install_sqls.php';
include 'includes/default_values.php';

$SQL->query($install_sqls['ALTER_DATABASE_UTF']);


$err           = 0;
$errors        = '';

foreach ($install_sqls as $name => $sql_content)
{
    if ($name == 'DROP_TABLES' || $name == 'ALTER_DATABASE_UTF')
    {
        continue;
    }

    if (! $SQL->query($sql_content))
    {
        $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
        echo $lang['INST_SQL_ERR'] . ' : ' . $name . '[basic]' . (CLI ? PHP_EOL : '<br>');
        $err++;
    }
}

if ($err == 0)
{
    //add configs
    foreach ($config_values as $cn)
    {
        if (empty($cn[6]))
        {
            $cn[6] = 0;
        }

        $sql = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES ('$cn[0]', '$cn[1]', '$cn[2]', '$cn[3]', '$cn[4]', '$cn[5]', '$cn[6]');";

        if (! $SQL->query($sql))
        {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [configs_values] ' . $cn . (CLI ? PHP_EOL : '<br>');
            $err++;
        }
    }

    //add groups configs
    foreach ($config_values as $cn)
    {
        if ($cn[4] != 'groups' or ! $cn[4])
        {
            continue;
        }

        $itxt = '';

        foreach ([1, 2, 3] as $im)
        {
            $itxt .= ($itxt == '' ? '' : ',') . "($im, '$cn[0]', '$cn[1]')";
        }

        $sql = "INSERT INTO `{$dbprefix}groups_data` (`group_id`, `name`, `value`) VALUES " . $itxt . ';';

        if (! $SQL->query($sql))
        {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [groups_configs_values] ' . $cn . (CLI ? PHP_EOL : '<br>');
            $err++;
        }
    }

    //add exts
    foreach ($ext_values as $gid => $exts)
    {
        $itxt = '';

        foreach ($exts as $t => $v)
        {
            $itxt .= ($itxt == '' ? '' : ',') . "('$t', $gid, $v)";
        }

        $sql = "INSERT INTO `{$dbprefix}groups_exts` (`ext`, `group_id`, `size`) VALUES " . $itxt . ';';

        if (! $SQL->query($sql))
        {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [ext_values] ' . $gid . (CLI ? PHP_EOL : '<br>');
            $err++;
        }
    }

    //add acls
    foreach ($acls_values as $cn => $ct)
    {
        $it   = 1;
        $itxt = '';

        foreach ($ct as $ctk)
        {
            $itxt .= ($itxt == '' ? '' : ',') . "('$cn', '$it', '$ctk')";
            $it++;
        }


        $sql = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES " . $itxt . ';';

        if (! $SQL->query($sql))
        {
            $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
            echo $lang['INST_SQL_ERR'] . ' : [acl_values] ' . $cn . (CLI ? PHP_EOL : '<br>');
            $err++;
        }
        $it++;
    }
}


if ($err > 0)
{
    echo CLI ? PHP_EOL : '<br><span style="color:red">';
    echo 'We encountered a problem during installation, see the error log:';
    echo CLI ? PHP_EOL : '</span><br>';
    echo CLI ? '' : '<textarea rows="10" style="width:100%">';
    echo $errors;
    echo CLI ? '' : '</textarea>';
}
else
{
    echo CLI ? '' : '<span style="color:green">';
    echo 'Kleeja has been installed successfully, enjoy ...';
    echo CLI ? PHP_EOL : '</span><br><br>';
    echo 'Username: admin' . (CLI ? PHP_EOL : '<br>');
    echo 'Password: ' . $password;
}
