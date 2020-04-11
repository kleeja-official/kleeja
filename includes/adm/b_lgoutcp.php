<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/


// not for directly open
if (! defined('IN_ADMIN'))
{
    exit();
}

//check _GET Csrf token
if (! kleeja_check_form_key_get('GLOBAL_FORM_KEY'))
{
    kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH), 2);
}


//remove just the administator session 
if ($usrcp->logout_cp())
{
    redirect($config['siteurl']);
    $SQL->close();

    exit;
}
