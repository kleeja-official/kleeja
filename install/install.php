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
define('PATH', '../');

if (file_exists(PATH . 'config.php'))
{
    include_once PATH . 'config.php';
}

include_once PATH . 'includes/plugins.php';
include_once PATH . 'includes/functions_display.php';
include_once PATH . 'includes/functions_alternative.php';
include_once PATH . 'includes/functions.php';


include_once PATH . 'includes/mysqli.php';

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

$contentof_license = 'GPL version 2
GNU General Public License, Free Software Foundation
The GNU General Public License is a Free Software license. Like any Free Software license, it grants to you the four following freedoms:
1. The freedom to run the program for any purpose.
2. The freedom to study how the program works and adapt it to your needs.
3. The freedom to redistribute copies so you can help your neighbor.
4. The freedom to improve the program and release your improvements to the public, so that the whole community benefits.
You may exercise the freedoms specified here provided that you comply with the express conditions of this license. The principal conditions are:
You must conspicuously and appropriately publish on each copy distributed an appropriate copyright notice and disclaimer of warranty and keep intact all the notices that refer to this License and to the absence of any warranty; and give any other recipients of the Program a copy of the GNU General Public License along with the Program. Any translation of the GNU General Public License must be accompanied by the GNU General Public License.
If you modify your copy or copies of the program or any portion of it, or develop a program based upon it, you may distribute the resulting work provided you do so under the GNU General Public License. Any translation of the GNU General Public License must be accompanied by the GNU General Public License.
If you copy or distribute the program, you must accompany it with the complete corresponding machine-readable source code or with a written offer, valid for at least three years, to furnish the complete corresponding machine-readable source code.
Any of the above conditions can be waived if you get permission from the copyright holder.';
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

    echo gettpl('check.html');

break;

case 'c':

    // after submit, generate config file
    if (ip('dbsubmit'))
    {
        //create config file, or export it to browser on failure
        do_config_export(p('db_server'), p('db_user'), p('db_pass'), p('db_name'), p('db_prefix'));
    }


    $no_config         = ! file_exists(PATH . 'config.php') || ig('force') ? false : true;
    $writeable_path    = is_writable(PATH) ? true : false;

    echo gettpl('configs.html');

break;

case 'check':

    $submit_disabled = $no_connection = $mysql_ver = false;

    //config.php
    if (! empty($dbname) && ! empty($dbuser))
    {
        //connect .. for check
        $SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname);


        if (! $SQL->is_connected())
        {
            $no_connection = true;
        }
        else
        {
            if (! empty($SQL->mysql_version()) && version_compare($SQL->mysql_version(), MIN_MYSQL_VERSION, '<'))
            {
                $mysql_ver = $SQL->mysql_version();
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
        $SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname);

        include_once PATH . 'includes/usr.php';
        include_once PATH . 'includes/functions_alternative.php';
        $usrcp = new usrcp;

        $user_salt                 = substr(kleeja_base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
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
