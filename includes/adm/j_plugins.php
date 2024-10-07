<?php
/**
 *
 * @package adm
 * @copyright (c) 2007 Kleeja.net
 * @license http://www.kleeja.net/license
 *
 */

// not for directly open
if (! defined('IN_ADMIN'))
{
    exit();
}

//turn time-limit off
@set_time_limit(0);

//get current case
$case = g('case', 'str', 'installed');

//set _get form key
$GET_FORM_KEY = kleeja_add_form_key_get('adm_plugins_get');
$H_FORM_KEYS  = kleeja_add_form_key('adm_plugins');

$action = ADMIN_PATH . '?cp=' . basename(__FILE__, '.php');

$plugin_install_link       = $action . '&amp;case=install&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_uninstall_link     = $action . '&amp;case=uninstall&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_enable_link        = $action . '&amp;case=enable&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_disable_link       = $action . '&amp;case=disable&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_download_link      = $action . '&amp;case=download&amp;' . $GET_FORM_KEY . '&amp;plg=';
$plugin_delete_folder_link = $action . '&amp;case=dfolder&amp;' . $GET_FORM_KEY . '&amp;plg=';

//check _GET Csrf token
if (! empty($case) && in_array($case, ['install', 'uninstall', 'enable', 'disable', 'download', 'dfolder']))
{
    if (! kleeja_check_form_key_get('adm_plugins_get'))
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['INVALID_GET_KEY']);
    }
}

//check _POST Csrf token
if (ip('newplugin'))
{
    if (! kleeja_check_form_key('adm_plugins'))
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action);
    }

    $case = 'upload';
}

