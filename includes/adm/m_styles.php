<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}


#current secondary menu action
$current_smt = preg_replace('/[^a-z0-9_]/i','', g('smt', 'str', 'general'));

$action = ADMIN_PATH . '?cp=' . basename(__file__, '.php');

$H_FORM_KEYS	= kleeja_add_form_key('adm_styles');


//for style ..
$stylee = "admin_styles";



//after submit
if (ip('style_choose') || ig('style_choose'))
{
    $style_id = ip('style_choose') ? p('style_choose') : g('style_choose');

    $style_id = preg_replace('/[^a-z0-9_\-\.]/i', '', $style_id);


    //if empty, let's ignore it
    if (empty($style_id))
    {
        redirect(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
    }

    // make style as default

    //check _GET Csrf token
    if (isset($_REQUEST['home']) && !kleeja_check_form_key_get('adm_start_actions'))
    {
        kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=start', 2);
    }

    //
    //check if this style depend on other style and
    //check kleeja version that required by this style
    //
    if (($style_info = kleeja_style_info($style_id)) != false)
    {
        if (isset($style_info['depend_on']) && !file_exists(PATH . 'styles/' . $style_info['depend_on']))
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

            $query = array(
                'SELECT' => 'plg_name, plg_disabled',
                'FROM' => "{$dbprefix}plugins",
            );

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
    update_config('style', $style_id);
    update_config('style_depend_on', isset($style_info['depend_on']) ? $style_info['depend_on'] : '');

    //delete all cache to get new style
    delete_cache('', true);

    //show msg
    kleeja_admin_info(sprintf($lang['STYLE_NOW_IS_DEFAULT'], htmlspecialchars($style_id)), true, '', true, basename(ADMIN_PATH) . '?cp=' . (isset($_REQUEST['home']) ? 'start' : basename(__file__, '.php')));

}
else if (ip('newstyle'))
{

    if(intval($userinfo['founder']) !== 1)
    {
        $ERRORS[] = $lang['HV_NOT_PRVLG_ACCESS'];
    }


    if(!kleeja_check_form_key('adm_styles'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action);
    }


    $ERRORS = array();

    #is uploaded?
    if(empty($_FILES['style_file']['tmp_name']))
    {
        $ERRORS[] = $lang['CHOSE_F'];
    }



    #extract it to plugins folder
    if(!sizeof($ERRORS))
    {
        if(class_exists('ZipArchive'))
        {
            $zip = new ZipArchive;
            if ($zip->open($_FILES['style_file']['tmp_name']) === true)
            {
                if(!$zip->extractTo(PATH . 'styles'))
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

    if(!empty($_FILES['style_file']['tmp_name']))
    {
        @unlink($_FILES['style_file']['tmp_name']);
    }


    if(!sizeof($ERRORS))
    {
        kleeja_admin_info($lang['NO_PROBLEM_AFTER_ZIP'], true, '', true, $action);
    }
    else
    {
        kleeja_admin_err('- ' . implode('<br>- ', $ERRORS), $action);
    }
}


//get styles
$arr = array();
if ($dh = @opendir(PATH . 'styles'))
{
    while (false !== ($folder_name = readdir($dh)))
    {
        if (is_dir(PATH  . 'styles/' . $folder_name) && preg_match('/[a-z0-9_.]{3,}/', $folder_name))
        {

            #info
            $style_info_arr = array
            (
                'name' => $folder_name,
                'desc' => '',
                'copyright'=> '',
                'version'=> ''
            );


            if(($style_info = kleeja_style_info($folder_name)) != false)
            {
                foreach (array('name', 'desc', 'copyright', 'version') as $InfoKey)
                {
                    if (array_key_exists($InfoKey, $style_info))
                    {
                        if(is_array($style_info[$InfoKey]))
                        {
                            $style_info_arr[$InfoKey] = !empty($style_info[$InfoKey][$config['language']])
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

            $arr[] = array
            (
                'style_name' => $folder_name,
                'is_default' => $config['style'] == $folder_name ? true : false,
                'link_mk_default' => basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;style_choose=' . $folder_name,
                'icon' => file_exists(PATH . 'styles/' . $folder_name . "/screenshot.png")
                    ? PATH . 'styles/' . $folder_name . "/screenshot.png"
                    : $STYLE_PATH_ADMIN . 'images/style.png',
                'info' => $style_info_arr
            );
        }
    }

    @closedir($dh);
}
