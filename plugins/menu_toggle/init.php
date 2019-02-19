<?php
# Kleeja Plugin
# menu_toggle
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['menu_toggle']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Menu Toggle',
        'ar' => 'عرض/إخفاء القوائم'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.1',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Ability to hide/show Kleeja Menu items',
        'ar' => 'القدرة على عرض/إخفاء عناصر قائمة كليجا'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['menu_toggle']['first_run']['ar'] = "
شكراً لاستخدامك هذه الإضافة قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['menu_toggle']['first_run']['en'] = "
Thanks for using this plugin, to report bugs contact us: 
<br>
info@kleeja.com
";


# Plugin Installation function
$kleeja_plugin['menu_toggle']['install'] = function ($plg_id)
{
    add_config_r(array(
        // 'menu_toggle_hide_all' =>
        //     array(
        //     'value' => '0',
        //     'plg_id' => $plg_id,
        //     'type' => 'menu_toggle'
        // ),
        'menu_toggle_hidden_topmenu_items' =>
            array(
            'value' => '',
            'plg_id' => $plg_id,
            'type' => 'menu_toggle'
        ),
        'menu_toggle_hidden_sidemenu_items' =>
            array(
            'value' => '',
            'plg_id' => $plg_id,
            'type' => 'menu_toggle'
        ),
    ));


   //new language variables
   add_olang(array(
                'R_MENUS_TOGGLE' => 'تحكم بقوائم كليجا',
                'MENU_TOGGLE_EXP' => 'يمكنك التحكم بالعناصر التي تريد عرضها أو إخفائها من قوائم كليجا.',
                'MENU_TOGGLE_TOP_MENU' => 'القائمة العلوية',
                'MENU_TOGGLE_SIDE_MENU' => 'القائمة الجانبية',
                'MENU_TOGGLE_HIDE' => 'إخفاء',
                'MENU_TOGGLE_SHOW' => 'عرض',

   ),
       'ar',
       $plg_id);

   add_olang(array(
            'R_MENUS_TOGGLE' => 'Menus Toggle',
            'MENU_TOGGLE_EXP' => 'You can control which items you want to hide/show of Kleeja menus.',
            'MENU_TOGGLE_TOP_MENU' => 'Top Menu',
            'MENU_TOGGLE_SIDE_MENU' => 'Side Menu',
            'MENU_TOGGLE_HIDE' => 'hide',
            'MENU_TOGGLE_SHOW' => 'show',
   ),
       'en',
       $plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['menu_toggle']['update'] = function ($old_version, $new_version) {
    // if(version_compare($old_version, '0.5', '<')){
    // 	//... update to 0.5
    // }
    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# Plugin Uninstallation, function to be called at unistalling
$kleeja_plugin['menu_toggle']['uninstall'] = function ($plg_id) {
    //delete language variables
    delete_olang(null, null, $plg_id);

    //delete options
    delete_config(array('menu_toggle_hidden_topmenu_items', 'menu_toggle_hidden_sidemenu_items'));
};


# Plugin functions
$kleeja_plugin['menu_toggle']['functions'] = array(
    //add to admin menu
    'begin_admin_page' => function ($args) {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];

        $adm_extensions[] = 'menus_toggle';
        $ext_icons['menus_toggle'] = 'list';
        return compact('adm_extensions', 'ext_icons');
    },

    //add as admin page to reach when click on admin menu item we added.
    'not_exists_menus_toggle' => function () {
        $include_alternative = dirname(__FILE__) . '/menus_toggle.php';

        return compact('include_alternative');
    },

    'Saaheader_links_func' => function($args){

        global $config;

        $return = [];

        foreach(array('top', 'side') as $menu)
        {
            if(trim($config['menu_toggle_hidden_'.$menu.'menu_items']) != '')
            {
                $current_menu = $args[$menu.'_menu'];
        
                $hidden_items = explode(':', $config['menu_toggle_hidden_'.$menu.'menu_items']);
                $hidden_items = array_filter($hidden_items);
                $new_menu = array();
                foreach($current_menu as $order => $item)
                {
                    if(! in_array($item['name'], $hidden_items))
                    {
                        $new_menu[$order] = $item;
                    }
                }

                $return[$menu . '_menu'] = $new_menu;
            }
        }



        if(sizeof($return))
        {
            return $return;
        }
    },
);

