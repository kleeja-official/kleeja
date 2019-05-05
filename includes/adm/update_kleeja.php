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


$new_version = unserialize($config['new_version'])['version_number'];


// he can reinstall kleeja if he want by $_GET['install_again'] => for developers only
if (! ig('install_again'))
{
    // not reinstall , he want to update , => check if kleeja need or not
    if (! version_compare(strtolower(KLEEJA_VERSION), strtolower($new_version), '<'))
    {
        // kleeja doesn't need to update
        kleeja_admin_info('there is no update for your version', ADMIN_PATH);

        exit;
    }
}
else
{
    // kleeja is up to date, unless your are a developer trying to make a point...
    if (! defined('DEV_STAGE'))
    {
        kleeja_admin_info('Kleeja is up to date!');

        exit;
    }
}

/**
 * we will download the last version from github and extract it in cache folder
 * then scan the new version files , and put it to the PATH
 * we don't need to create the folders again in PATH
 * and if we have to update the DB or removing some old files ,
 * we can check if there any update file of this version from the new install folder
 * EX : if file exists PATH . 'install/update/$old_version_$new_version.php': require_once the file
 */

$kj_new_pack_link = 'https://github.com/kleeja-official/kleeja/archive/';

$old_version = KLEEJA_VERSION;
$new_version = unserialize($config['new_version'])['version_number'];


if (! class_exists('ZipArchive'))
{
    //$error = $lang['NO_ZIP_ARCHIVE'];
    $down_new_pack = false;
}
else
{
    // downloaded the last version to cache folder
    $down_new_pack = fetch_remote_file($kj_new_pack_link . $new_version . '.zip', PATH . 'cache/kleeja.zip', 60, false, 10, true);
}

if ($down_new_pack)
{
    // let's extract the zip to cache
    $zip = new ZipArchive;

    if ($zip->open(PATH . 'cache/kleeja.zip') == true)
    {
        $zip->extractTo(PATH . 'cache/');
        $zip->close();
    }

    // some folder don't need it
    $no_need = [
        'cache', // delete_cache() function
        'plugins', // kleeja now support plugins update
        'uploads',
        'styles', // kleeja will support style_update soon
        'install' // befor removing install folder , we will take what we want from it
    ];

    // let's check if there any update files in install folder
    $update_file = PATH . "cache/kleeja-{$new_version}/install/includes/update_files/{$old_version}_to_{$new_version}.php";

    if (file_exists($update_file))
    {
        // move the update file from install folder to cache folder to include it later and delete install folder
        // becuse if install folder is exists , it can make some problems if dev mode is not active
        rename($update_file, PATH . "cache/update_{$old_version}_to_{$new_version}.php");
    }

    foreach ($no_need as $folderName)
    {
        kleeja_unlink(PATH . "cache/kleeja-{$new_version}/{$folderName}");
    }

    // delete plugin folder function with some changes :)
    $it    = new RecursiveDirectoryIterator(PATH . "cache/kleeja-{$new_version}/", RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    //rollback settings
    mkdir(PATH . 'cache/rollback');
    $update_failed    = false;

    //maintenance mode on
    update_config('siteclose', 1);

    foreach ($files as $file)
    {
        if ($file->isFile())
        {
            $file_path = str_replace("cache/kleeja-{$new_version}/", '', $file->getPathname());

            // same, no need to replace
            if (file_exists($file_path)  && md5_file($file_path) != md5_file($file->getPathname()))
            {
                continue;
            }

            if (! is_writable($file_path))
            {
                chmod($file_path, 0644);

                if (! is_writable($file_path))
                {
                    //if a host uses restrictive file permissions (e.g. 400) for all user files,
                    //this could solve the problem.
                    chmod($file_path, 0644 & ~ umask());
                }
            }

            //backup for rollback
            if (! file_put_contents(
                'cache/rollback/' . ltrim($file_path, '/'),
                file_get_contents($file_path)
            ))
            {
                $update_failed = true;

                break;
            }

            //copy file
            if (! file_put_contents(
                $file_path,
                file_get_contents($file->getPathname())
            ))
            {
                $update_failed = true;

                break;
            }
        }
        elseif ($file->isDir())
        {
            // here is folder , when we finish update , we will delete all folders and files
            //TODO if folder is new, then mkdir it.
            continue;
        }
        else
        {
            // not file or folder ?
        }
    }

    if ($update_failed)
    {
        //rollback to backup cache/rollback
    }
    else
    {
        // we will include what we want to do in this file , and kleeja will done
        if (file_exists($updateFiles = PATH . "cache/update_{$old_version}_to_{$new_version}.php"))
        {
            require_once $updateFiles;
        }

        // after we made success update , let's delete files and folders incache

        // kleeja new version files
        kleeja_unlink(PATH . "cache/kleeja-{$new_version}");

        // delete old cache files
        delete_cache('', true);

        /**
         * DDISPLAY SUCCESS MSG HERE , AND ALSO WE CAN INCLUDE SUCCESS MSG ON UPDATE FILE
         * OR WE CAN INCLUDE UPDATE FILES IN GITHUB , AND DOWNLOAD IT IN CACHE FOLDER WHEN IT REQUEST
         * AND DELETE AFTER WE FINISH ;
         */
    }

    //maintenance mode off
    update_config('siteclose', 0);
}
