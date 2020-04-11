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


//for style ..
$stylee           = 'admin_extra';
$current_smt      = preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', 'he'));
$action           = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=' . $current_smt;
$H_FORM_KEYS      = kleeja_add_form_key('adm_extra');

//
// Check form key
//
if (ip('submit'))
{
    if (! kleeja_check_form_key('adm_extra'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

$query    = [
    'SELECT'       => 'ex_header,ex_footer',
    'FROM'         => "{$dbprefix}stats"
];

$result = $SQL->build($query);

//is there any change !
$affected = false;

$extras = $SQL->fetch_array($result);


//when submit
if (ip('submit'))
{
    $update_sql = '';


    if (g('smt') == 'fe')
    {
        $ex_footer  = p('ex_footer', 'str');
        $update_sql = "ex_footer = '" . $SQL->real_escape(htmlspecialchars_decode($ex_footer)) . "'";
    }
    else
    {
        $ex_header  = p('ex_header', 'str');
        $update_sql = "ex_header = '" . $SQL->real_escape(htmlspecialchars_decode($ex_header)) . "'";
    }



    //update
    $update_query    = [
        'UPDATE'       => "{$dbprefix}stats",
        'SET'          => $update_sql
    ];

    $SQL->build($update_query);

    if ($SQL->affected())
    {
        $affected = true;
        //delete cache ..
        delete_cache('data_extra');
    }
}
else
{
    extract($extras);
}

//reverse
//$ex_header = htmlspecialchars_decode($ex_header);
//$ex_footer = htmlspecialchars_decode($ex_footer);


$SQL->freeresult($result);


//after submit 
if (ip('submit'))
{
    kleeja_admin_info(($affected ? $lang['EXTRA_UPDATED'] : $lang['NO_UP_CHANGE_S']), true, '', true, $action);
}


//secondary menu
$go_menu = [
    'he' => ['name'=>$lang['ADD_HEADER_EXTRA'], 'link'=> basename(ADMIN_PATH) . '?cp=n_extra&amp;smt=he', 'goto'=>'he', 'current'=> $current_smt == 'he'],
    'fe' => ['name'=>$lang['ADD_FOOTER_EXTRA'], 'link'=> basename(ADMIN_PATH) . '?cp=n_extra&amp;smt=fe', 'goto'=>'fe', 'current'=> $current_smt == 'fe'],
];
