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


$new_version = unserialize( $config['new_version'] )['version_number'];


// he can reinstall kleeja if he want by $_GET['install_again'] => for developers only
if (!ig('install_again')) 
{
    // not reinstall , he want to update , => check if kleeja need or not
    if ( ! version_compare(strtolower(KLEEJA_VERSION), strtolower($new_version), '<') ) 
    {
        // kleeja doesn't need to update
        kleeja_admin_info('there is no update for your version' ,  ADMIN_PATH );
        exit;
    }
} // $_GET['install_again'] is set => reinstall kleeja => check if he is a developer
else 
{
    //  please no .
    if ( ! defined('DEV_STAGE') ) 
    {
        kleeja_admin_err(":( NOOO!!");
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
$new_version = unserialize( $config['new_version'] )['version_number'];

$down_new_pack = fetch_remote_file($kj_new_pack_link . $new_version . '.zip', PATH . 'cache/kleeja.zip', 60, false, 10, true);

if ($down_new_pack) // we connected to github & downloaded the last version to cache folder
{
    // let's extract the zip to cache
    $zip = new ZipArchive;

    if ($zip->open( PATH . 'cache/kleeja.zip' ) == TRUE) 
    {
        $zip->extractTo( PATH . 'cache/' );
        $zip->close();
    }
        
        // some folder don't need it
        $no_need = array(
        'cache', // delete_cache() function
        'plugins', // kleeja now support plugins update
        'uploads',
        'styles', // kleeja will support style_update soon
        'install' // befor removing install folder , we will take what we want from it
    );

    // let's check if there any update files in install folder
    $update_file = PATH . "cache/kleeja-{$new_version}/install/includes/update_files/{$old_version}_to_{$new_version}.php";
    if (file_exists($update_file)) 
    {
        // move the update file from install folder to cache folder to include it later and delete install folder
        // becuse if install folder is exists , it can make some problems if dev mode is not active
        rename($update_file , PATH . "cache/update_{$old_version}_to_{$new_version}.php");
    }
        
    foreach ($no_need as $folderName) 
    {
        delete_plugin_folder( PATH . "cache/kleeja-{$new_version}/{$folderName}" );
    }

    // delete plugin folder function with some changes :)
    $it = new RecursiveDirectoryIterator(PATH . "cache/kleeja-{$new_version}/", RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files as $file) 
    {
        if ($file->isLink()) 
        { 
            file_put_contents( 
                str_replace("cache/kleeja-{$new_version}/", '' , $file->getPathname()) ,
                file_get_contents( $file->getPathname() )
             );
            unlink($file->getPathname());
        } 
        else if ($file->isDir()) 
        { 
            // here is folder , when we finish update , we will delete all folders and files
            continue;
        }
        else 
        {
            file_put_contents( 
                str_replace("cache/kleeja-{$new_version}/", '' , $file->getPathname()) ,
                file_get_contents( $file->getPathname() )
             );
            unlink($file->getPathname());
        }
    }

    if (file_exists( $updateFiles = PATH . "cache/update_{$old_version}_to_{$new_version}.php")) 
    {
        require_once $updateFiles; // we will include what we want to do in this file , and kleeja will done
    }

    // after we made success update , let's delete files and folders incache

    // kleeja new version files
    delete_plugin_folder(PATH . "cache/kleeja-{$new_version}");

    // delete old cache files
    delete_cache('' , true);

    /**
     * DDISPLAY SUCCESS MSG HERE , AND ALSO WE CAN INCLUDE SUCCESS MSG ON UPDATE FILE
     * OR WE CAN INCLUDE UPDATE FILES IN GITHUB , AND DOWNLOAD IT IN CACHE FOLDER WHEN IT REQUEST 
     * AND DELETE AFTER WE FINISH ;
     */

}
