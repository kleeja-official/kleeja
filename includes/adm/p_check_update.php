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

set_time_limit(0);

$current_version = KLEEJA_VERSION;
$new_version     = unserialize($config['new_version']);
$new_version     = empty($new_version['version_number'])
                ? KLEEJA_VERSION
                : $new_version['version_number'];
$backup_archive_path = PATH . 'cache/backup.zip';
$GET_FORM_KEY = kleeja_add_form_key_get('UPDATER_FORM_KEY');

define('KLEEJA_VERSION_CHECK_LINK', 'https://api.github.com/repos/kleeja-official/kleeja/releases/latest');
define('KLEEJA_LATEST_PACKAGE_LINK', 'https://api.github.com/repos/kleeja-official/kleeja/zipball/');

$stylee	     = 'admin_check_update';
$current_smt = preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', 'general'));
$update_link = $config['siteurl'] . 'install/update.php?lang=' . $config['language'];


if (in_array($current_smt, ['update1', 'update2', 'update3']))
{
    //only founders can do the upgrade process ...
    if (intval($userinfo['founder']) !== 1)
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    if (! kleeja_check_form_key_get('UPDATER_FORM_KEY'))
    {
        header('HTTP/1.0 401 Unauthorized');

        kleeja_admin_err($lang['INVALID_GET_KEY']);
    }
}

