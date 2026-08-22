<?php
/**
*
* @package install
* @copyright (c) 2007 Kleeja.net
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
define('PATH', '../');

if (file_exists(PATH . 'config.php'))
{
    include_once PATH . 'config.php';
}

include_once PATH . 'includes/plugins.php';
include_once PATH . 'includes/functions_display.php';
include_once PATH . 'includes/functions_alternative.php';
include_once PATH . 'includes/functions.php';


if (isset($dbtype) && $dbtype == 'sqlite')
{
    include PATH . 'includes/sqlite.php';
}
else
{
    include PATH . 'includes/mysqli.php';
}

include_once 'includes/functions_install.php';



if (! ig('step'))
{
    //if anyone request this file directly without passing index.php we will return him to index.php
    header('Location: index.php');

    exit;
}

//
// Kleeja must be safe ..
//
if (! empty($dbuser) && ! empty($dbname) && ! (ig('step') && in_array(g('step'), ['c','check', 'data', 'end', 'wizard'])))
{
    $d = inst_get_config('language');

    if (! empty($d))
    {
        header('Location: ./index.php');

        exit;
    }
}

/**
* Print header
*/
if (ip('dbsubmit') && ! is_writable(PATH))
{
    // soon
}
else
{
    echo gettpl('header.html');
}



// //navigate ..
switch (g('step'))
{
default:
case 'license':

$contentof_license = 'Copyright (c) [year] [copyright holder]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.';
$contentof_license = nl2br($contentof_license);
echo gettpl('license.html');

break;

case 'f':

    $check_ok = true;
    $advices  = $ziparchive_lib  = false;

    if (! class_exists('ZipArchive'))
    {
        $ziparchive_lib = true;
    }

    if ($ziparchive_lib)
    {
        $advices = true;
    }

    if (!extension_loaded('pdo')) {
        $check_ok = false;
    }

    echo gettpl('check.html');

break;

case 'c':

    // after submit, generate config file
    if (ip('dbsubmit'))
    {
        //create config file, or export it to browser on failure
        do_config_export(p('db_server'), p('db_user'), p('db_pass'), p('db_name'), p('db_prefix'), p('db_type'));
    }

    $no_config         = ! file_exists(PATH . 'config.php') || ig('force') ? false : true;
    $writeable_path    = is_writable(PATH) ? true : false;

    echo gettpl('configs.html');

break;

case 'check':

    $submit_disabled = $no_connection = $mysql_ver = false;

    //config.php
    if (! empty($dbname))
    {
        if (isset($dbtype) && $dbtype == 'sqlite')
        {
            @touch(PATH . $dbname);
        }

        //connect .. for check
        $SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname, $dbprefix);


        if (! $SQL->is_connected())
        {
            $no_connection = true;
        }
        else
        {
            if (defined('SQL_LAYER') && SQL_LAYER == 'mysqli')
            {
                if (! empty($SQL->version()) && version_compare($SQL->version(), MIN_MYSQL_VERSION, '<'))
                {
                    $mysql_ver = $SQL->version();
                }
            }
        }
    }

    //try to chmod them
    if (function_exists('chmod'))
    {
        @chmod(PATH . 'cache', 0755);
        @chmod(PATH . 'plugins', 0755);
        @chmod(PATH . 'styles', 0755);
        @chmod(PATH . 'uploads', 0755);
        @chmod(PATH . 'uploads/thumbs', 0755);
    }

    echo gettpl('check_all.html');

break;

