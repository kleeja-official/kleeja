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
$stylee    = 'admin_ban';
$H_FORM_KEYS_GET    = kleeja_add_form_key_get('adm_ban_get');
$H_FORM_KEYS    = kleeja_add_form_key('adm_ban');

$action      = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');
$delete_item    = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;' . $H_FORM_KEYS_GET . '&amp;case=del&amp;k=';
$new_item_action    = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;case=new';


//
// Check form key
//

$case   = g('case', 'str', 'view');
$update_ban_content = false;

$query    = [
    'SELECT'       => 'ban',
    'FROM'         => "{$dbprefix}stats"
];

$result = $SQL->build($query);

$current_ban_data = $SQL->fetch_array($result);
$SQL->freeresult($result);

$banned_items = explode('|', $current_ban_data['ban']);

$show_message = false;

if($case == 'del' && ig('k'))
{
    if (! kleeja_check_form_key_get('adm_ban_get'))
    {
        header('HTTP/1.0 401 Unauthorized');
        kleeja_admin_err($lang['INVALID_GET_KEY'], $action);
    }

    $to_delete = g('k');

    $banned_items = array_filter($banned_items, function($item) use($to_delete, $lang, &$show_message) {
        if(md5($item) == $to_delete) {
            $show_message = sprintf($lang['ITEM_DELETED'], $item);
            return false;
        }
        return true;
    });

    $update_ban_content = $show_message;
}

if($case == 'new')
{
    if (! kleeja_check_form_key('adm_ban'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }

    $to_add = p('k', 'str', '');

    if(! empty($to_add))
    {
        $banned_items[] = $to_add;
        $show_message = $lang['BAN_UPDATED'];
        $update_ban_content = true;
    }
}

if($update_ban_content)
{
    $banned_items = array_filter($banned_items);
    //update
    $update_query    = [
        'UPDATE'       => "{$dbprefix}stats",
        'SET'          => "ban='" . $SQL->escape(implode('|', $banned_items)) . "'"
    ];

    $SQL->build($update_query);

    if ($SQL->affected())
    {
        delete_cache('data_ban');
    }
}

array_walk($banned_items, function (&$value, &$key) {
    $value = ['content' => $value, 'del_key' => md5($value), 'id' => $key+1];
});