switch ($case):

    default:
    case 'local':
    case 'store':
    case 'check':

        // Get installed plugins
        $query = [
            'SELECT'   => 'plg_id, plg_name, plg_ver, plg_disabled, plg_author, plg_dsc',
            'FROM'     => "{$dbprefix}plugins",
            'ORDER BY' => 'plg_id ASC',
        ];

        $result = $SQL->build($query);

        $installed_plugins = [];

        while ($row = $SQL->fetch($result))
        {
            if (! file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $row['plg_name'] . '/init.php'))
            {
                continue;
            }

            if ($case == 'check' && $row['plg_disabled'] == 1)
            {
                continue;
            }

            $installed_plugins[$row['plg_name']] = $row;

            $installed_plugins[$row['plg_name']]['extra_info'] = Plugins::getInstance()->installed_plugin_info($row['plg_name']);

            $installed_plugins[$row['plg_name']]['icon'] = file_exists(
                PATH . KLEEJA_PLUGINS_FOLDER . '/' . $row['plg_name'] . '/icon.png'
            )
                ? PATH . KLEEJA_PLUGINS_FOLDER . '/' . $row['plg_name'] . '/icon.png'
                : $STYLE_PATH_ADMIN . 'images/plugin.png';

            $installed_plugins[$row['plg_name']]['has_settings_page'] = ! empty(
                $installed_plugins[$row['plg_name']]['extra_info']['settings_page']
            ) && ! preg_match('/^https?:\/\//', $installed_plugins[$row['plg_name']]['extra_info']['settings_page']);


            foreach (['plugin_title', 'plugin_description'] as $localized_info)
            {
                if (! empty($installed_plugins[$row['plg_name']]['extra_info'][$localized_info]) &&
                    is_array($installed_plugins[$row['plg_name']]['extra_info'][$localized_info]))
                {
                    if (! empty($installed_plugins[$row['plg_name']]['extra_info'][$localized_info][$config['language']]))
                    {
                        $installed_plugins[$row['plg_name']]['extra_info'][$localized_info] =
                            shorten_text($installed_plugins[$row['plg_name']]['extra_info'][$localized_info][$config['language']], 100);
                    }
                    elseif (! empty($installed_plugins[$row['plg_name']]['extra_info'][$localized_info]['en']))
                    {
                        $installed_plugins[$row['plg_name']]['extra_info'][$localized_info] =
                            shorten_text($installed_plugins[$row['plg_name']]['extra_info'][$localized_info]['en'], 100);
                    }
                    else
                    {
                        $installed_plugins[$row['plg_name']]['extra_info'][$localized_info] =
                            shorten_text($installed_plugins[$row['plg_name']]['extra_info'][$localized_info][0], 100);
                    }
                }
            }
        }
        $SQL->freeresult($result);

        //get available plugins
        $dh                = opendir(PATH . KLEEJA_PLUGINS_FOLDER);
        $available_plugins = [];
        while (false !== ($folder_name = readdir($dh)))
        {
            if (is_dir(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $folder_name) && preg_match('/[a-z0-9_.]{3,}/', $folder_name))
            {
                if (empty($installed_plugins[$folder_name]))
                {
                    array_push($available_plugins, [
                        'name' => $folder_name,
                        'icon' => file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $folder_name . '/icon.png')
                            ? PATH . KLEEJA_PLUGINS_FOLDER . '/' . $folder_name . '/icon.png'
                            : $STYLE_PATH_ADMIN . 'images/plugin.png',
                    ]);
                }
            }
        }
        @closedir($dh);

        $no_plugins           = sizeof($available_plugins) == 0 && sizeof($installed_plugins) == 0;
        $no_installed_plugins = sizeof($installed_plugins) == 0;

        $stylee = 'admin_plugins';

        //do not proceed if not store case
        if (! in_array($case, ['store', 'check']))
        {
            break;
        }

        // plugins avilable in kleeja remote catalog
        if (! ($store_catalog = $cache->get('store_catalog')))
        {
            $store_link = 'https://raw.githubusercontent.com/kleeja-official/store-catalog/master/catalog.json';

            $store_catalog = FetchFile::make($store_link)->get();
            $store_catalog = json_decode($store_catalog, true);

            if (json_last_error() == JSON_ERROR_NONE)
            {
                $cache->save('store_catalog', $store_catalog);
            }
        }

        // make an array for all plugins in kleeja remote catalog
        // that are not exsisted locally.
        $store_plugins           = [];
        $available_plugins_names = array_column($available_plugins, 'name');

        foreach ($store_catalog as $plugin_info)
        {
            if ($plugin_info['type'] != 'plugin')
            {
                continue;
            }

            if (isset($plugin_info['preview']) && defined('DEV_STAGE'))
            {
                $plugin_file = $plugin_info['preview'];
            }
            elseif (isset($plugin_info['file']))
            {
                $plugin_file = $plugin_info['file'];
            }
            else
            {
                continue;
            }


            if ($case == 'store' && (in_array($plugin_info['name'], $available_plugins_names) ||
                 ! empty($installed_plugins[$plugin_info['name']]))
            ) {
                continue;
            }

            // is there a new version of this in the store
            elseif ($case == 'check' && (! empty($installed_plugins[$plugin_info['name']]) &&
                version_compare(
                    strtolower($installed_plugins[$plugin_info['name']]['extra_info']['plugin_version']),
                    strtolower($plugin_file['version']),
                    '>='
                ) || empty($installed_plugins[$plugin_info['name']]))
            ) {
                continue;
            }

            $store_plugins[$plugin_info['name']] = [
                'name'            => $plugin_info['name'],
                'developer'       => $plugin_info['developer'],
                'version'         => $plugin_file['version'],
                'title'           => ! empty($plugin_info['title'][$config['language']]) ? $plugin_info['title'][$config['language']] : $plugin_info['title']['en'],
                'description'     => ! empty($plugin_info['description'][$config['language']]) ? $plugin_info['description'][$config['language']] : $plugin_info['description']['en'],
                'website'         => $plugin_info['website'],
                'current_version' => ! empty($installed_plugins[$plugin_info['name']]) ? strtolower($installed_plugins[$plugin_info['name']]['extra_info']['plugin_version']) : '',
                'kj_min_version'  => $plugin_info['kleeja_version']['min'],
                'kj_max_version'  => $plugin_info['kleeja_version']['max'],
                'kj_version_cmtp' => sprintf($lang['KLJ_VER_NO_PLUGIN'], $plugin_info['kleeja_version']['min'], $plugin_info['kleeja_version']['max']),
                'icon'            => $plugin_info['icon'],
                'NotCompatible'   => version_compare(strtolower($plugin_info['kleeja_version']['min']), KLEEJA_VERSION, '<=')
                && version_compare(strtolower($plugin_info['kleeja_version']['max']), KLEEJA_VERSION, '>=')
                ? false : true,
            ];
        }

        $store_plugins_count = sizeof($store_plugins);

        break;

    //
        //upload a plugin
    //
    case 'upload':
        $ERRORS = [];

        if (intval($userinfo['founder']) !== 1)
        {
            $ERRORS[] = $lang['HV_NOT_PRVLG_ACCESS'];
        }

        //is uploaded?
        if (empty($_FILES['plugin_file']['tmp_name']))
        {
            $ERRORS[] = $lang['CHOSE_F'];
        }

        //extract it to plugins folder
        if (! sizeof($ERRORS))
        {
            if (class_exists('ZipArchive'))
            {
                $zip = new ZipArchive();

                if ($zip->open($_FILES['plugin_file']['tmp_name']) === true)
                {
                    if (! $zip->extractTo(PATH . KLEEJA_PLUGINS_FOLDER))
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

        if (! empty($_FILES['plugin_file']['tmp_name']))
        {
            @unlink($_FILES['plugin_file']['tmp_name']);
        }

        if (! sizeof($ERRORS))
        {
            kleeja_admin_info($lang['NO_PROBLEM_AFTER_ZIP'], $action);
        }
        else
        {
            kleeja_admin_err('- ' . implode('<br>- ', $ERRORS), $action);
        }

        break;

    //
        //install a plugin
    //
    case 'install':

        if (intval($userinfo['founder']) !== 1)
        {
            header('HTTP/1.0 401 Unauthorized');
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], $action);
        }

        $plg_name = g('plg', 'str');

        if (empty($plg_name))
        {
            if (defined('DEV_STAGE'))
            {
                exit('empty($plg_name)');
            }
            //no plugin selected? back
            redirect(ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));
        }
        else
        {
            if (! file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php'))
            {
                if (defined('DEV_STAGE'))
                {
                    exit('!file_exists($plg_name)');
                }

                redirect(ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));

                exit;
            }

            //if already installed, show a message
            if (! empty(Plugins::getInstance()->installed_plugin_info($plg_name)))
            {
                kleeja_admin_info($lang['PLUGIN_EXISTS_BEFORE'], true, '', true, ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));

                exit;
            }

            $kleeja_plugin = [];

            //don't show mysql errors
            if (! defined('SQL_NO_ERRORS'))
            {
                define('SQL_NO_ERRORS', true);
            }

            @include PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php';

            $install_callback = $kleeja_plugin[$plg_name]['install'];
            $plugin_info      = $kleeja_plugin[$plg_name]['information'];
            $plugin_first_run = false;

            if (! empty($kleeja_plugin[$plg_name]['first_run'][$config['language']]))
            {
                $plugin_first_run = $kleeja_plugin[$plg_name]['first_run'][$config['language']];
            }
            elseif (! empty($kleeja_plugin[$plg_name]['first_run']['en']))
            {
                $plugin_first_run = $kleeja_plugin[$plg_name]['first_run']['en'];
            }

            //check if compatible with kleeja
            //'plugin_kleeja_version_min' => '1.8',
            // Max version of Kleeja that's required to run this plugin
            //'plugin_kleeja_version_max' => '3.8',
            //3.1.0 < 3.1.0

            if (! empty($plugin_info['plugin_kleeja_version_min']))
            {
                if (version_compare(KLEEJA_VERSION, $plugin_info['plugin_kleeja_version_min'], '<'))
                {
                    kleeja_admin_info(
                        $lang['PACKAGE_N_CMPT_KLJ'] . '<br>k:' . KLEEJA_VERSION . '|<|p.min:' . $plugin_info['plugin_kleeja_version_min'],
                        true,
                        '',
                        true,
                        ADMIN_PATH . '?cp=' . basename(__FILE__, '.php')
                    );

                    exit;
                }
            }

            if (! empty($plugin_info['plugin_kleeja_version_max']))
            {
                if (version_compare(KLEEJA_VERSION, $plugin_info['plugin_kleeja_version_max'], '>'))
                {
                    kleeja_admin_info(
                        $lang['PACKAGE_N_CMPT_KLJ'] . '<br>k:' . KLEEJA_VERSION . '|>|p.max:' . $plugin_info['plugin_kleeja_version_max'],
                        true,
                        '',
                        true,
                        ADMIN_PATH . '?cp=' . basename(__FILE__, '.php')
                    );

                    exit;
                }
            }

            delete_cache('', true);

            if (is_array($plugin_info['plugin_description']))
            {
                $plugin_info['plugin_description'] = ! empty($plugin_info['plugin_description']['en']) ? $plugin_info['plugin_description']['en'] : $plugin_info['plugin_description'][0];
            }

            //add to database
            $insert_query = [
                'INSERT' => '`plg_name` ,`plg_ver`, `plg_author`, `plg_dsc`, `plg_icon`, `plg_uninstall`, `plg_instructions`, `plg_store`, `plg_files`',
                'INTO'   => "{$dbprefix}plugins",
                'VALUES' => "'" . $SQL->escape($plg_name) . "','" . $SQL->escape($plugin_info['plugin_version']) . "', '" . $SQL->escape($plugin_info['plugin_developer']) . "','" . $SQL->escape($plugin_info['plugin_description']) . "', '', '', '', '', ''",
            ];

            $SQL->build($insert_query);

            //may God protect you brother.
            if (is_callable($install_callback))
            {
                $install_callback($SQL->insert_id());
            }

            //show done, msg
            $text = '<h3>' . $lang['NEW_PLUGIN_ADDED'] . '</h3>';

            if ($plugin_first_run)
            {
                $text .= $plugin_first_run;
                $text .= '<br><hr><a href="' . ADMIN_PATH . '?cp=' . basename(__FILE__, '.php') . '" class="btn btn-primary btn-lg">' . $lang['GO_BACK_BROWSER'] . '</a>';
            }
            else
            {
                $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . ADMIN_PATH . '?cp=' . basename(__FILE__, '.php') . '\');", 2000);</script>' . "\n";
            }

            $stylee = 'admin_info';
        }

        break;

    //
        //uninstall a plugin
    //
    case 'uninstall':

        if (intval($userinfo['founder']) !== 1)
        {
            header('HTTP/1.0 401 Unauthorized');
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], $action);
        }

        $plg_name = g('plg', 'str');

        if (empty($plg_name))
        {
            if (defined('DEV_STAGE'))
            {
                exit('empty($plg_name)');
            }

            //no plugin selected? back
            redirect(ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));
        }
        else
        {
            if (! file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php'))
            {
                if (defined('DEV_STAGE'))
                {
                    exit('!file_exists($plg_name)');
                }

                redirect(ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));

                exit;
            }

            $kleeja_plugin = [];

            include PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plg_name . '/init.php';

            $uninstall_callback = $kleeja_plugin[$plg_name]['uninstall'];

            if (! is_callable($uninstall_callback))
            {
                redirect(ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));

                exit;
            }

            $query = [
                'SELECT' => 'plg_id',
                'FROM'   => "{$dbprefix}plugins",
                'WHERE'  => "plg_name='" . $SQL->escape($plg_name) . "'"
            ];

            $result = $SQL->build($query);

            $pluginDatabaseInfo = $SQL->fetch($result);

            //sad to see you go, brother
            $uninstall_callback(! empty($pluginDatabaseInfo) ? $pluginDatabaseInfo['plg_id'] : 0);

            delete_cache('', true);

            //remove from database
            $query_del = [
                'DELETE' => "`{$dbprefix}plugins`",
                'WHERE'  => "plg_name='" . $SQL->escape($plg_name) . "'"
            ];

            $SQL->build($query_del);

            //show done, msg
            $text = '<h3>' . sprintf($lang['ITEM_DELETED'], $plg_name) . '</h3>';
            $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . ADMIN_PATH . '?cp=' . basename(__FILE__, '.php') . '\');", 2000);</script>' . "\n";

            $stylee = 'admin_info';
        }

        break;

    //
        // disable a plugin
    //
    case 'disable':
    case 'enable':

        if (intval($userinfo['founder']) !== 1)
        {
            header('HTTP/1.0 401 Unauthorized');
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], $action);
        }

        $plg_name = g('plg', 'str');

        if (empty($plg_name))
        {
            if (defined('DEV_STAGE'))
            {
                exit('empty($plg_name)');
            }
            //no plugin selected? back
            redirect(ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));
        }
        else
        {
            //update database
            $update_query = [
                'UPDATE' => "{$dbprefix}plugins",
                'SET'    => 'plg_disabled=' . ($case == 'disable' ? 1 : 0),
                'WHERE'  => "plg_name='" . $SQL->escape($plg_name) . "'"
            ];

            $SQL->build($update_query);

            delete_cache('', true);

            //show done, msg
            $text = '<h3>' . $lang['PLGUIN_DISABLED_ENABLED'] . '</h3>';
            $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . ADMIN_PATH . '?cp=' . basename(__FILE__, '.php') . '\');", 2000);</script>' . "\n";

            $stylee = 'admin_info';
        }

        break;

    case 'download':

        if (intval($userinfo['founder']) !== 1)
        {
            header('HTTP/1.0 401 Unauthorized');
            kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
        }

        $plugin_name = g('plg');

        $is_update = false;

        //if plugin exists before, then trigger update action. rename folder to rollback in case of failure
        if (file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name . '/init.php'))
        {
            $is_update = true;

            if (! rename(
                PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name,
                PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name . '_backup'
            ))
            {
                if (file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name))
                {
                    kleeja_unlink(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name);
                }
            }
        }

        // plugins avilable in kleeja store
        $store_link = 'https://raw.githubusercontent.com/kleeja-official/store-catalog/master/catalog.json';

        $catalog_plugins = FetchFile::make($store_link)->get();

        if ($catalog_plugins)
        {
            $catalog_plugins = json_decode($catalog_plugins, true);

            $store_plugins = [];

            // make an arry for all plugins in kleeja store that not included in our server
            foreach ($catalog_plugins as $plugin_info)
            {
                if ($plugin_info['type'] != 'plugin')
                {
                    continue;
                }

                if (isset($plugin_info['preview']) && defined('DEV_STAGE'))
                {
                    $plugin_file = $plugin_info['preview'];
                }
                elseif (isset($plugin_info['file']))
                {
                    $plugin_file = $plugin_info['file'];
                }
                else
                {
                    continue;
                }


                $store_plugins[$plugin_info['name']] = [
                    'name'           => $plugin_info['name'],
                    'plg_version'    => $plugin_file['version'],
                    'url'            => $plugin_file['url'],
                    'kj_min_version' => $plugin_info['kleeja_version']['min'],
                    'kj_max_version' => $plugin_info['kleeja_version']['max'],
                ];
            }

            // this plugin is hosted in our store
            if (isset($store_plugins[$plugin_name]))
            {
                // check if the version of the plugin is compatible with our kleeja version or not
                if (
                    version_compare(strtolower($store_plugins[$plugin_name]['kj_min_version']), KLEEJA_VERSION, '<=')
                    && version_compare(strtolower($store_plugins[$plugin_name]['kj_max_version']), KLEEJA_VERSION, '>=')
                ) {
                    $plugin_name_link = $store_plugins[$plugin_name]['url'];

                    $plugin_archive = FetchFile::make($plugin_name_link)
                        ->setDestinationPath(PATH . 'cache/' . $plugin_name . '.zip')
                        ->isBinaryFile(true)
                        ->get();

                    if ($plugin_archive)
                    {
                        if (file_exists(PATH . 'cache/' . $plugin_name . '.zip'))
                        {
                            $zip = new ZipArchive();

                            if ($zip->open(PATH . 'cache/' . $plugin_name . '.zip') === true)
                            {
                                if ($zip->extractTo(PATH . KLEEJA_PLUGINS_FOLDER))
                                {
                                    // uploaded plugin's archive has different name, so we change it
                                    rename(
                                        PATH . KLEEJA_PLUGINS_FOLDER . '/' . trim($zip->getNameIndex(0), '/'),
                                        PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name
                                    );

                                    $zip->close();

                                    // we dont need the zip file anymore
                                    kleeja_unlink(PATH . 'cache/' . $plugin_name . '.zip');

                                    // download or update msg
                                    $adminAjaxContent = '1:::' . sprintf($lang[$is_update ? 'ITEM_UPDATED' : 'ITEM_DOWNLOADED'], $plugin_name);

                                    //in case of update, delete back up version
                                    if (file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name . '_backup'))
                                    {
                                        kleeja_unlink(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name . '_backup');
                                    }
                                }
                                else
                                {
                                    $adminAjaxContent = '1003:::' . sprintf($lang['EXTRACT_ZIP_FAILED'], KLEEJA_PLUGINS_FOLDER);
                                }
                            }
                        }
                        else
                        {
                            $adminAjaxContent = '1004:::' . $lang['DOWNLOADED_FILE_NOT_FOUND'];
                        }
                    }
                    else
                    {
                        $adminAjaxContent = '1005:::' . $lang['STORE_SERVER_ERROR'];
                    }
                }
                else
                {
                    $adminAjaxContent = '1006:::' . $lang['PACKAGE_N_CMPT_KLJ'];
                }
            }
            else
            {
                $adminAjaxContent = '1007:::' . sprintf($lang['PACKAGE_REMOTE_FILE_MISSING'], $plugin_name);
            }
        }
        else
        {
            $adminAjaxContent = '1008:::' . $lang['STORE_SERVER_ERROR'];
        }

        //in case of update failure, rollback to current plugin version
        if (strpos($adminAjaxContent, '1:::') === false)
        {
            if (file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name . '_backup'))
            {
                rename(
                    PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name . '_backup',
                    PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name
                );
            }
        }

        break;

    case 'dfolder':

        $plugin_name = preg_replace('/[^a-z0-9_\-\.]/i', '', g('plg'));

        $plugin_folder_path = PATH . KLEEJA_PLUGINS_FOLDER . '/' . $plugin_name;

        if (file_exists($plugin_folder_path))
        {
            if (! is_writable($plugin_folder_path))
            {
                @chmod($plugin_folder_path, K_DIR_CHMOD);
            }

            kleeja_unlink($plugin_folder_path);
        }

        if (! file_exists($plugin_folder_path))
        {
            kleeja_admin_info(sprintf($lang['ITEM_DELETED'], $plugin_name), $action . '&amp;case=local');
        }

        kleeja_admin_err($lang['ERROR_TRY_AGAIN'], $action);

        break;
endswitch;