//check latest version
if ($current_smt == 'check')
{
    //get data from kleeja github repo
    if (! ($version_data = $cache->get('kleeja_repo_version')))
    {
        $github_data = fetch_remote_file(KLEEJA_VERSION_CHECK_LINK, false, 100);

        if (! empty($github_data))
        {
            $latest_release = json_decode($github_data, true);
            $version_data   = null;

            if (json_last_error() === JSON_ERROR_NONE)
            {
                $version_data = [
                    'version' => trim(htmlspecialchars($latest_release['tag_name'])),
                    'info'    => trim(htmlspecialchars($latest_release['body'])),
                    'date'    => trim(htmlspecialchars($latest_release['created_at'])),
                ];
                $cache->save('kleeja_repo_version', $version_data, 3600 * 2);
            }
        }
    }

    $error = 0;

    if (empty($version_data['version']))
    {
        $text  = $lang['ERROR_CHECK_VER'];
        $error = 1;
    }
    else
    {
        if (version_compare(strtolower($current_version), strtolower($version_data['version']), '<'))
        {
            $text	 = sprintf($lang['UPDATE_NOW_S'], $current_version, strtolower($version_data['version'])) .
                        '::--x--::' . $version_data['info'] . '::--x--::' . $version_data['date'];
            $error	= 2;
        }
        elseif (version_compare(strtolower($current_version), strtolower($version_data['version']), '='))
        {
            $text	= $lang['U_LAST_VER_KLJ'];
        }
        elseif (version_compare(strtolower($current_version), strtolower($version_data['version']), '>'))
        {
            $text	= $lang['U_USE_PRE_RE'];
        }
        else
        {
            $text = $lang['ERROR_CHECK_VER'] . ' [code: ' . htmlspecialchars($version_data['version']) . ']';
        }
    }

    $data	= [
        'version_number'	=> $version_data['version'],
        'last_check'		   => time()
    ];

    $data = serialize($data);

    update_config('new_version', $SQL->real_escape($data), false);
    delete_cache('data_config');

    $adminAjaxContent = $error . ':::' . $text;
}
// home of update page
elseif ($current_smt == 'general')
{
    //To prevent expected error [ infinit loop ]
    if (ig('show_msg'))
    {
        $query_get	= [
            'SELECT'	=> '*',
            'FROM'		 => "{$dbprefix}config",
            'WHERE'		=> "name = 'new_version'"
        ];

        $result_get =  $SQL->build($query_get);

        if (! $SQL->num_rows($result_get))
        {
            //add new config value
            add_config('new_version', '');
        }
    }

    $showMessage = ig('show_msg');
}
//1. download latest kleeja version
elseif ($current_smt == 'update1')
{
    if (! class_exists('ZipArchive'))
    {
        $adminAjaxContent = '930:::' . $lang['NO_ZIP_ARCHIVE'];
    }
    elseif (! version_compare(strtolower($current_version), strtolower($new_version), '<='))
    {
        $adminAjaxContent = '940:::' . $lang['U_LAST_VER_KLJ'];
    }
    else
    {
        // downloaded the last package to cache folder
        fetch_remote_file(KLEEJA_LATEST_PACKAGE_LINK . $new_version . '.zip', PATH . "cache/kleeja-{$new_version}.zip", 60, false, 10, true);

        if (file_exists(PATH . "cache/kleeja-{$new_version}.zip"))
        {
            $adminAjaxContent = '1:::';
            file_put_contents(PATH . 'cache/step1.done', time());
        }
        else
        {
            $adminAjaxContent = '2:::' . $lang['UPDATE_ERR_FETCH_PACKAGE'];
        }
    }
}
//2. extract new kleeja package, create backup zip file
elseif ($current_smt == 'update2')
{
    if (! file_exists(PATH . 'cache/step1.done'))
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    kleeja_unlink(PATH . 'cache/step1.done');

    // let's extract the zip to cache
    $zip = new ZipArchive;

    if ($zip->open(PATH . "cache/kleeja-{$new_version}.zip") == true)
    {
        $zip->extractTo(PATH . 'cache/');
        $zip->close();
    }

    // let's check if there any update files in install folder
    $update_file = PATH . "cache/kleeja-{$new_version}/install/includes/update_schema.php";

    if (file_exists($update_file))
    {
        // move the update file from install folder to cache folder to include it later and delete install folder
        // becuse if install folder is exists , it can make some problems if dev mode is not active
        rename($update_file, PATH . 'cache/update_schema.php');
    }

    // skip some folders
    foreach (['cache', 'plugins', 'uploads', 'styles', 'install'] as $folder_name)
    {
        kleeja_unlink(PATH . "cache/kleeja-{$new_version}/{$folder_name}");
    }

    if (file_exists($backup_archive_path))
    {
        kleeja_unlink($backup_archive_path);
    }

    file_put_contents(PATH . 'cache/step2.done', time());

    $adminAjaxContent = '1:::';
}
//3. update, or rollback on failure
elseif ($current_smt == 'update3')
{
    if (! file_exists(PATH . 'cache/step2.done'))
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS']);
    }

    kleeja_unlink(PATH . 'cache/step2.done');

    $backup = new ZipArchive;

    if ($backup->open($backup_archive_path, ZipArchive::CREATE) !== true)
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['UPDATE_BACKUP_CREATE_FAILED']);
    }

    // delete plugin folder function with some changes :)
    $it    = new RecursiveDirectoryIterator(PATH . "cache/kleeja-{$new_version}/", RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    $update_failed    = false;
    $failed_files     = $new_folders = [];

    //maintenance mode on
    update_config('siteclose', 1);

    foreach ($files as $file)
    {
        if ($file->isFile())
        {
            $file_path = str_replace("cache/kleeja-{$new_version}/", '', $file->getPathname());
            $file_dir  = str_replace("cache/kleeja-{$new_version}/", '', $file->getPath());

            // same, no need to replace
            if (file_exists($file_path)  && md5_file($file_path) == md5_file($file->getPathname()))
            {
                continue;
            }

            //no folder?
            if (! file_exists($file_dir))
            {
                mkdir($file_dir, K_DIR_CHMOD, true);
                array_push($new_folders, $file_dir);
            }

            if (! is_writable($file_path))
            {
                chmod($file_path, K_FILE_CHMOD);
            }

            //back up current file
            $backup->addFromString(
                $file_path,
                file_get_contents($file_path)
            );

            //copy file
            if (file_put_contents(
                $file_path,
                file_get_contents($file->getPathname())
            ) === false)
            {
                $update_failed = true;
                array_push($failed_files, $file_path);

                break;
            }
        }
        elseif ($file->isDir())
        {
            // here is folder , when we finish update , we will delete all folders and files
            if (! file_exists($file_path))
            {
                mkdir($file_path, K_DIR_CHMOD, true);
                array_push($new_folders, $file_path);
            }

            continue;
        }
    }

    $backup->close();

    if ($update_failed)
    {
        //rollback to backup
        $zip = new ZipArchive;
        $zip->open($backup_archive_path);
        $zip->extractTo(PATH);
        $zip->close();

        foreach ($new_folders as $folder)
        {
            kleeja_unlink($folder);
        }

        //maintenance mode off
        update_config('siteclose', 0);

        $adminAjaxContent = '1002:::' . $lang['UPDATE_PROCESS_FAILED'](defined('DEV_STAGE') ? '[failed files: ' . implode(', ', $failed_files) . ']' : '');
    }
    else
    {
        // we will include what we want to do in this file , and kleeja will done
        if (file_exists($db_update_file = PATH . 'cache/update_schema.php'))
        {
            require_once $db_update_file;

            $all_db_updates = array_keys($update_schema);

            $available_db_updates = array_filter($all_db_updates, function ($v) use ($config) {
                return $v > $config['db_version'];
            });

            sort($available_db_updates);

            if (sizeof($available_db_updates))
            {
                foreach ($available_db_updates as $db_update_version)
                {
                    $SQL->show_errors = false;

                    if (isset($update_schema[$db_update_version]['sql'])
                        && sizeof($update_schema[$db_update_version]['sql']) > 0)
                    {
                        foreach ($update_schema[$db_update_version]['sql'] as $name=>$sql_content)
                        {
                            $SQL->query($sql_content);
                        }
                    }

                    if (isset($update_schema[$db_update_version]['functions'])
                            && sizeof($update_schema[$db_update_version]['functions']) > 0)
                    {
                        foreach ($update_schema[$db_update_version]['functions'] as $n)
                        {
                            if (is_callable($n))
                            {
                                $n();
                            }
                        }
                    }


                    $SQL->query(
                        "UPDATE `{$dbprefix}config` SET `value` = '" . $db_update_version . "' WHERE `name` = 'db_version'"
                    );
                }
            }
        }

        //maintenance mode off
        update_config('siteclose', 0);

        // after a success update, delete files and folders in cache
        kleeja_unlink(PATH . "cache/kleeja-{$new_version}");
        kleeja_unlink(PATH . "cache/kleeja-{$new_version}.zip");
        delete_cache('', true);

        $adminAjaxContent = '1:::' . sprintf($lang['UPDATE_PROCESS_DONE'], $new_version);
    }
}