case 'data' :

    if (ip('datasubmit'))
    {
        //check data ...
        if (empty(p('sitename')) || empty(p('siteurl')) || empty(p('sitemail'))
             || empty(p('username')) || empty(p('password')) || empty(p('password2')) || empty(p('email')))
        {
            echo $lang['EMPTY_FIELDS'];
            echo $footer_inst;

            exit();
        }

        //fix bug #r1777 (alta3rq revision)
        if (! empty(p('password')) && ! empty(p('password2')) && p('password') != p('password2'))
        {
            echo $lang['PASS_NEQ_PASS2'];
            echo $footer_inst;

            exit();
        }

        if (strpos(p('email'), '@') === false)
        {
            echo $lang['WRONG_EMAIL'];
            echo $footer_inst;

            exit();
        }

        //connect .. for check
        $SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname, $dbprefix);

        include_once PATH . 'includes/usr.php';
        include_once PATH . 'includes/functions_alternative.php';
        $usrcp = new usrcp;

        $user_salt                 = substr(base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
        $user_pass                 = $usrcp->kleeja_hash_password(p('password') . $user_salt);
        $user_name                 = $SQL->escape(p('username'));
        $user_mail                 = $SQL->escape(p('email'));
        $config_sitename           = $SQL->escape(p('sitename'));
        $config_siteurl            = $SQL->escape(p('siteurl'));
        $config_sitemail           = $SQL->escape(p('sitemail'));
        $config_time_zone          = $SQL->escape(p('time_zone'));
        //$config_style        = ip('style') ? $SQL->escape(p('style')) : '';
        $config_urls_type          = in_array(p('urls_type'), ['id', 'filename', 'direct']) ? p('urls_type') : 'id';
        $clean_name                = $usrcp->cleanusername($SQL->escape($user_name));

        /// ok .. we will get sqls now ..
        include 'includes/install_sqls.php';
        include 'includes/default_values.php';

        $err    = $dots    = 0;
        $errors = '';

        //do important alter before
        $SQL->query($install_sqls['ALTER_DATABASE_UTF']);

        $sqls_done = $sql_err = [];

        foreach ($install_sqls as $name=>$sql_content)
        {
            if ($name == 'DROP_TABLES' || $name == 'ALTER_DATABASE_UTF')
            {
                continue;
            }

            if ($SQL->query($sql_content))
            {
                if ($name == 'call')
                {
                    $sqls_done[] = $lang['INST_CRT_CALL'];
                }
                elseif ($name == 'reports')
                {
                    $sqls_done[] = $lang['INST_CRT_REPRS'];
                }
                elseif ($name == 'stats')
                {
                    $sqls_done[] = $lang['INST_CRT_STS'];
                }
                elseif ($name == 'users')
                {
                    $sqls_done[] = $lang['INST_CRT_USRS'];
                }
                elseif ($name == 'users')
                {
                    $sqls_done[] = $lang['INST_CRT_ADM'];
                }
                elseif ($name == 'files')
                {
                    $sqls_done[] = $lang['INST_CRT_FLS'];
                }
                elseif ($name == 'config')
                {
                    $sqls_done[] = $lang['INST_CRT_CNF'];
                }
                elseif ($name == 'exts')
                {
                    $sqls_done[] = $lang['INST_CRT_EXT'];
                }
                elseif ($name == 'online')
                {
                    $sqls_done[] = $lang['INST_CRT_ONL'];
                }
                elseif ($name == 'hooks')
                {
                    $sqls_done[] = $lang['INST_CRT_HKS'];
                }
                elseif ($name == 'plugins')
                {
                    $sqls_done[] = $lang['INST_CRT_PLG'];
                }
                elseif ($name == 'lang')
                {
                    $sqls_done[] = $lang['INST_CRT_LNG'];
                }
                else
                {
                    $sqls_done[] = $name . '...';
                }
            }
            else
            {
                $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
                $sql_err[] = $lang['INST_SQL_ERR'] . ' : ' . $name . '[basic]';
                $err++;
            }
        }//for

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
                    $sql_err[] = $lang['INST_SQL_ERR'] . ' : [configs_values] ' . $cn;
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
                    $sql_err[] = $lang['INST_SQL_ERR'] . ' : [groups_configs_values] ' . $cn;
                    $err++;
                }
            }

            //add exts
            foreach ($ext_values as $gid=>$exts)
            {
                $itxt = '';

                foreach ($exts as $t=>$v)
                {
                    $itxt .= ($itxt == '' ? '' : ',') . "('$t', $gid, $v)";
                }

                $sql = "INSERT INTO `{$dbprefix}groups_exts` (`ext`, `group_id`, `size`) VALUES " . $itxt . ';';

                if (! $SQL->query($sql))
                {
                    $errors .= implode(':', $SQL->get_error()) . '' . "\n___\n";
                    $sql_err[] = $lang['INST_SQL_ERR'] . ' : [ext_values] ' . $gid;
                    $err++;
                }
            }

            //add acls
            foreach ($acls_values as $cn=>$ct)
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
                    $sql_err[] = $lang['INST_SQL_ERR'] . ' : [acl_values] ' . $cn;
                    $err++;
                }
                $it++;
            }
        }

        echo gettpl('sqls_done.html');
    }
    else
    {
        $urlsite =  'http://' . $_SERVER['HTTP_HOST'] . str_replace('install', '', dirname($_SERVER['PHP_SELF']));
        echo gettpl('data.html');
    }

break;

case 'end' :

        echo gettpl('end.html');
        //for safe ..
        //@rename("install.php", "install.lock");
break;
}


/**
* print footer
*/
echo gettpl('footer.html');
