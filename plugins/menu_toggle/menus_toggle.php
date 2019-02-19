<?php

// not for directly open
if (! defined('IN_ADMIN')) 
{
    exit;
}

#current template
$stylee = 'admin_menus_toggle';

// template variables
$styleePath = dirname(__FILE__);

$H_FORM_KEYS_GET = kleeja_add_form_key_get('adm_menus_toggle');

$action = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');

$hidden_side_menu_items = explode(':', $config['menu_toggle_hidden_sidemenu_items']);

$is_founder_admin = intval($userinfo['founder']) === 1;

$side_menu_items = array(
    array('name' => 'profile', 'title' => $lang['PROFILE'], 'hidden' => (int) in_array('profile', $hidden_side_menu_items)),
    array('name' => 'fileuser', 'title' => $lang['YOUR_FILEUSER'], 'hidden' => (int) in_array('fileuser', $hidden_side_menu_items)),
    array('name' => 'logout', 'title' => $lang['LOGOUT'], 'hidden' => (int) in_array('logout', $hidden_side_menu_items)),
    array('name' => 'login', 'title' => $lang['LOGIN'], 'hidden' => (int) in_array('login', $hidden_side_menu_items)),
    array('name' => 'register', 'title' => $lang['REGISTER'], 'hidden' => (int) in_array('register', $hidden_side_menu_items)),
);

$hidden_top_menu_items = explode(':', $config['menu_toggle_hidden_topmenu_items']);

$top_menu_items = array(
    array('name' => 'index', 'title' => $lang['INDEX'], 'hidden' => (int) in_array('index', $hidden_top_menu_items)),
    array('name' => 'rules', 'title' => $lang['RULES'], 'hidden' => (int) in_array('rules', $hidden_top_menu_items)),
    array('name' => 'guide', 'title' => $lang['GUIDE'], 'hidden' => (int) in_array('guide', $hidden_top_menu_items)),
    array('name' => 'stats', 'title' => $lang['STATS'], 'hidden' => (int) in_array('stats', $hidden_top_menu_items)),
    array('name' => 'report', 'title' => $lang['REPORT'], 'hidden' => (int) in_array('report', $hidden_top_menu_items)),
    array('name' => 'call', 'title' => $lang['CALL'], 'hidden' => (int) in_array('call', $hidden_top_menu_items)),
);

$hidden_admin_menu_items = explode(':', $config['menu_toggle_hidden_adminmenu_items']);

$admin_menu_items = array();
foreach($adm_extensions as $item)
{
    $m = isset($item[1]) && $item[1] == '_' ? substr($item, 2) : $item;

    if(in_array($m, array('start', 'lgoutcp', 'exts', 'configs')))
    {
        continue;
    }

    $admin_menu_items[] = array(
        'name' => $item,
        'title' => !empty($lang['R_' . strtoupper($m)]) ? $lang['R_' . strtoupper($m)] : (!empty($olang['R_' . strtoupper($m)]) ? $olang['R_' . strtoupper($m)] : strtoupper($m)),
        'hidden' => (int) in_array($item, $hidden_admin_menu_items)
    );
}

$go_menu = array(
   'menus_toggle' => array('name' => $olang['R_MENUS_TOGGLE'], 'link' => './?cp=menus_toggle', 'goto' => 'menus_toggle', 'current' => g('cp') == 'menus_toggle')
);

if(ig('toggle'))
{
    if (! kleeja_check_form_key_get('adm_menus_toggle', 3600)) 
    {
        header('HTTP/1.1 405 Method Not Allowed');
        $adminAjaxContent = $lang['INVALID_FORM_KEY'];
    }
    else
    {
        $name = g('name');
        $menu = g('menu');
        $hide = g('toggle', 'int') == 1;

        if($menu == 'admin' && ! $is_founder_admin)
        {
            header('HTTP/1.1 405 Method Not Allowed');
            $adminAjaxContent = $lang['HV_NOT_PRVLG_ACCESS'];
        }
        else
        {
            toggleMenuItem($name, $menu, $hide);

            $adminAjaxContent = $lang['CONFIGS_UPDATED'];
        }
    }
}


function toggleMenuItem($name, $menu, $hide)
{
    global $config;

    $items = explode(':', $config['menu_toggle_hidden_'.$menu.'menu_items']);
    $items = array_filter($items);

    if(in_array($name, $items) && ! $hide)
    {
        $new_items = array_diff($items, [$name]);
    }
    else if($hide)
    {
        $new_items = $items;
        $new_items[] = $name;
    }

    if($new_items != $items)
    {
        update_config('menu_toggle_hidden_' . $menu . 'menu_items', implode(':', $new_items));
    }
}