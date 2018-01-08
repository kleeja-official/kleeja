<?php
/**
 *
 * @package adm
 * @copyright (c) 2007 Kleeja.com
 * @license http://www.kleeja.com/license
 *
 */


// not for directly open
if (!defined('IN_ADMIN'))
{
    exit();
}


#turn time-limit off
@set_time_limit(0);


#get current case
$case = g('case', 'str');

#set _get form key
$GET_FORM_KEY = kleeja_add_form_key_get('PLUGINS_FORM_KEY');
$H_FORM_KEYS	= kleeja_add_form_key('adm_plugins');

$action = ADMIN_PATH . '?cp=' . basename(__file__, '.php');

$plugin_install_link = ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '&amp;case=install&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_uninstall_link = ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '&amp;case=uninstall&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_enable_link = ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '&amp;case=enable&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_disable_link = ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '&amp;case=disable&amp;' . $GET_FORM_KEY . '&amp;plg=';


//check _GET Csrf token
if ($case && in_array($case, array('install', 'uninstall', 'enable', 'disable')))
{
    if (!kleeja_check_form_key_get('PLUGINS_FORM_KEY'))
    {
        kleeja_admin_err($lang['INVALID_GET_KEY'], $action);
    }
}


if(ip('newplugin'))
{
    if(!kleeja_check_form_key('adm_plugins'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action);
    }

    $case = 'upload';
}

