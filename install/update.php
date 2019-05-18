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
include_once PATH . 'includes/functions.php';
include_once PATH . 'includes/functions_alternative.php';

include_once PATH . 'includes/mysqli.php';

include_once 'includes/functions_install.php';
include_once 'includes/update_schema.php';


$SQL = new KleejaDatabase($dbserver, $dbuser, $dbpass, $dbname);

//
// fix missing db_version
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
case 'update_now':

    $complete_update    = true;
    $update_msgs_arr    = [];
    $current_db_version = $config['db_version'];

    $all_db_updates = array_keys($update_schema);

    $available_db_updates = array_filter($all_db_updates, function ($v) use ($current_db_version) {
        return $v > $current_db_version;
    });

    sort($available_db_updates);

    if (! sizeof($available_db_updates))
    {
        $update_msgs_arr[] = '<span style="color:green;">' . $lang['INST_UPDATE_CUR_VER_IS_UP'] . '</span>';
        $complete_update   = false;
    }

    //
    //is there any sqls
    //
    if ($complete_update)
    {
        //loop through available updates
        foreach ($available_db_updates as $db_update_version)
        {
            $SQL->show_errors = false;

            //sqls
            if (isset($update_schema[$db_update_version]['sql'])
                    && sizeof($update_schema[$db_update_version]['sql']) > 0)
            {
                $err = '';

                $complete_update = true;

                foreach ($update_schema[$db_update_version]['sql'] as $name=>$sql_content)
                {
                    $err = '';
                    $SQL->query($sql_content);
                    $err = $SQL->get_error();

                    if (strpos($err[1], 'Duplicate') !== false || $err[0] == '1062' || $err[0] == '1060')
                    {
                        $complete_update   = false;
                    }
                }
            }

            //functions
            if ($complete_update)
            {
                if (isset($update_schema[$db_update_version]['functions']) && sizeof($update_schema[$db_update_version]['functions']) > 0)
                {
                    foreach ($update_schema[$db_update_version]['functions'] as $n)
                    {
                        if (is_callable($n))
                        {
                            $n();
                        }
                    }
                }
            }

            $sql = "UPDATE `{$dbprefix}config` SET `value` = '" . UPDATE_DB_VERSION . "' WHERE `name` = 'db_version'";
            $SQL->query($sql);
        }
    }


    delete_cache('', true);
    echo gettpl('update_end.html');

break;
}

/**
* print footer
*/
echo gettpl('footer.html');
