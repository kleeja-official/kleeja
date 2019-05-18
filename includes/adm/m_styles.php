<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (! defined('IN_ADMIN'))
{
    exit();
}

//set _get form key
$GET_FORM_KEY   = kleeja_add_form_key_get('adm_styles_get');
$H_FORM_KEYS    = kleeja_add_form_key('adm_styles');


$action                      = ADMIN_PATH . '?cp=' . basename(__file__, '.php');
$style_select_link           = $action . '&amp;case=select&amp;' . $GET_FORM_KEY . '&amp;style=';
$style_download_link         = $action . '&amp;case=download&amp;' . $GET_FORM_KEY . '&amp;style=';
$style_delete_link           = $action . '&amp;case=dfolder&amp;' . $GET_FORM_KEY . '&amp;style=';
$style_upload_link           = $action . '&amp;case=upload';

$stylee       = 'admin_styles';
$case         = g('case', 'str', 'local');

//check _GET Csrf token
if (! empty($case) && in_array($case, ['select', 'download', 'dfolder']))
{
    if (! kleeja_check_form_key_get('adm_styles_get'))
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['INVALID_GET_KEY'], $action);
    }
}

//check _POST Csrf token
if (ip('newstyle'))
{
    if (! kleeja_check_form_key('adm_styles'))
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

    //get styles
    $available_styles = [];

    if ($dh = @opendir(PATH . 'styles'))
    {
        while (false !== ($folder_name = readdir($dh)))
        {
            if (is_dir(PATH . 'styles/' . $folder_name) && preg_match('/[a-z0-9_.]{3,}/', $folder_name))
            {
                //info
                $style_info_arr = [
                    'name'      => $folder_name,
                    'desc'      => '',
                    'copyright' => '',
                    'version'   => ''
                ];

                if (($style_info = kleeja_style_info($folder_name)) != false)
                {
                    foreach (['name', 'desc', 'copyright', 'version'] as $InfoKey)
                    {
                        if (array_key_exists($InfoKey, $style_info))
                        {
                            if (is_array($style_info[$InfoKey]))
                            {
                                $style_info_arr[$InfoKey] = ! empty($style_info[$InfoKey][$config['language']])
                                    ? htmlspecialchars($style_info[$InfoKey][$config['language']])
                                    : htmlspecialchars($style_info[$InfoKey]['en']);
                            }
                            else
                            {
                                $style_info_arr[$InfoKey] = htmlspecialchars($style_info[$InfoKey]);
                            }
                        }
                    }
                }

                $available_styles[$folder_name] = [
                    'name'            => $folder_name,
                    'is_default'      => $config['style'] == $folder_name ? true : false,
                    'link_mk_default' => basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;style_choose=' . $folder_name,
                    'icon'            => file_exists(PATH . 'styles/' . $folder_name . '/screenshot.png')
                                            ? PATH . 'styles/' . $folder_name . '/screenshot.png'
                                            : $STYLE_PATH_ADMIN . 'images/style.png',
                    'info' => $style_info_arr
                ];
            }
        }

        @closedir($dh);
    }

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

        // make an array for all styles in kleeja remote catalog
        // that are not exsisted locally.
        $store_styles           = [];
        $available_styles_names = array_column($available_styles, 'name');

        foreach ($store_catalog as $style_info)
        {
            if ($style_info['type'] != 'style')
            {
                continue;
            }

            if ($case == 'store' && ! empty($available_styles[$style_info['name']]))
            {
                continue;
            }

            // is there a new version of this in the store
            elseif (
                $case == 'check' && (! empty($available_styles[$style_info['name']]['info']['version']) &&
                    version_compare(
                        strtolower($available_styles[$style_info['name']]['info']['version']),
                        strtolower($style_info['file']['version']),
                        '>='
                    ) ||  empty($available_styles[$style_info['name']]['info']['version']))
            ) {
                continue;
            }

            $store_styles[$style_info['name']] = [
                'name'             => $style_info['name'],
                'developer'        => $style_info['developer'],
                'version'          => $style_info['file']['version'],
                'title'            => ! empty($style_info['title'][$config['language']]) ? $style_info['title'][$config['language']] : $style_info['title']['en'],
                'website'          => $style_info['website'],
                'current_version'  => ! empty($available_styles[$style_info['name']]) ? strtolower($available_styles[$style_info['name']]['info']['version']) : '',
                'kj_min_version'   => $style_info['kleeja_version']['min'],
                'kj_max_version'   => $style_info['kleeja_version']['max'],
                'kj_version_cmtp'  => sprintf($lang['KLJ_VER_NO_PLUGIN'], $style_info['kleeja_version']['min'], $style_info['kleeja_version']['max']),
                'icon'             => $style_info['icon'],
                'NotCompatible'    => version_compare(strtolower($style_info['kleeja_version']['min']), KLEEJA_VERSION, '<=')
                && version_compare(strtolower($style_info['kleeja_version']['max']), KLEEJA_VERSION, '>=')
                ? false : true,
            ];
        }

        $store_styles_count = sizeof($store_styles);

break;

case 'select':

    $style_name = preg_replace('/[^a-z0-9_\-\.]/i', '', g('style'));

    //if empty, let's ignore it
    if (empty($style_name))
    {
        redirect(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
    }

    //
    //check if this style depend on other style and
    //check kleeja version that required by this style
    //
    if (($style_info = kleeja_style_info($style_name)) != false)
    {
        if (isset($style_info['depend_on']) && ! is_dir(PATH . 'styles/' . $style_info['depend_on']))
        {
            kleeja_admin_err(sprintf($lang['DEPEND_ON_NO_STYLE_ERR'], $style_info['depend_on']));
        }

        if (isset($style_info['kleeja_version']) && version_compare(strtolower($style_info['kleeja_version']), strtolower(KLEEJA_VERSION), '>'))
        {
            kleeja_admin_err(sprintf($lang['KLJ_VER_NO_STYLE_ERR'], $style_info['kleeja_version']));
        }

        //is this style require some plugins to be installed
        if (isset($style_info['plugins_required']))
        {
            $plugins_required = explode(',', $style_info['plugins_required']);
            $plugins_required = array_map('trim', $plugins_required);

            $query = [
                'SELECT' => 'plg_name, plg_disabled',
                'FROM'   => "{$dbprefix}plugins",
            ];

            $result = $SQL->build($query);

            if ($SQL->num_rows($result) != 0)
            {
                $plugins_required = array_flip($plugins_required);
                while ($row = $SQL->fetch_array($result))
                {
                    if (in_array($row['plg_name'], $plugins_required) and $row['plg_disabled'] != 1)
                    {
                        unset($plugins_required[$row['plg_name']]);
                    }
                }
            }

            $SQL->freeresult($result);

            $plugins_required = array_flip($plugins_required);

            if (sizeof($plugins_required))
            {
                kleeja_admin_err(sprintf($lang['PLUGINS_REQ_NO_STYLE_ERR'], implode(', ', $plugins_required)));
            }
        }
    }


    //make it as default
    update_config('style', $style_name);
    update_config('style_depend_on', isset($style_info['depend_on']) ? $style_info['depend_on'] : '');

    //delete all cache to get new style
    delete_cache('', true);

    //show msg
    kleeja_admin_info(sprintf($lang['STYLE_NOW_IS_DEFAULT'], $style_name), $action);

break;

case 'upload':

    if (intval($userinfo['founder']) !== 1)
    {
        $ERRORS[] = $lang['HV_NOT_PRVLG_ACCESS'];
    }


    $ERRORS = [];

    //is uploaded?
    if (empty($_FILES['style_file']['tmp_name']))
    {
        $ERRORS[] = $lang['CHOSE_F'];
    }


    //extract it to plugins folder
    if (! sizeof($ERRORS))
    {
        if (class_exists('ZipArchive'))
        {
            $zip = new ZipArchive;

            if ($zip->open($_FILES['style_file']['tmp_name']) === true)
            {
                if (! $zip->extractTo(PATH . 'styles'))
                {
                    $ERRORS[] = sprintf($lang['EXTRACT_ZIP_FAILED'], 'styles');
                }
                $zip->close();
            }
            else
            {
                $ERRORS[] =  sprintf($lang['EXTRACT_ZIP_FAILED'], 'styles');
            }
        }
        else
        {
            $ERRORS[] = $lang['NO_ZIP_ARCHIVE'];
        }
    }

    if (! empty($_FILES['style_file']['tmp_name']))
    {
        @unlink($_FILES['style_file']['tmp_name']);
    }


    if (! sizeof($ERRORS))
    {
        kleeja_admin_info($lang['NO_PROBLEM_AFTER_ZIP'], true, '', true, $action);
    }
    else
    {
        kleeja_admin_err('- ' . implode('<br>- ', $ERRORS), $action);
    }

    break;

    case 'dfolder':

        $style_name = preg_replace('/[^a-z0-9_\-\.]/i', '', g('style'));

        //can not delete default style
        if ($config['style'] === $style_name)
        {
            kleeja_admin_info($lang['CANT_DEL_DEFAULT_STYLE'], true, '', true, $action);
        }

        $style_folder_path = PATH . 'styles/' . $style_name;

        if (file_exists($style_folder_path))
        {
            if (! is_writable($style_folder_path))
            {
                @chmod($style_folder_path, K_DIR_CHMOD);
            }

            kleeja_unlink($style_folder_path);
        }


        if (! file_exists($style_folder_path))
        {
            kleeja_admin_info(sprintf($lang['ITEM_DELETED'], $style_name), $action);
        }

        kleeja_admin_err($lang['ERROR_TRY_AGAIN'], $action);

    break;


case 'download':

    if (intval($userinfo['founder']) !== 1)
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    $style_name = g('style');

    $is_update = false;


    if (! is_writable(PATH . 'styles'))
    {
        @chmod(PATH . 'styles', K_DIR_CHMOD);
    }

    //if style exists before, then trigger update action. rename folder to rollback in case of failure
    if (file_exists(PATH . 'styles/' . $style_name))
    {
        $is_update = true;

        if (! rename(
            PATH . 'styles/' . $style_name,
            PATH . 'styles/' . $style_name . '_backup'
        ))
        {
            if (file_exists(PATH . 'styles/' . $style_name))
            {
                kleeja_unlink(PATH . 'styles/' . $style_name);
            }
        }
    }

    // plugins avilable in kleeja store
    $store_link = 'https://raw.githubusercontent.com/kleeja-official/store-catalog/master/catalog.json';

    $catalog_styles = FetchFile::make($store_link)->get();

    if ($catalog_styles)
    {
        $catalog_styles = json_decode($catalog_styles, true);

        $store_styles = [];

        // make an arry for all plugins in kleeja store that not included in our server
        foreach ($catalog_styles as $style_info)
        {
            if ($style_info['type'] != 'style')
            {
                continue;
            }

            $store_styles[$style_info['name']] = [
                'name'           => $style_info['name'] ,
                'plg_version'    => $style_info['file']['version'] ,
                'url'            => $style_info['file']['url'] ,
                'kj_min_version' => $style_info['kleeja_version']['min'] ,
                'kj_max_version' => $style_info['kleeja_version']['max'] ,
            ];
        }

        // this style is hosted in our store
        if (isset($store_styles[$style_name]))
        {
            // check if the version of the plugin is compatible with our kleeja version or not
            if (
                version_compare(strtolower($store_styles[$style_name]['kj_min_version']), KLEEJA_VERSION, '<=')
                && version_compare(strtolower($store_styles[$style_name]['kj_max_version']), KLEEJA_VERSION, '>=')
                ) {
                $style_name_link = $store_styles[$style_name]['url'];

                $style_archive = FetchFile::make($style_name_link)
                                ->setDestinationPath(PATH . 'cache/' . $style_name . '.zip')
                                ->isBinaryFile(true)
                                ->get();

                if ($style_archive)
                {
                    if (file_exists(PATH . 'cache/' . $style_name . '.zip'))
                    {
                        $zip = new ZipArchive();

                        if ($zip->open(PATH . 'cache/' . $style_name . '.zip') === true)
                        {
                            if ($zip->extractTo(PATH . 'styles'))
                            {
                                // we dont need the zip file anymore
                                kleeja_unlink(PATH . 'cache/' . $style_name . '.zip');

                                // uploaded style's archive has different name, so we change it
                                rename(
                                    PATH . 'styles/' . trim($zip->getNameIndex(0), '/'),
                                    PATH . 'styles/' . $style_name
                                );

                                $zip->close();

                                // download or update msg
                                $adminAjaxContent = '1:::' . sprintf($lang[$is_update  ? 'ITEM_UPDATED' : 'ITEM_DOWNLOADED'], $style_name);

                                //in case of update, delete back up version
                                if (file_exists(PATH . 'styles/' . $style_name . '_backup'))
                                {
                                    kleeja_unlink(PATH . 'styles/' . $style_name . '_backup');
                                }
                            }
                            else
                            {
                                $adminAjaxContent = '1003:::' . sprintf($lang['EXTRACT_ZIP_FAILED'], PATH . 'styles');
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
            $adminAjaxContent = '1007:::' . sprintf($lang['PACKAGE_REMOTE_FILE_MISSING'], $style_name);
        }
    }
    else
    {
        $adminAjaxContent = '1008:::' . $lang['STORE_SERVER_ERROR'];
    }


    //in case of update failure, rollback to current plugin version
    if (strpos($adminAjaxContent, '1:::') === false)
    {
        if (file_exists(PATH . 'styles/' . $style_name . '_backup'))
        {
            rename(
                PATH . 'styles/' . $style_name . '_backup',
                PATH . 'styles/' . $style_name
            );
        }
    }

    break;
endswitch;