switch ($case):

    default:

        # Get installed plugins
        $query = array(
            'SELECT' => "plg_id, plg_name, plg_ver, plg_disabled, plg_author, plg_dsc",
            'FROM' => "{$dbprefix}plugins",
            'ORDER BY' => "plg_id ASC",
        );


        $result = $SQL->build($query);

        $installed_plugins = array();

        while ($row = $SQL->fetch($result))
        {

            $installed_plugins[$row['plg_name']] = $row;

            $installed_plugins[$row['plg_name']]['extra_info'] = Plugins::getInstance()->installed_plugin_info($row['plg_name']);


            $installed_plugins[$row['plg_name']]['icon'] = file_exists(
                PATH . KLEEJA_PLUGINS_FOLDER . '/' . $row['plg_name'] . "/icon.png"
                )
                ? PATH . KLEEJA_PLUGINS_FOLDER . '/' . $row['plg_name'] . "/icon.png"
                : $STYLE_PATH_ADMIN . 'images/plugin.png';


            foreach (array('plugin_title', 'plugin_description') as $localizedInfo)
            {
                if (is_array($installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo]))
                {
                    if (!empty($installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo][$config['language']]))
                    {
                        $installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo] =
                            shorten_text($installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo][$config['language']], 100);
                    }
                    else if (!empty($installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo]['en']))
                    {
                        $installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo] =
                            shorten_text($installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo]['en'], 100);
                    }
                    else
                    {
                        $installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo] =
                            shorten_text($installed_plugins[$row['plg_name']]['extra_info'][$localizedInfo][0], 100);
                    }
                }
            }
        }
        $SQL->free($result);


        #get available plugins
        $dh = opendir(PATH . KLEEJA_PLUGINS_FOLDER);
        $available_plugins = array();
        while (false !== ($folder_name = readdir($dh)))
        {
            if (is_dir(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $folder_name) && preg_match('/[a-z0-9_.]{3,}/', $folder_name)) {
                if (empty($installed_plugins[$folder_name]))
                {
                    array_push($available_plugins,
                        array(
                            'name' => $folder_name,
                            'icon' => file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $folder_name . "/icon.png")
                                ? PATH . KLEEJA_PLUGINS_FOLDER . '/' . $folder_name . "/icon.png"
                                : $STYLE_PATH_ADMIN . 'images/plugin.png',

                        )
                    );
                }
            }
        }
        @closedir($dh);

        $no_plugins = sizeof($available_plugins) == 0 && sizeof($installed_plugins) == 0;

        $stylee = "admin_plugins";

        break;


    //
    //upload a plugin
    //
    case 'upload':


        $ERRORS = array();


        if(intval($userinfo['founder']) !== 1)
        {
            $ERRORS[] = $lang['HV_NOT_PRVLG_ACCESS'];
        }


        #is uploaded?
        if(empty($_FILES['plugin_file']['tmp_name']))
        {
            $ERRORS[] = $lang['CHOSE_F'];
        }



        #extract it to plugins folder
        if(!sizeof($ERRORS))
        {
            if(class_exists('ZipArchive'))
            {
                $zip = new ZipArchive;
                if ($zip->open($_FILES['plugin_file']['tmp_name']) === true)
                {
                    if(!$zip->extractTo(PATH . KLEEJA_PLUGINS_FOLDER))
                    {
                        $ERRORS[] = sprintf($lang['EXTRACT_ZIP_FAILED'], KLEEJA_PLUGINS_FOLDER);
                    }
                    $zip->close();
                }
                else
                {
                    $ERRORS[] = sprintf($lang['EXTRACT_ZIP_FAILED'], KLEEJA_PLUGINS_FOLDER);
                }
            }
            else
            {
                $ERRORS[] = $lang['NO_ZIP_ARCHIVE'];
            }
        }

        if(!empty($_FILES['plugin_file']['tmp_name']))
        {
            @unlink($_FILES['plugin_file']['tmp_name']);
        }


        if(!sizeof($ERRORS))
        {
            kleeja_admin_info($lang['NO_PROBLEM_AFTER_ZIP'], true, '', true, $action);
        }
        else
        {
            kleeja_admin_err('- ' . implode('<br>- ', $ERRORS), ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
        }

        break;


    //
    //install a plugin
    //
    case 'install':


        if(intval($userinfo['founder']) !== 1)
        {
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
            exit;
        }


        $plg_name = g('plg', 'str');


        if (empty($plg_name))
        {
            if (defined('DEBUG'))
            {
                exit('empty($plg_name)');
            }
            //no plugin selected? back
            redirect(ADMIN_PATH . "?cp=" . basename(__file__, '.php'));
        }
        else
        {
            if (!file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php'))
            {
                if (defined('DEBUG'))
                {
                    exit('!file_exists($plg_name)');
                }

                redirect(ADMIN_PATH . "?cp=" . basename(__file__, '.php'));
                exit;
            }

            #if already installed, show a message
            if (!empty(Plugins::getInstance()->installed_plugin_info($plg_name)))
            {
                kleeja_admin_info($lang['PLUGIN_EXISTS_BEFORE'], ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
                exit;
            }

            $kleeja_plugin = array();

            include PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php';

            $install_callback = $kleeja_plugin[$plg_name]['install'];
            $plugin_info = $kleeja_plugin[$plg_name]['information'];
            $plugin_first_run = false;

            if (!empty($kleeja_plugin[$plg_name]['first_run'][$config['language']]))
            {
                $plugin_first_run = $kleeja_plugin[$plg_name]['first_run'][$config['language']];
            }
            else if (!empty($kleeja_plugin[$plg_name]['first_run']['en']))
            {
                $plugin_first_run = $kleeja_plugin[$plg_name]['first_run']['en'];
            }


            #check if compatible with kleeja
            #'plugin_kleeja_version_min' => '1.8',
            # Max version of Kleeja that's required to run this plugin
            #'plugin_kleeja_version_max' => '3.8',

            if (version_compare(KLEEJA_VERSION, $plugin_info['plugin_kleeja_version_min'], '<'))
            {
                kleeja_admin_info($lang['PLUGIN_N_CMPT_KLJ'] . '<br>k:' . KLEEJA_VERSION . '|<|p.min:' . $plugin_info['plugin_kleeja_version_min'], ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
                exit;
            }

            if ($plugin_info['plugin_kleeja_version_max'] != '0')
            {
                if (version_compare(KLEEJA_VERSION, $plugin_info['plugin_kleeja_version_max'], '>'))
                {
                    kleeja_admin_info($lang['PLUGIN_N_CMPT_KLJ'] . '<br>k:' . KLEEJA_VERSION . '|>|p.max:' . $plugin_info['plugin_kleeja_version_max'], ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
                    exit;
                }
            }

            delete_cache('', true);


            if (is_array($plugin_info['plugin_description']))
            {
                $plugin_info['plugin_description'] = !empty($plugin_info['plugin_description']['en']) ? $plugin_info['plugin_description']['en'] : $plugin_info['plugin_description'][0];
            }


            #add to database
            $insert_query = array(
                'INSERT' => '`plg_name` ,`plg_ver`, `plg_author`, `plg_dsc`, `plg_icon`, `plg_uninstall`, `plg_instructions`, `plg_store`, `plg_files`',
                'INTO' => "{$dbprefix}plugins",
                'VALUES' => "'" . $SQL->escape($plg_name) . "','" . $SQL->escape($plugin_info['plugin_version']) . "', '" . $SQL->escape($plugin_info['plugin_developer']) . "','" . $SQL->escape($plugin_info['plugin_description']) . "', '', '', '', '', ''",
            );

            $SQL->build($insert_query);


            #may God protect you brother.
            if(is_callable($install_callback))
            {
                $install_callback($SQL->insert_id());
            }


            #show done, msg
            $text = '<h3>' . $lang['NEW_PLUGIN_ADDED'] . '</h3>';
            if ($plugin_first_run)
            {
                $text .= $plugin_first_run;
                $text .= '<br><hr><a href="' . ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '" class="btn btn-primary btn-lg">' . $lang['GO_BACK_BROWSER'] . '</a>';
            }
            else
            {
                $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '\');", 2000);</script>' . "\n";
            }


            $stylee = 'admin_info';
        }

        break;



    //
    //uninstall a plugin
    //
    case 'uninstall':

        if(intval($userinfo['founder']) !== 1)
        {
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
            exit;
        }


        $plg_name = g('plg', 'str');


        if (empty($plg_name))
        {
            if (defined('DEV_STAGE'))
            {
                exit('empty($plg_name)');
            }
            //no plugin selected? back
            redirect(ADMIN_PATH . "?cp=" . basename(__file__, '.php'));
        }
        else
        {
            if (!file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php'))
            {
                if (defined('DEV_STAGE'))
                {
                    exit('!file_exists($plg_name)');
                }

                redirect(ADMIN_PATH . "?cp=" . basename(__file__, '.php'));
                exit;
            }

            $kleeja_plugin = array();

            include PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php';

            $uninstall_callback = $kleeja_plugin[$plg_name]['uninstall'];

            if (!is_callable($uninstall_callback))
            {
                redirect(ADMIN_PATH . "?cp=" . basename(__file__, '.php'));
                exit;
            }


            $query = array(
                'SELECT' => "plg_id",
                'FROM' => "{$dbprefix}plugins",
                'WHERE' => "plg_name='" . $SQL->escape($plg_name) . "'"
            );


            $result = $SQL->build($query);

            $pluginDatabaseInfo = $SQL->fetch($result);


            #sad to see you go, brother
            $uninstall_callback(!empty($pluginDatabaseInfo) ? $pluginDatabaseInfo['plg_id'] : 0);


            delete_cache('', true);

            #remove from database
            $query_del = array(
                'DELETE' => "`{$dbprefix}plugins`",
                'WHERE' => "plg_name='" . $SQL->escape($plg_name) . "'"
            );

            $SQL->build($query_del);

            #show done, msg
            $text = '<h3>' . $lang['PLUGIN_DELETED'] . '</h3>';
            $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '\');", 2000);</script>' . "\n";


            $stylee = 'admin_info';
        }

        break;


    //
    //disable a plugin
    //
    case 'disable':
    case 'enable':

        if(intval($userinfo['founder']) !== 1)
        {
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], ADMIN_PATH . '?cp=' . basename(__file__, '.php'));
            exit;
        }


        $plg_name = g('plg', 'str');


        if (empty($plg_name))
        {
            if (defined('DEV_STAGE'))
            {
                exit('empty($plg_name)');
            }
            //no plugin selected? back
            redirect(ADMIN_PATH . "?cp=" . basename(__file__, '.php'));
        }
        else
        {
            #update database
            $update_query = array(
                'UPDATE' => "{$dbprefix}plugins",
                'SET' => "plg_disabled=" . ($case == 'disable' ? 1 : 0),
                'WHERE' => "plg_name='" . $SQL->escape($plg_name) . "'"
            );


            $SQL->build($update_query);


            delete_cache('', true);

            #show done, msg
            $text = '<h3>' . $lang['PLGUIN_DISABLED_ENABLED'] . '</h3>';
            $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . ADMIN_PATH . '?cp=' . basename(__file__, '.php') . '\');", 2000);</script>' . "\n";


            $stylee = 'admin_info';
        }

        break;


endswitch;
