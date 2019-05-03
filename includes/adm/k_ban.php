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

//for style ..
$stylee	= 'admin_ban';
$action	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');

$affected    = false;
$H_FORM_KEYS	= kleeja_add_form_key('adm_ban');

//
// Check form key
//
if (ip('submit'))
{
    if (! kleeja_check_form_key('adm_ban'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}


$query	= [
    'SELECT'	=> 'ban',
    'FROM'		 => "{$dbprefix}stats"
];

$result = $SQL->build($query);

$current_ban_data =$SQL->fetch_array($result);

$ban = p('ban_text', 'str', $current_ban_data['ban']);

//when submit
if (ip('submit'))
{
    //update
    $update_query	= [
        'UPDATE'	=> "{$dbprefix}stats",
        'SET'		  => "ban='" . $SQL->escape($ban) . "'"
    ];

    $SQL->build($update_query);

    if ($SQL->affected())
    {
        $affected = true;
        delete_cache('data_ban');
    }
}


$SQL->freeresult($result);

//after submit 
if (ip('submit'))
{
    $text	= ($affected ? $lang['BAN_UPDATED'] : $lang['NO_UP_CHANGE_S']);
    $text	.= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '\');", 2000);</script>' . "\n";
    $stylee	= 'admin_info';
}
