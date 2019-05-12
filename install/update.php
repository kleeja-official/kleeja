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
$_path = '../';
define('PATH', $_path);

if (file_exists($_path . 'config.php'))
{
    include_once $_path . 'config.php';
}

include_once $_path . 'includes/functions.php';
include_once $_path . 'includes/functions_alternative.php';

include_once $_path . 'includes/mysqli.php';

include_once 'includes/functions_install.php';


$order_update_files = [
    '1.7_to_2.0'	=> 9,
    // filename => db_version
];

$SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname);

//
// Is current db is up-to-date ?
//
$config['db_version'] = inst_get_config('db_version');

if ($config['db_version'] == false)
{
    $SQL->query("INSERT INTO `{$dbprefix}config` (`name` ,`value`) VALUES ('db_version', '')");
}



$IN_UPDATE = true;

/**
* print header
*/
if (! ip('action_file_do'))
{
    echo gettpl('header.html');
}



/**
* Navigation ..
*/
switch (g('step', 'str', 'action_file'))
{
default:
case 'action_file':

    if (ip('action_file_do'))
    {
        if (p('action_file_do', 'str', '') !== '')
        {
            echo '<meta http-equiv="refresh" content="0;url=' . $_SERVER['PHP_SELF'] . '?step=update_now&action_file_do=' . p('action_file_do') . '&amp;' . getlang(1) . '">';
        }
    }
    else
    {
        //get fles
        $s_path  = 'includes/update_files';
        $dh      = opendir($s_path);
        $upfiles = [];

        while (($file = readdir($dh)) !== false)
        {
            if (substr($file, -3) == 'php')
            {
                $file   = str_replace('.php', '', $file);
                $db_ver = $order_update_files[$file];

                // var_dump($db_ver);

                if ((empty($config['db_version']) || $db_ver > $config['db_version']))
                {
                    $upfiles[$db_ver] = $file;
                }
            }
        }
        @closedir($dh);

        ksort($upfiles);

        echo gettpl('update_list.html');
    }

break;

case 'update_now':

        if (! ig('action_file_do'))
        {
            echo '<meta http-equiv="refresh" content="0;url=' . $_SERVER['PHP_SELF'] . '?step=action_file&' . getlang(1) . '">';

            exit();
        }

        if (ig('complet_up_func'))
        {
            define('C_U_F', true);
        }

        $file_for_up = 'includes/update_files/' . preg_replace('/[^a-z0-9_\-\.]/i', '', g('action_file_do')) . '.php';

        if (! file_exists($file_for_up))
        {
            echo '<span style="color:red;">' . $lang['INST_ERR_NO_SELECTED_UPFILE_GOOD'] . ' [ ' . $file_for_up . ' ]</span><br />';
        }
        else
        {
            //get it
            require $file_for_up;
            $complete_update = true;
            $update_msgs_arr = [];


            if ($config['db_version'] >= UPDATE_DB_VERSION && ! defined('DEV_STAGE'))
            {
                $update_msgs_arr[] = '<span style="color:green;">' . $lang['INST_UPDATE_CUR_VER_IS_UP'] . '</span>';
                $complete_update   = false;
            }

            //
            //is there any sqls
            //
            if (($complete_update || (defined('DEV_STAGE')) && ! defined('C_U_F')))
            {
                $SQL->show_errors = false;

                if (isset($update_sqls) && sizeof($update_sqls) > 0)
                {
                    $err = '';

                    foreach ($update_sqls as $name=>$sql_content)
                    {
                        $err = '';
                        $SQL->query($sql_content);
                        $err = $SQL->get_error();

                        if (strpos($err[1], 'Duplicate') !== false || $err[0] == '1062' || $err[0] == '1060')
                        {
                            $update_msgs_arr[] = '<span style="color:green;">' . $lang['INST_UPDATE_CUR_VER_IS_UP'] . '</span>';
                            $complete_update   = false;
                        }
                    }
                }
            }

            //
            //is there any functions
            //
            if ($complete_update || defined('DEV_STAGE') || defined('C_U_F'))
            {
                if (isset($update_functions) && sizeof($update_functions) > 0)
                {
                    foreach ($update_functions as $n)
                    {
                        if (is_callable($n))
                        {
                            $n();
                        }
                    }
                }
            }

            //
            //is there any notes
            //
            $NOTES_CUP = false;

            if ($complete_update || defined('DEV_STAGE'))
            {
                if (isset($update_notes) && sizeof($update_notes) > 0)
                {
                    $i         =1;
                    $NOTES_CUP = [];

                    foreach ($update_notes as $n)
                    {
                        $NOTES_CUP[$i] = $n;
                        ++$i;
                    }
                }

                $sql = "UPDATE `{$dbprefix}config` SET `value` = '" . UPDATE_DB_VERSION . "' WHERE `name` = 'db_version'";
                $SQL->query($sql);
            }

            echo gettpl('update_end.html');
        }

break;
}

/**
* print footer
*/
echo gettpl('footer.html');
